<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Failover Panel') — {{ config('app.name') }}</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sidebar-width: 240px;
            --color-jh: #0d6efd;
            --color-upcloud: #198754;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
        }

        body { background: #f0f2f5; font-size: 0.9rem; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: #1a1d23;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            padding-top: 1rem;
        }
        .sidebar .brand {
            padding: 0.75rem 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
            margin-bottom: 0.5rem;
        }
        .sidebar .brand h5 { color: #fff; margin: 0; font-size: 1rem; font-weight: 700; }
        .sidebar .brand small { color: rgba(255,255,255,.5); font-size: 0.75rem; }
        .sidebar .nav-link {
            color: rgba(255,255,255,.65);
            padding: 0.6rem 1.25rem;
            border-radius: 0;
            display: flex; align-items: center; gap: 0.6rem;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.08);
        }
        .sidebar .nav-link i { font-size: 1rem; width: 1.2rem; }

        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
        }

        /* Status cards */
        .server-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        .server-card .card-header {
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            font-size: 0.85rem;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        /* Status badge */
        .status-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .status-dot.online  { background: #198754; box-shadow: 0 0 0 3px rgba(25,135,84,.2); }
        .status-dot.offline { background: #dc3545; box-shadow: 0 0 0 3px rgba(220,53,69,.2); }
        .status-dot.unknown { background: #6c757d; }
        .status-dot.warning { background: #ffc107; box-shadow: 0 0 0 3px rgba(255,193,7,.2); }

        /* Active server badge */
        .active-server-badge {
            font-size: 1.1rem;
            font-weight: 700;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
        }

        /* Progress steps */
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .step-item:last-child { border-bottom: none; }
        .step-icon {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
        }
        .step-icon.pending  { background: #e9ecef; color: #6c757d; }
        .step-icon.running  { background: #fff3cd; color: #856404; }
        .step-icon.success  { background: #d1e7dd; color: #0f5132; }
        .step-icon.failed   { background: #f8d7da; color: #842029; }
        .step-icon.warning  { background: #fff3cd; color: #856404; }

        /* Metric bars */
        .metric-label { font-size: 0.75rem; color: #6c757d; margin-bottom: 2px; }
        .metric-value { font-size: 0.9rem; font-weight: 600; }

        /* Checklist modal */
        .checklist-item {
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.4rem;
            background: #f8f9fa;
            cursor: pointer;
            transition: background .15s;
        }
        .checklist-item:hover { background: #e9ecef; }
        .checklist-item.checked { background: #d1e7dd; }

        /* Pulse animation for running */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .pulse { animation: pulse 1.5s infinite; }

        /* Replica status */
        .replica-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
        }

        /* Log table */
        .log-table td { vertical-align: middle; }
        .log-table .badge { font-size: 0.75rem; }
    </style>

    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="brand">
        <h5><i class="bi bi-shield-check me-2"></i>Failover Panel</h5>
        <small>{{ config('app.name') }}</small>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="{{ route('admin.failover.index') }}"
               class="nav-link {{ request()->routeIs('admin.failover.index') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.failover.logs') }}"
               class="nav-link {{ request()->routeIs('admin.failover.logs*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Failover Logs
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.failover.settings') }}"
               class="nav-link {{ request()->routeIs('admin.failover.settings*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Settings
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.servers.index') }}"
               class="nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                <i class="bi bi-hdd-rack"></i> Manage Servers
            </a>
        </li>
        <li class="nav-item mt-3">
            <hr style="border-color: rgba(255,255,255,.1); margin: 0 1.25rem;">
        </li>
        <li class="nav-item">
            <a href="{{ url('/') }}" class="nav-link">
                <i class="bi bi-arrow-left"></i> Kembali ke App
            </a>
        </li>
    </ul>

    <div class="position-absolute bottom-0 w-100 p-3" style="border-top: 1px solid rgba(255,255,255,.1);">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                 style="width:32px;height:32px;font-size:.8rem;color:#fff;">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div>
                <div style="color:#fff;font-size:.8rem;font-weight:600;">{{ auth()->user()->name ?? 'User' }}</div>
                <div style="color:rgba(255,255,255,.4);font-size:.7rem;">Superadmin</div>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
</body>
</html>
