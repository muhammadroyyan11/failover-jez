<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('failover_servers', function (Blueprint $table) {
            // Database Configuration
            $table->string('db_host')->nullable()->after('agent_url');
            $table->integer('db_port')->default(3306)->after('db_host');
            $table->string('db_username')->nullable()->after('db_port');
            $table->text('db_password')->nullable()->after('db_username'); // encrypted
            $table->string('db_database')->nullable()->after('db_password');
            
            // Replication Configuration
            $table->string('replication_user')->nullable()->after('db_database');
            $table->text('replication_password')->nullable()->after('replication_user'); // encrypted
            $table->enum('db_role', ['master', 'slave', 'standalone'])->default('standalone')->after('replication_password');
            
            // Replication Status (cached)
            $table->boolean('replication_io_running')->default(false)->after('db_role');
            $table->boolean('replication_sql_running')->default(false)->after('replication_io_running');
            $table->integer('seconds_behind_master')->nullable()->after('replication_sql_running');
            $table->timestamp('replication_checked_at')->nullable()->after('seconds_behind_master');
        });
    }

    public function down(): void
    {
        Schema::table('failover_servers', function (Blueprint $table) {
            $table->dropColumn([
                'db_host', 'db_port', 'db_username', 'db_password', 'db_database',
                'replication_user', 'replication_password', 'db_role',
                'replication_io_running', 'replication_sql_running', 
                'seconds_behind_master', 'replication_checked_at'
            ]);
        });
    }
};
