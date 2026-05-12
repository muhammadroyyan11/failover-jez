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
            --color-primary: #E31E24;
            --color-primary-dark: #B91419;
            --color-primary-light: #FF4449;
            --color-jh: #E31E24;
            --color-upcloud: #198754;
            --color-danger: #dc3545;
            --color-warning: #ffc107;
        }

        body { background: #f0f2f5; font-size: 0.9rem; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: #ffffff;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            padding-top: 1rem;
            transition: transform 0.3s ease;
            border-right: 2px solid #f0f0f0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
        .sidebar .brand {
            padding: 1rem 1.25rem 1rem;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 0.5rem;
        }
        .sidebar .brand h5 { 
            color: #1a1d23; 
            margin: 0; 
            font-size: 1.1rem; 
            font-weight: 700;
            line-height: 1.2;
        }
        .sidebar .brand small { 
            color: #6c757d; 
            font-size: 0.7rem;
            display: block;
            margin-top: 0.25rem;
        }
        .sidebar .nav-link {
            color: #4a5568;
            padding: 0.6rem 1.25rem;
            border-radius: 0;
            display: flex; align-items: center; gap: 0.6rem;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        .sidebar .nav-link:hover {
            color: var(--color-primary);
            background: rgba(227, 30, 36, 0.05);
            border-left: 3px solid var(--color-primary);
            padding-left: calc(1.25rem - 3px);
        }
        .sidebar .nav-link.active {
            color: var(--color-primary);
            background: rgba(227, 30, 36, 0.1);
            border-left: 3px solid var(--color-primary);
            padding-left: calc(1.25rem - 3px);
            font-weight: 600;
        }
        .sidebar .nav-link i { font-size: 1rem; width: 1.2rem; color: inherit; }

        /* Main content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: margin-left 0.3s ease;
        }

        /* Mobile menu toggle */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #ffffff;
            border-bottom: 2px solid #f0f0f0;
            z-index: 1002;
            padding: 0 1rem;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .mobile-menu-toggle {
            background: transparent;
            border: none;
            color: #1a1d23;
            padding: 0.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-logo {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .mobile-spacer {
            width: 40px; /* Same width as hamburger button for centering */
        }

        /* Mobile overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
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

        /* Custom JezPro Red Buttons */
        .btn-danger, .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            border: none;
            transition: all 0.2s ease;
        }
        
        .btn-danger:hover, .btn-primary:hover {
            background: linear-gradient(135deg, var(--color-primary-dark), var(--color-primary));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(227, 30, 36, 0.3);
        }
        
        .text-primary {
            color: var(--color-primary) !important;
        }
        
        .bg-primary {
            background-color: var(--color-primary) !important;
        }
        
        .border-primary {
            border-color: var(--color-primary) !important;
        }

        /* RESPONSIVE MOBILE */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .sidebar {
                transform: translateX(-100%);
                top: 0;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 70px; /* Space for mobile header */
            }
            
            /* Make cards full width on mobile */
            .col-md-6, .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            /* Smaller text on mobile */
            body { font-size: 0.85rem; }
            h1, .h1 { font-size: 1.5rem; }
            h4, .h4 { font-size: 1.1rem; }
            
            /* Hide sidebar user info on mobile */
            .sidebar .position-absolute.bottom-0 {
                display: none;
            }
            
            /* Make table responsive */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Smaller buttons on mobile */
            .btn-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            /* Adjust header spacing on mobile */
            .d-flex.justify-content-between.align-items-center.mb-4 {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            
            .d-flex.justify-content-between.align-items-center.mb-4 > div:last-child {
                width: 100%;
                flex-wrap: wrap;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

<!-- Mobile Header (only visible on mobile) -->
<div class="mobile-header">
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="bi bi-list"></i>
    </button>
    <div class="mobile-logo">
        <img src="{{ asset('logo/jez_pro.png') }}" alt="JezPro Logo" style="height: 28px; width: auto;">
    </div>
    <div class="mobile-spacer"></div>
</div>

<!-- Sidebar Overlay (for mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="brand">
        <div class="d-flex align-items-center gap-3 mb-1">
            <img src="{{ asset('logo/jez_pro.png') }}" alt="JezPro Logo" style="height: 30px; width: auto; margin-left:15px;">
            <div>
            </div>
        </div>
        <small style="padding-left: 0; margin-left:15px;">Jezpro Failover Panel</small>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="{{ route('admin.failover.index') }}"
               class="nav-link {{ request()->routeIs('admin.failover.index') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.failover.switch') }}"
               class="nav-link {{ request()->routeIs('admin.failover.switch*') ? 'active' : '' }}">
                <i class="bi bi-arrow-repeat"></i> Execute Failover
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.servers.index') }}"
               class="nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                <i class="bi bi-hdd-rack"></i> Manage Servers
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.failover.logs') }}"
               class="nav-link {{ request()->routeIs('admin.failover.logs*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Failover Logs
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.users.index') }}"
               class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Users
            </a>
        </li>
        <li class="nav-item mt-3">
            <hr style="border-color: #e9ecef; margin: 0 1.25rem;">
        </li>
        <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link btn btn-link text-start w-100" style="text-decoration: none;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </li>
    </ul>

    <div class="position-absolute bottom-0 w-100 p-3" style="border-top: 2px solid #f0f0f0; background: #fafafa;">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width:36px;height:36px;font-size:.85rem;color:#fff;background:var(--color-primary);font-weight:600;">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div>
                <div style="color:#1a1d23;font-size:.85rem;font-weight:600;">{{ auth()->user()->name ?? 'User' }}</div>
                <div style="color:#6c757d;font-size:.7rem;">Superadmin</div>
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

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        // Close sidebar when clicking a link on mobile
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });
    }
});
</script>

@stack('scripts')
</body>
</html>
