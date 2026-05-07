<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Failover Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1d23 0%, #2d3748 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #1a1d23, #2d3748);
            padding: 2rem;
            text-align: center;
            color: #fff;
        }

        .login-header .shield-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
        }

        .login-header h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .login-header p {
            margin: 0.25rem 0 0;
            opacity: .6;
            font-size: .85rem;
        }

        .login-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 8px;
            padding: .65rem 1rem;
            border: 1.5px solid #e2e8f0;
            transition: border-color .2s;
        }

        .form-control:focus {
            border-color: #4a5568;
            box-shadow: 0 0 0 3px rgba(74,85,104,.1);
        }

        .btn-login {
            background: linear-gradient(135deg, #1a1d23, #4a5568);
            border: none;
            border-radius: 8px;
            padding: .75rem;
            font-weight: 600;
            letter-spacing: .3px;
            transition: opacity .2s;
        }

        .btn-login:hover { opacity: .9; }

        .input-group-text {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            color: #718096;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control:focus {
            border-left: none;
        }

        .server-status {
            display: flex;
            gap: .5rem;
            justify-content: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f0f0f0;
        }

        .status-pill {
            font-size: .7rem;
            padding: .2rem .6rem;
            border-radius: 50px;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert {
            border-radius: 8px;
            font-size: .875rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="shield-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <h4>Failover Panel</h4>
        <p>Disaster Recovery Management</p>
    </div>

    <div class="login-body">

        @if($errors->any())
            <div class="alert alert-danger d-flex gap-2 align-items-center mb-3">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-success mb-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted text-uppercase" style="letter-spacing:.5px;">
                    Email
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" placeholder="admin@jezpro.id"
                           autofocus autocomplete="email" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted text-uppercase" style="letter-spacing:.5px;">
                    Password
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="passwordInput"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="••••••••" autocomplete="current-password" required>
                    <button type="button" class="btn btn-outline-secondary border-start-0"
                            style="border: 1.5px solid #e2e8f0; border-left: none; border-radius: 0 8px 8px 0;"
                            onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted" for="remember">Ingat saya</label>
                </div>
            </div>

            <button type="submit" class="btn btn-login btn-dark w-100 text-white">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Panel
            </button>
        </form>

        <div class="server-status">
            <span class="status-pill"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Superadmin Only</span>
            <span class="status-pill"><i class="bi bi-shield-lock me-1"></i>Secured</span>
        </div>

        <p class="text-center text-muted mt-3 mb-0" style="font-size:.75rem;">
            Jezpro Failover Panel &copy; {{ date('Y') }}
        </p>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
