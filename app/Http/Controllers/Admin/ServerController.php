<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FailoverServer;
use App\Services\DatabaseReplicationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServerController extends Controller
{
    public function __construct(
        private DatabaseReplicationService $replicationService
    ) {}

    /**
     * Display a listing of servers
     */
    public function index()
    {
        $servers = FailoverServer::orderBy('priority', 'desc')->get();
        
        return view('admin.servers.index', compact('servers'));
    }

    /**
     * Show the form for creating a new server
     */
    public function create()
    {
        return view('admin.servers.create');
    }

    /**
     * Store a newly created server
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:failover_servers,name',
            'label' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'agent_url' => 'required|url',
            'domain' => 'nullable|string|max:255',
            'role' => 'required|in:primary,replica',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:0|max:999',
            'notes' => 'nullable|string',
            'ssh_host' => 'nullable|string',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string',
            'ssh_password' => 'nullable|string',
            'app_path' => 'nullable|string',
            'cyberpanel_url' => 'nullable|url',
            'cyberpanel_user' => 'nullable|string',
            'cyberpanel_pass' => 'nullable|string',
            'db_host' => 'nullable|string',
            'db_port' => 'nullable|integer|min:1|max:65535',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
            'db_database' => 'nullable|string',
            'db_role' => 'nullable|in:standalone,master,slave',
            'replication_user' => 'nullable|string',
            'replication_password' => 'nullable|string',
        ]);

        // If setting as primary, demote current primary to replica
        if ($validated['role'] === 'primary') {
            FailoverServer::where('role', 'primary')->update(['role' => 'replica']);
        }

        FailoverServer::create($validated);

        return redirect()
            ->route('admin.servers.index')
            ->with('success', 'Server berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified server
     */
    public function edit(FailoverServer $server)
    {
        return view('admin.servers.edit', compact('server'));
    }

    /**
     * Update the specified server
     */
    public function update(Request $request, FailoverServer $server)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:failover_servers,name,' . $server->id,
            'label' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'agent_url' => 'required|url',
            'domain' => 'nullable|string|max:255',
            'role' => 'required|in:primary,replica',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:0|max:999',
            'notes' => 'nullable|string',
            'ssh_host' => 'nullable|string',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string',
            'ssh_password' => 'nullable|string',
            'app_path' => 'nullable|string',
            'cyberpanel_url' => 'nullable|url',
            'cyberpanel_user' => 'nullable|string',
            'cyberpanel_pass' => 'nullable|string',
            'db_host' => 'nullable|string',
            'db_port' => 'nullable|integer|min:1|max:65535',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
            'db_database' => 'nullable|string',
            'db_role' => 'nullable|in:standalone,master,slave',
            'replication_user' => 'nullable|string',
            'replication_password' => 'nullable|string',
        ]);

        // If setting as primary, demote current primary to replica
        if ($validated['role'] === 'primary' && $server->role !== 'primary') {
            FailoverServer::where('role', 'primary')->update(['role' => 'replica']);
        }

        // Only update passwords if provided
        $data = collect($validated)->except(['db_password', 'replication_password', 'ssh_password', 'cyberpanel_pass'])->toArray();
        
        if ($request->filled('db_password')) {
            $data['db_password'] = $request->db_password;
        }
        if ($request->filled('replication_password')) {
            $data['replication_password'] = $request->replication_password;
        }
        if ($request->filled('ssh_password')) {
            $data['ssh_password'] = $request->ssh_password;
        }
        if ($request->filled('cyberpanel_pass')) {
            $data['cyberpanel_pass'] = $request->cyberpanel_pass;
        }

        $server->update($data);

        return redirect()
            ->route('admin.servers.index')
            ->with('success', 'Server berhasil diupdate!');
    }

    /**
     * Remove the specified server
     */
    public function destroy(FailoverServer $server)
    {
        // Prevent deleting primary server
        if ($server->isPrimary()) {
            return redirect()
                ->route('admin.servers.index')
                ->with('error', 'Tidak bisa menghapus server primary!');
        }

        $server->delete();

        return redirect()
            ->route('admin.servers.index')
            ->with('success', 'Server berhasil dihapus!');
    }

    /**
     * Toggle server active status
     */
    public function toggleActive(FailoverServer $server)
    {
        $server->update(['is_active' => !$server->is_active]);

        return redirect()
            ->route('admin.servers.index')
            ->with('success', 'Status server berhasil diubah!');
    }

    /**
     * Promote server to primary
     */
    public function promote(FailoverServer $server)
    {
        // Demote current primary
        FailoverServer::where('role', 'primary')->update(['role' => 'replica']);

        // Promote this server
        $server->update(['role' => 'primary']);

        return redirect()
            ->route('admin.servers.index')
            ->with('success', "Server {$server->label} berhasil dipromote menjadi primary!");
    }

    /**
     * Test database connection
     */
    public function testDatabase(FailoverServer $server): JsonResponse
    {
        $result = $this->replicationService->testConnection($server);
        return response()->json($result);
    }

    /**
     * Check replication status
     */
    public function checkReplication(FailoverServer $server): JsonResponse
    {
        if ($server->db_role === 'master') {
            $result = $this->replicationService->getMasterStatus($server);
        } else {
            $result = $this->replicationService->getSlaveStatus($server);
        }
        
        // Update cached status
        if ($result['success'] && $server->db_role === 'slave') {
            $this->replicationService->updateReplicationStatus($server);
        }
        
        return response()->json($result);
    }

    /**
     * Setup replication wizard
     */
    public function setupReplication(Request $request, FailoverServer $server): JsonResponse
    {
        $request->validate([
            'master_server_id' => 'required|exists:failover_servers,id',
        ]);
        
        $master = FailoverServer::findOrFail($request->master_server_id);
        
        // Step 1: Get master status
        $masterStatus = $this->replicationService->getMasterStatus($master);
        if (!$masterStatus['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get master status: ' . $masterStatus['message'],
            ], 500);
        }
        
        // Step 2: Configure slave
        $result = $this->replicationService->configureSlave($server, $master, $masterStatus);
        
        if ($result['success']) {
            $server->update(['db_role' => 'slave']);
        }
        
        return response()->json($result);
    }

    /**
     * Promote database to master
     */
    public function promoteDatabase(FailoverServer $server): JsonResponse
    {
        $result = $this->replicationService->promoteToMaster($server);
        
        if ($result['success']) {
            $server->update([
                'db_role' => 'master',
                'replication_io_running' => false,
                'replication_sql_running' => false,
                'seconds_behind_master' => null,
            ]);
        }
        
        return response()->json($result);
    }
}
