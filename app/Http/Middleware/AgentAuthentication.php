<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk melindungi /api/agent/* endpoint.
 * Validasi:
 *  1. IP whitelist
 *  2. Bearer token
 *  3. HMAC signature + timestamp (replay attack prevention)
 */
class AgentAuthentication
{
    /**
     * Toleransi timestamp dalam detik (cegah replay attack).
     */
    private const TIMESTAMP_TOLERANCE = 60;

    public function handle(Request $request, Closure $next): Response
    {
        // 1. IP Whitelist
        $allowedIps = config('failover.allowed_ips', []);
        $clientIp   = $request->ip();

        if (! empty($allowedIps) && ! in_array($clientIp, $allowedIps, true)) {
            return response()->json([
                'success' => false,
                'message' => 'IP not allowed.',
            ], 403);
        }

        // 2. Bearer Token
        $token         = config('failover.agent_token');
        $authorization = $request->header('Authorization', '');

        if (! str_starts_with($authorization, 'Bearer ')) {
            return response()->json(['success' => false, 'message' => 'Missing authorization.'], 401);
        }

        $providedToken = substr($authorization, 7);
        if (! hash_equals($token, $providedToken)) {
            return response()->json(['success' => false, 'message' => 'Invalid token.'], 401);
        }

        // 3. HMAC Signature
        $timestamp = $request->header('X-Agent-Timestamp');
        $signature = $request->header('X-Agent-Signature');

        if (! $timestamp || ! $signature) {
            return response()->json(['success' => false, 'message' => 'Missing signature headers.'], 401);
        }

        // Cek timestamp freshness
        $now = time();
        if (abs($now - (int) $timestamp) > self::TIMESTAMP_TOLERANCE) {
            return response()->json(['success' => false, 'message' => 'Request expired.'], 401);
        }

        // Verifikasi HMAC
        $method    = $request->method();
        $path      = $request->getPathInfo();
        $bodyHash  = hash('sha256', $request->getContent() ?: '');
        $message   = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
        $expected  = hash_hmac('sha256', $message, config('failover.hmac_secret'));

        if (! hash_equals($expected, $signature)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 401);
        }

        return $next($request);
    }
}
