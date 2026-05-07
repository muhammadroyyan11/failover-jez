<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    </style>
</head>
<body>
<div class="text-center">
    <i class="bi bi-shield-x text-danger" style="font-size:4rem;"></i>
    <h2 class="mt-3 fw-bold">403 — Akses Ditolak</h2>
    <p class="text-muted">{{ $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini.' }}</p>
    <a href="{{ route('login') }}" class="btn btn-dark mt-2">
        <i class="bi bi-arrow-left me-2"></i>Kembali ke Login
    </a>
</div>
</body>
</html>
