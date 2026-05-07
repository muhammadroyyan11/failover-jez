<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failover_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('active_server', ['jh', 'upcloud'])->default('jh');
            $table->string('jh_ip', 45)->nullable();
            $table->string('upcloud_ip', 45)->nullable();
            $table->string('primary_domain')->default('jezpro.id');
            $table->string('standby_domain')->default('jezpro.com');
            $table->string('cloudflare_zone_id')->nullable();
            $table->string('cloudflare_record_id')->nullable();
            $table->string('jh_agent_url')->nullable();
            $table->string('upcloud_agent_url')->nullable();
            $table->boolean('maintenance_mode_jh')->default(false);
            $table->boolean('maintenance_mode_upcloud')->default(false);
            $table->json('extra')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failover_settings');
    }
};
