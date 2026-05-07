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
        Schema::create('failover_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // jh, upcloud, aws, etc
            $table->string('label'); // Display name: "VPS JH", "VPS UPCLOUD"
            $table->string('ip_address');
            $table->string('agent_url'); // https://jezpro.id
            $table->string('domain')->nullable(); // jezpro.id
            $table->enum('role', ['primary', 'replica'])->default('replica');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher = preferred for failover
            $table->text('notes')->nullable();
            
            // SSH Config (optional)
            $table->string('ssh_host')->nullable();
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_user')->nullable();
            $table->string('app_path')->nullable();
            
            // CyberPanel Config (optional)
            $table->string('cyberpanel_url')->nullable();
            $table->string('cyberpanel_user')->nullable();
            $table->string('cyberpanel_pass')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failover_servers');
    }
};
