{{-- Failover Progress Modal --}}
<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header" id="progressModalHeader" style="background:#1a1d23;color:#fff;">
                <h5 class="modal-title">
                    <span class="spinner-border spinner-border-sm me-2" id="progressSpinner"></span>
                    <span id="progressTitle">Menjalankan Failover...</span>
                </h5>
            </div>

            <div class="modal-body">

                {{-- Overall progress bar --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Progress</small>
                        <small class="text-muted" id="progressPercent">0%</small>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                             id="progressBar" style="width:0%"></div>
                    </div>
                </div>

                {{-- Steps --}}
                <div id="progressSteps">
                    @php
                    $steps = [
                        ['id' => 'maintenance_jh',   'label' => 'Set JH ke Maintenance Mode',        'desc' => 'php artisan down'],
                        ['id' => 'check_replica',    'label' => 'Verifikasi Replica Sync',            'desc' => 'SHOW REPLICA STATUS'],
                        ['id' => 'promote_upcloud',  'label' => 'Promote UPCLOUD menjadi Primary',    'desc' => 'STOP REPLICA; RESET REPLICA ALL'],
                        ['id' => 'clear_cache',      'label' => 'Clear Cache UPCLOUD',                'desc' => 'optimize:clear + config:cache'],
                        ['id' => 'update_dns',       'label' => 'Update DNS Cloudflare',              'desc' => 'A Record → IP UPCLOUD'],
                        ['id' => 'update_setting',   'label' => 'Update Active Server Setting',       'desc' => 'Database update'],
                        ['id' => 'restart_queue',    'label' => 'Restart Queue Worker UPCLOUD',       'desc' => 'queue:restart'],
                    ];
                    @endphp

                    @foreach($steps as $step)
                    <div class="step-item" id="step_{{ $step['id'] }}">
                        <div class="step-icon pending" id="stepIcon_{{ $step['id'] }}">
                            <i class="bi bi-circle"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $step['label'] }}</div>
                            <div class="text-muted" style="font-size:.75rem;">
                                <code>{{ $step['desc'] }}</code>
                            </div>
                            <div class="text-muted small mt-1" id="stepDetail_{{ $step['id'] }}"></div>
                        </div>
                        <div id="stepBadge_{{ $step['id'] }}">
                            <span class="badge bg-secondary">Pending</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Result message --}}
                <div id="progressResult" class="mt-3 d-none">
                    <div id="progressResultContent"></div>
                </div>

            </div>

            <div class="modal-footer" id="progressFooter" style="display:none!important;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Refresh Dashboard
                </button>
                <a href="{{ route('admin.failover.logs') }}" class="btn btn-primary">
                    <i class="bi bi-journal-text me-1"></i>Lihat Log Detail
                </a>
            </div>

        </div>
    </div>
</div>

<script>
const STEP_LABELS = {
    maintenance_jh:      'Set JH ke Maintenance Mode',
    maintenance_upcloud: 'Set UPCLOUD ke Maintenance Mode',
    check_replica:       'Verifikasi Replica Sync',
    check_replica_jh:    'Verifikasi Replica JH',
    promote_upcloud:     'Promote UPCLOUD menjadi Primary',
    promote_jh:          'Promote JH menjadi Primary',
    clear_cache:         'Clear Cache UPCLOUD',
    clear_cache_jh:      'Clear Cache JH',
    update_dns:          'Update DNS Cloudflare',
    update_setting:      'Update Active Server Setting',
    restart_queue:       'Restart Queue Worker',
    bring_up_jh:         'Aktifkan JH Kembali',
};

function startFailoverProgress(target, password, checklist) {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    // Reset all steps to pending
    document.querySelectorAll('.step-icon').forEach(el => {
        el.className = 'step-icon pending';
        el.innerHTML = '<i class="bi bi-circle"></i>';
    });
    document.querySelectorAll('[id^="stepBadge_"]').forEach(el => {
        el.innerHTML = '<span class="badge bg-secondary">Pending</span>';
    });
    document.querySelectorAll('[id^="stepDetail_"]').forEach(el => {
        el.textContent = '';
    });

    setProgress(5, 'Mengirim request failover...');

    fetch('{{ route("admin.failover.switch") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            target_server:    target,
            password_confirm: password,
            checklist:        Object.fromEntries(checklist.map(v => [v, 'on'])),
        }),
    })
    .then(r => r.json())
    .then(data => {
        // Update steps from response
        if (data.steps && Array.isArray(data.steps)) {
            data.steps.forEach((step, i) => {
                updateStep(step.step, step.status, step.detail || '');
                setProgress(Math.round(((i + 1) / data.steps.length) * 90) + 5);
            });
        }

        if (data.success) {
            setProgress(100, 'Failover berhasil!');
            showResult(true, data.message || 'Failover berhasil diselesaikan.', data.log_id);
        } else {
            setProgress(100, 'Failover gagal');
            showResult(false, data.message || 'Terjadi kesalahan.', data.log_id);
        }
    })
    .catch(err => {
        setProgress(100, 'Error');
        showResult(false, 'Network error: ' + err.message, null);
    });
}

function updateStep(stepId, status, detail) {
    const iconEl   = document.getElementById('stepIcon_' + stepId);
    const badgeEl  = document.getElementById('stepBadge_' + stepId);
    const detailEl = document.getElementById('stepDetail_' + stepId);

    if (!iconEl) return;

    const icons = {
        success: '<i class="bi bi-check-lg"></i>',
        failed:  '<i class="bi bi-x-lg"></i>',
        warning: '<i class="bi bi-exclamation"></i>',
        running: '<span class="spinner-border spinner-border-sm"></span>',
        pending: '<i class="bi bi-circle"></i>',
    };

    const badges = {
        success: '<span class="badge bg-success">Sukses</span>',
        failed:  '<span class="badge bg-danger">Gagal</span>',
        warning: '<span class="badge bg-warning text-dark">Warning</span>',
        running: '<span class="badge bg-primary pulse">Running</span>',
        pending: '<span class="badge bg-secondary">Pending</span>',
    };

    iconEl.className = 'step-icon ' + status;
    iconEl.innerHTML = icons[status] || icons.pending;
    if (badgeEl) badgeEl.innerHTML = badges[status] || badges.pending;
    if (detailEl && detail) detailEl.textContent = detail;
}

function setProgress(percent, label) {
    const bar     = document.getElementById('progressBar');
    const pct     = document.getElementById('progressPercent');
    const title   = document.getElementById('progressTitle');
    if (bar) bar.style.width = percent + '%';
    if (pct) pct.textContent = percent + '%';
    if (label && title) title.textContent = label;
}

function showResult(success, message, logId) {
    const spinner = document.getElementById('progressSpinner');
    const header  = document.getElementById('progressModalHeader');
    const result  = document.getElementById('progressResult');
    const content = document.getElementById('progressResultContent');
    const footer  = document.getElementById('progressFooter');
    const bar     = document.getElementById('progressBar');

    if (spinner) spinner.style.display = 'none';

    if (success) {
        if (header) header.style.background = '#198754';
        if (bar) { bar.classList.remove('progress-bar-animated'); bar.classList.add('bg-success'); }
        if (content) content.innerHTML = `
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <strong>Failover Berhasil!</strong><br>
                ${message}
                ${logId ? `<br><small class="text-muted">Log ID: #${logId}</small>` : ''}
            </div>`;
    } else {
        if (header) header.style.background = '#dc3545';
        if (bar) { bar.classList.remove('progress-bar-animated'); bar.classList.add('bg-danger'); }
        if (content) content.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                <strong>Failover Gagal!</strong><br>
                ${message}
                ${logId ? `<br><small class="text-muted">Log ID: #${logId}</small>` : ''}
            </div>`;
    }

    if (result) result.classList.remove('d-none');
    if (footer) footer.style.removeProperty('display');
}
</script>
