<?php

namespace App\Console\Commands;

use App\Services\CloudflareDnsService;
use Illuminate\Console\Command;

class FailoverDnsUpdate extends Command
{
    protected $signature   = 'failover:dns-update {target : jh atau upcloud}';
    protected $description = 'Update DNS Cloudflare secara manual ke server tertentu';

    public function handle(CloudflareDnsService $cloudflare): int
    {
        $target = $this->argument('target');

        if (! in_array($target, ['jh', 'upcloud'])) {
            $this->error('Target harus "jh" atau "upcloud".');
            return Command::FAILURE;
        }

        $this->info("Mengupdate DNS ke {$target}...");

        $result = $target === 'jh'
            ? $cloudflare->updateARecordToJH()
            : $cloudflare->updateARecordToUpcloud();

        if ($result['success'] ?? false) {
            $this->info("✓ DNS berhasil diupdate ke IP: " . ($result['ip'] ?? 'N/A'));
            return Command::SUCCESS;
        }

        $this->error("✗ Gagal: " . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }
}
