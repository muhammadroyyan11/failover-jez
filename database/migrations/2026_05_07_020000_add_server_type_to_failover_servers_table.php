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
            $table->enum('server_type', ['web', 'database', 'both'])
                  ->default('web')
                  ->after('role')
                  ->comment('Server type: web (has agent API), database (MySQL only), both (web + db)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failover_servers', function (Blueprint $table) {
            $table->dropColumn('server_type');
        });
    }
};
