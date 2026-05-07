<?php

namespace App\Console\Commands;

use App\Services\CloudflareDnsService;
use App\Services\ServerAgentClient;
use Illuminate\Console\Command;

class FailoverTestConnection extends Command
{
    protected $signature   = 'failover:test-connection {--server= : jh atau upcloud (default: keduanya)}';
    protected $description = 'Test koneksi ke agent server dan Cloudflare API';

    public function handle(CloudflareDnsService $cloudflare): int
    {
        $server = $this->option('server');
        $servers = $server ? [$server] : ['jh', 'upcloud'];

        foreach ($servers as $srv) {
            $this->info("Testing connection to {$srv}...");

            try {
                $agent  = new ServerAgentClient($srv);
                $health = $agent->health();

                if ($health['reachable'] ?? false) {
                    $this->line("  ✓ Reachable");
                    $this->line("  ✓ Server: " . ($health['server'] ?? 'unknown'));
                    $this->line("  ✓ Maintenance: " . (($health['maintenance'] ?? false) ? 'YES' : 'no'));
                } else {
                    $this->error("  ✗ Not reachable: " . ($health['error'] ?? 'unknown error'));
                }

                $this->info("  Testing system status...");
                $system = $agent->systemStatus();
                if ($system['reachable'] ?? false) {
                    $data = $system['data'] ?? [];
                    $this->line("  ✓ CPU 1min: " . ($data['cpu_load']['1min'] ?? 'N/A'));
                    $this->line("  ✓ RAM: " . ($data['memory']['percent'] ?? 'N/A') . '%');
                    $this->line("  ✓ Disk: " . ($data['disk']['percent'] ?? 'N/A') . '%');
                }

                $this->info("  Testing replication status...");
                $replica = $agent->replicationStatus();
                if ($replica['is_slave'] ?? false) {
                    $this->line("  ✓ Is replica: YES");
                    $this->line("  ✓ Seconds behind: " . ($replica['seconds_behind_source'] ?? 'N/A'));
                    $this->line("  ✓ IO Running: " . ($replica['io_running'] ?? 'N/A'));
                    $this->line("  ✓ SQL Running: " . ($replica['sql_running'] ?? 'N/A'));
                } else {
                    $this->line("  - Not a replica (or no replication configured)");
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ Exception: " . $e->getMessage());
            }

            $this->newLine();
        }

        // Test Cloudflare
        $this->info("Testing Cloudflare DNS...");
        $dns = $cloudflare->getCurrentDnsTarget();
        if ($dns['success'] ?? false) {
            $this->line("  ✓ Current DNS: " . ($dns['ip'] ?? 'N/A') . " (" . ($dns['server'] ?? 'unknown') . ")");
        } else {
            $this->error("  ✗ Cloudflare error: " . ($dns['error'] ?? 'unknown'));
        }

        return Command::SUCCESS;
    }
}
