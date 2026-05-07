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
    </div>
</div>

{{-- ============================================================ --}}
{{-- ACTIVE SERVER BANNER --}}
{{-- ============================================================ --}}
<div class="card border-0 mb-4 shadow-sm" style="border-radius:12px;overflow:hidden;">
    <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between
        {{ $setting->active_server === 'jh' ? 'bg-primary' : 'bg-success' }} text-white">
        <div>
            <div class="small opacity-75 mb-1">PRODUCTION AKTIF SAAT INI</div>
            <div class="d-flex align-items-center gap-3">
                <span class="fs-4 fw-bold" id="activeServerLabel">
                    {{ $setting->active_server === 'jh' ? 'VPS JH (Primary)' : 'VPS UPCLOUD (Standby)' }}
                </span>
                <span class="badge bg-white text-dark" id="activeServerDomain">
                    {{ $setting->active_server === 'jh' ? $setting->primary_domain : $setting->standby_domain }}
                </span>
            </div>
        </div>
        <div class="text-end">
            <div class="small opacity-75">IP Aktif</div>
            <div class="fw-bold" id="activeServerIp">
                {{ $setting->active_server === 'jh' ? $setting->jh_ip : $setting->upcloud_ip }}
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SERVER STATUS CARDS --}}
{{-- ============================================================ --}}
<div class="row g-3 mb-4">

    {{-- JH Card --}}
    <div class="col-md-6">
        <div class="card server-card h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-server me-2"></i>VPS JH — Primary</span>
                <span class="badge bg-white text-primary" id="jhStatusBadge">
                    @if($jhStatus['online'] ?? false)
                        <span class="status-dot online"></span>Online
                    @else
                        <span class="status-dot offline"></span>Offline
                    @endif
                </span>
            </div>
            <div class="card-body" id="jhCardBody">
                @include('admin.failover._server_metrics', ['status' => $jhStatus, 'server' => 'jh'])
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="testConnection('jh')">
                    <i class="bi bi-wifi me-1"></i>Test Koneksi
                </button>
                <span class="text-muted small align-self-center" id="jhLastCheck">
                    {{ $setting->jh_ip }}
                </span>
            </div>
        </div>
    </div>

    {{-- UPCLOUD Card --}}
    <div class="col-md-6">
        <div class="card server-card h-100">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-server me-2"></i>VPS UPCLOUD — Standby</span>
                <span class="badge bg-white text-success" id="upcloudStatusBadge">
                    @if($upcloudStatus['online'] ?? false)
                        <span class="status-dot online"></span>Online
                    @else
                        <span class="status-dot offline"></span>Offline
                    @endif
                </span>
            </div>
            <div class="card-body" id="upcloudCardBody">
                @include('admin.failover._server_metrics', ['status' => $upcloudStatus, 'server' => 'upcloud'])
            </div>
            <div class="card-footer bg-transparent d-flex gap-2">
                <button class="btn btn-sm btn-outline-success" onclick="testConnection('upcloud')">
                    <i class="bi bi-wifi me-1"></i>Test Koneksi
                </button>
                <span class="text-muted small align-self-center" id="upcloudLastCheck">
                    {{ $setting->upcloud_ip }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- REPLICATION + DNS STATUS --}}
{{-- ============================================================ --}}
<div class="row g-3 mb-4">

    {{-- Replication Status --}}
    <div class="col-md-6">
        <div class="card server-card h-100">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-database-check me-2"></i>Status Replikasi Database
            </div>
            <div class="card-body" id="replicationCard">
                @php $replica = $upcloudStatus['replica'] ?? []; @endphp
                @include('admin.failover._replication_status', ['replica' => $replica])
            </div>
        </div>
    </div>

    {{-- DNS Status --}}
    <div class="col-md-6">
        <div class="card server-card h-100">
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
{{-- FAILOVER ACTION --}}
{{-- ============================================================ --}}
<div class="card server-card mb-4 border-danger">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-lightning-charge me-2"></i>Manual Failover
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-2 fw-semibold">Switch Production Server</p>
                <p class="text-muted small mb-0">
                    Proses ini akan: (1) set maintenance mode, (2) verifikasi replica sync,
                    (3) promote server tujuan, (4) update DNS Cloudflare.
                    Estimasi waktu: <strong>5–10 menit</strong>.
                </p>
            </div>
            <div class="col-md-4 text-end">
                @if($setting->active_server === 'jh')
                    <button class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#failoverModal"
                            data-target="upcloud" data-label="Switch ke UPCLOUD">
                        <i class="bi bi-arrow-right-circle me-2"></i>Switch ke UPCLOUD
                    </button>
                @else
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#failoverModal"
                            data-target="jh" data-label="Switch ke JH">
                        <i class="bi bi-arrow-left-circle me-2"></i>Switch ke JH
                    </button>
                @endif
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
                        <th></th>
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
                        <td>
                            <a href="{{ route('admin.failover.logs.detail', $log) }}"
                               class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
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

{{-- ============================================================ --}}
{{-- FAILOVER CONFIRMATION MODAL --}}
{{-- ============================================================ --}}
@include('admin.failover._modal_confirm', ['setting' => $setting])

{{-- ============================================================ --}}
{{-- PROGRESS MODAL --}}
{{-- ============================================================ --}}
@include('admin.failover._progress')

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ----------------------------------------------------------------
// Refresh status
// ----------------------------------------------------------------
function refreshStatus() {
    fetch('{{ route("admin.failover.status") }}', {
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        updateServerCard('jh', data.jh);
        updateServerCard('upcloud', data.upcloud);
        updateDnsCard(data.dns);
        updateReplicationCard(data.upcloud?.replica);
        updateActiveServerBanner(data.active_server, data);
        document.getElementById('lastRefresh').innerHTML =
            '<i class="bi bi-clock me-1"></i>' + new Date().toLocaleTimeString('id-ID');
    })
    .catch(err => console.error('Refresh failed:', err));
}

function updateActiveServerBanner(activeServer, data) {
    const label = document.getElementById('activeServerLabel');
    const ip    = document.getElementById('activeServerIp');
    if (label) label.textContent = activeServer === 'jh' ? 'VPS JH (Primary)' : 'VPS UPCLOUD (Standby)';
    if (ip) ip.textContent = activeServer === 'jh'
        ? (data.jh?.system?.ip || '{{ $setting->jh_ip }}')
        : (data.upcloud?.system?.ip || '{{ $setting->upcloud_ip }}');
}

function updateServerCard(server, status) {
    const badge = document.getElementById(server + 'StatusBadge');
    if (!badge) return;

    const online = status?.online ?? false;
    const dot    = online ? 'online' : 'offline';
    const label  = online ? 'Online' : 'Offline';
    badge.innerHTML = `<span class="status-dot ${dot}"></span>${label}`;

    // Update metrics
    const body = document.getElementById(server + 'CardBody');
    if (body && status?.system) {
        const s = status.system;
        const cpu  = s.cpu_load?.['1min'] ?? 'N/A';
        const ram  = s.memory?.percent ?? 'N/A';
        const disk = s.disk?.percent ?? 'N/A';
        const maint = status.maintenance ? '<span class="badge bg-warning text-dark">Maintenance</span>' : '<span class="badge bg-success">Live</span>';

        body.querySelector('[data-metric="cpu"]')  && (body.querySelector('[data-metric="cpu"]').textContent  = cpu);
        body.querySelector('[data-metric="ram"]')  && (body.querySelector('[data-metric="ram"]').textContent  = ram + '%');
        body.querySelector('[data-metric="disk"]') && (body.querySelector('[data-metric="disk"]').textContent = disk + '%');
        body.querySelector('[data-metric="maint"]') && (body.querySelector('[data-metric="maint"]').innerHTML = maint);
    }
}

function updateDnsCard(dns) {
    const card = document.getElementById('dnsCard');
    if (!card || !dns) return;
    const ip     = dns.ip ?? 'N/A';
    const server = dns.server ?? 'unknown';
    const color  = server === 'jh' ? 'primary' : (server === 'upcloud' ? 'success' : 'secondary');
    card.querySelector('[data-dns="ip"]')     && (card.querySelector('[data-dns="ip"]').textContent = ip);
    card.querySelector('[data-dns="server"]') && (card.querySelector('[data-dns="server"]').innerHTML =
        `<span class="badge bg-${color}">${server.toUpperCase()}</span>`);
}

function updateReplicationCard(replica) {
    if (!replica) return;
    const delay = replica.seconds_behind_source ?? 'N/A';
    const io    = replica.io_running ?? 'N/A';
    const sql   = replica.sql_running ?? 'N/A';

    document.querySelector('[data-rep="delay"]')  && (document.querySelector('[data-rep="delay"]').textContent = delay === 0 ? '0 (in sync)' : delay + 's');
    document.querySelector('[data-rep="io"]')     && (document.querySelector('[data-rep="io"]').textContent = io);
    document.querySelector('[data-rep="sql"]')    && (document.querySelector('[data-rep="sql"]').textContent = sql);
}

// ----------------------------------------------------------------
// Test connection
// ----------------------------------------------------------------
function testConnection(server) {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Testing...';

    fetch(`{{ url('admin/failover/test-connection') }}/${server}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const icon  = data.success ? 'bi-check-circle text-success' : 'bi-x-circle text-danger';
        const label = data.success ? 'Connected' : 'Failed';
        btn.innerHTML = `<i class="bi ${icon} me-1"></i>${label}`;
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-wifi me-1"></i>Test Koneksi';
        }, 3000);
    })
    .catch(() => {
        btn.innerHTML = '<i class="bi bi-x-circle text-danger me-1"></i>Error';
        btn.disabled = false;
    });
}

// ----------------------------------------------------------------
// Failover modal setup
// ----------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('failoverModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            const btn    = e.relatedTarget;
            const target = btn.getAttribute('data-target');
            const label  = btn.getAttribute('data-label');
            document.getElementById('failoverTargetInput').value = target;
            document.getElementById('failoverModalTitle').textContent = label;
            document.getElementById('failoverTargetLabel').textContent =
                target === 'upcloud' ? 'VPS UPCLOUD' : 'VPS JH';
        });
    }

    // Auto refresh setiap 30 detik
    setInterval(refreshStatus, 30000);
    document.getElementById('btnRefresh').addEventListener('click', refreshStatus);

    // Set initial time
    document.getElementById('lastRefresh').innerHTML =
        '<i class="bi bi-clock me-1"></i>' + new Date().toLocaleTimeString('id-ID');
});
</script>
@endpush
