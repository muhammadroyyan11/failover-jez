<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailoverSetting extends Model
{
    protected $fillable = [
        'active_server',
        'jh_ip',
        'upcloud_ip',
        'primary_domain',
        'standby_domain',
        'cloudflare_zone_id',
        'cloudflare_record_id',
        'cloudflare_api_token',
        'jh_agent_url',
        'upcloud_agent_url',
        'agent_token',
        'hmac_secret',
        'maintenance_mode_jh',
        'maintenance_mode_upcloud',
        'extra',
    ];

    protected $casts = [
        'extra'                    => 'array',
        'maintenance_mode_jh'      => 'boolean',
        'maintenance_mode_upcloud' => 'boolean',
    ];

    protected $hidden = [
        'agent_token',
        'hmac_secret',
        'cloudflare_api_token',
    ];

    /**
     * Encrypt sensitive fields
     */
    public function setAgentTokenAttribute($value)
    {
        $this->attributes['agent_token'] = $value ? encrypt($value) : null;
    }

    public function getAgentTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function setHmacSecretAttribute($value)
    {
        $this->attributes['hmac_secret'] = $value ? encrypt($value) : null;
    }

    public function getHmacSecretAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function setCloudflareApiTokenAttribute($value)
    {
        $this->attributes['cloudflare_api_token'] = $value ? encrypt($value) : null;
    }

    public function getCloudflareApiTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Ambil satu-satunya record settings (singleton pattern).
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'active_server'  => config('failover.this_server', 'jh'),
                'jh_ip'          => config('failover.jh_ip'),
                'upcloud_ip'     => config('failover.upcloud_ip'),
                'primary_domain' => config('failover.primary_domain', 'jezpro.id'),
                'standby_domain' => config('failover.standby_domain', 'jezpro.com'),
                'jh_agent_url'   => config('failover.jh_agent_url'),
                'upcloud_agent_url' => config('failover.upcloud_agent_url'),
            ]
        );
    }

    public function isJhActive(): bool
    {
        return $this->active_server === 'jh';
    }

    public function isUpcloudActive(): bool
    {
        return $this->active_server === 'upcloud';
    }

    public function getTargetIp(string $server): ?string
    {
        return $server === 'jh' ? $this->jh_ip : $this->upcloud_ip;
    }
}
