@extends('admin.failover.layout')

@section('title', 'Add New Server')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">Add New Server</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.failover.index') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.servers.index') }}">Servers</a></li>
                <li class="breadcrumb-item active">Add New</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.servers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Server Name (Identifier) *</label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}"
                                       placeholder="e.g., aws, digitalocean"
                                       required>
                                <small class="text-muted">Lowercase, no spaces. Used in code.</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="label" class="form-label">Display Label *</label>
                                <input type="text" 
                                       class="form-control @error('label') is-invalid @enderror" 
                                       id="label" 
                                       name="label" 
                                       value="{{ old('label') }}"
                                       placeholder="e.g., VPS AWS Singapore"
                                       required>
                                @error('label')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ip_address" class="form-label">IP Address *</label>
                                <input type="text" 
                                       class="form-control @error('ip_address') is-invalid @enderror" 
                                       id="ip_address" 
                                       name="ip_address" 
                                       value="{{ old('ip_address') }}"
                                       placeholder="1.2.3.4"
                                       required>
                                @error('ip_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="domain" class="form-label">Domain</label>
                                <input type="text" 
                                       class="form-control @error('domain') is-invalid @enderror" 
                                       id="domain" 
                                       name="domain" 
                                       value="{{ old('domain') }}"
                                       placeholder="example.com">
                                @error('domain')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="agent_url" class="form-label">Agent API URL *</label>
                            <input type="url" 
                                   class="form-control @error('agent_url') is-invalid @enderror" 
                                   id="agent_url" 
                                   name="agent_url" 
                                   value="{{ old('agent_url') }}"
                                   placeholder="https://example.com"
                                   required>
                            <small class="text-muted">Full URL where agent API is installed</small>
                            @error('agent_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select @error('role') is-invalid @enderror" 
                                        id="role" 
                                        name="role" 
                                        required>
                                    <option value="replica" {{ old('role') === 'replica' ? 'selected' : '' }}>Replica</option>
                                    <option value="primary" {{ old('role') === 'primary' ? 'selected' : '' }}>Primary</option>
                                </select>
                                <small class="text-muted">Setting as primary will demote current primary</small>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="server_type" class="form-label">Server Type *</label>
                                <select class="form-select @error('server_type') is-invalid @enderror" 
                                        id="server_type" 
                                        name="server_type" 
                                        required>
                                    <option value="web" {{ old('server_type', 'web') === 'web' ? 'selected' : '' }}>Web Server</option>
                                    <option value="database" {{ old('server_type') === 'database' ? 'selected' : '' }}>Database Only</option>
                                    <option value="both" {{ old('server_type') === 'both' ? 'selected' : '' }}>Web + Database</option>
                                </select>
                                <small class="text-muted">Web = has Agent API, Database = MySQL only</small>
                                @error('server_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <input type="number" 
                                       class="form-control @error('priority') is-invalid @enderror" 
                                       id="priority" 
                                       name="priority" 
                                       value="{{ old('priority', 50) }}"
                                       min="0" 
                                       max="999"
                                       required>
                                <small class="text-muted">Higher = preferred for failover</small>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="is_active" class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           value="1"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="2"
                                      placeholder="Optional notes about this server">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">SSH Configuration (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="ssh_host" class="form-label">SSH Host</label>
                                <input type="text" 
                                       class="form-control @error('ssh_host') is-invalid @enderror" 
                                       id="ssh_host" 
                                       name="ssh_host" 
                                       value="{{ old('ssh_host') }}"
                                       placeholder="1.2.3.4">
                                @error('ssh_host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2 mb-3">
                                <label for="ssh_port" class="form-label">SSH Port</label>
                                <input type="number" 
                                       class="form-control @error('ssh_port') is-invalid @enderror" 
                                       id="ssh_port" 
                                       name="ssh_port" 
                                       value="{{ old('ssh_port', 22) }}"
                                       min="1" 
                                       max="65535">
                                @error('ssh_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="ssh_user" class="form-label">SSH User</label>
                                <input type="text" 
                                       class="form-control @error('ssh_user') is-invalid @enderror" 
                                       id="ssh_user" 
                                       name="ssh_user" 
                                       value="{{ old('ssh_user', 'root') }}"
                                       placeholder="root">
                                @error('ssh_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="ssh_auth_type" class="form-label">Authentication Type</label>
                                <select class="form-select @error('ssh_auth_type') is-invalid @enderror" 
                                        id="ssh_auth_type" 
                                        name="ssh_auth_type" 
                                        onchange="toggleSshAuth()">
                                    <option value="password" {{ old('ssh_auth_type') === 'password' ? 'selected' : '' }}>Password</option>
                                    <option value="key" {{ old('ssh_auth_type') === 'key' ? 'selected' : '' }}>SSH Key</option>
                                </select>
                                @error('ssh_auth_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row" id="ssh_password_field">
                            <div class="col-md-12 mb-3">
                                <label for="ssh_password" class="form-label">SSH Password</label>
                                <input type="password" 
                                       class="form-control @error('ssh_password') is-invalid @enderror" 
                                       id="ssh_password" 
                                       name="ssh_password" 
                                       placeholder="Enter SSH password">
                                @error('ssh_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row" id="ssh_key_field" style="display: none;">
                            <div class="col-md-8 mb-3">
                                <label for="ssh_key_file" class="form-label">SSH Private Key File</label>
                                <input type="file" 
                                       class="form-control @error('ssh_key_file') is-invalid @enderror" 
                                       id="ssh_key_file" 
                                       name="ssh_key_file"
                                       accept=".ppk,.pem,.key,.txt">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Upload PPK, PEM, or OpenSSH private key. PPK files will be auto-converted.
                                </small>
                                @error('ssh_key_file')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="ssh_key_passphrase" class="form-label">Key Passphrase (Optional)</label>
                                <input type="password" 
                                       class="form-control @error('ssh_password') is-invalid @enderror" 
                                       id="ssh_key_passphrase" 
                                       name="ssh_password" 
                                       placeholder="If key is encrypted">
                                <small class="text-muted">Leave empty if no passphrase</small>
                                @error('ssh_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="app_path" class="form-label">Application Path</label>
                            <input type="text" 
                                   class="form-control @error('app_path') is-invalid @enderror" 
                                   id="app_path" 
                                   name="app_path" 
                                   value="{{ old('app_path') }}"
                                   placeholder="/home/user/public_html">
                            @error('app_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="bi bi-database me-2"></i>Database Configuration (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Database terpisah dari web server?</strong> Isi config ini untuk akses database langsung (untuk replication monitoring & setup).
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" 
                                       class="form-control @error('db_host') is-invalid @enderror" 
                                       id="db_host" 
                                       name="db_host" 
                                       value="{{ old('db_host') }}"
                                       placeholder="103.245.39.246 or localhost">
                                <small class="text-muted">IP atau hostname database server</small>
                                @error('db_host')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2 mb-3">
                                <label for="db_port" class="form-label">DB Port</label>
                                <input type="number" 
                                       class="form-control @error('db_port') is-invalid @enderror" 
                                       id="db_port" 
                                       name="db_port" 
                                       value="{{ old('db_port', 3306) }}"
                                       min="1" 
                                       max="65535">
                                @error('db_port')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="db_database" class="form-label">Database Name</label>
                                <input type="text" 
                                       class="form-control @error('db_database') is-invalid @enderror" 
                                       id="db_database" 
                                       name="db_database" 
                                       value="{{ old('db_database') }}"
                                       placeholder="jez_erp">
                                @error('db_database')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_username" class="form-label">DB Username</label>
                                <input type="text" 
                                       class="form-control @error('db_username') is-invalid @enderror" 
                                       id="db_username" 
                                       name="db_username" 
                                       value="{{ old('db_username') }}"
                                       placeholder="root">
                                @error('db_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="db_password" class="form-label">DB Password</label>
                                <input type="password" 
                                       class="form-control @error('db_password') is-invalid @enderror" 
                                       id="db_password" 
                                       name="db_password" 
                                       placeholder="••••••••">
                                <small class="text-muted">Password akan di-encrypt di database</small>
                                @error('db_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="db_role" class="form-label">Database Role</label>
                                <select class="form-select @error('db_role') is-invalid @enderror" 
                                        id="db_role" 
                                        name="db_role">
                                    <option value="standalone" {{ old('db_role') === 'standalone' ? 'selected' : '' }}>Standalone (No Replication)</option>
                                    <option value="master" {{ old('db_role') === 'master' ? 'selected' : '' }}>Master (Primary DB)</option>
                                    <option value="slave" {{ old('db_role') === 'slave' ? 'selected' : '' }}>Slave (Replica DB)</option>
                                </select>
                                @error('db_role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="replication_user" class="form-label">Replication User</label>
                                <input type="text" 
                                       class="form-control @error('replication_user') is-invalid @enderror" 
                                       id="replication_user" 
                                       name="replication_user" 
                                       value="{{ old('replication_user') }}"
                                       placeholder="repl_user">
                                <small class="text-muted">For replication setup</small>
                                @error('replication_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="replication_password" class="form-label">Replication Password</label>
                                <input type="password" 
                                       class="form-control @error('replication_password') is-invalid @enderror" 
                                       id="replication_password" 
                                       name="replication_password" 
                                       placeholder="••••••••">
                                @error('replication_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">CyberPanel Configuration (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="cyberpanel_url" class="form-label">CyberPanel URL</label>
                            <input type="url" 
                                   class="form-control @error('cyberpanel_url') is-invalid @enderror" 
                                   id="cyberpanel_url" 
                                   name="cyberpanel_url" 
                                   value="{{ old('cyberpanel_url') }}"
                                   placeholder="https://1.2.3.4:8090">
                            @error('cyberpanel_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cyberpanel_user" class="form-label">CyberPanel Username</label>
                                <input type="text" 
                                       class="form-control @error('cyberpanel_user') is-invalid @enderror" 
                                       id="cyberpanel_user" 
                                       name="cyberpanel_user" 
                                       value="{{ old('cyberpanel_user', 'admin') }}"
                                       placeholder="admin">
                                @error('cyberpanel_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cyberpanel_pass" class="form-label">CyberPanel Password</label>
                                <input type="password" 
                                       class="form-control @error('cyberpanel_pass') is-invalid @enderror" 
                                       id="cyberpanel_pass" 
                                       name="cyberpanel_pass" 
                                       value="{{ old('cyberpanel_pass') }}"
                                       placeholder="••••••••">
                                @error('cyberpanel_pass')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Server
                    </button>
                    <a href="{{ route('admin.servers.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title">💡 Tips</h6>
                    <ul class="small mb-0">
                        <li><strong>Server Name:</strong> Unique identifier (lowercase, no spaces)</li>
                        <li><strong>Priority:</strong> Higher number = preferred for failover (0-999)</li>
                        <li><strong>Role:</strong> Only one server can be primary at a time</li>
                        <li><strong>Agent URL:</strong> Must have agent API installed</li>
                        <li><strong>SSH:</strong> Required for remote command execution</li>
                        <li><strong>CyberPanel:</strong> Required for web server management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleSshAuth() {
    const authType = document.getElementById('ssh_auth_type').value;
    const passwordField = document.getElementById('ssh_password_field');
    const keyField = document.getElementById('ssh_key_field');
    
    if (authType === 'password') {
        passwordField.style.display = 'block';
        keyField.style.display = 'none';
    } else {
        passwordField.style.display = 'none';
        keyField.style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSshAuth();
});
</script>
@endpush
