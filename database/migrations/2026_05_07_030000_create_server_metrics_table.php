<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('failover_servers')->onDelete('cascade');
            
            // CPU Metrics
            $table->decimal('cpu_load_1min', 8, 2)->nullable();
            $table->decimal('cpu_load_5min', 8, 2)->nullable();
            $table->decimal('cpu_load_15min', 8, 2)->nullable();
            $table->decimal('cpu_usage_percent', 5, 2)->nullable();
            
            // Memory Metrics
            $table->bigInteger('memory_total')->nullable()->comment('in MB');
            $table->bigInteger('memory_used')->nullable()->comment('in MB');
            $table->bigInteger('memory_free')->nullable()->comment('in MB');
            $table->decimal('memory_percent', 5, 2)->nullable();
            
            // Disk Metrics
            $table->bigInteger('disk_total')->nullable()->comment('in GB');
            $table->bigInteger('disk_used')->nullable()->comment('in GB');
            $table->bigInteger('disk_free')->nullable()->comment('in GB');
            $table->decimal('disk_percent', 5, 2)->nullable();
            
            // Network Metrics
            $table->bigInteger('network_rx_bytes')->nullable()->comment('received bytes');
            $table->bigInteger('network_tx_bytes')->nullable()->comment('transmitted bytes');
            
            // Additional Metrics
            $table->integer('process_count')->nullable();
            $table->integer('uptime_seconds')->nullable();
            $table->boolean('is_online')->default(true);
            
            $table->timestamp('recorded_at')->useCurrent();
            
            // Indexes for fast queries
            $table->index(['server_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
