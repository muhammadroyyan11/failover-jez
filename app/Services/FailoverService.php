<?php

namespace App\Services;

use App\Models\FailoverLog;
use App\Models\FailoverSetting;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator utama untuk proses failover manual.
 *
 * Flow failover JH -> UPCLOUD:
 *  1. Set JH ke maintenance mode
 *  2. Cek replica delay di UPCLOUD (harus 0)
 *  3. Promote UPCLOUD menjadi primary
 *  4. Clear cache di UPCLOUD
 *  5. Update DNS Cloudflare ke IP UPCLOUD
 *  6. Update active_server di database
 *  7. Restart queue di UPCLOUD
 */
class FailoverService
{
    public function __construct(
        private CloudflareDnsService    $cloudflare,
        private ReplicationStatusService $replication,
    ) {}

    /**
     * Jalankan failover penuh dari JH ke UPCLOUD.
     *
     * @param  int    $userId
     * @param  string $userName
     * @param  string $ip
     * @return array  ['success' => bool, 'log_id' => int, 'message' => string, 'steps' => array]
     */
    public function failoverToUpcloud(int $userId, string $userName, string $ip): array
    {
        $log = FailoverLog::startLog('full_failover', 'jh', 'upcloud', $userId, $userName, $ip);

        $steps  = [];
        $jhAgent = new ServerAgentClient('jh');
        $ucAgent = new ServerAgentClient('upcloud');

        try {
            // ----------------------------------------------------------------
            // STEP 1: Set JH ke maintenance mode
            // ----------------------------------------------------------------
            $log->addStep('maintenance_jh', 'running', 'Mengirim perintah artisan down ke JH...');
            $result = $jhAgent->artisanDown();
            if (! ($result['success'] ?? false)) {
                // Jika JH tidak reachable, lanjutkan (mungkin sudah down)
                $detail = $result['reachable'] ? ($result['error'] ?? 'Unknown error') : 'JH tidak reachable (mungkin sudah down)';
                $log->addStep('maintenance_jh', 'warning', $detail);
                $steps[] = ['step' => 'maintenance_jh', 'status' => 'warning', 'detail' => $detail];
            } else {
                $log->addStep('maintenance_jh', 'success', 'JH berhasil masuk maintenance mode.');
                $steps[] = ['step' => 'maintenance_jh', 'status' => 'success', 'detail' => 'JH maintenance mode aktif.'];
            }

            // ----------------------------------------------------------------
            // STEP 2: Cek replica delay di UPCLOUD
            // ----------------------------------------------------------------
            $log->addStep('check_replica', 'running', 'Mengecek status replikasi di UPCLOUD...');
            $replicaResult = $ucAgent->replicationStatus();

            if (! ($replicaResult['success'] ?? false)) {
                $error = $replicaResult['error'] ?? 'Tidak bisa mengambil status replikasi dari UPCLOUD.';
                $log->addStep('check_replica', 'failed', $error);
                $log->markFailed("Gagal cek replikasi: {$error}");
                return $this->failResult($log->id, "Gagal cek replikasi UPCLOUD: {$error}", $steps);
            }

            $delay = (int) ($replicaResult['seconds_behind_source'] ?? PHP_INT_MAX);
            if ($delay !== 0) {
                $error = "Replica delay = {$delay} detik. Harus 0 sebelum failover.";
                $log->addStep('check_replica', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            if (($replicaResult['io_running'] ?? 'No') !== 'Yes' || ($replicaResult['sql_running'] ?? 'No') !== 'Yes') {
                $error = "Replica IO/SQL tidak running. IO: {$replicaResult['io_running']}, SQL: {$replicaResult['sql_running']}";
                $log->addStep('check_replica', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $log->addStep('check_replica', 'success', "Replica delay = 0, IO/SQL running. Aman untuk failover.");
            $steps[] = ['step' => 'check_replica', 'status' => 'success', 'detail' => 'Replica in sync.'];

            // ----------------------------------------------------------------
            // STEP 3: Promote UPCLOUD menjadi primary
            // ----------------------------------------------------------------
            $log->addStep('promote_upcloud', 'running', 'Mempromote UPCLOUD menjadi primary...');
            $promoteResult = $ucAgent->promotePrimary();

            if (! ($promoteResult['success'] ?? false)) {
                $error = $promoteResult['error'] ?? 'Gagal promote UPCLOUD.';
                $log->addStep('promote_upcloud', 'failed', $error);
                $log->markFailed("Gagal promote UPCLOUD: {$error}");
                return $this->failResult($log->id, "Gagal promote UPCLOUD: {$error}", $steps);
            }

            $log->addStep('promote_upcloud', 'success', 'UPCLOUD berhasil dipromote menjadi primary.');
            $steps[] = ['step' => 'promote_upcloud', 'status' => 'success', 'detail' => 'UPCLOUD is now primary.'];

            // ----------------------------------------------------------------
            // STEP 4: Clear cache di UPCLOUD
            // ----------------------------------------------------------------
            $log->addStep('clear_cache', 'running', 'Membersihkan cache di UPCLOUD...');
            $cacheResult = $ucAgent->clearCache();

            if (! ($cacheResult['success'] ?? false)) {
                $log->addStep('clear_cache', 'warning', $cacheResult['error'] ?? 'Cache clear gagal tapi lanjut.');
                $steps[] = ['step' => 'clear_cache', 'status' => 'warning', 'detail' => 'Cache clear gagal, lanjut.'];
            } else {
                $log->addStep('clear_cache', 'success', 'Cache UPCLOUD berhasil dibersihkan.');
                $steps[] = ['step' => 'clear_cache', 'status' => 'success', 'detail' => 'Cache cleared.'];
            }

            // ----------------------------------------------------------------
            // STEP 5: Update DNS Cloudflare
            // ----------------------------------------------------------------
            $log->addStep('update_dns', 'running', 'Mengupdate DNS Cloudflare ke IP UPCLOUD...');
            $dnsResult = $this->cloudflare->updateARecordToUpcloud();

            if (! ($dnsResult['success'] ?? false)) {
                $error = $dnsResult['error'] ?? 'Gagal update DNS.';
                $log->addStep('update_dns', 'failed', $error);
                $log->markFailed("Gagal update DNS: {$error}");
                return $this->failResult($log->id, "Gagal update DNS Cloudflare: {$error}", $steps);
            }

            $log->addStep('update_dns', 'success', "DNS berhasil diarahkan ke UPCLOUD ({$dnsResult['ip']}).");
            $steps[] = ['step' => 'update_dns', 'status' => 'success', 'detail' => "DNS -> {$dnsResult['ip']}"];

            // ----------------------------------------------------------------
            // STEP 6: Update active_server di database
            // ----------------------------------------------------------------
            $setting = FailoverSetting::current();
            $setting->update(['active_server' => 'upcloud']);
            $log->addStep('update_setting', 'success', 'Active server diupdate ke UPCLOUD.');
            $steps[] = ['step' => 'update_setting', 'status' => 'success', 'detail' => 'DB setting updated.'];

            // ----------------------------------------------------------------
            // STEP 7: Restart queue di UPCLOUD
            // ----------------------------------------------------------------
            $log->addStep('restart_queue', 'running', 'Merestart queue worker di UPCLOUD...');
            $queueResult = $ucAgent->restartQueue();

            if (! ($queueResult['success'] ?? false)) {
                $log->addStep('restart_queue', 'warning', 'Queue restart gagal, perlu manual.');
                $steps[] = ['step' => 'restart_queue', 'status' => 'warning', 'detail' => 'Queue restart gagal.'];
            } else {
                $log->addStep('restart_queue', 'success', 'Queue worker UPCLOUD berhasil direstart.');
                $steps[] = ['step' => 'restart_queue', 'status' => 'success', 'detail' => 'Queue restarted.'];
            }

            // ----------------------------------------------------------------
            // SELESAI
            // ----------------------------------------------------------------
            $log->markSuccess('Failover ke UPCLOUD berhasil.', ['steps' => $steps]);
            Log::info('[FailoverService] Failover to UPCLOUD completed', ['log_id' => $log->id]);

            return [
                'success' => true,
                'log_id'  => $log->id,
                'message' => 'Failover ke UPCLOUD berhasil diselesaikan.',
                'steps'   => $steps,
            ];
        } catch (\Throwable $e) {
            Log::error('[FailoverService] Unexpected error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $log->markFailed('Unexpected error: ' . $e->getMessage());
            return $this->failResult($log->id, 'Unexpected error: ' . $e->getMessage(), $steps);
        }
    }

    /**
     * Failover balik dari UPCLOUD ke JH (rollback).
     */
    public function failoverToJH(int $userId, string $userName, string $ip): array
    {
        $log = FailoverLog::startLog('full_failover', 'upcloud', 'jh', $userId, $userName, $ip);
        $steps = [];
        $jhAgent = new ServerAgentClient('jh');
        $ucAgent = new ServerAgentClient('upcloud');

        try {
            // Step 1: Maintenance UPCLOUD
            $log->addStep('maintenance_upcloud', 'running', 'Mengirim artisan down ke UPCLOUD...');
            $ucAgent->artisanDown();
            $log->addStep('maintenance_upcloud', 'success', 'UPCLOUD maintenance mode aktif.');
            $steps[] = ['step' => 'maintenance_upcloud', 'status' => 'success'];

            // Step 2: Cek replica di JH
            $log->addStep('check_replica_jh', 'running', 'Mengecek replikasi di JH...');
            $replicaResult = $jhAgent->replicationStatus();
            $delay = (int) ($replicaResult['seconds_behind_source'] ?? PHP_INT_MAX);

            if ($delay !== 0) {
                $error = "JH replica delay = {$delay} detik. Harus 0.";
                $log->addStep('check_replica_jh', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }
            $log->addStep('check_replica_jh', 'success', 'JH replica in sync.');
            $steps[] = ['step' => 'check_replica_jh', 'status' => 'success'];

            // Step 3: Promote JH
            $log->addStep('promote_jh', 'running', 'Mempromote JH menjadi primary...');
            $promoteResult = $jhAgent->promotePrimary();
            if (! ($promoteResult['success'] ?? false)) {
                $error = $promoteResult['error'] ?? 'Gagal promote JH.';
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }
            $log->addStep('promote_jh', 'success', 'JH berhasil dipromote.');
            $steps[] = ['step' => 'promote_jh', 'status' => 'success'];

            // Step 4: Clear cache JH
            $jhAgent->clearCache();
            $log->addStep('clear_cache_jh', 'success', 'Cache JH dibersihkan.');
            $steps[] = ['step' => 'clear_cache_jh', 'status' => 'success'];

            // Step 5: Update DNS ke JH
            $log->addStep('update_dns', 'running', 'Mengupdate DNS ke JH...');
            $dnsResult = $this->cloudflare->updateARecordToJH();
            if (! ($dnsResult['success'] ?? false)) {
                $error = $dnsResult['error'] ?? 'Gagal update DNS.';
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }
            $log->addStep('update_dns', 'success', "DNS -> JH ({$dnsResult['ip']}).");
            $steps[] = ['step' => 'update_dns', 'status' => 'success'];

            // Step 6: Update setting
            FailoverSetting::current()->update(['active_server' => 'jh']);
            $steps[] = ['step' => 'update_setting', 'status' => 'success'];

            // Step 7: Artisan up JH + restart queue
            $jhAgent->artisanUp();
            $jhAgent->restartQueue();
            $log->addStep('bring_up_jh', 'success', 'JH kembali online.');
            $steps[] = ['step' => 'bring_up_jh', 'status' => 'success'];

            $log->markSuccess('Failover balik ke JH berhasil.', ['steps' => $steps]);

            return [
                'success' => true,
                'log_id'  => $log->id,
                'message' => 'Failover ke JH berhasil.',
                'steps'   => $steps,
            ];
        } catch (\Throwable $e) {
            $log->markFailed('Unexpected error: ' . $e->getMessage());
            return $this->failResult($log->id, $e->getMessage(), $steps);
        }
    }

    private function failResult(int $logId, string $message, array $steps): array
    {
        return [
            'success' => false,
            'log_id'  => $logId,
            'message' => $message,
            'steps'   => $steps,
        ];
    }
}
