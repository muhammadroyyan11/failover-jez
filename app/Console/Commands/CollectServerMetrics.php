<?php

namespace App\Console\Commands;

use App\Models\FailoverServer;
use App\Models\ServerMetric;
use App\Services\ServerAgentClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CollectServerMetrics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'metrics:collect 
                            {--server= : Specific server ID to collect}
                            {--force : Force collection even if server is inactive}';

    /**
     * The console command description.
     */
    protected $description = 'Collect server metrics from all active servers via Agent API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting metrics collection...');

        $query = FailoverServer::query();

        // Filter by specific server if provided
        if ($serverId = $this->option('server')) {
            $query->where('id', $serverId);
        }

        // Only active servers unless forced
        if (!$this->option('force')) {
            $query->where('is_active', true);
        }

        // Only web servers (not database-only)
        $query->whereIn('server_type', ['web', 'both']);

        $servers = $query->get();

        if ($servers->isEmpty()) {
            $this->warn('⚠️  No servers found to collect metrics from.');
            return self::SUCCESS;
        }

        $this->info("📊 Collecting metrics from {$servers->count()} server(s)...");

        $successCount = 0;
        $failCount = 0;

        foreach ($servers as $server) {
            try {
                $this->line("  → {$server->label} ({$server->name})...");
                
                $metrics = $this->collectMetrics($server);
                
                if ($metrics) {
                    ServerMetric::create($metrics);
                    $this->info("    ✅ Success");
                    $successCount++;
                } else {
                    $this->error("    ❌ Failed to collect metrics");
                    $failCount++;
                }
            } catch (\Throwable $e) {
                $this->error("    ❌ Error: {$e->getMessage()}");
                Log::error("Metrics collection failed for server {$server->id}", [
                    'server' => $server->name,
                    'error' => $e->getMessage(),
                ]);
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("✅ Collection complete: {$successCount} success, {$failCount} failed");

        // Cleanup old metrics (keep last 30 days)
        $this->cleanupOldMetrics();

        return self::SUCCESS;
    }

    /**
     * Collect metrics from a server
     */
    private function collectMetrics(FailoverServer $server): ?array
    {
        try {
            $agent = new ServerAgentClient($server->name);
            
            // Fetch system status
            $response = $agent->systemStatus();
            
            if (!($response['success'] ?? false)) {
                return $this->createOfflineMetric($server);
            }

            $data = $response['data'] ?? [];

            return [
                'server_id' => $server->id,
                'cpu_load_1min' => $data['cpu_load']['1min'] ?? null,
                'cpu_load_5min' => $data['cpu_load']['5min'] ?? null,
                'cpu_load_15min' => $data['cpu_load']['15min'] ?? null,
                'cpu_usage_percent' => $data['cpu_usage'] ?? null,
                'memory_total' => $data['memory']['total'] ?? null,
                'memory_used' => $data['memory']['used'] ?? null,
                'memory_free' => $data['memory']['free'] ?? null,
                'memory_percent' => $data['memory']['percent'] ?? null,
                'disk_total' => $data['disk']['total'] ?? null,
                'disk_used' => $data['disk']['used'] ?? null,
                'disk_free' => $data['disk']['free'] ?? null,
                'disk_percent' => $data['disk']['percent'] ?? null,
                'network_rx_bytes' => $data['network']['rx_bytes'] ?? null,
                'network_tx_bytes' => $data['network']['tx_bytes'] ?? null,
                'process_count' => $data['process_count'] ?? null,
                'uptime_seconds' => $data['uptime'] ?? null,
                'is_online' => true,
                'recorded_at' => now(),
            ];
        } catch (\Throwable $e) {
            Log::warning("Failed to collect metrics for {$server->name}: {$e->getMessage()}");
            return $this->createOfflineMetric($server);
        }
    }

    /**
     * Create offline metric entry
     */
    private function createOfflineMetric(FailoverServer $server): array
    {
        return [
            'server_id' => $server->id,
            'is_online' => false,
            'recorded_at' => now(),
        ];
    }

    /**
     * Cleanup old metrics (keep last 30 days)
     */
    private function cleanupOldMetrics(): void
    {
        $deleted = ServerMetric::where('recorded_at', '<', now()->subDays(30))->delete();
        
        if ($deleted > 0) {
            $this->info("🗑️  Cleaned up {$deleted} old metric(s)");
        }
    }
}
