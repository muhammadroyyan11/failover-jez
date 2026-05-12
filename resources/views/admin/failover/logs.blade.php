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

<div class="card server-card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="logs-table" class="table table-hover log-table" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Waktu</th>
                        <th>Action</th>
                        <th>From → To</th>
                        <th>Status</th>
                        <th>Durasi</th>
                        <th>Oleh</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.failover.logs') }}',
        columns: [
            { data: 'id', name: 'id', width: '50px' },
            { data: 'started_at', name: 'started_at' },
            { data: 'action_label', name: 'action' },
            { 
                data: 'from_server', 
                name: 'from_server',
                render: function(data, type, row) {
                    let html = '';
                    if (data) {
                        html += '<span class="badge bg-primary">' + data.toUpperCase() + '</span>';
                        html += ' <i class="bi bi-arrow-right mx-1 text-muted"></i> ';
                    }
                    if (row.to_server) {
                        html += '<span class="badge bg-success">' + row.to_server.toUpperCase() + '</span>';
                    }
                    return html;
                }
            },
            { data: 'status_badge', name: 'status' },
            { data: 'duration_seconds', name: 'duration_seconds', width: '80px' },
            { data: 'triggered_by_name', name: 'triggered_by_name' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        language: {
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(difilter dari _MAX_ total data)',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya'
            }
        }
    });
});
</script>
@endpush
@endsection
