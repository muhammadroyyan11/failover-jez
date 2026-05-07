{{-- Failover Confirmation Modal --}}
<div class="modal fade" id="failoverModal" tabindex="-1" aria-labelledby="failoverModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="failoverModalLabel">
                    <i class="bi bi-lightning-charge me-2"></i>
                    <span id="failoverModalTitle">Konfirmasi Failover</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- Warning Banner --}}
                <div class="alert alert-warning d-flex gap-2 mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Perhatian!</strong> Proses ini akan memindahkan production ke
                        <strong id="failoverTargetLabel">server tujuan</strong>.
                        Pastikan semua checklist di bawah sudah terpenuhi sebelum melanjutkan.
                    </div>
                </div>

                {{-- Checklist --}}
                <div class="mb-4">
                    <p class="fw-semibold mb-3">
                        <i class="bi bi-list-check me-2"></i>Checklist Sebelum Failover
                    </p>

                    <div class="checklist-item" onclick="toggleCheck(this, 'check_maintenance')">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="checklist[]" value="maintenance"
                                   id="check_maintenance" class="form-check-input mt-0" style="width:1.2rem;height:1.2rem;">
                            <label for="check_maintenance" class="mb-0" style="cursor:pointer;">
                                <strong>a. JH sudah masuk maintenance mode</strong>
                                <div class="text-muted small">Aplikasi JH tidak menerima request baru dari user.</div>
                            </label>
                        </div>
                    </div>

                    <div class="checklist-item" onclick="toggleCheck(this, 'check_replica')">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="checklist[]" value="replica"
                                   id="check_replica" class="form-check-input mt-0" style="width:1.2rem;height:1.2rem;">
                            <label for="check_replica" class="mb-0" style="cursor:pointer;">
                                <strong>b. Replica delay = 0 (sudah sync)</strong>
                                <div class="text-muted small">Seconds_Behind_Source harus 0 sebelum promote.</div>
                            </label>
                        </div>
                    </div>

                    <div class="checklist-item" onclick="toggleCheck(this, 'check_storage')">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="checklist[]" value="storage"
                                   id="check_storage" class="form-check-input mt-0" style="width:1.2rem;height:1.2rem;">
                            <label for="check_storage" class="mb-0" style="cursor:pointer;">
                                <strong>c. Storage sudah disync</strong>
                                <div class="text-muted small">File uploads, storage/app sudah tersinkronisasi ke server tujuan.</div>
                            </label>
                        </div>
                    </div>

                    <div class="checklist-item" onclick="toggleCheck(this, 'check_upcloud_ready')">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="checklist[]" value="upcloud_ready"
                                   id="check_upcloud_ready" class="form-check-input mt-0" style="width:1.2rem;height:1.2rem;">
                            <label for="check_upcloud_ready" class="mb-0" style="cursor:pointer;">
                                <strong>d. Server tujuan siap menjadi primary</strong>
                                <div class="text-muted small">Server tujuan online, web server berjalan, tidak ada error kritis.</div>
                            </label>
                        </div>
                    </div>

                    <div class="checklist-item" onclick="toggleCheck(this, 'check_dns')">
                        <div class="d-flex align-items-center gap-3">
                            <input type="checkbox" name="checklist[]" value="dns"
                                   id="check_dns" class="form-check-input mt-0" style="width:1.2rem;height:1.2rem;">
                            <label for="check_dns" class="mb-0" style="cursor:pointer;">
                                <strong>e. DNS Cloudflare siap diarahkan</strong>
                                <div class="text-muted small">API Token Cloudflare valid dan record ID sudah dikonfigurasi.</div>
                            </label>
                        </div>
                    </div>
                </div>

                <hr>

                {{-- Password Confirmation --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-lock me-1"></i>Konfirmasi Password Anda
                    </label>
                    <input type="password" class="form-control" id="passwordConfirm"
                           placeholder="Masukkan password akun Anda" autocomplete="current-password">
                    <div class="form-text text-muted">
                        Diperlukan untuk verifikasi identitas sebelum eksekusi failover.
                    </div>
                </div>

                {{-- Error message --}}
                <div id="failoverError" class="alert alert-danger d-none">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="failoverErrorMsg"></span>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmFailover" disabled>
                    <i class="bi bi-lightning-charge me-2"></i>Confirm Failover
                </button>
            </div>

            {{-- Hidden input --}}
            <input type="hidden" id="failoverTargetInput" value="">
        </div>
    </div>
</div>

<script>
function toggleCheck(el, id) {
    const cb = document.getElementById(id);
    cb.checked = !cb.checked;
    el.classList.toggle('checked', cb.checked);
    validateChecklist();
}

function validateChecklist() {
    const checkboxes = document.querySelectorAll('#failoverModal input[type="checkbox"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    const password   = document.getElementById('passwordConfirm').value.trim();
    document.getElementById('btnConfirmFailover').disabled = !(allChecked && password.length > 0);
}

document.getElementById('passwordConfirm')?.addEventListener('input', validateChecklist);

document.getElementById('btnConfirmFailover')?.addEventListener('click', function () {
    const target   = document.getElementById('failoverTargetInput').value;
    const password = document.getElementById('passwordConfirm').value;
    const errorDiv = document.getElementById('failoverError');
    const errorMsg = document.getElementById('failoverErrorMsg');

    // Collect checklist values
    const checkboxes = document.querySelectorAll('#failoverModal input[type="checkbox"]:checked');
    const checklist  = Array.from(checkboxes).map(cb => cb.value);

    if (checklist.length < 5) {
        errorDiv.classList.remove('d-none');
        errorMsg.textContent = 'Semua checklist harus dicentang.';
        return;
    }

    // Disable button, show loading
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    errorDiv.classList.add('d-none');

    // Close confirm modal, open progress modal
    bootstrap.Modal.getInstance(document.getElementById('failoverModal')).hide();
    new bootstrap.Modal(document.getElementById('progressModal')).show();
    startFailoverProgress(target, password, checklist);
});
</script>
