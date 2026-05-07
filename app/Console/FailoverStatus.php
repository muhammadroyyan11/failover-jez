<?php

namespace App\Console\Commands;

use App\Models\FailoverSetting;
use App\Services\CloudflareDnsService;
use App\Services\ReplicationStatusService;
use App\Services\ServerAgentClient;
use Illuminate\Console\Command;

class FailoverStatus extends Command
{
    protected $signature   = 'failover:status';
    protected $description = 'Tampilkan status lengkap failover panel (server, replikasi, DNS)';

    public function handle(
        CloudflareDnsService     $cloudflare,
        ReplicationStatusService $replication
    ): int {
        $setting = FailoverSetting::current();

        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║       FAILOVER PANEL STATUS              ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // Active server
        $this->line('  Active Server : <fg=yellow>' . strtoupper($setting->active_server) . '</>');
        $this->line('  Primary Domain: ' . $setting->primary_domain);
        $this->line('  Standby Domain: ' . $setting->standby_domain);
        $this->info('');

        // JH Status
        $this->info('── VPS JH (' . $setting->jh_ip . ') ──────────────────────');
        $this->checkServer('jh');

        $this->info('');

        // UPCLOUD Status
        $this->info('── VPS UPCLOUD (' . $setting->upcloud_ip . ') ──────────────');
        $this->checkServer('upcloud');

        $this->info('');

        // Replication (local)
        $this->info('── Replication Status (local DB) ──────────');
        $replica = $replication->getLocalReplicationStatus();
        if ($replica['is_slave'] ?? false) {
            $delay = $replica['seconds_behind_source'] ?? 'N/A';
            $io    = $replica['io_running'] ?? 'N/A';
            $sql   = $replica['sql_running'] ?? 'N/A';
            $color = $delay === 0 ? 'green' : 'red';
            $this->line("  Seconds Behind : <fg={$color}>{$delay}</>");
            $this->line("  IO Running     : <fg=" . ($io === 'Yes' ? 'green' : 'red') . ">{$io}</>");
            $this->line("  SQL Running    : <fg=" . ($sql === 'Yes' ? 'green' : 'red') . ">{$sql}</>");
        } else {
            $this->line('  <fg=yellow>Bukan replica atau tidak ada replikasi aktif.</>');
        }

        $this->info('');

        // DNS
        $this->info('── Cloudflare DNS ─────────────────────────');
        $dns = $cloudflare->getCurrentDnsTarget();
        if ($dns['success'] ?? false) {
            $server = $dns['server'] ?? 'unknown';
            $color  = $server === 'jh' ? 'blue' : ($server === 'upcloud' ? 'green' : 'yellow');
            $this->line("  Domain : " . ($dns['name'] ?? '-'));
            $this->line("  IP     : " . ($dns['ip'] ?? '-'));
            $this->line("  Server : <fg={$color}>" . strtoupper($server) . "</>");
        } else {
            $this->error('  Cloudflare API error: ' . ($dns['error'] ?? 'unknown'));
        }

        $this->info('');

        return Command::SUCCESS;
    }

    private function checkServer(string $server): void
    {
        try {
            $agent  = new ServerAgentClient($server);
            $health = $agent->health();

            if ($health['reachable'] ?? false) {
                $maint = ($health['maintenance'] ?? false) ? '<fg=yellow>MAINTENANCE</>' : '<fg=green>LIVE</>';
                $this->line("  Status     : <fg=green>ONLINE</> | App: {$maint}");

                $system = $agent->systemStatus();
                if ($system['reachable'] ?? false) {
                    $data = $system['data'] ?? [];
                    $cpu  = $data['cpu_load']['1min'] ?? 'N/A';
                    $ram  = $data['memory']['percent'] ?? 'N/A';
                    $disk = $data['disk']['percent'] ?? 'N/A';
                    $this->line("  CPU Load   : {$cpu}");
                    $this->line("  RAM Usage  : {$ram}%");
                    $this->line("  Disk Usage : {$disk}%");
                }

                $replica = $agent->replicationStatus();
                if ($replica['is_slave'] ?? false) {
                    $delay = $replica['seconds_behind_source'] ?? 'N/A';
                    $color = $delay === 0 ? 'green' : 'red';
                    $this->line("  Replica    : <fg={$color}>delay={$delay}s IO={$replica['io_running']} SQL={$replica['sql_running']}</>");
                } else {
                    $this->line("  Replica    : <fg=yellow>Not a replica</>");
                }
            } else {
                $this->line("  Status     : <fg=red>OFFLINE</> | " . ($health['error'] ?? 'unreachable'));
            }
        } catch (\Throwable $e) {
            $this->line("  Status     : <fg=red>ERROR</> | " . $e->getMessage());
        }
    }
}
