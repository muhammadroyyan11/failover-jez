@php
    $sys   = $status['system'] ?? [];
    $cpu   = $sys['cpu_load']['1min'] ?? null;
    $ram   = $sys['memory']['percent'] ?? null;
    $disk  = $sys['disk']['percent'] ?? null;
    $ramMb = $sys['memory']['used_mb'] ?? null;
    $diskGb= $sys['disk']['used_gb'] ?? null;
    $queue = $sys['queue_status']['running'] ?? null;
    $maint = $status['maintenance'] ?? null;
    $online= $status['online'] ?? false;
@endphp

@if(!$online)
    <div class="text-center py-3 text-muted">
        <i class="bi bi-wifi-off fs-3 d-block mb-2 text-danger"></i>
        <div class="fw-semibold text-danger">Server tidak dapat dijangkau</div>
        @if(isset($status['error']))
            <small class="text-muted">{{ Str::limit($status['error'], 80) }}</small>
        @endif
    </div>
@else
    <div class="row g-2 mb-3">
        {{-- CPU --}}
        <div class="col-4">
            <div class="text-center p-2 rounded" style="background:#f8f9fa;">
                <div class="metric-label">CPU Load</div>
                <div class="metric-value {{ ($cpu ?? 0) > 2 ? 'text-danger' : 'text-dark' }}" data-metric="cpu">
                    {{ $cpu ?? 'N/A' }}
                </div>
            </div>
        </div>
        {{-- RAM --}}
        <div class="col-4">
            <div class="text-center p-2 rounded" style="background:#f8f9fa;">
                <div class="metric-label">RAM</div>
                <div class="metric-value {{ ($ram ?? 0) > 85 ? 'text-danger' : (($ram ?? 0) > 70 ? 'text-warning' : 'text-dark') }}"
                     data-metric="ram">
                    {{ $ram !== null ? $ram . '%' : 'N/A' }}
                </div>
            </div>
        </div>
        {{-- Disk --}}
        <div class="col-4">
            <div class="text-center p-2 rounded" style="background:#f8f9fa;">
                <div class="metric-label">Disk</div>
                <div class="metric-value {{ ($disk ?? 0) > 85 ? 'text-danger' : (($disk ?? 0) > 70 ? 'text-warning' : 'text-dark') }}"
                     data-metric="disk">
                    {{ $disk !== null ? $disk . '%' : 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="metric-label d-block">App Status</span>
            <span data-metric="maint">
                @if($maint)
                    <span class="badge bg-warning text-dark">Maintenance</span>
                @else
                    <span class="badge bg-success">Live</span>
                @endif
            </span>
        </div>
        <div>
            <span class="metric-label d-block">Queue Worker</span>
            @if($queue === null)
                <span class="badge bg-secondary">Unknown</span>
            @elseif($queue)
                <span class="badge bg-success">Running</span>
            @else
                <span class="badge bg-danger">Stopped</span>
            @endif
        </div>
        <div>
            <span class="metric-label d-block">RAM Used</span>
            <span class="small fw-semibold">{{ $ramMb ? $ramMb . ' MB' : 'N/A' }}</span>
        </div>
        <div>
            <span class="metric-label d-block">Disk Used</span>
            <span class="small fw-semibold">{{ $diskGb ? $diskGb . ' GB' : 'N/A' }}</span>
        </div>
    </div>
@endif
