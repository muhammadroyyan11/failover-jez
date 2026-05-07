<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FailoverSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('failover_settings')->count() === 0) {
            DB::table('failover_settings')->insert([
                'active_server'       => config('failover.this_server', 'jh'),
                'jh_ip'               => config('failover.jh_ip'),
                'upcloud_ip'          => config('failover.upcloud_ip'),
                'primary_domain'      => config('failover.primary_domain', 'jezpro.id'),
                'standby_domain'      => config('failover.standby_domain', 'jezpro.com'),
                'cloudflare_zone_id'  => config('failover.cloudflare.zone_id'),
                'cloudflare_record_id'=> config('failover.cloudflare.record_id'),
                'jh_agent_url'        => config('failover.jh_agent_url'),
                'upcloud_agent_url'   => config('failover.upcloud_agent_url'),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }
}
