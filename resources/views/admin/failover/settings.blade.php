@extends('admin.failover.layout')

@section('title', 'Failover Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Failover Settings</h4>
        <small class="text-muted">Konfigurasi server, DNS, dan agent URL</small>
    </div>
    <a href="{{ route('admin.failover.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
</div>

<form method="POST" action="{{ route('admin.failover.update-settings') }}">
    @csrf
    @method('PUT')

    <div class="row g-3">

        {{-- Server IPs --}}
        <div class="col-md-6">
            <div class="card server-card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-server me-2"></i>Server Configuration
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">IP VPS JH (Primary)</label>
                        <input type="text" name="jh_ip" class="form-control @error('jh_ip') is-invalid @enderror"
                               value="{{ old('jh_ip', $setting->jh_ip) }}" placeholder="1.2.3.4">
                        @error('jh_ip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IP VPS UPCLOUD (Standby)</label>
                        <input type="text" name="upcloud_ip" class="form-control @error('upcloud_ip') is-invalid @enderror"
                               value="{{ old('upcloud_ip', $setting->upcloud_ip) }}" placeholder="5.6.7.8">
                        @error('upcloud_ip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Primary Domain</label>
                        <input type="text" name="primary_domain" class="form-control @error('primary_domain') is-invalid @enderror"
                               value="{{ old('primary_domain', $setting->primary_domain) }}" placeholder="jezpro.id">
                        @error('primary_domain') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Standby Domain</label>
                        <input type="text" name="standby_domain" class="form-control @error('standby_domain') is-invalid @enderror"
                               value="{{ old('standby_domain', $setting->standby_domain) }}" placeholder="jezpro.com">
                        @error('standby_domain') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Agent URLs --}}
        <div class="col-md-6">
            <div class="card server-card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-link-45deg me-2"></i>Agent URLs
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">JH Agent Base URL</label>
                        <input type="url" name="jh_agent_url" class="form-control @error('jh_agent_url') is-invalid @enderror"
                               value="{{ old('jh_agent_url', $setting->jh_agent_url) }}" placeholder="https://jezpro.id">
                        <div class="form-text">URL dasar untuk memanggil /api/agent/* di JH</div>
                        @error('jh_agent_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">UPCLOUD Agent Base URL</label>
                        <input type="url" name="upcloud_agent_url" class="form-control @error('upcloud_agent_url') is-invalid @enderror"
                               value="{{ old('upcloud_agent_url', $setting->upcloud_agent_url) }}" placeholder="https://jezpro.com">
                        <div class="form-text">URL dasar untuk memanggil /api/agent/* di UPCLOUD</div>
                        @error('upcloud_agent_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Agent Token</strong> dikonfigurasi via <code>.env</code> (FAILOVER_AGENT_TOKEN).
                        Tidak ditampilkan di UI untuk keamanan.
                    </div>
                </div>
            </div>
        </div>

        {{-- Cloudflare --}}
        <div class="col-12">
            <div class="card server-card">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-cloud me-2"></i>Cloudflare DNS Configuration
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Zone ID</label>
                            <input type="text" name="cloudflare_zone_id"
                                   class="form-control @error('cloudflare_zone_id') is-invalid @enderror"
                                   value="{{ old('cloudflare_zone_id', $setting->cloudflare_zone_id) }}"
                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            @error('cloudflare_zone_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Record ID (A Record jezpro.id)</label>
                            <input type="text" name="cloudflare_record_id"
                                   class="form-control @error('cloudflare_record_id') is-invalid @enderror"
                                   value="{{ old('cloudflare_record_id', $setting->cloudflare_record_id) }}"
                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                            @error('cloudflare_record_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="alert alert-warning py-2 px-3 small mt-3 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>API Token</strong> dikonfigurasi via <code>.env</code> (CLOUDFLARE_API_TOKEN).
                        Untuk mendapatkan Record ID, jalankan:
                        <code>php artisan failover:test-connection</code> atau gunakan Cloudflare API Explorer.
                    </div>
                </div>
            </div>
        </div>

        {{-- Current Status --}}
        <div class="col-12">
            <div class="card server-card border-primary">
                <div class="card-header bg-primary text-white fw-semibold">
                    <i class="bi bi-info-circle me-2"></i>Status Saat Ini
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 text-center">
                            <div class="metric-label">Active Server</div>
                            <span class="badge bg-{{ $setting->active_server === 'jh' ? 'primary' : 'success' }} fs-6">
                                {{ strtoupper($setting->active_server) }}
                            </span>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-label">JH IP</div>
                            <code>{{ $setting->jh_ip ?? 'Belum diset' }}</code>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-label">UPCLOUD IP</div>
                            <code>{{ $setting->upcloud_ip ?? 'Belum diset' }}</code>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-label">Last Updated</div>
                            <small class="text-muted">{{ $setting->updated_at->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-2"></i>Simpan Settings
        </button>
        <a href="{{ route('admin.failover.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection
