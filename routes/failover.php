<?php

use App\Http\Controllers\Admin\FailoverController;
use App\Http\Controllers\Api\AgentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Failover Panel Routes
|--------------------------------------------------------------------------
| Dilindungi: auth + FailoverSuperadmin middleware
*/
Route::middleware(['web', 'auth', 'failover.superadmin'])
    ->prefix('admin/failover')
    ->name('admin.failover.')
    ->group(function () {

        // Dashboard
        Route::get('/', [FailoverController::class, 'index'])->name('index');

        // AJAX status refresh
        Route::get('/status', [FailoverController::class, 'status'])->name('status');

        // Eksekusi failover
        Route::post('/switch', [FailoverController::class, 'switch'])->name('switch');

        // Test koneksi ke agent
        Route::post('/test-connection/{server}', [FailoverController::class, 'testConnection'])
            ->name('test-connection');

        // Logs
        Route::get('/logs', [FailoverController::class, 'logs'])->name('logs');
        Route::get('/logs/{log}', [FailoverController::class, 'logDetail'])->name('logs.detail');

        // Settings
        Route::get('/settings', [FailoverController::class, 'settings'])->name('settings');
        Route::put('/settings', [FailoverController::class, 'updateSettings'])->name('settings.update');
    });

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
| Dilindungi: AgentAuthentication middleware (Bearer token + IP + HMAC)
| Tidak menggunakan session/cookie - murni API
*/
Route::middleware(['api', 'agent.auth'])
    ->prefix('api/agent')
    ->name('api.agent.')
    ->group(function () {

        // Health check
        Route::get('/health', [AgentController::class, 'health'])->name('health');

        // System metrics
        Route::get('/system-status', [AgentController::class, 'systemStatus'])->name('system-status');

        // Replication status
        Route::get('/replication-status', [AgentController::class, 'replicationStatus'])->name('replication-status');

        // Artisan commands
        Route::post('/artisan-down', [AgentController::class, 'artisanDown'])->name('artisan-down');
        Route::post('/artisan-up', [AgentController::class, 'artisanUp'])->name('artisanUp');

        // Database operations
        Route::post('/promote-primary', [AgentController::class, 'promotePrimary'])->name('promote-primary');

        // Cache & Queue
        Route::post('/clear-cache', [AgentController::class, 'clearCache'])->name('clear-cache');
        Route::post('/restart-queue', [AgentController::class, 'restartQueue'])->name('restart-queue');
    });
