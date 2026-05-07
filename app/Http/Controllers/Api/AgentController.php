<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReplicationStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

/**
 * Agent API Controller
 * Endpoint internal yang dipanggil oleh server lain saat failover.
 * Semua endpoint dilindungi AgentAuthentication middleware.
 */
class AgentController extends Controller
{
    public function __construct(
        private ReplicationStatusService $replication
    ) {}

    /**
     * GET /api/agent/health
     * Cek apakah server ini online dan aplikasi berjalan.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'server'     => config('failover.this_server'),
            'app'        => config('app.name'),
            'env'        => config('app.env'),
            'time'       => now()->toIso8601String(),
            'maintenance' => app()->isDownForMaintenance(),
        ]);
    }

    /**
     * GET /api/agent/system-status
     * CPU, RAM, disk usage, queue, scheduler.
     */
    public function systemStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'server'  => config('failover.this_server'),
            'data'    => [
                'cpu_load'     => $this->getCpuLoad(),
                'memory'       => $this->getMemoryUsage(),
                'disk'         => $this->getDiskUsage(),
                'queue_status' => $this->getQueueStatus(),
                'maintenance'  => app()->isDownForMaintenance(),
                'time'         => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/agent/replication-status
     * Status replikasi MySQL/MariaDB.
     */
    public function replicationStatus(): JsonResponse
    {
        $status = $this->replication->getLocalReplicationStatus();

        return response()->json(array_merge(
            ['server' => config('failover.this_server')],
            $status
        ));
    }

    /**
     * POST /api/agent/artisan-down
     * Set aplikasi ke maintenance mode.
     */
    public function artisanDown(): JsonResponse
    {
        try {
            Artisan::call('down', ['--secret' => config('failover.agent_token')]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode enabled.',
                'output'  => trim($output),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/agent/artisan-up
     * Matikan maintenance mode.
     */
    public function artisanUp(): JsonResponse
    {
        try {
            Artisan::call('up');
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Application is now live.',
                'output'  => trim($output),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/agent/promote-primary
     * Promote server ini menjadi primary MySQL.
     */
    public function promotePrimary(): JsonResponse
    {
        $result = $this->replication->promoteToPrimary();

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'error'   => $result['error'] ?? 'Promote failed.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Server promoted to primary.',
            'syntax'  => $result['syntax'],
        ]);
    }

    /**
     * POST /api/agent/clear-cache
     * Jalankan optimize:clear dan config:cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            $outputs = [];

            Artisan::call('optimize:clear');
            $outputs['optimize:clear'] = trim(Artisan::output());

            Artisan::call('config:cache');
            $outputs['config:cache'] = trim(Artisan::output());

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared and config cached.',
                'outputs' => $outputs,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/agent/restart-queue
     * Restart queue workers.
     */
    public function restartQueue(): JsonResponse
    {
        try {
            Artisan::call('queue:restart');
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Queue restart signal sent.',
                'output'  => trim($output),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers - System metrics
    // -------------------------------------------------------------------------

    private function getCpuLoad(): array
    {
        try {
            $load = sys_getloadavg();
            return [
                '1min'  => round($load[0], 2),
                '5min'  => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        } catch (\Throwable) {
            return ['1min' => null, '5min' => null, '15min' => null];
        }
    }

    private function getMemoryUsage(): array
    {
        try {
            $process = new Process(['free', '-b']);
            $process->run();

            if (! $process->isSuccessful()) {
                return ['total' => null, 'used' => null, 'free' => null, 'percent' => null];
            }

            $lines = explode("\n", trim($process->getOutput()));
            // Line 1: Mem: total used free shared buff/cache available
            $parts = preg_split('/\s+/', $lines[1]);

            $total     = (int) $parts[1];
            $used      = (int) $parts[2];
            $available = (int) ($parts[6] ?? $parts[3]);
            $percent   = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            return [
                'total'     => $total,
                'used'      => $used,
                'available' => $available,
                'percent'   => $percent,
                'total_mb'  => round($total / 1024 / 1024, 1),
                'used_mb'   => round($used / 1024 / 1024, 1),
            ];
        } catch (\Throwable) {
            return ['total' => null, 'used' => null, 'free' => null, 'percent' => null];
        }
    }

    private function getDiskUsage(): array
    {
        try {
            $path  = base_path();
            $total = disk_total_space($path);
            $free  = disk_free_space($path);
            $used  = $total - $free;

            return [
                'total'    => $total,
                'used'     => $used,
                'free'     => $free,
                'percent'  => $total > 0 ? round(($used / $total) * 100, 1) : 0,
                'total_gb' => round($total / 1024 / 1024 / 1024, 1),
                'used_gb'  => round($used / 1024 / 1024 / 1024, 1),
                'free_gb'  => round($free / 1024 / 1024 / 1024, 1),
            ];
        } catch (\Throwable) {
            return ['total' => null, 'used' => null, 'free' => null, 'percent' => null];
        }
    }

    private function getQueueStatus(): array
    {
        try {
            // Cek apakah ada queue worker yang berjalan
            $process = new Process(['pgrep', '-f', 'queue:work']);
            $process->run();

            return [
                'running'  => $process->isSuccessful(),
                'pid_list' => $process->isSuccessful()
                    ? array_filter(explode("\n", trim($process->getOutput())))
                    : [],
            ];
        } catch (\Throwable) {
            return ['running' => null, 'pid_list' => []];
        }
    }
}
