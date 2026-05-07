@extends('admin.failover.layout')

@section('title', 'Execute Failover')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.failover.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
    </a>
</div>

<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0 text-white"><i class="bi bi-exclamation-triangle me-2"></i>Execute Failover</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Perhatian!</strong> Failover adalah operasi kritis yang akan mengubah konfigurasi production server. 
                    Pastikan Anda memahami konsekuensi dari setiap skenario sebelum melanjutkan.
                </div>

                <h6 class="fw-bold mb-3">Pilih Skenario Failover:</h6>

                {{-- SCENARIO 1: Web Server Down --}}
                <div class="card mb-3 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0 text-white">
                            <i class="bi bi-1-circle me-2"></i>Skenario 1: VPS A (Web Server) Down
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Kondisi:</strong> VPS A (jezpro.id) tidak dapat diakses, tetapi VPS B (database) masih berjalan normal.</p>
                        <p class="mb-2"><strong>Solusi:</strong> Switch DNS ke VPS C (UPCLOUD). VPS C akan menggunakan MySQL replica lokal.</p>
                        <p class="mb-3"><strong>Dampak:</strong></p>
                        <ul class="small mb-3">
                            <li>DNS jezpro.id akan diarahkan ke IP VPS C</li>
                            <li>VPS C menjadi primary web server</li>
                            <li>VPS B tetap sebagai database master</li>
                            <li>Tidak ada perubahan database</li>
                        </ul>
                        <button class="btn btn-primary" onclick="showConfirmModal('web_server_down')">
                            <i class="bi bi-arrow-repeat me-1"></i>Execute Skenario 1
                        </button>
                    </div>
                </div>

                {{-- SCENARIO 2: Database Down --}}
                <div class="card mb-3 border-warning">
                    <div class="card-header bg-warning text-white">
                        <h6 class="mb-0 text-white">
                            <i class="bi bi-2-circle me-2"></i>Skenario 2: VPS B (Database) Down
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Kondisi:</strong> VPS B (103.245.39.246) database server tidak dapat diakses, tetapi VPS A (web) masih berjalan.</p>
                        <p class="mb-2"><strong>Solusi:</strong> Promote VPS C MySQL menjadi master, reverse replication VPS B → VPS C, update VPS A untuk connect ke VPS C.</p>
                        <p class="mb-3"><strong>Dampak:</strong></p>
                        <ul class="small mb-3">
                            <li>VPS C MySQL dipromote menjadi master</li>
                            <li><strong class="text-success">AUTO:</strong> VPS B dikonfigurasi sebagai slave dari VPS C (reverse replication)</li>
                            <li>VPS A tetap melayani traffic</li>
                            <li><strong class="text-danger">MANUAL:</strong> Perlu update .env di VPS A (DB_HOST ke VPS C)</li>
                            <li>VPS A perlu restart aplikasi setelah .env diupdate</li>
                        </ul>
                        <button class="btn btn-warning" onclick="showConfirmModal('database_down')">
                            <i class="bi bi-arrow-repeat me-1"></i>Execute Skenario 2
                        </button>
                    </div>
                </div>

                {{-- SCENARIO 3: Complete Failover --}}
                <div class="card mb-3 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0 text-white">
                            <i class="bi bi-3-circle me-2"></i>Skenario 3: VPS A & B Down (Complete Failover)
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Kondisi:</strong> Baik VPS A (web) maupun VPS B (database) tidak dapat diakses.</p>
                        <p class="mb-2"><strong>Solusi:</strong> Full failover ke VPS C. VPS C menjadi standalone primary.</p>
                        <p class="mb-3"><strong>Dampak:</strong></p>
                        <ul class="small mb-3">
                            <li>VPS C MySQL dipromote menjadi master</li>
                            <li><strong class="text-success">AUTO:</strong> VPS B dikonfigurasi sebagai slave dari VPS C (jika online)</li>
                            <li>DNS jezpro.id diarahkan ke IP VPS C</li>
                            <li>VPS C menjadi primary untuk web dan database</li>
                            <li>Operasi standalone di VPS C</li>
                        </ul>
                        <button class="btn btn-danger" onclick="showConfirmModal('complete_failover')">
                            <i class="bi bi-arrow-repeat me-1"></i>Execute Skenario 3
                        </button>
                    </div>
                </div>

                {{-- ROLLBACK --}}
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0 text-white">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Rollback: Kembali ke VPS A
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Kondisi:</strong> VPS A dan VPS B sudah kembali normal, ingin rollback dari VPS C.</p>
                        <p class="mb-2"><strong>Solusi:</strong> Switch DNS kembali ke VPS A, reconfigure replication VPS B → VPS C.</p>
                        <p class="mb-3"><strong>Dampak:</strong></p>
                        <ul class="small mb-3">
                            <li>DNS jezpro.id diarahkan kembali ke IP VPS A</li>
                            <li>VPS A kembali menjadi primary web server</li>
                            <li>VPS B kembali menjadi database master</li>
                            <li><strong class="text-success">AUTO:</strong> Replication VPS B → VPS C dikonfigurasi otomatis</li>
                            <li><strong class="text-warning">MANUAL (jika auto gagal):</strong> Reconfigure replication manual</li>
                        </ul>
                        <button class="btn btn-success" onclick="showConfirmModal('rollback')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Execute Rollback
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CONFIRMATION MODAL --}}
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Konfirmasi Failover</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="failoverForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>PERINGATAN!</strong> Operasi ini akan mengubah konfigurasi production server.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Skenario yang dipilih:</label>
                        <div id="selectedScenario" class="alert alert-info"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Checklist Persiapan:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check1" name="checklist[]" value="1" required>
                            <label class="form-check-label" for="check1">
                                Saya sudah memverifikasi status server
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check2" name="checklist[]" value="2" required>
                            <label class="form-check-label" for="check2">
                                Saya sudah membuat backup jika diperlukan
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check3" name="checklist[]" value="3" required>
                            <label class="form-check-label" for="check3">
                                Saya memahami dampak dari operasi ini
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check4" name="checklist[]" value="4" required>
                            <label class="form-check-label" for="check4">
                                Saya siap melakukan langkah manual jika diperlukan
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirm" class="form-label fw-bold">Konfirmasi Password Anda:</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                        <small class="text-muted">Masukkan password akun Anda untuk konfirmasi</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="btnExecute">
                        <i class="bi bi-arrow-repeat me-1"></i>Execute Failover
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- PROGRESS MODAL --}}
<div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-gear-fill me-2 spinner-border spinner-border-sm"></i>Executing Failover...</h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Mohon tunggu, proses failover sedang berjalan...
                </div>
                <div id="progressSteps"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let currentScenario = '';

const scenarios = {
    'web_server_down': {
        title: 'Skenario 1: VPS A (Web Server) Down',
        url: '{{ route("admin.failover.execute-web-down") }}',
        color: 'primary'
    },
    'database_down': {
        title: 'Skenario 2: VPS B (Database) Down',
        url: '{{ route("admin.failover.execute-db-down") }}',
        color: 'warning'
    },
    'complete_failover': {
        title: 'Skenario 3: Complete Failover',
        url: '{{ route("admin.failover.execute-complete") }}',
        color: 'danger'
    },
    'rollback': {
        title: 'Rollback ke VPS A',
        url: '{{ route("admin.failover.execute-rollback") }}',
        color: 'success'
    }
};

function showConfirmModal(scenario) {
    currentScenario = scenario;
    const scenarioData = scenarios[scenario];
    
    document.getElementById('selectedScenario').innerHTML = `
        <i class="bi bi-arrow-repeat me-2"></i><strong>${scenarioData.title}</strong>
    `;
    
    document.getElementById('failoverForm').action = scenarioData.url;
    
    // Reset form
    document.getElementById('failoverForm').reset();
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

document.getElementById('failoverForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = this.action;
    
    // Hide confirm modal
    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
    
    // Show progress modal
    const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
    progressModal.show();
    
    document.getElementById('progressSteps').innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';
    
    // Execute failover
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showResult(data, 'success');
        } else {
            showResult(data, 'error');
        }
    })
    .catch(err => {
        showResult({
            success: false,
            message: 'Network error: ' + err.message,
            steps: []
        }, 'error');
    });
});

function showResult(data, type) {
    const stepsHtml = (data.steps || []).map(step => {
        const icon = step.status === 'success' ? 'check-circle text-success' : 
                     step.status === 'warning' ? 'exclamation-triangle text-warning' : 
                     'x-circle text-danger';
        return `
            <div class="d-flex align-items-start mb-2">
                <i class="bi bi-${icon} me-2 mt-1"></i>
                <div>
                    <strong>${step.step}</strong>
                    ${step.detail ? '<br><small class="text-muted">' + step.detail + '</small>' : ''}
                </div>
            </div>
        `;
    }).join('');
    
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check-circle' : 'x-circle';
    
    document.getElementById('progressSteps').innerHTML = `
        <div class="alert ${alertClass}">
            <i class="bi bi-${icon} me-2"></i>
            <strong>${data.message}</strong>
        </div>
        ${stepsHtml ? '<h6 class="mt-3">Execution Steps:</h6>' + stepsHtml : ''}
        <div class="mt-3 text-end">
            <a href="{{ route('admin.failover.index') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
            </a>
        </div>
    `;
}
</script>
@endpush
