<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailoverServer extends Model
{
    protected $fillable = [
        'name',
        'label',
        'ip_address',
        'agent_url',
        'domain',
        'role',
        'is_active',
        'priority',
        'notes',
        'server_type', // web, database, both
        'ssh_host',
        'ssh_port',
        'ssh_user',
        'ssh_password',
        'ssh_key_file',
        'ssh_auth_type',
        'app_path',
        'cyberpanel_url',
        'cyberpanel_user',
        'cyberpanel_pass',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'db_database',
        'replication_user',
        'replication_password',
        'db_role',
        'replication_io_running',
        'replication_sql_running',
        'seconds_behind_master',
        'replication_checked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'ssh_port' => 'integer',
        'db_port' => 'integer',
        'replication_io_running' => 'boolean',
        'replication_sql_running' => 'boolean',
        'seconds_behind_master' => 'integer',
        'replication_checked_at' => 'datetime',
    ];

    protected $hidden = [
        'db_password',
        'replication_password',
        'cyberpanel_pass',
        'ssh_password',
    ];

    /**
     * Scope: Active servers only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Primary server
     */
    public function scopePrimary($query)
    {
        return $query->where('role', 'primary');
    }

    /**
     * Scope: Replica servers
     */
    public function scopeReplica($query)
    {
        return $query->where('role', 'replica');
    }

    /**
     * Scope: Order by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if this server is primary
     */
    public function isPrimary(): bool
    {
        return $this->role === 'primary';
    }

    /**
     * Check if this server is replica
     */
    public function isReplica(): bool
    {
        return $this->role === 'replica';
    }

    /**
     * Get badge color based on role
     */
    public function getRoleBadgeColorAttribute(): string
    {
        return $this->role === 'primary' ? 'success' : 'info';
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Encrypt/Decrypt database password
     */
    public function setDbPasswordAttribute($value)
    {
        $this->attributes['db_password'] = $value ? encrypt($value) : null;
    }

    public function getDbPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Encrypt/Decrypt replication password
     */
    public function setReplicationPasswordAttribute($value)
    {
        $this->attributes['replication_password'] = $value ? encrypt($value) : null;
    }

    public function getReplicationPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Encrypt/Decrypt SSH password
     */
    public function setSshPasswordAttribute($value)
    {
        $this->attributes['ssh_password'] = $value ? encrypt($value) : null;
    }

    public function getSshPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Check if replication is healthy
     */
    public function isReplicationHealthy(): bool
    {
        return $this->replication_io_running 
            && $this->replication_sql_running 
            && $this->seconds_behind_master !== null 
            && $this->seconds_behind_master < 10;
    }

    /**
     * Get replication status badge
     */
    public function getReplicationStatusBadgeAttribute(): string
    {
        if (!$this->replication_io_running || !$this->replication_sql_running) {
            return 'danger';
        }
        if ($this->seconds_behind_master === null) {
            return 'secondary';
        }
        if ($this->seconds_behind_master == 0) {
            return 'success';
        }
        if ($this->seconds_behind_master < 10) {
            return 'warning';
        }
        return 'danger';
    }
}
