@extends('admin.failover.layout')

@section('title', 'Failover Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Failover Dashboard</h4>
        <small class="text-muted">Monitor dan kelola failover production server</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="btnRefresh">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
        <span class="badge bg-light text-dark border" id="lastRefresh">
            <i class="bi bi-clock me-1"></i>--:--:--
        </span>
        <div class="form-check form-switch ms-2">
            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
            <label class="form-check-label small" for="autoRefresh">Auto-refresh (30s)</label>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- ACTIVE SERVER BANNER --}}
{{-- ============================================================ --}}
@php
    $primaryServer = $servers->where('role', 'primary')->first();
@endphp

@if($primaryServer)
<div class="card border-0 mb-4 shadow-sm" style="border-radius:12px;overflow:hidden;">
    <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between bg-success text-white">
        <div>
            <div class="small opacity-75 mb-1">PRIMARY SERVER AKTIF</div>
            <div class="d-flex align-items-center gap-3">
                <span class="fs-4 fw-bold">{{ $primaryServer->label }}</span>
                @if($primaryServer->domain)
                    <span class="badge bg-white text-dark">{{ $primaryServer->domain }}</span>
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="small opacity-75">IP Address</div>
            <div class="fw-bold">{{ $primaryServer->ip_address }}</div>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- SERVER STATUS CARDS (FROM DATABASE) --}}
{{-- ============================================================ --}}
<div class="row g-3 mb-4">
    @forelse($servers as $server)
    <div class="col-md-6 col-lg-4">
        <div class="card server-card h-100" data-server-id="{{ $server->id }}">
            <div class="card-header {{ $server->isPrimary() ? 'bg-success' : 'bg-primary' }} text-white d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-{{ $server->server_type === 'database' ? 'database' : 'server' }} me-2"></i>{{ $server->label }}
                    @if($server->isPrimary())
                        <span class="badge bg-white text-success ms-2">PRIMARY</span>
                    @endif
                </span>
                <span class="badge bg-white {{ $server->isPrimary() ? 'text-success' : 'text-primary' }}" data-status-badge="{{ $server->id }}">
                    <span class="status-dot unknown"></span>Checking...
                </span>
            </div>
            <div class="card-body" data-server-body="{{ $server->id }}">
                @if($server->server_type === 'database')
                    {{-- DATABASE SERVER CARD - Show Replication Only --}}
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Database Server</strong> - Monitoring via direct MySQL connection
                    </div>
                    
                    @if($server->db_role && $server->db_role !== 'standalone')
                    <div class="border rounded p-3 bg-light">
                        <div class="metric-label mb-2">
                            <i class="bi bi-database-check me-1"></i>Database Replication Status
                        </div>
                        <div class="small" data-replication="{{ $server->id }}">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Role:</span>
                                <span class="badge bg-{{ $server->db_role === 'master' ? 'primary' : 'info' }}">
                                    {{ strtoupper($server->db_role) }}
                                </span>
                            </div>
                            @if($server->db_role === 'master')
                            <div class="d-flex justify-content-between mb-1">
                                <span>Binlog File:</span>
                                <code class="small" data-master-file="{{ $server->id }}">--</code>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Position:</span>
                                <code class="small" data-master-pos="{{ $server->id }}">--</code>
                            </div>
                            @else
                            <div class="d-flex justify-content-between mb-1">
                                <span>Replication Lag:</span>
                                <span data-rep-lag="{{ $server->id }}">--</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>IO Thread:</span>
                                <span data-rep-io="{{ $server->id }}">--</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>SQL Thread:</span>
                                <span data-rep-sql="{{ $server->id }}">--</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Status:</span>
                                <span data-rep-status="{{ $server->id }}">
                                    <span class="badge bg-secondary">Unknown</span>
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-database fs-3 d-block mb-2"></i>
                        <small>No replication configured</small>
                    </div>
                    @endif
                    
                @else
                    {{-- WEB SERVER CARD - Show System Metrics --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="metric-label">CPU Load</div>
                            <div class="metric-value" data-metric-cpu="{{ $server->id }}">--</div>
                        </div>
                        <div class="col-6">
                            <div class="metric-label">Memory</div>
                            <div class="metric-value" data-metric-ram="{{ $server->id }}">--</div>
                        </div>
                        <div class="col-6">
                            <div class="metric-label">Disk Usage</div>
                            <div class="metric-value" data-metric-disk="{{ $server->id }}">--</div>
                        </div>
                        <div class="col-6">
                            <div class="metric-label">Status</div>
                            <div class="metric-value" data-metric-status="{{ $server->id }}">
                                <span class="badge bg-secondary">Unknown</span>
                            </div>
                        </div>
                    </div>

                    @if($server->db_role && $server->db_role !== 'standalone')
                    <div class="border-top pt-2">
                        <div class="metric-label"><i class="bi bi-database me-1"></i>Database Replication</div>
                        <div class="small" data-replication="{{ $server->id }}">
                            <div class="d-flex justify-content-between">
                                <span>Role:</span>
                                <span class="badge bg-{{ $server->db_role === 'master' ? 'primary' : 'info' }}">
                                    {{ strtoupper($server->db_role) }}
                                </span>
                            </div>
                            @if($server->db_role === 'slave')
                            <div class="d-flex justify-content-between mt-1">
                                <span>Lag:</span>
                                <span data-rep-lag="{{ $server->id }}">--</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span>Status:</span>
                                <span data-rep-status="{{ $server->id }}">
                                    <span class="badge bg-secondary">Unknown</span>
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                @endif
            </div>
            <div class="card-footer bg-transparent d-flex gap-2 justify-content-between">
                <div class="small text-muted">
                    <i class="bi bi-hdd-network me-1"></i>{{ $server->ip_address }}
                    @if($server->server_type === 'database')
                        <span class="badge bg-secondary ms-1">DB Only</span>
                    @endif
                </div>
                <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-xs btn-outline-secondary btn-sm">
                    <i class="bi bi-gear"></i> Config
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Belum ada server yang terdaftar. 
            <a href="{{ route('admin.servers.create') }}" class="alert-link">Tambah server sekarang</a>
        </div>
    </div>
    @endforelse
</div>

{{-- ============================================================ --}}
{{-- DNS STATUS --}}
{{-- ============================================================ --}}
<div class="row g-3 mb-4">
    <div class="col-md-12">
        <div class="card server-card">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-globe me-2"></i>Status DNS Cloudflare
            </div>
            <div class="card-body" id="dnsCard">
                @include('admin.failover._dns_status', ['dns' => $dnsStatus])
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- RECENT LOGS --}}
{{-- ============================================================ --}}
<div class="card server-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-journal-text me-2"></i>Recent Failover Logs</span>
        <a href="{{ route('admin.failover.logs') }}" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover log-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Action</th>
                        <th>From → To</th>
                        <th>Status</th>
                        <th>Durasi</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted small">{{ $log->created_at->format('d/m/y H:i:s') }}</td>
                        <td><code class="small">{{ $log->action }}</code></td>
                        <td>
                            <span class="badge bg-primary">{{ strtoupper($log->from_server ?? '-') }}</span>
                            <i class="bi bi-arrow-right mx-1 text-muted"></i>
                            <span class="badge bg-success">{{ strtoupper($log->to_server ?? '-') }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $log->status_badge }}">{{ $log->status }}</span>
                        </td>
                        <td class="text-muted small">
                            {{ $log->duration_seconds ? $log->duration_seconds . 's' : '-' }}
                        </td>
                        <td class="small">{{ $log->triggered_by_name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            Belum ada log failover.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let autoRefreshInterval = null;

// ----------------------------------------------------------------
// Refresh status untuk semua server
// ----------------------------------------------------------------
function refreshAllServers() {
    const servers = @json($servers->pluck('name', 'id'));
    
    Object.entries(servers).forEach(([id, name]) => {
        refreshServerStatus(id, name);
    });
    
    updateLastRefreshTime();
}

function refreshServerStatus(serverId, serverName) {
    // Update status badge to loading
    const badge = document.querySelector(`[data-status-badge="${serverId}"]`);
    if (badge) {
        badge.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking...';
    }
    
    // Check if this is a database-only server
    const serverCard = document.querySelector(`[data-server-id="${serverId}"]`);
    const isDatabaseServer = serverCard && serverCard.querySelector('.alert-info strong')?.textContent === 'Database Server';
    
    if (isDatabaseServer) {
        // Database server - check via direct MySQL
        refreshDatabaseServer(serverId);
    } else {
        // Web server - fetch via agent API
        fetch(`/admin/failover/server-status/${serverName}`, {
            headers: { 
                'X-CSRF-TOKEN': CSRF, 
                'Accept': 'application/json' 
            }
        })
        .then(r => r.json())
        .then(data => {
            updateServerCard(serverId, data);
        })
        .catch(err => {
            console.error(`Failed to fetch status for ${serverName}:`, err);
            updateServerCardOffline(serverId);
        });
    }
}

function refreshDatabaseServer(serverId) {
    // Check database replication status via direct MySQL
    fetch(`/admin/servers/${serverId}/check-replication`, {
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': CSRF, 
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        updateDatabaseServerCard(serverId, data);
    })
    .catch(err => {
        console.error(`Failed to check database replication for server ${serverId}:`, err);
        updateDatabaseServerOffline(serverId);
    });
}

function updateDatabaseServerCard(serverId, data) {
    const badge = document.querySelector(`[data-status-badge="${serverId}"]`);
    
    if (data.success) {
        // Database is reachable
        if (badge) {
            badge.innerHTML = '<span class="status-dot online"></span>Online';
        }
        
        // Update master status
        if (data.file && data.position) {
            const fileEl = document.querySelector(`[data-master-file="${serverId}"]`);
            const posEl = document.querySelector(`[data-master-pos="${serverId}"]`);
            if (fileEl) fileEl.textContent = data.file;
            if (posEl) posEl.textContent = data.position;
        }
        
        // Update slave status
        if (data.io_running !== undefined) {
            const lagEl = document.querySelector(`[data-rep-lag="${serverId}"]`);
            const ioEl = document.querySelector(`[data-rep-io="${serverId}"]`);
            const sqlEl = document.querySelector(`[data-rep-sql="${serverId}"]`);
            const statusEl = document.querySelector(`[data-rep-status="${serverId}"]`);
            
            if (lagEl) {
                const lag = data.seconds_behind ?? null;
                lagEl.textContent = lag === 0 ? '0s (in sync)' : (lag ? lag + 's' : 'N/A');
            }
            
            if (ioEl) {
                ioEl.innerHTML = data.io_running 
                    ? '<span class="badge bg-success">Running</span>' 
                    : '<span class="badge bg-danger">Stopped</span>';
            }
            
            if (sqlEl) {
                sqlEl.innerHTML = data.sql_running 
                    ? '<span class="badge bg-success">Running</span>' 
                    : '<span class="badge bg-danger">Stopped</span>';
            }
            
            if (statusEl) {
                const healthy = data.io_running && data.sql_running && data.seconds_behind !== null && data.seconds_behind < 10;
                const badgeClass = healthy ? 'bg-success' : 'bg-danger';
                const badgeText = healthy ? 'Healthy' : 'Error';
                statusEl.innerHTML = `<span class="badge ${badgeClass}">${badgeText}</span>`;
            }
        }
    } else {
        updateDatabaseServerOffline(serverId);
    }
}

function updateDatabaseServerOffline(serverId) {
    const badge = document.querySelector(`[data-status-badge="${serverId}"]`);
    if (badge) {
        badge.innerHTML = '<span class="status-dot offline"></span>Offline';
    }
    
    // Reset replication status
    const lagEl = document.querySelector(`[data-rep-lag="${serverId}"]`);
    const statusEl = document.querySelector(`[data-rep-status="${serverId}"]`);
    
    if (lagEl) lagEl.textContent = '--';
    if (statusEl) statusEl.innerHTML = '<span class="badge bg-danger">Offline</span>';
}

function updateServerCard(serverId, status) {
    const online = status?.online ?? false;
    const badge = document.querySelector(`[data-status-badge="${serverId}"]`);
    
    if (badge) {
        const dot = online ? 'online' : 'offline';
        const label = online ? 'Online' : 'Offline';
        badge.innerHTML = `<span class="status-dot ${dot}"></span>${label}`;
    }
    
    // Update metrics
    if (status?.system) {
        const s = status.system;
        const cpu = s.cpu_load?.['1min'] ?? 'N/A';
        const ram = s.memory?.percent ?? 'N/A';
        const disk = s.disk?.percent ?? 'N/A';
        
        const cpuEl = document.querySelector(`[data-metric-cpu="${serverId}"]`);
        const ramEl = document.querySelector(`[data-metric-ram="${serverId}"]`);
        const diskEl = document.querySelector(`[data-metric-disk="${serverId}"]`);
        const statusEl = document.querySelector(`[data-metric-status="${serverId}"]`);
        
        if (cpuEl) cpuEl.textContent = cpu;
        if (ramEl) ramEl.textContent = ram + '%';
        if (diskEl) diskEl.textContent = disk + '%';
        if (statusEl) {
            const maint = status.maintenance 
                ? '<span class="badge bg-warning text-dark">Maintenance</span>' 
                : '<span class="badge bg-success">Live</span>';
            statusEl.innerHTML = maint;
        }
    }
    
    // Update replication status if slave
    if (status?.replica) {
        const lag = status.replica.seconds_behind_source ?? null;
        const ioRunning = status.replica.io_running ?? false;
        const sqlRunning = status.replica.sql_running ?? false;
        
        const lagEl = document.querySelector(`[data-rep-lag="${serverId}"]`);
        const statusEl = document.querySelector(`[data-rep-status="${serverId}"]`);
        
        if (lagEl) {
            lagEl.textContent = lag === 0 ? '0s (in sync)' : (lag ? lag + 's' : 'N/A');
        }
        
        if (statusEl) {
            const healthy = ioRunning && sqlRunning && lag !== null && lag < 10;
            const badgeClass = healthy ? 'bg-success' : 'bg-danger';
            const badgeText = healthy ? 'Healthy' : 'Error';
            statusEl.innerHTML = `<span class="badge ${badgeClass}">${badgeText}</span>`;
        }
    }
}

function updateServerCardOffline(serverId) {
    const badge = document.querySelector(`[data-status-badge="${serverId}"]`);
    if (badge) {
        badge.innerHTML = '<span class="status-dot offline"></span>Offline';
    }
    
    // Reset metrics
    const cpuEl = document.querySelector(`[data-metric-cpu="${serverId}"]`);
    const ramEl = document.querySelector(`[data-metric-ram="${serverId}"]`);
    const diskEl = document.querySelector(`[data-metric-disk="${serverId}"]`);
    const statusEl = document.querySelector(`[data-metric-status="${serverId}"]`);
    
    if (cpuEl) cpuEl.textContent = '--';
    if (ramEl) ramEl.textContent = '--';
    if (diskEl) diskEl.textContent = '--';
    if (statusEl) statusEl.innerHTML = '<span class="badge bg-danger">Offline</span>';
}

function updateLastRefreshTime() {
    const timeEl = document.getElementById('lastRefresh');
    if (timeEl) {
        timeEl.innerHTML = '<i class="bi bi-clock me-1"></i>' + new Date().toLocaleTimeString('id-ID');
    }
}

// ----------------------------------------------------------------
// Auto-refresh toggle
// ----------------------------------------------------------------
function toggleAutoRefresh() {
    const checkbox = document.getElementById('autoRefresh');
    
    if (checkbox.checked) {
        // Start auto-refresh every 30 seconds
        autoRefreshInterval = setInterval(refreshAllServers, 30000);
        console.log('Auto-refresh enabled (30s)');
    } else {
        // Stop auto-refresh
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
        console.log('Auto-refresh disabled');
    }
}

// ----------------------------------------------------------------
// Initialize
// ----------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    // Manual refresh button
    document.getElementById('btnRefresh').addEventListener('click', refreshAllServers);
    
    // Auto-refresh toggle
    document.getElementById('autoRefresh').addEventListener('change', toggleAutoRefresh);
    
    // Initial refresh
    refreshAllServers();
    
    // Start auto-refresh
    toggleAutoRefresh();
    
    // Set initial time
    updateLastRefreshTime();
});
</script>
@endpush
