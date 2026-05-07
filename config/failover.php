<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Identity
    |--------------------------------------------------------------------------
    | Identitas server ini: 'jh' atau 'upcloud'
    */
    'this_server' => env('FAILOVER_THIS_SERVER', 'jh'),

    /*
    |--------------------------------------------------------------------------
    | Agent Token
    |--------------------------------------------------------------------------
    | Bearer token untuk autentikasi antar server agent.
    | Harus sama di kedua server.
    */
    'agent_token' => env('FAILOVER_AGENT_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Server IPs
    |--------------------------------------------------------------------------
    */
    'jh_ip'      => env('JH_IP'),
    'upcloud_ip' => env('UPCLOUD_IP'),

    /*
    |--------------------------------------------------------------------------
    | Agent URLs
    |--------------------------------------------------------------------------
    | Base URL untuk memanggil agent endpoint di masing-masing server.
    */
    'jh_agent_url'      => env('JH_AGENT_URL', 'https://jezpro.id'),
    'upcloud_agent_url' => env('UPCLOUD_AGENT_URL', 'https://jezpro.com'),

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs for Agent Endpoint
    |--------------------------------------------------------------------------
    | IP yang diizinkan memanggil /api/agent/* endpoint.
    */
    'allowed_ips' => array_filter(
        explode(',', env('FAILOVER_ALLOWED_IPS', '127.0.0.1'))
    ),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare
    |--------------------------------------------------------------------------
    */
    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id'   => env('CLOUDFLARE_ZONE_ID'),
        'record_id' => env('CLOUDFLARE_RECORD_ID'),
        'domain'    => env('CLOUDFLARE_DOMAIN', 'jezpro.id'),
        'ttl'       => 1, // 1 = auto
        'proxied'   => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Domains
    |--------------------------------------------------------------------------
    */
    'primary_domain'  => env('CLOUDFLARE_DOMAIN', 'jezpro.id'),
    'standby_domain'  => env('FAILOVER_STANDBY_DOMAIN', 'jezpro.com'),

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    | Timeout dalam detik untuk HTTP request ke agent.
    */
    'agent_timeout'   => 30,
    'artisan_timeout' => 120,

    /*
    |--------------------------------------------------------------------------
    | Replica Delay Threshold
    |--------------------------------------------------------------------------
    | Maksimal Seconds_Behind_Source yang diizinkan saat failover.
    | Harus 0 untuk keamanan data.
    */
    'max_replica_delay' => 0,

    /*
    |--------------------------------------------------------------------------
    | Whitelisted Artisan Commands
    |--------------------------------------------------------------------------
    | Hanya command ini yang boleh dieksekusi via agent.
    */
    'allowed_artisan_commands' => [
        'down',
        'up',
        'optimize:clear',
        'config:cache',
        'queue:restart',
        'migrate:status',
    ],

    /*
    |--------------------------------------------------------------------------
    | HMAC Secret
    |--------------------------------------------------------------------------
    | Secret untuk signing request antar server.
    */
    'hmac_secret' => env('FAILOVER_HMAC_SECRET', env('FAILOVER_AGENT_TOKEN')),

];
