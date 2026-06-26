<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Monitor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'group_id',
        'name',
        'url',
        'type',
        'method',
        'headers',
        'body',
        'expected_status_code',
        'expected_keyword',
        'tcp_host',
        'tcp_port',
        'check_interval_seconds',
        'status',
        'last_status_change',
        'last_checked_at',
        'next_check_at',
        'last_http_code',
        'last_response_time_ms',
        'last_error_message',
        'consecutive_failures',
        'is_paused',
        'ssl_enabled',
        'domain_enabled',
        'alert_threshold_seconds',
        'alert_on_down',
        'alert_on_up',
        'alert_on_ssl_expiry',
        'alert_on_domain_expiry',
        'ssl_alert_threshold_days',
        'domain_alert_threshold_days',
        'slug',
    ];

    protected $hidden = [
        'headers',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'expected_status_code' => 'integer',
            'tcp_port' => 'integer',
            'check_interval_seconds' => 'integer',
            'last_status_change' => 'datetime',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'last_http_code' => 'integer',
            'last_response_time_ms' => 'integer',
            'consecutive_failures' => 'integer',
            'is_paused' => 'boolean',
            'ssl_enabled' => 'boolean',
            'domain_enabled' => 'boolean',
            'alert_threshold_seconds' => 'integer',
            'alert_on_down' => 'boolean',
            'alert_on_up' => 'boolean',
            'alert_on_ssl_expiry' => 'boolean',
            'alert_on_domain_expiry' => 'boolean',
            'ssl_alert_threshold_days' => 'integer',
            'domain_alert_threshold_days' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Monitor $monitor) {
            if (empty($monitor->slug)) {
                $monitor->slug = Str::random(12);
            }
            if (is_null($monitor->next_check_at)) {
                $monitor->next_check_at = now()->addSeconds((int) $monitor->check_interval_seconds);
            }
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function group()
    {
        return $this->belongsTo(MonitorGroup::class, 'group_id');
    }

    public function checkResults()
    {
        return $this->hasMany(CheckResult::class);
    }

    public function sslChecks()
    {
        return $this->hasMany(MonitorSsl::class);
    }

    public function domainChecks()
    {
        return $this->hasMany(MonitorDomain::class);
    }

    public function statusPages()
    {
        return $this->belongsToMany(StatusPage::class, 'status_page_monitors')
            ->withPivot('sort_order', 'is_visible')
            ->withTimestamps();
    }

    public function incidents()
    {
        return $this->belongsToMany(Incident::class, 'incident_monitors');
    }

    public function maintenanceWindows()
    {
        return $this->belongsToMany(MaintenanceWindow::class, 'maintenance_monitors');
    }

    public function latestCheck()
    {
        return $this->hasOne(CheckResult::class)->latestOfMany('checked_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_paused', false)->where('status', '!=', 'paused');
    }

    public function scopeDueForCheck($query)
    {
        return $query->where('is_paused', false)
            ->where('next_check_at', '<=', now());
    }

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function isUp(): bool
    {
        return $this->status === 'up';
    }

    public function isDown(): bool
    {
        return $this->status === 'down';
    }

    public function isPaused(): bool
    {
        return $this->is_paused;
    }

    public function pause(): void
    {
        $this->update(['is_paused' => true, 'status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update([
            'is_paused' => false,
            'status' => 'unknown',
            'next_check_at' => now(),
        ]);
    }

    public function recordCheck(string $status, ?int $httpCode = null, ?int $responseTimeMs = null, ?string $message = null): void
    {
        $previousStatus = $this->status;

        $this->update([
            'status' => $status,
            'last_checked_at' => now(),
            'next_check_at' => now()->addSeconds((int) $this->check_interval_seconds),
            'last_http_code' => $httpCode,
            'last_response_time_ms' => $responseTimeMs,
            'last_error_message' => $message,
            'consecutive_failures' => $status === 'down' ? $this->consecutive_failures + 1 : 0,
            'last_status_change' => $previousStatus !== $status ? now() : $this->last_status_change,
        ]);

        $this->checkResults()->create([
            'status' => $status === 'paused' ? 'up' : $status,
            'http_code' => $httpCode,
            'response_time_ms' => $responseTimeMs,
            'message' => $message,
            'checked_at' => now(),
        ]);

        if ($previousStatus !== $status && $previousStatus !== 'unknown') {
            return;
        }
    }
}
