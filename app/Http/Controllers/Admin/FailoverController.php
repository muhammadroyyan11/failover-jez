<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FailoverLog;
use App\Models\FailoverSetting;
use App\Services\CloudflareDnsService;
use App\Services\FailoverService;
use App\Services\ServerAgentClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FailoverController extends Controller
{
    public function __construct(
        private FailoverService      $failoverService,
        private CloudflareDnsService $cloudflare,
    ) {}

    /**
     * GET /admin/failover
     * Dashboard utama failover panel.
     */
    public function index(): View
    {
        $setting = FailoverSetting::current();
        $logs    = FailoverLog::latest()->limit(20)->get();

        // Ambil semua active servers dari database
        $servers = \App\Models\FailoverServer::active()->byPriority()->get();
        
        // Get status untuk setiap server
        $serversStatus = [];
        foreach ($servers as $server) {
            $serversStatus[$server->name] = $this->getServerStatusFromDb($server);
        }

        // Legacy support: jh & upcloud
        $jhStatus = $serversStatus['jh'] ?? ['server' => 'jh', 'reachable' => false, 'online' => false];
        $upcloudStatus = $serversStatus['upcloud'] ?? ['server' => 'upcloud', 'reachable' => false, 'online' => false];
        
        $dnsStatus = $this->cloudflare->getCurrentDnsTarget();

        return view('admin.failover.index', compact(
            'setting',
            'logs',
            'servers',
            'serversStatus',
            'jhStatus',
            'upcloudStatus',
            'dnsStatus'
        ));
    }

    /**
     * GET /admin/failover/status (AJAX)
     * Refresh status semua server.
     */
    public function status(): JsonResponse
    {
        $jhStatus      = $this->getServerStatus('jh');
        $upcloudStatus = $this->getServerStatus('upcloud');
        $dnsStatus     = $this->cloudflare->getCurrentDnsTarget();
        $setting       = FailoverSetting::current();

        return response()->json([
            'success'       => true,
            'jh'            => $jhStatus,
            'upcloud'       => $upcloudStatus,
            'dns'           => $dnsStatus,
            'active_server' => $setting->active_server,
        ]);
    }

    /**
     * GET /admin/failover/server-status/{server} (AJAX)
     * Get status for specific server by name
     */
    public function serverStatus(string $serverName): JsonResponse
    {
        try {
            $status = $this->getServerStatus($serverName);
            return response()->json($status);
        } catch (\Throwable $e) {
            return response()->json([
                'server' => $serverName,
                'reachable' => false,
                'online' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /admin/failover/switch
     * Eksekusi failover manual.
     */
    public function switch(Request $request): JsonResponse
    {
        // Validasi input
        $request->validate([
            'target_server'    => 'required|in:jh,upcloud',
            'password_confirm' => 'required|string',
            'checklist'        => 'required|array|min:5',
            'checklist.*'      => 'accepted',
        ]);

        // Verifikasi password user
        if (! Hash::check($request->password_confirm, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak valid.',
            ], 422);
        }

        $target = $request->target_server;
        $user   = $request->user();
        $ip     = $request->ip();

        // Cek apakah sudah di server yang dituju
        $setting = FailoverSetting::current();
        if ($setting->active_server === $target) {
            return response()->json([
                'success' => false,
                'message' => "Server sudah aktif di {$target}.",
            ], 422);
        }

        // Jalankan failover
        if ($target === 'upcloud') {
            $result = $this->failoverService->failoverToUpcloud($user->id, $user->name, $ip);
        } else {
            $result = $this->failoverService->failoverToJH($user->id, $user->name, $ip);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * GET /admin/failover/switch
     * Halaman untuk execute failover dengan pilihan skenario
     */
    public function switchPage(): View
    {
        // Get all active servers for target selection
        $servers = \App\Models\FailoverServer::active()->get();
        
        return view('admin.failover.switch', compact('servers'));
    }

    /**
     * POST /admin/failover/execute/web-down
     * Execute Scenario 1: Web Server Down
     */
    public function executeWebDown(Request $request): JsonResponse
    {
        $request->validate([
            'password_confirm' => 'required|string',
            'checklist'        => 'required|array|min:4',
        ]);

        // Verify password
        if (!Hash::check($request->password_confirm, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak valid.',
            ], 422);
        }

        $user = $request->user();
        $result = $this->failoverService->failoverWebServerDown(
            $user->id,
            $user->name,
            $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * POST /admin/failover/execute/db-down
     * Execute Scenario 2: Database Down
     */
    public function executeDbDown(Request $request): JsonResponse
    {
        $request->validate([
            'password_confirm' => 'required|string',
            'checklist'        => 'required|array|min:4',
        ]);

        if (!Hash::check($request->password_confirm, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak valid.',
            ], 422);
        }

        $user = $request->user();
        $result = $this->failoverService->failoverDatabaseDown(
            $user->id,
            $user->name,
            $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * POST /admin/failover/execute/complete
     * Execute Scenario 3: Complete Failover
     */
    public function executeComplete(Request $request): JsonResponse
    {
        $request->validate([
            'password_confirm' => 'required|string',
            'checklist'        => 'required|array|min:4',
        ]);

        if (!Hash::check($request->password_confirm, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak valid.',
            ], 422);
        }

        $user = $request->user();
        $result = $this->failoverService->failoverComplete(
            $user->id,
            $user->name,
            $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * POST /admin/failover/execute/rollback
     * Execute Rollback to VPS A
     */
    public function executeRollback(Request $request): JsonResponse
    {
        $request->validate([
            'password_confirm' => 'required|string',
            'checklist'        => 'required|array|min:4',
        ]);

        if (!Hash::check($request->password_confirm, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak valid.',
            ], 422);
        }

        $user = $request->user();
        $result = $this->failoverService->failoverToJH(
            $user->id,
            $user->name,
            $request->ip()
        );

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * GET /admin/failover/logs
     * Daftar semua failover logs.
     */
    public function logs(Request $request)
    {
        if ($request->ajax()) {
            $logs = FailoverLog::with('triggeredByUser')->latest();
            
            return \Yajra\DataTables\Facades\DataTables::of($logs)
                ->addColumn('status_badge', function ($log) {
                    $badges = [
                        'success' => '<span class="badge bg-success">Success</span>',
                        'failed' => '<span class="badge bg-danger">Failed</span>',
                        'running' => '<span class="badge bg-warning">Running</span>',
                    ];
                    return $badges[$log->status] ?? '<span class="badge bg-secondary">' . ucfirst($log->status) . '</span>';
                })
                ->addColumn('action_label', function ($log) {
                    $labels = [
                        'complete_failover' => 'Complete Failover',
                        'rollback_to_vps_a' => 'Rollback to VPS A',
                        'web_server_failover' => 'Web Server Failover',
                        'database_failover' => 'Database Failover',
                    ];
                    return $labels[$log->action] ?? ucfirst(str_replace('_', ' ', $log->action));
                })
                ->addColumn('actions', function ($log) {
                    return '<a href="' . route('admin.failover.log-detail', $log) . '" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Detail
                    </a>';
                })
                ->editColumn('started_at', function ($log) {
                    return $log->started_at->format('d M Y H:i:s');
                })
                ->editColumn('duration_seconds', function ($log) {
                    return $log->duration_seconds ? $log->duration_seconds . 's' : '-';
                })
                ->filterColumn('status', function($query, $keyword) {
                    $query->where('status', 'like', "%{$keyword}%");
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('admin.failover.logs');
    }

    /**
     * GET /admin/failover/logs/{log}
     * Detail satu log.
     */
    public function logDetail(FailoverLog $log): View
    {
        return view('admin.failover.log-detail', compact('log'));
    }

    /**
     * GET /admin/failover/settings
     * Form settings.
     */
    public function settings(): View
    {
        $setting = FailoverSetting::current();
        return view('admin.failover.settings', compact('setting'));
    }

    /**
     * PUT /admin/failover/settings
     * Update settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'jh_ip'                  => 'required|ip',
            'upcloud_ip'             => 'required|ip',
            'primary_domain'         => 'required|string|max:255',
            'standby_domain'         => 'required|string|max:255',
            'jh_agent_url'           => 'required|url',
            'upcloud_agent_url'      => 'required|url',
            'agent_token'            => 'nullable|string|min:32',
            'hmac_secret'            => 'nullable|string|min:32',
            'cloudflare_zone_id'     => 'nullable|string|max:100',
            'cloudflare_record_id'   => 'nullable|string|max:100',
            'cloudflare_api_token'   => 'nullable|string',
        ]);

        $setting = FailoverSetting::current();
        
        $data = $request->only([
            'jh_ip', 'upcloud_ip', 'primary_domain', 'standby_domain',
            'jh_agent_url', 'upcloud_agent_url',
            'cloudflare_zone_id', 'cloudflare_record_id',
        ]);

        // Only update tokens if provided (not empty)
        if ($request->filled('agent_token')) {
            $data['agent_token'] = $request->agent_token;
        }
        if ($request->filled('hmac_secret')) {
            $data['hmac_secret'] = $request->hmac_secret;
        }
        if ($request->filled('cloudflare_api_token')) {
            $data['cloudflare_api_token'] = $request->cloudflare_api_token;
        }

        $setting->update($data);

        return redirect()->route('admin.failover.settings')
            ->with('success', 'Settings berhasil disimpan!');
    }

    /**
     * POST /admin/failover/test-connection/{server}
     * Test koneksi ke agent server.
     */
    public function testConnection(string $server): JsonResponse
    {
        if (! in_array($server, ['jh', 'upcloud'])) {
            return response()->json(['success' => false, 'message' => 'Invalid server.'], 422);
        }

        try {
            $agent  = new ServerAgentClient($server);
            $result = $agent->health();

            return response()->json([
                'success'   => $result['reachable'] ?? false,
                'server'    => $server,
                'data'      => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'server'  => $server,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getServerStatus(string $server): array
    {
        try {
            $agent = new ServerAgentClient($server);

            $health = $agent->health();
            $system = $agent->systemStatus();
            $replica = $agent->replicationStatus();

            return [
                'server'    => $server,
                'reachable' => $health['reachable'] ?? false,
                'online'    => ($health['reachable'] ?? false) && ($health['success'] ?? false),
                'maintenance' => $health['maintenance'] ?? null,
                'system'    => $system['data'] ?? [],
                'replica'   => $replica,
            ];
        } catch (\Throwable $e) {
            return [
                'server'    => $server,
                'reachable' => false,
                'online'    => false,
                'error'     => $e->getMessage(),
                'system'    => [],
                'replica'   => [],
            ];
        }
    }

    /**
     * Get server status from database model
     */
    private function getServerStatusFromDb(\App\Models\FailoverServer $server): array
    {
        try {
            $agent = new ServerAgentClient($server->name);

            $health = $agent->health();
            $system = $agent->systemStatus();
            $replica = $agent->replicationStatus();

            return [
                'id'        => $server->id,
                'name'      => $server->name,
                'label'     => $server->label,
                'role'      => $server->role,
                'server'    => $server->name,
                'reachable' => $health['reachable'] ?? false,
                'online'    => ($health['reachable'] ?? false) && ($health['success'] ?? false),
                'maintenance' => $health['maintenance'] ?? null,
                'system'    => $system['data'] ?? [],
                'replica'   => $replica,
                'db_role'   => $server->db_role,
                'replication_healthy' => $server->isReplicationHealthy(),
            ];
        } catch (\Throwable $e) {
            return [
                'id'        => $server->id,
                'name'      => $server->name,
                'label'     => $server->label,
                'role'      => $server->role,
                'server'    => $server->name,
                'reachable' => false,
                'online'    => false,
                'error'     => $e->getMessage(),
                'system'    => [],
                'replica'   => [],
                'db_role'   => $server->db_role,
                'replication_healthy' => false,
            ];
        }
    }
}
