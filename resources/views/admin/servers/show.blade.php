@extends('admin.failover.layout')

@section('title', 'Server Monitoring - ' . $server->label)

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-{{ $server->server_type === 'database' ? 'database' : 'server' }} me-2"></i>
                    {{ $server->label }}
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.failover.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.servers.index') }}">Servers</a></li>
                        <li class="breadcrumb-item active">{{ $server->name }}</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" id="periodSelect" style="width: auto;">
                    <option value="1h">Last 1 Hour</option>
                    <option value="6h">Last 6 Hours</option>
                    <option value="12h">Last 12 Hours</option>
                    <option value="24h" selected>Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                </select>
                <button class="btn btn-sm btn-outline-secondary" onclick="refreshCharts()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                </button>
                <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-gear me-1"></i>Configure
                </a>
            </div>
        </div>
    </div>

    {{-- Server Info Card --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card server-card">
                <div class="card-body">
                    <div class="metric-label">Status</div>
                    <div class="metric-value" id="serverStatus">
                        <span class="badge bg-secondary">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card server-card">
                <div class="card-body">
                    <div class="metric-label">CPU Load</div>
                    <div class="metric-value" id="currentCpu">--</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card server-card">
                <div class="card-body">
                    <div class="metric-label">Memory Usage</div>
                    <div class="metric-value" id="currentMemory">--</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card server-card">
                <div class="card-body">
                    <div class="metric-label">Disk Usage</div>
                    <div class="metric-value" id="currentDisk">--</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card server-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-cpu me-2"></i>CPU Load</h6>
                </div>
                <div class="card-body">
                    <canvas id="cpuChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card server-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-memory me-2"></i>Memory Usage</h6>
                </div>
                <div class="card-body">
                    <canvas id="memoryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card server-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-hdd me-2"></i>Disk Usage</h6>
                </div>
                <div class="card-body">
                    <canvas id="diskChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card server-card">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Network Traffic</h6>
                </div>
                <div class="card-body">
                    <canvas id="networkChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Server Details --}}
    <div class="card server-card">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Server Information</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Server Name:</th>
                            <td><code>{{ $server->name }}</code></td>
                        </tr>
                        <tr>
                            <th>IP Address:</th>
                            <td>{{ $server->ip_address }}</td>
                        </tr>
                        <tr>
                            <th>Domain:</th>
                            <td>{{ $server->domain ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td>
                                <span class="badge bg-{{ $server->isPrimary() ? 'success' : 'info' }}">
                                    {{ strtoupper($server->role) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Server Type:</th>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ strtoupper($server->server_type ?? 'web') }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Agent URL:</th>
                            <td><a href="{{ $server->agent_url }}" target="_blank">{{ $server->agent_url }}</a></td>
                        </tr>
                        <tr>
                            <th>Priority:</th>
                            <td>{{ $server->priority }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $server->is_active ? 'success' : 'secondary' }}">
                                    {{ $server->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        @if($server->db_role)
                        <tr>
                            <th>Database Role:</th>
                            <td>
                                <span class="badge bg-{{ $server->db_role === 'master' ? 'primary' : 'info' }}">
                                    {{ strtoupper($server->db_role) }}
                                </span>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Created:</th>
                            <td>{{ $server->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const serverId = {{ $server->id }};
let cpuChart, memoryChart, diskChart, networkChart;

// Chart configurations
const chartConfig = {
    type: 'line',
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
};

// Initialize charts
function initCharts() {
    cpuChart = new Chart(document.getElementById('cpuChart'), {
        ...chartConfig,
        data: {
            labels: [],
            datasets: [{
                label: 'CPU Load',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true
            }]
        }
    });

    memoryChart = new Chart(document.getElementById('memoryChart'), {
        ...chartConfig,
        data: {
            labels: [],
            datasets: [{
                label: 'Memory Usage',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                fill: true
            }]
        }
    });

    diskChart = new Chart(document.getElementById('diskChart'), {
        ...chartConfig,
        data: {
            labels: [],
            datasets: [{
                label: 'Disk Usage',
                data: [],
                borderColor: 'rgb(255, 205, 86)',
                backgroundColor: 'rgba(255, 205, 86, 0.1)',
                tension: 0.4,
                fill: true
            }]
        }
    });

    networkChart = new Chart(document.getElementById('networkChart'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Network In',
                    data: [],
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Network Out',
                    data: [],
                    borderColor: 'rgb(153, 102, 255)',
                    backgroundColor: 'rgba(153, 102, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return (value / 1024 / 1024).toFixed(2) + ' MB';
                        }
                    }
                }
            }
        }
    });
}

// Fetch and update charts
function refreshCharts() {
    const period = document.getElementById('periodSelect').value;
    
    // Fetch CPU metrics
    fetch(`/admin/servers/${serverId}/metrics?metric=cpu&period=${period}`)
        .then(r => r.json())
        .then(data => {
            cpuChart.data.labels = data.timestamps;
            cpuChart.data.datasets[0].data = data.values;
            cpuChart.update();
        });
    
    // Fetch Memory metrics
    fetch(`/admin/servers/${serverId}/metrics?metric=memory&period=${period}`)
        .then(r => r.json())
        .then(data => {
            memoryChart.data.labels = data.timestamps;
            memoryChart.data.datasets[0].data = data.values;
            memoryChart.update();
        });
    
    // Fetch Disk metrics
    fetch(`/admin/servers/${serverId}/metrics?metric=disk&period=${period}`)
        .then(r => r.json())
        .then(data => {
            diskChart.data.labels = data.timestamps;
            diskChart.data.datasets[0].data = data.values;
            diskChart.update();
        });
    
    // Fetch Network metrics
    fetch(`/admin/servers/${serverId}/metrics?metric=network_in&period=${period}`)
        .then(r => r.json())
        .then(dataIn => {
            return fetch(`/admin/servers/${serverId}/metrics?metric=network_out&period=${period}`)
                .then(r => r.json())
                .then(dataOut => {
                    networkChart.data.labels = dataIn.timestamps;
                    networkChart.data.datasets[0].data = dataIn.values;
                    networkChart.data.datasets[1].data = dataOut.values;
                    networkChart.update();
                });
        });
    
    // Fetch latest metrics for current values
    fetch(`/admin/servers/${serverId}/metrics/latest`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('currentCpu').textContent = data.data.cpu_load ?? '--';
                document.getElementById('currentMemory').textContent = (data.data.memory_percent ?? '--') + '%';
                document.getElementById('currentDisk').textContent = (data.data.disk_percent ?? '--') + '%';
                
                const statusEl = document.getElementById('serverStatus');
                if (data.data.is_online) {
                    statusEl.innerHTML = '<span class="badge bg-success"><span class="status-dot online"></span>Online</span>';
                } else {
                    statusEl.innerHTML = '<span class="badge bg-danger"><span class="status-dot offline"></span>Offline</span>';
                }
            }
        });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    refreshCharts();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshCharts, 30000);
    
    // Refresh on period change
    document.getElementById('periodSelect').addEventListener('change', refreshCharts);
});
</script>
@endpush
