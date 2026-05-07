@extends('admin.failover.layout')

@section('title', 'Manage Servers')

@section('content')
<style>
    .server-card-item {
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .server-card-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,.15) !important;
        border-color: rgba(13, 110, 253, 0.3);
    }
    .server-card-item.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .server-card-item.primary .badge {
        background: rgba(255,255,255,0.2) !important;
        color: white !important;
    }
    .server-card-item.replica {
        background: white;
    }
    .server-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    .server-card-item.replica .server-icon {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
    }
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .action-btn:hover {
        transform: scale(1.1);
    }
    .stats-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .add-server-card {
        border: 2px dashed #dee2e6;
        background: #f8f9fa;
        transition: all 0.3s;
        cursor: pointer;
        min-height: 200px;
    }
    .add-server-card:hover {
        border-color: #667eea;
        background: #f0f2ff;
        transform: translateY(-5px);
    }
    .priority-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255,255,255,0.9);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .server-card-item.primary .priority-badge {
        background: rgba(255,255,255,0.2);
        color: white;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">🖥️ Server Management</h1>
            <p class="text-muted mb-0">Manage your failover servers and replicas</p>
        </div>
        <a href="{{ route('admin.servers.create') }}" class="btn btn-primary btn-lg shadow">
            <i class="bi bi-plus-circle me-2"></i> Add New Server
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                                <i class="bi bi-hdd-network fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Servers</h6>
                            <h3 class="mb-0">{{ $servers->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-success rounded p-3">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active</h6>
                            <h3 class="mb-0">{{ $servers->where('is_active', true)->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded p-3">
                                <i class="bi bi-star fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Primary</h6>
                            <h3 class="mb-0">{{ $servers->where('role', 'primary')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 text-warning rounded p-3">
                                <i class="bi bi-layers fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Replicas</h6>
                            <h3 class="mb-0">{{ $servers->where('role', 'replica')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Cards -->
    <div class="row g-4">
        @forelse($servers as $server)
            <div class="col-lg-6">
                <div class="card server-card-item {{ $server->role }} border-0 shadow-sm position-relative">
                    <div class="priority-badge">
                        <i class="bi bi-star-fill me-1"></i>{{ $server->priority }}
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start mb-3">
                            <div class="server-icon me-3">
                                <i class="bi bi-{{ $server->isPrimary() ? 'star-fill' : 'hdd-rack' }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1 {{ $server->isPrimary() ? 'text-white' : '' }}">
                                    {{ $server->label }}
                                    @if($server->isPrimary())
                                        <i class="bi bi-patch-check-fill ms-1"></i>
                                    @endif
                                </h5>
                                <p class="mb-2 {{ $server->isPrimary() ? 'text-white-50' : 'text-muted' }}">
                                    <code class="{{ $server->isPrimary() ? 'text-white' : '' }}">{{ $server->name }}</code>
                                </p>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge {{ $server->isPrimary() ? 'bg-white bg-opacity-25 text-white' : 'bg-primary' }}">
                                        <i class="bi bi-{{ $server->isPrimary() ? 'star' : 'layers' }} me-1"></i>
                                        {{ ucfirst($server->role) }}
                                    </span>
                                    <span class="badge bg-{{ $server->is_active ? 'success' : 'secondary' }}">
                                        <i class="bi bi-{{ $server->is_active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                                        {{ $server->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <small class="{{ $server->isPrimary() ? 'text-white-50' : 'text-muted' }} d-block mb-1">
                                    <i class="bi bi-hdd-network me-1"></i>IP Address
                                </small>
                                <strong class="{{ $server->isPrimary() ? 'text-white' : '' }}">{{ $server->ip_address }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="{{ $server->isPrimary() ? 'text-white-50' : 'text-muted' }} d-block mb-1">
                                    <i class="bi bi-globe me-1"></i>Domain
                                </small>
                                @if($server->domain)
                                    <a href="https://{{ $server->domain }}" 
                                       target="_blank" 
                                       class="{{ $server->isPrimary() ? 'text-white' : 'text-primary' }}">
                                        {{ $server->domain }}
                                    </a>
                                @else
                                    <span class="{{ $server->isPrimary() ? 'text-white-50' : 'text-muted' }}">-</span>
                                @endif
                            </div>
                        </div>

                        @if($server->notes)
                            <div class="alert {{ $server->isPrimary() ? 'alert-light' : 'alert-info' }} alert-sm mb-3">
                                <small><i class="bi bi-info-circle me-1"></i>{{ $server->notes }}</small>
                            </div>
                        @endif

                        <div class="d-flex gap-2 justify-content-end">
                            @if(in_array($server->server_type, ['web', 'both']))
                                <a href="{{ route('admin.servers.show', $server) }}" 
                                   class="action-btn btn btn-sm {{ $server->isPrimary() ? 'btn-light' : 'btn-outline-info' }}" 
                                   title="View Metrics">
                                    <i class="bi bi-graph-up"></i>
                                </a>
                            @endif
                            
                            <a href="{{ route('admin.servers.edit', $server) }}" 
                               class="action-btn btn btn-sm {{ $server->isPrimary() ? 'btn-light' : 'btn-outline-primary' }}" 
                               title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            
                            @if($server->isReplica())
                                <form action="{{ route('admin.servers.promote', $server) }}" 
                                      method="POST" 
                                      class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="action-btn btn btn-sm btn-outline-success" 
                                            title="Promote to Primary"
                                            onclick="return confirm('Promote {{ $server->label }} to primary?')">
                                        <i class="bi bi-arrow-up-circle"></i>
                                    </button>
                                </form>
                            @endif
                            
                            <form action="{{ route('admin.servers.toggle-active', $server) }}" 
                                  method="POST" 
                                  class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="action-btn btn btn-sm btn-outline-{{ $server->is_active ? 'warning' : 'info' }}" 
                                        title="{{ $server->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="bi bi-{{ $server->is_active ? 'pause' : 'play' }}-circle"></i>
                                </button>
                            </form>
                            
                            @if($server->isReplica())
                                <form action="{{ route('admin.servers.destroy', $server) }}" 
                                      method="POST" 
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="action-btn btn btn-sm btn-outline-danger" 
                                            title="Delete"
                                            onclick="return confirm('Delete {{ $server->label }}? This cannot be undone.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h4 class="mt-3">No servers found</h4>
                    <p class="text-muted">Get started by adding your first server</p>
                    <a href="{{ route('admin.servers.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Server
                    </a>
                </div>
            </div>
        @endforelse

        <!-- Add New Server Card -->
        <div class="col-lg-6">
            <a href="{{ route('admin.servers.create') }}" class="text-decoration-none">
                <div class="card add-server-card border-0 shadow-sm d-flex align-items-center justify-content-center">
                    <div class="text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-plus-circle display-1 text-muted"></i>
                        </div>
                        <h5 class="text-muted mb-2">Add New Server</h5>
                        <p class="text-muted small mb-0">Click to add a new replica server</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="mt-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Quick Guide</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">1</span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <strong>Priority</strong>
                                <p class="text-muted small mb-0">Higher number = preferred for failover (0-999)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <span class="badge bg-success rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">2</span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <strong>Primary</strong>
                                <p class="text-muted small mb-0">Current active production server (only one)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <span class="badge bg-info rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">3</span>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <strong>Promote</strong>
                                <p class="text-muted small mb-0">Click ↑ to promote replica to primary</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
