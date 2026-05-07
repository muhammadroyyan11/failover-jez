<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareDnsService
{
    private string $apiToken;
    private string $zoneId;
    private string $recordId;
    private string $domain;
    private bool   $proxied;
    private int    $ttl;

    public function __construct()
    {
        $this->apiToken = config('failover.cloudflare.api_token');
        $this->zoneId   = config('failover.cloudflare.zone_id');
        $this->recordId = config('failover.cloudflare.record_id');
        $this->domain   = config('failover.cloudflare.domain');
        $this->proxied  = config('failover.cloudflare.proxied', true);
        $this->ttl      = config('failover.cloudflare.ttl', 1);
    }

    /**
     * Arahkan DNS A Record ke IP server JH.
     */
    public function updateARecordToJH(): array
    {
        $ip = config('failover.jh_ip');
        return $this->updateARecordLegacy($ip, "Failover: pointing {$this->domain} to JH ({$ip})");
    }

    /**
     * Arahkan DNS A Record ke IP server UPCLOUD.
     */
    public function updateARecordToUpcloud(): array
    {
        $ip = config('failover.upcloud_ip');
        return $this->updateARecordLegacy($ip, "Failover: pointing {$this->domain} to UPCLOUD ({$ip})");
    }

    /**
     * Dapatkan target DNS saat ini.
     */
    public function getCurrentDnsTarget(): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(15)
                ->get("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records/{$this->recordId}");

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error'   => 'Cloudflare API error: ' . $response->status(),
                    'body'    => $response->json(),
                ];
            }

            $data = $response->json('result');

            return [
                'success'  => true,
                'ip'       => $data['content'] ?? null,
                'name'     => $data['name'] ?? null,
                'proxied'  => $data['proxied'] ?? null,
                'ttl'      => $data['ttl'] ?? null,
                'modified' => $data['modified_on'] ?? null,
                'server'   => $this->resolveServerFromIp($data['content'] ?? ''),
            ];
        } catch (\Throwable $e) {
            Log::error('[CloudflareDnsService] getCurrentDnsTarget failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update A Record ke IP tertentu (public method).
     */
    public function updateARecord(string $ip, string $comment = ''): array
    {
        if (empty($ip)) {
            return ['success' => false, 'error' => 'Target IP is not configured.'];
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(15)
                ->put("https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/dns_records/{$this->recordId}", [
                    'type'    => 'A',
                    'name'    => $this->domain,
                    'content' => $ip,
                    'ttl'     => $this->ttl,
                    'proxied' => $this->proxied,
                    'comment' => $comment,
                ]);

            if (! $response->successful()) {
                Log::error('[CloudflareDnsService] updateARecord failed', [
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
                return [
                    'success' => false,
                    'error'   => 'Cloudflare API error: ' . $response->status(),
                    'body'    => $response->json(),
                ];
            }

            Log::info('[CloudflareDnsService] DNS updated', ['ip' => $ip, 'domain' => $this->domain]);

            return [
                'success' => true,
                'ip'      => $ip,
                'result'  => $response->json('result'),
            ];
        } catch (\Throwable $e) {
            Log::error('[CloudflareDnsService] updateARecord exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update A Record ke IP tertentu (private helper for legacy methods).
     */
    private function updateARecordLegacy(string $ip, string $comment = ''): array
    {
        return $this->updateARecord($ip, $comment);
    }

    /**
     * Resolve nama server dari IP.
     */
    private function resolveServerFromIp(string $ip): string
    {
        if ($ip === config('failover.jh_ip')) {
            return 'jh';
        }
        if ($ip === config('failover.upcloud_ip')) {
            return 'upcloud';
        }
        return 'unknown';
    }
}
