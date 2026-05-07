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
     * GET /admin/failover/logs
     * Daftar semua failover logs.
     */
    public function logs(Request $request): View
    {
        $logs = FailoverLog::latest()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate(25);

        return view('admin.failover.logs', compact('logs'));
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
