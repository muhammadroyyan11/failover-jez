<?php

namespace App\Services;

use App\Models\FailoverServer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class DatabaseReplicationService
{
    /**
     * Test database connection
     */
    public function testConnection(FailoverServer $server): array
    {
        try {
            $pdo = $this->createConnection($server);
            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'version' => $version,
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get master status
     */
    public function getMasterStatus(FailoverServer $server): array
    {
        try {
            $pdo = $this->createConnection($server);
            $stmt = $pdo->query('SHOW MASTER STATUS');
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$status) {
                return [
                    'success' => false,
                    'message' => 'Not configured as master or binary logging disabled',
                ];
            }
            
            return [
                'success' => true,
                'file' => $status['File'],
                'position' => $status['Position'],
                'binlog_do_db' => $status['Binlog_Do_DB'] ?? '',
                'binlog_ignore_db' => $status['Binlog_Ignore_DB'] ?? '',
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get slave status
     */
    public function getSlaveStatus(FailoverServer $server): array
    {
        try {
            $pdo = $this->createConnection($server);
            
            // Try SHOW REPLICA STATUS first (MySQL 8.0.22+)
            $stmt = $pdo->query('SHOW REPLICA STATUS');
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Fallback to SHOW SLAVE STATUS
            if (!$status) {
                $stmt = $pdo->query('SHOW SLAVE STATUS');
                $status = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if (!$status) {
                return [
                    'success' => false,
                    'message' => 'Not configured as slave/replica',
                ];
            }
            
            return [
                'success' => true,
                'io_running' => ($status['Slave_IO_Running'] ?? $status['Replica_IO_Running'] ?? 'No') === 'Yes',
                'sql_running' => ($status['Slave_SQL_Running'] ?? $status['Replica_SQL_Running'] ?? 'No') === 'Yes',
                'seconds_behind' => $status['Seconds_Behind_Master'] ?? $status['Seconds_Behind_Source'] ?? null,
                'master_host' => $status['Master_Host'] ?? $status['Source_Host'] ?? '',
                'master_log_file' => $status['Master_Log_File'] ?? $status['Source_Log_File'] ?? '',
                'read_master_log_pos' => $status['Read_Master_Log_Pos'] ?? $status['Read_Source_Log_Pos'] ?? 0,
                'last_error' => $status['Last_Error'] ?? $status['Last_SQL_Error'] ?? '',
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Setup replication (create user on master)
     */
    public function setupReplicationUser(FailoverServer $master, string $replUser, string $replPassword): array
    {
        try {
            $pdo = $this->createConnection($master);
            
            // Create replication user
            $pdo->exec("CREATE USER IF NOT EXISTS '{$replUser}'@'%' IDENTIFIED BY '{$replPassword}'");
            $pdo->exec("GRANT REPLICATION SLAVE ON *.* TO '{$replUser}'@'%'");
            $pdo->exec("FLUSH PRIVILEGES");
            
            return [
                'success' => true,
                'message' => 'Replication user created successfully',
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Configure slave to replicate from master
     * 
     * @param FailoverServer $slave Server yang akan jadi slave
     * @param string|FailoverServer $master Master server atau IP address
     * @param string $replUser Replication username
     * @param string $replPassword Replication password
     * @param string $logFile Master log file
     * @param int $logPos Master log position
     */
    public function configureSlave(
        FailoverServer $slave, 
        $master, 
        string $replUser = null,
        string $replPassword = null,
        string $logFile = null,
        int $logPos = null
    ): array {
        try {
            $pdo = $this->createConnection($slave);
            
            // If master is FailoverServer object, extract data
            if ($master instanceof FailoverServer) {
                $masterHost = $master->db_host;
                $masterPort = $master->db_port;
                $replUser = $replUser ?? $master->replication_user;
                $replPassword = $replPassword ?? $master->replication_password;
                
                // Get master status if not provided
                if (!$logFile || !$logPos) {
                    $masterStatus = $this->getMasterStatus($master);
                    if (!$masterStatus['success']) {
                        return [
                            'success' => false,
                            'message' => 'Failed to get master status: ' . ($masterStatus['message'] ?? 'Unknown'),
                        ];
                    }
                    $logFile = $masterStatus['file'];
                    $logPos = $masterStatus['position'];
                }
            } else {
                // Master is IP address string
                $masterHost = $master;
                $masterPort = 3306;
            }
            
            // Validate required parameters
            if (!$replUser || !$replPassword || !$logFile || !$logPos) {
                return [
                    'success' => false,
                    'message' => 'Missing required parameters for slave configuration',
                ];
            }
            
            // Stop slave if running
            try {
                $pdo->exec('STOP SLAVE');
            } catch (\PDOException $e) {
                // Ignore if slave not running
            }
            
            // Configure master connection
            $changeMaster = sprintf(
                "CHANGE MASTER TO MASTER_HOST='%s', MASTER_PORT=%d, MASTER_USER='%s', MASTER_PASSWORD='%s', MASTER_LOG_FILE='%s', MASTER_LOG_POS=%d",
                $masterHost,
                $masterPort,
                $replUser,
                $replPassword,
                $logFile,
                $logPos
            );
            
            $pdo->exec($changeMaster);
            
            // Start slave
            $pdo->exec('START SLAVE');
            
            // Check status
            sleep(2);
            $status = $this->getSlaveStatus($slave);
            
            return [
                'success' => true,
                'message' => 'Slave configured successfully',
                'status' => $status,
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Promote slave to master
     */
    public function promoteToMaster(FailoverServer $slave): array
    {
        try {
            $pdo = $this->createConnection($slave);
            
            // Stop slave
            $pdo->exec('STOP SLAVE');
            
            // Reset slave (promote to master)
            $pdo->exec('RESET SLAVE ALL');
            
            // Disable read_only mode (allow writes)
            $pdo->exec('SET GLOBAL read_only = OFF');
            
            Log::info('[DatabaseReplicationService] Server promoted to master', [
                'server' => $slave->name,
                'ip' => $slave->ip_address,
            ]);
            
            return [
                'success' => true,
                'message' => 'Server promoted to master successfully (read_only disabled)',
            ];
        } catch (PDOException $e) {
            Log::error('[DatabaseReplicationService] Promote to master failed', [
                'server' => $slave->name,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Demote master to slave (enable read_only)
     */
    public function demoteToSlave(FailoverServer $server): array
    {
        try {
            $pdo = $this->createConnection($server);
            
            // Enable read_only mode
            $pdo->exec('SET GLOBAL read_only = ON');
            
            Log::info('[DatabaseReplicationService] Server demoted to slave', [
                'server' => $server->name,
                'ip' => $server->ip_address,
            ]);
            
            return [
                'success' => true,
                'message' => 'Server demoted to slave (read_only enabled)',
            ];
        } catch (PDOException $e) {
            Log::error('[DatabaseReplicationService] Demote to slave failed', [
                'server' => $server->name,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update cached replication status
     */
    public function updateReplicationStatus(FailoverServer $server): void
    {
        if ($server->db_role === 'slave') {
            $status = $this->getSlaveStatus($server);
            
            if ($status['success']) {
                $server->update([
                    'replication_io_running' => $status['io_running'],
                    'replication_sql_running' => $status['sql_running'],
                    'seconds_behind_master' => $status['seconds_behind'],
                    'replication_checked_at' => now(),
                ]);
            }
        }
    }

    /**
     * Create PDO connection
     */
    private function createConnection(FailoverServer $server): PDO
    {
        // Force TCP/IP connection (avoid Unix socket issues)
        $host = $server->db_host;
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $host = '127.0.0.1'; // Force TCP/IP instead of Unix socket
        }
        
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $host,
            $server->db_port
        );
        
        if ($server->db_database) {
            $dsn .= ';dbname=' . $server->db_database;
        }
        
        return new PDO(
            $dsn,
            $server->db_username,
            $server->db_password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]
        );
    }
}
