@php
    $success = $dns['success'] ?? false;
    $ip      = $dns['ip'] ?? null;
    $server  = $dns['server'] ?? 'unknown';
    $proxied = $dns['proxied'] ?? null;
    $name    = $dns['name'] ?? config('failover.primary_domain');
    $modified= $dns['modified'] ?? null;
@endphp

@if(!$success)
    <div class="text-center py-3 text-muted">
        <i class="bi bi-cloud-slash fs-3 d-block mb-2 text-warning"></i>
        <div>Tidak bisa mengambil data DNS Cloudflare.</div>
        @if(isset($dns['error']))
            <small class="text-danger">{{ $dns['error'] }}</small>
        @endif
    </div>
@else
    <div class="mb-3">
        <div class="metric-label">Domain</div>
        <div class="fw-semibold">{{ $name }}</div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-2 rounded text-center" style="background:#f8f9fa;">
                <div class="metric-label">IP Saat Ini</div>
                <div class="metric-value" data-dns="ip">{{ $ip ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="p-2 rounded text-center" style="background:#f8f9fa;">
                <div class="metric-label">Mengarah ke</div>
                <div data-dns="server">
                    @if($server === 'jh')
                        <span class="badge bg-primary fs-6">JH</span>
                    @elseif($server === 'upcloud')
                        <span class="badge bg-success fs-6">UPCLOUD</span>
                    @else
                        <span class="badge bg-secondary fs-6">UNKNOWN</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 small text-muted">
        <span>
            <i class="bi bi-shield{{ $proxied ? '-check' : '-x' }} me-1"></i>
            Cloudflare Proxy: {{ $proxied ? 'Aktif' : 'Bypass' }}
        </span>
        @if($modified)
            <span>
                <i class="bi bi-clock me-1"></i>
                Update: {{ \Carbon\Carbon::parse($modified)->diffForHumans() }}
            </span>
        @endif
    </div>
@endif
