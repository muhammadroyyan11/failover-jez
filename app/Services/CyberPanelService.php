<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CyberPanelService
{
    private string $baseUrl;
    private string $adminUser;
    private string $adminPass;

    public function __construct(string $server = 'upcloud')
    {
        $this->baseUrl = $server === 'jh' 
            ? env('JH_CYBERPANEL_URL', 'https://1.2.3.4:8090') 
            : env('UPCLOUD_CYBERPANEL_URL', 'https://5.6.7.8:8090');
        
        $this->adminUser = $server === 'jh'
            ? env('JH_CYBERPANEL_USER', 'admin')
            : env('UPCLOUD_CYBERPANEL_USER', 'admin');
        
        $this->adminPass = $server === 'jh'
            ? env('JH_CYBERPANEL_PASS')
            : env('UPCLOUD_CYBERPANEL_PASS');
    }

    /**
     * Verify login credentials
     */
    public function verifyLogin(): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->post("{$this->baseUrl}/api/verifyLogin", [
                    'adminUser' => $this->adminUser,
                    'adminPass' => $this->adminPass
                ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("CyberPanel API error: " . $e->getMessage());
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get website list
     */
    public function listWebsites(): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->post("{$this->baseUrl}/api/fetchWebsites", [
                    'adminUser' => $this->adminUser,
                    'adminPass' => $this->adminPass
                ]);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Restart LiteSpeed Web Server
     */
    public function restartLiteSpeed(): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post("{$this->baseUrl}/api/restartLiteSpeed", [
                    'adminUser' => $this->adminUser,
                    'adminPass' => $this->adminPass
                ]);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get server status
     */
    public function getServerStatus(): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->post("{$this->baseUrl}/api/serverStatus", [
                    'adminUser' => $this->adminUser,
                    'adminPass' => $this->adminPass
                ]);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create database backup
     */
    public function createDatabaseBackup(string $databaseName): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(60)
                ->post("{$this->baseUrl}/api/createBackup", [
                    'adminUser' => $this->adminUser,
                    'adminPass' => $this->adminPass,
                    'databaseName' => $databaseName
                ]);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Issue SSL certificate
     */
    public function issueSSL(string $domain): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(60)
                ->post("{$this->baseUrl}/api/issueSSL", [
                    'adminUser' => $this->adminUser,
                    'adminPass' => $this->adminPass,
                    'domainName' => $domain
                ]);

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => $e->getMessage()];
        }
    }
}
