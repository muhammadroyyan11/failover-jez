<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Log;

class SshService
{
    private SSH2 $ssh;
    private string $server;

    public function __construct(string $server = 'upcloud')
    {
        $this->server = $server;
        $host = $server === 'jh' ? env('JH_SSH_HOST') : env('UPCLOUD_SSH_HOST');
        $port = $server === 'jh' ? env('JH_SSH_PORT', 22) : env('UPCLOUD_SSH_PORT', 22);
        
        $this->ssh = new SSH2($host, $port);
    }

    /**
     * Connect menggunakan private key
     */
    public function connect(): bool
    {
        try {
            $username = $this->server === 'jh' 
                ? env('JH_SSH_USER', 'root') 
                : env('UPCLOUD_SSH_USER', 'root');
            
            $keyPath = $this->server === 'jh'
                ? storage_path('ssh/jh_key')
                : storage_path('ssh/upcloud_key');

            if (!file_exists($keyPath)) {
                Log::error("SSH key not found: {$keyPath}");
                return false;
            }

            $key = PublicKeyLoader::load(file_get_contents($keyPath));
            
            if (!$this->ssh->login($username, $key)) {
                Log::error("SSH login failed for {$this->server}");
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("SSH connection error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute command via SSH
     */
    public function exec(string $command): array
    {
        if (!$this->connect()) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'SSH connection failed'
            ];
        }

        try {
            $output = $this->ssh->exec($command);
            $exitStatus = $this->ssh->getExitStatus();

            return [
                'success' => $exitStatus === 0,
                'output' => $output,
                'exit_code' => $exitStatus
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute Laravel artisan command
     */
    public function artisan(string $command): array
    {
        $appPath = $this->server === 'jh' 
            ? env('JH_APP_PATH', '/home/jezpro/public_html') 
            : env('UPCLOUD_APP_PATH', '/home/jezpro/public_html');

        $fullCommand = "cd {$appPath} && php artisan {$command}";
        return $this->exec($fullCommand);
    }

    /**
     * Restart services via SSH
     */
    public function restartService(string $service): array
    {
        // CyberPanel menggunakan systemd
        return $this->exec("sudo systemctl restart {$service}");
    }

    /**
     * Check disk usage
     */
    public function getDiskUsage(): array
    {
        $result = $this->exec("df -h / | tail -1 | awk '{print \$5}'");
        
        if ($result['success']) {
            return [
                'success' => true,
                'usage' => trim($result['output'])
            ];
        }

        return $result;
    }

    /**
     * Sync storage via rsync
     */
    public function syncStorage(string $source, string $destination): array
    {
        // Rsync dari JH ke UPCLOUD
        $command = sprintf(
            "rsync -avz --delete %s %s@%s:%s",
            $source,
            env('UPCLOUD_SSH_USER', 'root'),
            env('UPCLOUD_SSH_HOST'),
            $destination
        );

        return $this->exec($command);
    }

    public function disconnect(): void
    {
        $this->ssh->disconnect();
    }
}
