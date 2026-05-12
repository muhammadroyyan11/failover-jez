<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------
// Auth Routes
// -------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// -------------------------------------------------------
// Root redirect
// -------------------------------------------------------
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.failover.index')
        : redirect()->route('login');
});

// -------------------------------------------------------
// Admin Routes (Superadmin only)
// -------------------------------------------------------
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Failover Dashboard
    Route::get('failover', [\App\Http\Controllers\Admin\FailoverController::class, 'index'])
        ->name('failover.index');
    Route::get('failover/status', [\App\Http\Controllers\Admin\FailoverController::class, 'status'])
        ->name('failover.status');
    Route::get('failover/server-status/{server}', [\App\Http\Controllers\Admin\FailoverController::class, 'serverStatus'])
        ->name('failover.server-status');
    Route::get('failover/logs', [\App\Http\Controllers\Admin\FailoverController::class, 'logs'])
        ->name('failover.logs');
    Route::get('failover/settings', [\App\Http\Controllers\Admin\FailoverController::class, 'settings'])
        ->name('failover.settings');
    Route::put('failover/settings', [\App\Http\Controllers\Admin\FailoverController::class, 'updateSettings'])
        ->name('failover.update-settings');
    
    // Failover Execution
    Route::get('failover/switch', [\App\Http\Controllers\Admin\FailoverController::class, 'switchPage'])
        ->name('failover.switch-page');
    Route::post('failover/execute/web-down', [\App\Http\Controllers\Admin\FailoverController::class, 'executeWebDown'])
        ->name('failover.execute-web-down');
    Route::post('failover/execute/db-down', [\App\Http\Controllers\Admin\FailoverController::class, 'executeDbDown'])
        ->name('failover.execute-db-down');
    Route::post('failover/execute/complete', [\App\Http\Controllers\Admin\FailoverController::class, 'executeComplete'])
        ->name('failover.execute-complete');
    Route::post('failover/execute/rollback', [\App\Http\Controllers\Admin\FailoverController::class, 'executeRollback'])
        ->name('failover.execute-rollback');
    
    // Server Management
    Route::resource('servers', \App\Http\Controllers\Admin\ServerController::class);
    Route::patch('servers/{server}/toggle-active', [\App\Http\Controllers\Admin\ServerController::class, 'toggleActive'])
        ->name('servers.toggle-active');
    Route::patch('servers/{server}/promote', [\App\Http\Controllers\Admin\ServerController::class, 'promote'])
        ->name('servers.promote');
    
    // Server Metrics
    Route::get('servers/{server}/metrics', [\App\Http\Controllers\Admin\ServerController::class, 'getMetrics'])
        ->name('servers.metrics');
    Route::get('servers/{server}/metrics/latest', [\App\Http\Controllers\Admin\ServerController::class, 'getLatestMetrics'])
        ->name('servers.metrics.latest');
    
    // Database Replication
    Route::post('servers/{server}/test-db', [\App\Http\Controllers\Admin\ServerController::class, 'testDatabase'])
        ->name('servers.test-db');
    Route::post('servers/{server}/check-replication', [\App\Http\Controllers\Admin\ServerController::class, 'checkReplication'])
        ->name('servers.check-replication');
    Route::post('servers/{server}/setup-replication', [\App\Http\Controllers\Admin\ServerController::class, 'setupReplication'])
        ->name('servers.setup-replication');
    Route::post('servers/{server}/promote-db', [\App\Http\Controllers\Admin\ServerController::class, 'promoteDatabase'])
        ->name('servers.promote-db');
    
    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
});

