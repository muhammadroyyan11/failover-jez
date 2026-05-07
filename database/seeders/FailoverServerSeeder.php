<?php

namespace Database\Seeders;

use App\Models\FailoverServer;
use Illuminate\Database\Seeder;

class FailoverServerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servers = [
            [
                'name' => 'jh',
                'label' => 'VPS JH (Primary)',
                'ip_address' => env('JH_IP', '1.2.3.4'),
                'agent_url' => env('JH_AGENT_URL', 'https://jezpro.id'),
                'domain' => 'jezpro.id',
                'role' => 'primary',
                'is_active' => true,
                'priority' => 100,
                'notes' => 'Production primary server',
                'ssh_host' => env('JH_SSH_HOST'),
                'ssh_port' => env('JH_SSH_PORT', 22),
                'ssh_user' => env('JH_SSH_USER', 'root'),
                'app_path' => env('JH_APP_PATH', '/home/jezpro/public_html'),
                'cyberpanel_url' => env('JH_CYBERPANEL_URL'),
                'cyberpanel_user' => env('JH_CYBERPANEL_USER', 'admin'),
                'cyberpanel_pass' => env('JH_CYBERPANEL_PASS'),
            ],
            [
                'name' => 'upcloud',
                'label' => 'VPS UPCLOUD (Standby)',
                'ip_address' => env('UPCLOUD_IP', '5.6.7.8'),
                'agent_url' => env('UPCLOUD_AGENT_URL', 'https://jezpro.com'),
                'domain' => 'jezpro.com',
                'role' => 'replica',
                'is_active' => true,
                'priority' => 90,
                'notes' => 'Standby replica server',
                'ssh_host' => env('UPCLOUD_SSH_HOST'),
                'ssh_port' => env('UPCLOUD_SSH_PORT', 22),
                'ssh_user' => env('UPCLOUD_SSH_USER', 'root'),
                'app_path' => env('UPCLOUD_APP_PATH', '/home/jezpro/public_html'),
                'cyberpanel_url' => env('UPCLOUD_CYBERPANEL_URL'),
                'cyberpanel_user' => env('UPCLOUD_CYBERPANEL_USER', 'admin'),
                'cyberpanel_pass' => env('UPCLOUD_CYBERPANEL_PASS'),
            ],
        ];

        foreach ($servers as $server) {
            FailoverServer::updateOrCreate(
                ['name' => $server['name']],
                $server
            );
        }
    }
}
