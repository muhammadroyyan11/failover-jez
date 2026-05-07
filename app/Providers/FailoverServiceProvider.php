<?php

namespace App\Providers;

use App\Services\CloudflareDnsService;
use App\Services\FailoverService;
use App\Services\ReplicationStatusService;
use Illuminate\Support\ServiceProvider;

class FailoverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton agar tidak membuat instance baru setiap inject
        $this->app->singleton(CloudflareDnsService::class);
        $this->app->singleton(ReplicationStatusService::class);
        $this->app->singleton(FailoverService::class);
    }

    public function boot(): void
    {
        // Load routes failover
        $this->loadRoutesFrom(base_path('routes/failover.php'));
    }
}
