<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'cpu_load_1min',
        'cpu_load_5min',
        'cpu_load_15min',
        'cpu_usage_percent',
        'memory_total',
        'memory_used',
        'memory_free',
        'memory_percent',
        'disk_total',
        'disk_used',
        'disk_free',
        'disk_percent',
        'network_rx_bytes',
        'network_tx_bytes',
        'process_count',
        'uptime_seconds',
        'is_online',
        'recorded_at',
    ];

    protected $casts = [
        'cpu_load_1min' => 'decimal:2',
        'cpu_load_5min' => 'decimal:2',
        'cpu_load_15min' => 'decimal:2',
        'cpu_usage_percent' => 'decimal:2',
        'memory_percent' => 'decimal:2',
        'disk_percent' => 'decimal:2',
        'is_online' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    /**
     * Relationship to FailoverServer
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(FailoverServer::class, 'server_id');
    }

    /**
     * Scope: Get metrics for specific server
     */
    public function scopeForServer($query, int $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * Scope: Get metrics within time range
     */
    public function scopeWithinPeriod($query, string $period = '24h')
    {
        $hours = match($period) {
            '1h' => 1,
            '6h' => 6,
            '12h' => 12,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };

        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Latest metrics first
     */
    public function scopeLatest($query, $column = 'recorded_at')
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * Get formatted memory usage
     */
    public function getFormattedMemoryAttribute(): string
    {
        if (!$this->memory_used || !$this->memory_total) {
            return 'N/A';
        }
        return sprintf('%d MB / %d MB (%.1f%%)', 
            $this->memory_used, 
            $this->memory_total, 
            $this->memory_percent
        );
    }

    /**
     * Get formatted disk usage
     */
    public function getFormattedDiskAttribute(): string
    {
        if (!$this->disk_used || !$this->disk_total) {
            return 'N/A';
        }
        return sprintf('%d GB / %d GB (%.1f%%)', 
            $this->disk_used, 
            $this->disk_total, 
            $this->disk_percent
        );
    }
}
