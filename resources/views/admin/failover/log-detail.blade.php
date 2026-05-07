@extends('admin.failover.layout')

@section('title', 'Log Detail #' . $log->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">
            <i class="bi bi-journal-text me-2 text-primary"></i>Log Detail #{{ $log->id }}
        </h4>
        <small class="text-muted">{{ $log->created_at->format('d F Y, H:i:s') }}</small>
    </div>
    <a href="{{ route('admin.failover.logs') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="row g-3">
    {{-- Info Card --}}
    <div class="col-md-4">
        <div class="card server-card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-2"></i>Informasi
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted small">Action</td>
                        <td><code>{{ $log->action }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Status</td>
                        <td><span class="badge bg-{{ $log->status_badge }} fs-6">{{ $log->status }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted small">From</td>
                        <td>
                            @if($log->from_server)
                                <span class="badge bg-primary">{{ strtoupper($log->from_server) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">To</td>
                        <td>
                            @if($log->to_server)
                                <span class="badge bg-success">{{ strtoupper($log->to_server) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Mulai</td>
                        <td class="small">{{ $log->started_at?->format('H:i:s') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Selesai</td>
                        <td class="small">{{ $log->finished_at?->format('H:i:s') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Durasi</td>
                        <td class="small">{{ $log->duration_seconds !== null ? $log->duration_seconds . ' detik' : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">Oleh</td>
                        <td class="small">{{ $log->triggered_by_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">IP</td>
                        <td class="small">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Steps & Message --}}
    <div class="col-md-8">
        {{-- Message --}}
        @if($log->message)
        <div class="alert alert-{{ $log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'info') }} mb-3">
            <i class="bi bi-{{ $log->status === 'success' ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
            {{ $log->message }}
        </div>
        @endif

        {{-- Step-by-step --}}
        @php $steps = collect($log->payload ?? [])->filter(fn($s) => isset($s['step'])); @endphp
        @if($steps->isNotEmpty())
        <div class="card server-card mb-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-list-check me-2"></i>Step-by-Step Execution
            </div>
            <div class="card-body">
                @foreach($steps as $step)
                @php
                    $status = $step['status'] ?? 'pending';
                    $icons  = ['success' => 'check-circle-fill text-success', 'failed' => 'x-circle-fill text-danger',
                               'warning' => 'exclamation-triangle-fill text-warning', 'running' => 'arrow-repeat text-primary',
                               'pending' => 'circle text-secondary'];
                    $icon   = $icons[$status] ?? $icons['pending'];
                @endphp
                <div class="step-item">
                    <div class="step-icon {{ $status }}">
                        @if($status === 'success') <i class="bi bi-check-lg"></i>
                        @elseif($status === 'failed') <i class="bi bi-x-lg"></i>
                        @elseif($status === 'warning') <i class="bi bi-exclamation"></i>
                        @else <i class="bi bi-circle"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">{{ $step['step'] ?? '-' }}</div>
                        @if(!empty($step['detail']))
                            <div class="text-muted" style="font-size:.8rem;">{{ $step['detail'] }}</div>
                        @endif
                        @if(!empty($step['time']))
                            <div class="text-muted" style="font-size:.72rem;">
                                <i class="bi bi-clock me-1"></i>{{ \Carbon\Carbon::parse($step['time'])->format('H:i:s') }}
                            </div>
                        @endif
                    </div>
                    <span class="badge bg-{{ match($status) { 'success' => 'success', 'failed' => 'danger', 'warning' => 'warning', default => 'secondary' } }}">
                        {{ $status }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Raw Payload --}}
        @if($log->payload)
        <div class="card server-card">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                <span><i class="bi bi-code-slash me-2"></i>Raw Payload</span>
                <button class="btn btn-sm btn-outline-secondary py-0"
                        onclick="document.getElementById('rawPayload').classList.toggle('d-none')">
                    Toggle
                </button>
            </div>
            <div class="card-body d-none" id="rawPayload">
                <pre class="mb-0 small" style="max-height:300px;overflow:auto;background:#f8f9fa;padding:1rem;border-radius:6px;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
