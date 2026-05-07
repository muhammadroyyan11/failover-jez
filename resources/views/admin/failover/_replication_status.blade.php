@php
    $isReplica = $replica['is_slave'] ?? false;
    $delay     = $replica['seconds_behind_source'] ?? null;
    $io        = $replica['io_running'] ?? 'N/A';
    $sql       = $replica['sql_running'] ?? 'N/A';
    $inSync    = $replica['is_in_sync'] ?? false;
    $source    = $replica['source_host'] ?? null;
    $lastError = $replica['last_error'] ?? null;
@endphp

@if(!$isReplica)
    <div class="text-center py-3 text-muted">
        <i class="bi bi-database-slash fs-3 d-block mb-2"></i>
        <div>{{ $replica['message'] ?? 'Tidak ada replikasi aktif atau server tidak reachable.' }}</div>
    </div>
@else
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-2 rounded text-center" style="background:#f8f9fa;">
                <div class="metric-label">Seconds Behind Source</div>
                <div class="metric-value {{ $delay === 0 ? 'text-success' : 'text-danger' }}" data-rep="delay">
                    {{ $delay === 0 ? '0 (in sync)' : ($delay !== null ? $delay . 's' : 'N/A') }}
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 rounded text-center" style="background:#f8f9fa;">
                <div class="metric-label">IO Running</div>
                <div class="metric-value {{ $io === 'Yes' ? 'text-success' : 'text-danger' }}" data-rep="io">
                    {{ $io }}
                </div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-2 rounded text-center" style="background:#f8f9fa;">
                <div class="metric-label">SQL Running</div>
                <div class="metric-value {{ $sql === 'Yes' ? 'text-success' : 'text-danger' }}" data-rep="sql">
                    {{ $sql }}
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-2">
        @if($inSync)
            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Replica In Sync</span>
        @else
            <span class="badge bg-danger pulse"><i class="bi bi-exclamation-triangle me-1"></i>Replica Out of Sync</span>
        @endif
        @if($source)
            <span class="text-muted small">Source: {{ $source }}</span>
        @endif
    </div>

    @if($lastError)
        <div class="alert alert-danger py-2 px-3 mb-0 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Last Error:</strong> {{ $lastError }}
        </div>
    @endif
@endif
