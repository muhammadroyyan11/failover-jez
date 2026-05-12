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
        Schema::table('failover_servers', function (Blueprint $table) {
            $table->string('db_root_username')->nullable()->after('db_password');
            $table->text('db_root_password')->nullable()->after('db_root_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failover_servers', function (Blueprint $table) {
            $table->dropColumn(['db_root_username', 'db_root_password']);
        });
    }
};
