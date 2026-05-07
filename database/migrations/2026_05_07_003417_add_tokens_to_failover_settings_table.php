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
        Schema::table('failover_settings', function (Blueprint $table) {
            $table->text('agent_token')->nullable()->after('upcloud_agent_url');
            $table->text('hmac_secret')->nullable()->after('agent_token');
            $table->text('cloudflare_api_token')->nullable()->after('cloudflare_record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failover_settings', function (Blueprint $table) {
            $table->dropColumn(['agent_token', 'hmac_secret', 'cloudflare_api_token']);
        });
    }
};
