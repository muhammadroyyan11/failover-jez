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
            'server_type' => 'required|in:web,database,both',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:0|max:999',
            'notes' => 'nullable|string',
            'ssh_host' => 'nullable|string',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string',
            'ssh_password' => 'nullable|string',
            'ssh_auth_type' => 'required|in:password,key',
            'ssh_key_file' => 'nullable|file|mimes:ppk,pem,key,txt|max:10240',
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

        // Handle SSH key file upload
        if ($request->hasFile('ssh_key_file') && $validated['ssh_auth_type'] === 'key') {
            $keyPath = $this->handleSshKeyUpload($request->file('ssh_key_file'), $validated['name']);
            $validated['ssh_key_file'] = $keyPath;
        }

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
            'server_type' => 'required|in:web,database,both',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:0|max:999',
            'notes' => 'nullable|string',
            'ssh_host' => 'nullable|string',
            'ssh_port' => 'nullable|integer|min:1|max:65535',
            'ssh_user' => 'nullable|string',
            'ssh_password' => 'nullable|string',
            'ssh_auth_type' => 'required|in:password,key',
            'ssh_key_file' => 'nullable|file|mimes:ppk,pem,key,txt|max:10240',
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

        // Handle SSH key file upload
        if ($request->hasFile('ssh_key_file') && $validated['ssh_auth_type'] === 'key') {
            $keyPath = $this->handleSshKeyUpload($request->file('ssh_key_file'), $validated['name']);
            $validated['ssh_key_file'] = $keyPath;
        }

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

    /**
     * Show server detail with metrics
     */
    public function show(FailoverServer $server)
    {
        return view('admin.servers.show', compact('server'));
    }

    /**
     * Get server metrics for charts (AJAX)
     */
    public function getMetrics(FailoverServer $server, Request $request): JsonResponse
    {
        $period = $request->get('period', '24h');
        $metric = $request->get('metric', 'cpu'); // cpu, memory, disk, network
        
        $metrics = \App\Models\ServerMetric::forServer($server->id)
            ->withinPeriod($period)
            ->orderBy('recorded_at', 'desc')
            ->get();
        
        // Format data for Chart.js
        $timestamps = [];
        $values = [];
        
        foreach ($metrics->reverse() as $m) {
            $timestamps[] = $m->recorded_at->format('H:i');
            
            $values[] = match($metric) {
                'cpu' => $m->cpu_load_1min,
                'memory' => $m->memory_percent,
                'disk' => $m->disk_percent,
                'network_in' => $m->network_rx_bytes,
                'network_out' => $m->network_tx_bytes,
                default => $m->cpu_load_1min,
            };
        }
        
        return response()->json([
            'success' => true,
            'timestamps' => $timestamps,
            'values' => $values,
            'metric' => $metric,
            'period' => $period,
        ]);
    }

    /**
     * Get latest metrics summary
     */
    public function getLatestMetrics(FailoverServer $server): JsonResponse
    {
        $latest = \App\Models\ServerMetric::forServer($server->id)
            ->orderBy('recorded_at', 'desc')
            ->first();
        
        if (!$latest) {
            return response()->json([
                'success' => false,
                'message' => 'No metrics available yet',
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'cpu_load' => $latest->cpu_load_1min,
                'memory_percent' => $latest->memory_percent,
                'disk_percent' => $latest->disk_percent,
                'is_online' => $latest->is_online,
                'recorded_at' => $latest->recorded_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Handle SSH key file upload and conversion
     */
    private function handleSshKeyUpload($file, string $serverName): string
    {
        $keyDir = storage_path('app/ssh-keys');
        
        // Create directory if not exists
        if (!file_exists($keyDir)) {
            mkdir($keyDir, 0700, true);
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $keyFileName = $serverName . '_key';
        $keyPath = $keyDir . '/' . $keyFileName;

        // Read uploaded file content
        $keyContent = file_get_contents($file->getRealPath());

        // Check if it's PPK format and convert
        if ($extension === 'ppk' || strpos($keyContent, 'PuTTY-User-Key-File') !== false) {
            try {
                // Load PPK key using phpseclib
                $key = \phpseclib3\Crypt\PublicKeyLoader::load($keyContent);
                
                // Convert to OpenSSH format
                $opensshKey = $key->toString('OpenSSH');
                
                // Save converted key
                file_put_contents($keyPath, $opensshKey);
                chmod($keyPath, 0600);
                
            } catch (\Exception $e) {
                throw new \Exception("Failed to convert PPK key: " . $e->getMessage());
            }
        } else {
            // Already in OpenSSH/PEM format, just save it
            file_put_contents($keyPath, $keyContent);
            chmod($keyPath, 0600);
        }

        return $keyPath;
    }
}
