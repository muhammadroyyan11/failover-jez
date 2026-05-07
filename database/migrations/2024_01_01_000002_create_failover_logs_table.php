<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failover_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100);                          // e.g. full_failover, maintenance_on, dns_update
            $table->enum('from_server', ['jh', 'upcloud'])->nullable();
            $table->enum('to_server', ['jh', 'upcloud'])->nullable();
            $table->enum('status', ['pending', 'running', 'success', 'failed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedBigInteger('triggered_by')->nullable(); // user id
            $table->string('triggered_by_name')->nullable();        // user name snapshot
            $table->text('message')->nullable();
            $table->json('payload')->nullable();                    // step-by-step detail
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failover_logs');
    }
};
