<?php

namespace App\Services;

use App\Models\FailoverLog;
use App\Models\FailoverServer;
use App\Models\FailoverSetting;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator untuk proses failover dengan 3-server architecture.
 *
 * Architecture:
 *  - VPS A (jh): Web Server Primary → connects to VPS B for database
 *  - VPS B (db): Database Primary → replicates to VPS C
 *  - VPS C (upcloud): Standby with Web + DB Replica
 *
 * Failover Scenarios:
 *  1. VPS A down → Switch DNS to VPS C
 *  2. VPS B down → Promote VPS C DB, update VPS A to connect to VPS C
 *  3. VPS A & B down → Full failover to VPS C
 */
class FailoverService
{
    public function __construct(
        private CloudflareDnsService       $cloudflare,
        private DatabaseReplicationService $dbReplication,
    ) {}

    /**
     * SCENARIO 1: VPS A (Web Server) Down
     * Switch DNS to VPS C, VPS C uses local MySQL replica
     *
     * @param  int    $userId
     * @param  string $userName
     * @param  string $ip
     * @return array  ['success' => bool, 'log_id' => int, 'message' => string, 'steps' => array]
     */
    public function failoverWebServerDown(int $userId, string $userName, string $ip): array
    {
        $vpsA = FailoverServer::where('name', 'jh')->first();
        $vpsC = FailoverServer::where('name', 'upcloud')->first();
        
        $log = FailoverLog::startLog('web_server_failover', 'vps_a', 'vps_c', $userId, $userName, $ip);
        $steps = [];

        try {
            $ucAgent = new ServerAgentClient('upcloud');

            // ----------------------------------------------------------------
            // STEP 1: Verify VPS C replication is in sync
            // ----------------------------------------------------------------
            $log->addStep('check_replica', 'running', 'Mengecek status replikasi VPS C...');
            
            $replicaStatus = $this->dbReplication->getSlaveStatus($vpsC);
            
            if (!$replicaStatus['success']) {
                $error = 'Gagal cek status replikasi VPS C: ' . ($replicaStatus['error'] ?? 'Unknown');
                $log->addStep('check_replica', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $lag = $replicaStatus['seconds_behind'] ?? PHP_INT_MAX;
            if ($lag > 10) {
                $error = "Replica lag terlalu besar: {$lag} detik. Maksimal 10 detik.";
                $log->addStep('check_replica', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $log->addStep('check_replica', 'success', "Replica in sync (lag: {$lag}s)");
            $steps[] = ['step' => 'check_replica', 'status' => 'success', 'detail' => "Lag: {$lag}s"];

            // ----------------------------------------------------------------
            // STEP 2: Clear cache di VPS C
            // ----------------------------------------------------------------
            $log->addStep('clear_cache', 'running', 'Membersihkan cache di VPS C...');
            $cacheResult = $ucAgent->clearCache();
            
            if ($cacheResult['success'] ?? false) {
                $log->addStep('clear_cache', 'success', 'Cache cleared');
                $steps[] = ['step' => 'clear_cache', 'status' => 'success'];
            } else {
                $log->addStep('clear_cache', 'warning', 'Cache clear gagal, lanjut');
                $steps[] = ['step' => 'clear_cache', 'status' => 'warning'];
            }

            // ----------------------------------------------------------------
            // STEP 3: Update DNS to VPS C
            // ----------------------------------------------------------------
            $log->addStep('update_dns', 'running', 'Mengupdate DNS ke VPS C...');
            $dnsResult = $this->cloudflare->updateARecord($vpsC->ip_address);

            if (!($dnsResult['success'] ?? false)) {
                $error = $dnsResult['error'] ?? 'Gagal update DNS';
                $log->addStep('update_dns', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $log->addStep('update_dns', 'success', "DNS → VPS C ({$vpsC->ip_address})");
            $steps[] = ['step' => 'update_dns', 'status' => 'success', 'detail' => $vpsC->ip_address];

            // ----------------------------------------------------------------
            // STEP 4: Update server roles in database
            // ----------------------------------------------------------------
            $vpsA->update(['role' => 'replica', 'is_active' => false]);
            $vpsC->update(['role' => 'primary', 'is_active' => true]);
            
            $log->addStep('update_roles', 'success', 'Server roles updated');
            $steps[] = ['step' => 'update_roles', 'status' => 'success'];

            // ----------------------------------------------------------------
            // STEP 5: Restart queue di VPS C
            // ----------------------------------------------------------------
            $log->addStep('restart_queue', 'running', 'Restart queue VPS C...');
            $queueResult = $ucAgent->restartQueue();
            
            if ($queueResult['success'] ?? false) {
                $log->addStep('restart_queue', 'success', 'Queue restarted');
                $steps[] = ['step' => 'restart_queue', 'status' => 'success'];
            } else {
                $log->addStep('restart_queue', 'warning', 'Queue restart gagal');
                $steps[] = ['step' => 'restart_queue', 'status' => 'warning'];
            }

            $log->markSuccess('Failover web server berhasil', ['steps' => $steps]);
            
            return [
                'success' => true,
                'log_id'  => $log->id,
                'message' => 'Failover berhasil. DNS diarahkan ke VPS C.',
                'steps'   => $steps,
            ];

        } catch (\Throwable $e) {
            Log::error('[FailoverService] Web server failover error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $log->markFailed('Error: ' . $e->getMessage());
            return $this->failResult($log->id, $e->getMessage(), $steps);
        }
    }

    /**
     * SCENARIO 2: VPS B (Database) Down
     * Promote VPS C MySQL to primary, update VPS A to connect to VPS C
     *
     * @param  int    $userId
     * @param  string $userName
     * @param  string $ip
     * @return array
     */
    public function failoverDatabaseDown(int $userId, string $userName, string $ip): array
    {
        $vpsA = FailoverServer::where('name', 'jh')->first();
        $vpsB = FailoverServer::where('name', 'DB WEB PRODUCTION')->first();
        $vpsC = FailoverServer::where('name', 'upcloud')->first();
        
        $log = FailoverLog::startLog('database_failover', 'vps_b', 'vps_c', $userId, $userName, $ip);
        $steps = [];

        try {
            // ----------------------------------------------------------------
            // STEP 1: Promote VPS C MySQL to master
            // ----------------------------------------------------------------
            $log->addStep('promote_database', 'running', 'Promote VPS C MySQL ke master...');
            
            $promoteResult = $this->dbReplication->promoteToMaster($vpsC);
            
            if (!$promoteResult['success']) {
                $error = 'Gagal promote VPS C: ' . ($promoteResult['error'] ?? 'Unknown');
                $log->addStep('promote_database', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $log->addStep('promote_database', 'success', 'VPS C MySQL is now master');
            $steps[] = ['step' => 'promote_database', 'status' => 'success'];

            // ----------------------------------------------------------------
            // STEP 1b: Configure VPS B as slave from VPS C (reverse replication)
            // ----------------------------------------------------------------
            if ($vpsB && $vpsB->is_active) {
                $log->addStep('reverse_replication', 'running', 'Configure VPS B sebagai slave dari VPS C...');
                
                try {
                    // Get master status from VPS C
                    $masterStatus = $this->dbReplication->getMasterStatus($vpsC);
                    
                    if ($masterStatus['success']) {
                        // Configure VPS B as slave
                        $slaveResult = $this->dbReplication->configureSlave(
                            $vpsB,
                            $vpsC->ip_address,
                            $vpsC->replication_user ?? 'replication',
                            $vpsC->replication_password ?? '',
                            $masterStatus['file'],
                            $masterStatus['position']
                        );
                        
                        if ($slaveResult['success']) {
                            $log->addStep('reverse_replication', 'success', 'VPS B configured as slave from VPS C');
                            $steps[] = ['step' => 'reverse_replication', 'status' => 'success'];
                        } else {
                            $log->addStep('reverse_replication', 'warning', 'Gagal configure VPS B: ' . ($slaveResult['error'] ?? 'Unknown'));
                            $steps[] = ['step' => 'reverse_replication', 'status' => 'warning', 'detail' => 'Perlu manual setup'];
                        }
                    } else {
                        $log->addStep('reverse_replication', 'warning', 'Gagal get master status VPS C');
                        $steps[] = ['step' => 'reverse_replication', 'status' => 'warning', 'detail' => 'Perlu manual setup'];
                    }
                } catch (\Throwable $e) {
                    $log->addStep('reverse_replication', 'warning', 'Error: ' . $e->getMessage());
                    $steps[] = ['step' => 'reverse_replication', 'status' => 'warning', 'detail' => 'Perlu manual setup'];
                }
            } else {
                $log->addStep('reverse_replication', 'skipped', 'VPS B offline, skip reverse replication');
                $steps[] = ['step' => 'reverse_replication', 'status' => 'skipped'];
            }

            // ----------------------------------------------------------------
            // STEP 2: Update VPS A .env to connect to VPS C
            // ----------------------------------------------------------------
            $log->addStep('update_vps_a_config', 'running', 'Update VPS A database config...');
            
            // TODO: Implement SSH to VPS A and update .env file
            // For now, manual instruction
            $log->addStep('update_vps_a_config', 'warning', 
                'MANUAL: Update VPS A .env → DB_HOST=' . $vpsC->ip_address);
            $steps[] = [
                'step' => 'update_vps_a_config', 
                'status' => 'warning',
                'detail' => 'Perlu manual update .env di VPS A'
            ];

            // ----------------------------------------------------------------
            // STEP 3: Clear cache VPS A
            // ----------------------------------------------------------------
            $log->addStep('clear_cache_vps_a', 'running', 'Clear cache VPS A...');
            
            $jhAgent = new ServerAgentClient('jh');
            $cacheResult = $jhAgent->clearCache();
            
            if ($cacheResult['success'] ?? false) {
                $log->addStep('clear_cache_vps_a', 'success', 'Cache cleared');
                $steps[] = ['step' => 'clear_cache_vps_a', 'status' => 'success'];
            } else {
                $log->addStep('clear_cache_vps_a', 'warning', 'Cache clear gagal');
                $steps[] = ['step' => 'clear_cache_vps_a', 'status' => 'warning'];
            }

            // ----------------------------------------------------------------
            // STEP 4: Update database roles
            // ----------------------------------------------------------------
            $vpsB->update(['role' => 'replica', 'is_active' => false, 'db_role' => 'slave']);
            $vpsC->update(['db_role' => 'master']);
            
            $log->addStep('update_db_roles', 'success', 'Database roles updated');
            $steps[] = ['step' => 'update_db_roles', 'status' => 'success'];

            $log->markSuccess('Database failover berhasil', ['steps' => $steps]);
            
            return [
                'success' => true,
                'log_id'  => $log->id,
                'message' => 'Database failover berhasil. VPS C sekarang master. MANUAL: Update .env di VPS A.',
                'steps'   => $steps,
            ];

        } catch (\Throwable $e) {
            Log::error('[FailoverService] Database failover error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $log->markFailed('Error: ' . $e->getMessage());
            return $this->failResult($log->id, $e->getMessage(), $steps);
        }
    }

    /**
     * SCENARIO 3: Full Failover (VPS A & B Down)
     * Complete failover to VPS C
     */
    public function failoverComplete(int $userId, string $userName, string $ip): array
    {
        $vpsA = FailoverServer::where('name', 'jh')->first();
        $vpsB = FailoverServer::where('name', 'DB WEB PRODUCTION')->first();
        $vpsC = FailoverServer::where('name', 'upcloud')->first();
        
        $log = FailoverLog::startLog('complete_failover', 'vps_a_b', 'vps_c', $userId, $userName, $ip);
        $steps = [];

        try {
            $ucAgent = new ServerAgentClient('upcloud');

            // ----------------------------------------------------------------
            // STEP 1: Promote VPS C MySQL to master
            // ----------------------------------------------------------------
            $log->addStep('promote_database', 'running', 'Promote VPS C MySQL ke master...');
            
            $promoteResult = $this->dbReplication->promoteToMaster($vpsC);
            
            if (!$promoteResult['success']) {
                $error = 'Gagal promote VPS C: ' . ($promoteResult['error'] ?? 'Unknown');
                $log->addStep('promote_database', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $log->addStep('promote_database', 'success', 'VPS C MySQL is now master');
            $steps[] = ['step' => 'promote_database', 'status' => 'success'];

            // ----------------------------------------------------------------
            // STEP 1b: Configure VPS B as slave from VPS C (reverse replication)
            // ----------------------------------------------------------------
            if ($vpsB && $vpsB->is_active) {
                $log->addStep('reverse_replication', 'running', 'Configure VPS B sebagai slave dari VPS C...');
                
                try {
                    // Get master status from VPS C
                    $masterStatus = $this->dbReplication->getMasterStatus($vpsC);
                    
                    if ($masterStatus['success']) {
                        // Configure VPS B as slave
                        $slaveResult = $this->dbReplication->configureSlave(
                            $vpsB,
                            $vpsC->ip_address,
                            $vpsC->replication_user ?? 'replication',
                            $vpsC->replication_password ?? '',
                            $masterStatus['file'],
                            $masterStatus['position']
                        );
                        
                        if ($slaveResult['success']) {
                            $log->addStep('reverse_replication', 'success', 'VPS B configured as slave from VPS C');
                            $steps[] = ['step' => 'reverse_replication', 'status' => 'success'];
                        } else {
                            $log->addStep('reverse_replication', 'warning', 'Gagal configure VPS B: ' . ($slaveResult['error'] ?? 'Unknown'));
                            $steps[] = ['step' => 'reverse_replication', 'status' => 'warning', 'detail' => 'Perlu manual setup'];
                        }
                    } else {
                        $log->addStep('reverse_replication', 'warning', 'Gagal get master status VPS C');
                        $steps[] = ['step' => 'reverse_replication', 'status' => 'warning', 'detail' => 'Perlu manual setup'];
                    }
                } catch (\Throwable $e) {
                    $log->addStep('reverse_replication', 'warning', 'Error: ' . $e->getMessage());
                    $steps[] = ['step' => 'reverse_replication', 'status' => 'warning', 'detail' => 'Perlu manual setup'];
                }
            } else {
                $log->addStep('reverse_replication', 'skipped', 'VPS B offline, skip reverse replication');
                $steps[] = ['step' => 'reverse_replication', 'status' => 'skipped'];
            }

            // ----------------------------------------------------------------
            // STEP 2: Clear cache VPS C
            // ----------------------------------------------------------------
            $log->addStep('clear_cache', 'running', 'Clear cache VPS C...');
            
            try {
                $cacheResult = $ucAgent->clearCache();
                
                if ($cacheResult['success'] ?? false) {
                    $log->addStep('clear_cache', 'success', 'Cache cleared');
                    $steps[] = ['step' => 'clear_cache', 'status' => 'success'];
                } else {
                    $log->addStep('clear_cache', 'warning', 'Cache clear gagal (agent not installed?)');
                    $steps[] = ['step' => 'clear_cache', 'status' => 'warning'];
                }
            } catch (\Throwable $e) {
                $log->addStep('clear_cache', 'warning', 'Cache clear error: ' . $e->getMessage());
                $steps[] = ['step' => 'clear_cache', 'status' => 'warning'];
            }

            // ----------------------------------------------------------------
            // STEP 3: Update DNS to VPS C
            // ----------------------------------------------------------------
            $log->addStep('update_dns', 'running', 'Update DNS ke VPS C...');
            $dnsResult = $this->cloudflare->updateARecord($vpsC->ip_address);

            if (!($dnsResult['success'] ?? false)) {
                $error = $dnsResult['error'] ?? 'Gagal update DNS';
                $log->addStep('update_dns', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $log->addStep('update_dns', 'success', "DNS → VPS C ({$vpsC->ip_address})");
            $steps[] = ['step' => 'update_dns', 'status' => 'success'];

            // ----------------------------------------------------------------
            // STEP 4: Update all server roles
            // ----------------------------------------------------------------
            $vpsA->update(['role' => 'replica', 'is_active' => false]);
            $vpsB->update(['role' => 'replica', 'is_active' => false, 'db_role' => 'slave']);
            $vpsC->update(['role' => 'primary', 'is_active' => true, 'db_role' => 'master']);
            
            $log->addStep('update_roles', 'success', 'All roles updated');
            $steps[] = ['step' => 'update_roles', 'status' => 'success'];

            // ----------------------------------------------------------------
            // STEP 5: Restart queue VPS C
            // ----------------------------------------------------------------
            $log->addStep('restart_queue', 'running', 'Restart queue VPS C...');
            
            try {
                $queueResult = $ucAgent->restartQueue();
                
                if ($queueResult['success'] ?? false) {
                    $log->addStep('restart_queue', 'success', 'Queue restarted');
                    $steps[] = ['step' => 'restart_queue', 'status' => 'success'];
                } else {
                    $log->addStep('restart_queue', 'warning', 'Queue restart gagal');
                    $steps[] = ['step' => 'restart_queue', 'status' => 'warning'];
                }
            } catch (\Throwable $e) {
                $log->addStep('restart_queue', 'warning', 'Queue restart error: ' . $e->getMessage());
                $steps[] = ['step' => 'restart_queue', 'status' => 'warning'];
            }

            $log->markSuccess('Complete failover berhasil', ['steps' => $steps]);
            
            return [
                'success' => true,
                'log_id'  => $log->id,
                'message' => '✅ Complete failover berhasil! VPS C sekarang primary (web + database master).',
                'steps'   => $steps,
            ];

        } catch (\Throwable $e) {
            Log::error('[FailoverService] Complete failover error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $log->markFailed('Error: ' . $e->getMessage());
            return $this->failResult($log->id, $e->getMessage(), $steps);
        }
    }

    /**
     * Legacy method - redirect to web server failover
     */
    public function failoverToUpcloud(int $userId, string $userName, string $ip): array
    {
        return $this->failoverWebServerDown($userId, $userName, $ip);
    }

    /**
     * Rollback: Switch back to VPS A
     */
    public function failoverToJH(int $userId, string $userName, string $ip): array
    {
        $vpsA = FailoverServer::where('name', 'jh')->first();
        $vpsB = FailoverServer::where('name', 'DB WEB PRODUCTION')->first();
        $vpsC = FailoverServer::where('name', 'upcloud')->first();
        
        $log = FailoverLog::startLog('rollback_to_vps_a', 'vps_c', 'vps_a', $userId, $userName, $ip);
        $steps = [];

        try {
            $jhAgent = new ServerAgentClient('jh');
            $ucAgent = new ServerAgentClient('upcloud');

            // ----------------------------------------------------------------
            // STEP 1: Verify VPS A is online
            // ----------------------------------------------------------------
            $log->addStep('check_vps_a', 'running', 'Cek VPS A online...');
            
            try {
                $healthResult = $jhAgent->health();
                
                if (!($healthResult['reachable'] ?? false)) {
                    $error = 'VPS A tidak reachable';
                    $log->addStep('check_vps_a', 'failed', $error);
                    $log->markFailed($error);
                    return $this->failResult($log->id, $error, $steps);
                }

                $log->addStep('check_vps_a', 'success', 'VPS A online');
                $steps[] = ['step' => 'check_vps_a', 'status' => 'success'];
            } catch (\Throwable $e) {
                $error = 'VPS A health check error: ' . $e->getMessage();
                $log->addStep('check_vps_a', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            // ----------------------------------------------------------------
            // STEP 2: Verify VPS B is online
            // ----------------------------------------------------------------
            $log->addStep('check_vps_b', 'running', 'Cek VPS B online...');
            
            try {
                $dbTest = $this->dbReplication->testConnection($vpsB);
                
                if (!$dbTest['success']) {
                    $error = 'VPS B database tidak reachable';
                    $log->addStep('check_vps_b', 'failed', $error);
                    $log->markFailed($error);
                    return $this->failResult($log->id, $error, $steps);
                }

                $log->addStep('check_vps_b', 'success', 'VPS B online');
                $steps[] = ['step' => 'check_vps_b', 'status' => 'success'];
            } catch (\Throwable $e) {
                $error = 'VPS B connection error: ' . $e->getMessage();
                $log->addStep('check_vps_b', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            // ----------------------------------------------------------------
            // STEP 3: Reconfigure replication VPS B → VPS C
            // ----------------------------------------------------------------
            $log->addStep('reconfigure_replication', 'running', 'Reconfigure replication VPS B → VPS C...');
            
            try {
                // First, promote VPS B back to master (stop slave if it was slave from VPS C)
                $promoteB = $this->dbReplication->promoteToMaster($vpsB);
                
                if (!$promoteB['success']) {
                    $log->addStep('reconfigure_replication', 'warning', 'Gagal promote VPS B: ' . ($promoteB['error'] ?? 'Unknown'));
                    $steps[] = [
                        'step' => 'reconfigure_replication',
                        'status' => 'warning',
                        'detail' => 'Perlu manual setup replication'
                    ];
                } else {
                    // Demote VPS C to slave (enable read_only)
                    $demoteC = $this->dbReplication->demoteToSlave($vpsC);
                    
                    // Get master status from VPS B
                    $masterStatus = $this->dbReplication->getMasterStatus($vpsB);
                    
                    if ($masterStatus['success']) {
                        // Configure VPS C as slave from VPS B
                        $slaveResult = $this->dbReplication->configureSlave(
                            $vpsC,
                            $vpsB->ip_address,
                            $vpsB->replication_user ?? 'replication',
                            $vpsB->replication_password ?? '',
                            $masterStatus['file'],
                            $masterStatus['position']
                        );
                        
                        if ($slaveResult['success']) {
                            $log->addStep('reconfigure_replication', 'success', 'Replication VPS B → VPS C configured');
                            $steps[] = ['step' => 'reconfigure_replication', 'status' => 'success'];
                        } else {
                            $log->addStep('reconfigure_replication', 'warning', 'Gagal configure slave: ' . ($slaveResult['error'] ?? 'Unknown'));
                            $steps[] = [
                                'step' => 'reconfigure_replication',
                                'status' => 'warning',
                                'detail' => 'Perlu manual setup replication'
                            ];
                        }
                    } else {
                        $log->addStep('reconfigure_replication', 'warning', 'Gagal get master status VPS B');
                        $steps[] = [
                            'step' => 'reconfigure_replication',
                            'status' => 'warning',
                            'detail' => 'Perlu manual setup replication'
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $log->addStep('reconfigure_replication', 'warning', 'Error: ' . $e->getMessage());
                $steps[] = [
                    'step' => 'reconfigure_replication',
                    'status' => 'warning',
                    'detail' => 'Perlu manual setup replication'
                ];
            }

            // ----------------------------------------------------------------
            // STEP 4: Clear cache VPS A
            // ----------------------------------------------------------------
            $log->addStep('clear_cache', 'running', 'Clear cache VPS A...');
            
            try {
                $cacheResult = $jhAgent->clearCache();
            
            if ($cacheResult['success'] ?? false) {
                $log->addStep('clear_cache', 'success', 'Cache cleared');
                $steps[] = ['step' => 'clear_cache', 'status' => 'success'];
            } else {
                $log->addStep('clear_cache', 'warning', 'Cache clear gagal (agent not installed?)');
                $steps[] = ['step' => 'clear_cache', 'status' => 'warning'];
            }
            } catch (\Throwable $e) {
                $log->addStep('clear_cache', 'warning', 'Cache clear gagal: ' . $e->getMessage());
                $steps[] = ['step' => 'clear_cache', 'status' => 'warning'];
            }

            // ----------------------------------------------------------------
            // STEP 5: Update DNS back to VPS A
            // ----------------------------------------------------------------
            $log->addStep('update_dns', 'running', 'Update DNS ke VPS A...');
            $dnsResult = $this->cloudflare->updateARecord($vpsA->ip_address);

            if (!($dnsResult['success'] ?? false)) {
                $error = $dnsResult['error'] ?? 'Gagal update DNS';
                $log->addStep('update_dns', 'failed', $error);
                $log->markFailed($error);
                return $this->failResult($log->id, $error, $steps);
            }

            $log->addStep('update_dns', 'success', "DNS → VPS A ({$vpsA->ip_address})");
            $steps[] = ['step' => 'update_dns', 'status' => 'success'];

            // ----------------------------------------------------------------
            // STEP 6: Update server roles
            // ----------------------------------------------------------------
            $vpsA->update(['role' => 'primary', 'is_active' => true]);
            $vpsB->update(['role' => 'primary', 'is_active' => true, 'db_role' => 'master']);
            $vpsC->update(['role' => 'replica', 'is_active' => true, 'db_role' => 'slave']);
            
            $log->addStep('update_roles', 'success', 'Roles updated');
            $steps[] = ['step' => 'update_roles', 'status' => 'success'];

            // ----------------------------------------------------------------
            // STEP 7: Restart queue VPS A
            // ----------------------------------------------------------------
            $log->addStep('restart_queue', 'running', 'Restart queue VPS A...');
            
            try {
                $queueResult = $jhAgent->restartQueue();
            
            if ($queueResult['success'] ?? false) {
                $log->addStep('restart_queue', 'success', 'Queue restarted');
                $steps[] = ['step' => 'restart_queue', 'status' => 'success'];
            } else {
                $log->addStep('restart_queue', 'warning', 'Queue restart gagal');
                $steps[] = ['step' => 'restart_queue', 'status' => 'warning'];
            }
            } catch (\Throwable $e) {
                $log->addStep('restart_queue', 'warning', 'Queue restart gagal: ' . $e->getMessage());
                $steps[] = ['step' => 'restart_queue', 'status' => 'warning'];
            }

            $log->markSuccess('Rollback ke VPS A berhasil', ['steps' => $steps]);
            
            return [
                'success' => true,
                'log_id'  => $log->id,
                'message' => '✅ Rollback berhasil! VPS A kembali primary, VPS B master, replication VPS B → VPS C.',
                'steps'   => $steps,
            ];

        } catch (\Throwable $e) {
            Log::error('[FailoverService] Rollback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $log->markFailed('Error: ' . $e->getMessage());
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
