@extends('admin.failover.layout')

@section('title', 'Failover Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-journal-text me-2 text-primary"></i>Failover Logs</h4>
        <small class="text-muted">Riwayat semua operasi failover</small>
    </div>
    <a href="{{ route('admin.failover.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
</div>

{{-- Filter --}}
<div class="card server-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="text-muted small mb-0">Filter Status:</label>
            @foreach(['', 'success', 'failed', 'running', 'pending'] as $s)
                <a href="{{ route('admin.failover.logs', $s ? ['status' => $s] : []) }}"
                   class="btn btn-sm {{ request('status') === $s || (!request('status') && !$s) ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $s ?: 'Semua' }}
                </a>
            @endforeach
        </form>
    </div>
</div>

<div class="card server-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover log-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Waktu</th>
                        <th>Action</th>
                        <th>From → To</th>
                        <th>Status</th>
                        <th>Durasi</th>
                        <th>Oleh</th>
                        <th>IP</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted small">{{ $log->id }}</td>
                        <td class="text-muted small">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td><code class="small">{{ $log->action }}</code></td>
                        <td>
                            @if($log->from_server)
                                <span class="badge bg-primary">{{ strtoupper($log->from_server) }}</span>
                                <i class="bi bi-arrow-right mx-1 text-muted"></i>
                            @endif
                            @if($log->to_server)
                                <span class="badge bg-success">{{ strtoupper($log->to_server) }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $log->status_badge }}">{{ $log->status }}</span>
                        </td>
                        <td class="text-muted small">
                            {{ $log->duration_seconds !== null ? $log->duration_seconds . 's' : '-' }}
                        </td>
                        <td class="small">{{ $log->triggered_by_name ?? '-' }}</td>
                        <td class="small text-muted">{{ $log->ip_address ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.failover.logs.detail', $log) }}"
                               class="btn btn-sm btn-outline-secondary py-0 px-2">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            Tidak ada log ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-transparent">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
