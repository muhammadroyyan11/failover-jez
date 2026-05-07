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
     */
    public function configureSlave(FailoverServer $slave, FailoverServer $master, array $masterStatus): array
    {
        try {
            $pdo = $this->createConnection($slave);
            
            // Stop slave if running
            $pdo->exec('STOP SLAVE');
            
            // Configure master connection
            $changeMaster = sprintf(
                "CHANGE MASTER TO MASTER_HOST='%s', MASTER_PORT=%d, MASTER_USER='%s', MASTER_PASSWORD='%s', MASTER_LOG_FILE='%s', MASTER_LOG_POS=%d",
                $master->db_host,
                $master->db_port,
                $master->replication_user,
                $master->replication_password,
                $masterStatus['file'],
                $masterStatus['position']
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
            
            return [
                'success' => true,
                'message' => 'Server promoted to master successfully',
            ];
        } catch (PDOException $e) {
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
