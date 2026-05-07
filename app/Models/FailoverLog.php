<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailoverLog extends Model
{
    protected $fillable = [
        'action',
        'from_server',
        'to_server',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'triggered_by',
        'triggered_by_name',
        'message',
        'payload',
        'ip_address',
    ];

    protected $casts = [
        'payload'     => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Buat log baru dengan status pending.
     */
    public static function startLog(
        string $action,
        ?string $fromServer,
        ?string $toServer,
        ?int $userId,
        ?string $userName,
        ?string $ip = null
    ): self {
        return static::create([
            'action'            => $action,
            'from_server'       => $fromServer,
            'to_server'         => $toServer,
            'status'            => 'running',
            'started_at'        => now(),
            'triggered_by'      => $userId,
            'triggered_by_name' => $userName,
            'ip_address'        => $ip,
            'payload'           => [],
        ]);
    }

    /**
     * Tandai log sebagai sukses.
     */
    public function markSuccess(string $message = 'Failover completed successfully', array $payload = []): void
    {
        $this->update([
            'status'           => 'success',
            'finished_at'      => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
            'message'          => $message,
            'payload'          => array_merge($this->payload ?? [], $payload),
        ]);
    }

    /**
     * Tandai log sebagai gagal.
     */
    public function markFailed(string $message, array $payload = []): void
    {
        $this->update([
            'status'           => 'failed',
            'finished_at'      => now(),
            'duration_seconds' => now()->diffInSeconds($this->started_at),
            'message'          => $message,
            'payload'          => array_merge($this->payload ?? [], $payload),
        ]);
    }

    /**
     * Tambah step ke payload log.
     */
    public function addStep(string $step, string $status, string $detail = ''): void
    {
        $payload   = $this->payload ?? [];
        $payload[] = [
            'step'   => $step,
            'status' => $status,
            'detail' => $detail,
            'time'   => now()->toIso8601String(),
        ];
        $this->update(['payload' => $payload]);
    }

    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'triggered_by');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed'  => 'danger',
            'running' => 'warning',
            default   => 'secondary',
        };
    }
}
