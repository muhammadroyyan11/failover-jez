<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client untuk berkomunikasi dengan Agent API di server lain.
 * Semua request dilindungi Bearer token + HMAC signature.
 */
class ServerAgentClient
{
    private string $baseUrl;
    private string $token;
    private string $hmacSecret;
    private int    $timeout;

    public function __construct(string $server)
    {
        $this->token      = config('failover.agent_token');
        $this->hmacSecret = config('failover.hmac_secret');
        $this->timeout    = config('failover.agent_timeout', 30);

        $this->baseUrl = match ($server) {
            'jh'      => rtrim(config('failover.jh_agent_url'), '/'),
            'upcloud' => rtrim(config('failover.upcloud_agent_url'), '/'),
            default   => throw new \InvalidArgumentException("Unknown server: {$server}"),
        };
    }

    /**
     * GET /api/agent/health
     */
    public function health(): array
    {
        return $this->get('/api/agent/health');
    }

    /**
     * GET /api/agent/system-status
     */
    public function systemStatus(): array
    {
        return $this->get('/api/agent/system-status');
    }

    /**
     * GET /api/agent/replication-status
     */
    public function replicationStatus(): array
    {
        return $this->get('/api/agent/replication-status');
    }

    /**
     * POST /api/agent/artisan-down
     */
    public function artisanDown(): array
    {
        return $this->post('/api/agent/artisan-down');
    }

    /**
     * POST /api/agent/artisan-up
     */
    public function artisanUp(): array
    {
        return $this->post('/api/agent/artisan-up');
    }

    /**
     * POST /api/agent/promote-primary
     * Promote server ini menjadi primary (stop replica, reset replica all).
     */
    public function promotePrimary(): array
    {
        return $this->post('/api/agent/promote-primary');
    }

    /**
     * POST /api/agent/clear-cache
     */
    public function clearCache(): array
    {
        return $this->post('/api/agent/clear-cache');
    }

    /**
     * POST /api/agent/restart-queue
     */
    public function restartQueue(): array
    {
        return $this->post('/api/agent/restart-queue');
    }

    // -------------------------------------------------------------------------
    // HTTP Helpers
    // -------------------------------------------------------------------------

    private function get(string $path): array
    {
        try {
            $response = Http::withHeaders($this->buildHeaders('GET', $path))
                ->timeout($this->timeout)
                ->get($this->baseUrl . $path);

            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            Log::error("[ServerAgentClient] GET {$path} failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'reachable' => false];
        }
    }

    private function post(string $path, array $data = []): array
    {
        try {
            $response = Http::withHeaders($this->buildHeaders('POST', $path, $data))
                ->timeout($this->timeout)
                ->post($this->baseUrl . $path, $data);

            return $this->parseResponse($response);
        } catch (\Throwable $e) {
            Log::error("[ServerAgentClient] POST {$path} failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage(), 'reachable' => false];
        }
    }

    /**
     * Build headers dengan Bearer token dan HMAC signature.
     */
    private function buildHeaders(string $method, string $path, array $body = []): array
    {
        $timestamp = (string) time();
        $bodyHash  = hash('sha256', $body ? json_encode($body) : '');
        $message   = "{$method}\n{$path}\n{$timestamp}\n{$bodyHash}";
        $signature = hash_hmac('sha256', $message, $this->hmacSecret);

        return [
            'Authorization'       => 'Bearer ' . $this->token,
            'X-Agent-Timestamp'   => $timestamp,
            'X-Agent-Signature'   => $signature,
            'Accept'              => 'application/json',
            'Content-Type'        => 'application/json',
        ];
    }

    private function parseResponse(\Illuminate\Http\Client\Response $response): array
    {
        $data = $response->json() ?? [];

        if (! $response->successful()) {
            return array_merge($data, [
                'success'   => false,
                'http_code' => $response->status(),
                'reachable' => true,
            ]);
        }

        return array_merge($data, ['reachable' => true]);
    }
}
