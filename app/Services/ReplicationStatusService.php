<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengecek status replikasi MySQL/MariaDB.
 * Support syntax baru (REPLICA) dan lama (SLAVE).
 */
class ReplicationStatusService
{
    /**
     * Ambil status replikasi dari database lokal.
     * Otomatis fallback dari SHOW REPLICA STATUS ke SHOW SLAVE STATUS.
     */
    public function getLocalReplicationStatus(): array
    {
        try {
            // Coba syntax baru (MySQL 8.0.22+ / MariaDB 10.5.1+)
            $result = $this->tryShowReplicaStatus();

            if ($result === null) {
                // Fallback ke syntax lama
                $result = $this->tryShowSlaveStatus();
            }

            if ($result === null) {
                return [
                    'success'  => false,
                    'is_slave' => false,
                    'message'  => 'Server ini bukan replica atau tidak ada replikasi aktif.',
                ];
            }

            return $this->normalizeReplicationStatus($result);
        } catch (\Throwable $e) {
            Log::error('[ReplicationStatusService] getLocalReplicationStatus failed', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success'  => false,
                'is_slave' => false,
                'message'  => $e->getMessage(),
            ];
        }
    }

    /**
     * Cek apakah replica sudah sync (delay = 0).
     */
    public function isReplicaInSync(): bool
    {
        $status = $this->getLocalReplicationStatus();

        if (! $status['success'] || ! $status['is_slave']) {
            return false;
        }

        return (int) ($status['seconds_behind_source'] ?? PHP_INT_MAX) === 0
            && $status['io_running'] === 'Yes'
            && $status['sql_running'] === 'Yes';
    }

    /**
     * Promote server ini menjadi primary:
     * STOP REPLICA; RESET REPLICA ALL;
     * (fallback: STOP SLAVE; RESET SLAVE ALL;)
     */
    public function promoteToPrimary(): array
    {
        try {
            // Coba syntax baru
            try {
                DB::statement('STOP REPLICA');
                DB::statement('RESET REPLICA ALL');
                Log::info('[ReplicationStatusService] Promoted to primary using REPLICA syntax');
                return ['success' => true, 'syntax' => 'replica'];
            } catch (\Throwable $e) {
                // Fallback ke syntax lama
                DB::statement('STOP SLAVE');
                DB::statement('RESET SLAVE ALL');
                Log::info('[ReplicationStatusService] Promoted to primary using SLAVE syntax');
                return ['success' => true, 'syntax' => 'slave'];
            }
        } catch (\Throwable $e) {
            Log::error('[ReplicationStatusService] promoteToPrimary failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function tryShowReplicaStatus(): ?object
    {
        try {
            $result = DB::select('SHOW REPLICA STATUS');
            return $result[0] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function tryShowSlaveStatus(): ?object
    {
        try {
            $result = DB::select('SHOW SLAVE STATUS');
            return $result[0] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Normalisasi field dari SHOW REPLICA/SLAVE STATUS ke format standar.
     */
    private function normalizeReplicationStatus(object $row): array
    {
        $data = (array) $row;

        // Support kedua naming convention
        $secondsBehind = $data['Seconds_Behind_Source']
            ?? $data['Seconds_Behind_Master']
            ?? null;

        $ioRunning = $data['Replica_IO_Running']
            ?? $data['Slave_IO_Running']
            ?? 'No';

        $sqlRunning = $data['Replica_SQL_Running']
            ?? $data['Slave_SQL_Running']
            ?? 'No';

        $ioState = $data['Replica_IO_State']
            ?? $data['Slave_IO_State']
            ?? '';

        $sqlState = $data['Replica_SQL_Running_State']
            ?? $data['Slave_SQL_Running_State']
            ?? '';

        $lastError = $data['Last_Error']
            ?? $data['Last_SQL_Error']
            ?? '';

        return [
            'success'              => true,
            'is_slave'             => true,
            'seconds_behind_source'=> $secondsBehind,
            'io_running'           => $ioRunning,
            'sql_running'          => $sqlRunning,
            'io_state'             => $ioState,
            'sql_state'            => $sqlState,
            'last_error'           => $lastError,
            'source_host'          => $data['Source_Host'] ?? $data['Master_Host'] ?? null,
            'source_port'          => $data['Source_Port'] ?? $data['Master_Port'] ?? null,
            'relay_log_pos'        => $data['Relay_Log_Pos'] ?? null,
            'exec_source_log_pos'  => $data['Exec_Source_Log_Pos'] ?? $data['Exec_Master_Log_Pos'] ?? null,
            'is_in_sync'           => (int) $secondsBehind === 0 && $ioRunning === 'Yes' && $sqlRunning === 'Yes',
            'raw'                  => $data,
        ];
    }
}
