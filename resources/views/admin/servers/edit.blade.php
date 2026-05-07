@extends('admin.failover.layout')

@section('title', 'Edit Server')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-0">Edit Server: {{ $server->label }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.failover.index') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.servers.index') }}">Servers</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.servers.update', $server) }}" method="POST">
                @csrf
                @method('PUT')
                
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
                                       value="{{ old('name', $server->name) }}"
                                       required>
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
                                       value="{{ old('label', $server->label) }}"
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
                                       value="{{ old('ip_address', $server->ip_address) }}"
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
                                       value="{{ old('domain', $server->domain) }}">
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
                                   value="{{ old('agent_url', $server->agent_url) }}"
                                   required>
                            @error('agent_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select @error('role') is-invalid @enderror" 
                                        id="role" 
                                        name="role" 
                                        required>
                                    <option value="replica" {{ old('role', $server->role) === 'replica' ? 'selected' : '' }}>Replica</option>
                                    <option value="primary" {{ old('role', $server->role) === 'primary' ? 'selected' : '' }}>Primary</option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <input type="number" 
                                       class="form-control @error('priority') is-invalid @enderror" 
                                       id="priority" 
                                       name="priority" 
                                       value="{{ old('priority', $server->priority) }}"
                                       min="0" 
                                       max="999"
                                       required>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="is_active" class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           value="1"
                                           {{ old('is_active', $server->is_active) ? 'checked' : '' }}>
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
                                      rows="2">{{ old('notes', $server->notes) }}</textarea>
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
                                       value="{{ old('ssh_host', $server->ssh_host) }}">
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
                                       value="{{ old('ssh_port', $server->ssh_port) }}"
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
                                       value="{{ old('ssh_user', $server->ssh_user) }}">
                                @error('ssh_user')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="ssh_password" class="form-label">SSH Password</label>
                                <input type="password" 
                                       class="form-control @error('ssh_password') is-invalid @enderror" 
                                       id="ssh_password" 
                                       name="ssh_password" 
                                       placeholder="Leave blank to use SSH key">
                                <small class="text-muted">For password authentication</small>
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
                                   value="{{ old('app_path', $server->app_path) }}">
                            @error('app_path')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="bi bi-database me-2"></i>Database Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Database terpisah dari web server?</strong> Isi config ini untuk akses database langsung (untuk replication monitoring & setup).
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Penting untuk Docker:</strong> Jangan gunakan <code>localhost</code> atau <code>127.0.0.1</code> karena akan connect ke container itu sendiri. 
                            Gunakan:
                            <ul class="mb-0 mt-2">
                                <li><strong>IP External:</strong> <code>103.245.39.246</code> (recommended)</li>
                                <li><strong>Host Machine:</strong> <code>host.docker.internal</code> (Mac/Windows) atau <code>172.17.0.1</code> (Linux)</li>
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" 
                                       class="form-control @error('db_host') is-invalid @enderror" 
                                       id="db_host" 
                                       name="db_host" 
                                       value="{{ old('db_host', $server->db_host) }}"
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
                                       value="{{ old('db_port', $server->db_port ?? 3306) }}"
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
                                       value="{{ old('db_database', $server->db_database) }}"
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
                                       value="{{ old('db_username', $server->db_username) }}"
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
                                       placeholder="Leave blank to keep current">
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
                                    <option value="standalone" {{ old('db_role', $server->db_role) === 'standalone' ? 'selected' : '' }}>Standalone (No Replication)</option>
                                    <option value="master" {{ old('db_role', $server->db_role) === 'master' ? 'selected' : '' }}>Master (Primary DB)</option>
                                    <option value="slave" {{ old('db_role', $server->db_role) === 'slave' ? 'selected' : '' }}>Slave (Replica DB)</option>
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
                                       value="{{ old('replication_user', $server->replication_user) }}"
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
                                       placeholder="Leave blank to keep current">
                                @error('replication_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="testDatabaseConnection()">
                                <i class="bi bi-plug"></i> Test DB Connection
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="checkReplicationStatus()">
                                <i class="bi bi-arrow-repeat"></i> Check Replication Status
                            </button>
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
                                   value="{{ old('cyberpanel_url', $server->cyberpanel_url) }}">
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
                                       value="{{ old('cyberpanel_user', $server->cyberpanel_user) }}">
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
                                       value="{{ old('cyberpanel_pass', $server->cyberpanel_pass) }}"
                                       placeholder="Leave blank to keep current">
                                @error('cyberpanel_pass')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Server
                    </button>
                    <a href="{{ route('admin.servers.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function testDatabaseConnection() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing...';
    
    fetch('{{ route("admin.servers.test-db", $server) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            alert('✅ Connection successful!\n\nMySQL Version: ' + data.version);
        } else {
            alert('❌ Connection failed!\n\n' + data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Error: ' + err.message);
    });
}

function checkReplicationStatus() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Checking...';
    
    fetch('{{ route("admin.servers.check-replication", $server) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.success) {
            let msg = '✅ Replication Status:\n\n';
            if (data.io_running !== undefined) {
                msg += 'IO Running: ' + (data.io_running ? '✅ Yes' : '❌ No') + '\n';
                msg += 'SQL Running: ' + (data.sql_running ? '✅ Yes' : '❌ No') + '\n';
                msg += 'Seconds Behind Master: ' + (data.seconds_behind ?? 'N/A') + '\n';
                msg += 'Master Host: ' + (data.master_host || 'N/A');
            } else {
                msg += 'Master Log File: ' + data.file + '\n';
                msg += 'Position: ' + data.position;
            }
            alert(msg);
        } else {
            alert('❌ Failed!\n\n' + data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('❌ Error: ' + err.message);
    });
}
</script>
@endsection
