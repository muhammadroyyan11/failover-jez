<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FailoverSuperadmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->role !== 'superadmin') {
            abort(403, 'Akses ditolak. Hanya superadmin yang diizinkan.');
        }

        return $next($request);
    }
}
