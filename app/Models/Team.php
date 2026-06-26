<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'plan',
        'max_monitors',
        'check_interval_seconds',
        'retention_days',
        'is_active',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'max_monitors' => 'integer',
            'check_interval_seconds' => 'integer',
            'retention_days' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name) . '-' . Str::random(6);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function monitors()
    {
        return $this->hasMany(Monitor::class);
    }

    public function groups()
    {
        return $this->hasMany(MonitorGroup::class);
    }

    public function statusPages()
    {
        return $this->hasMany(StatusPage::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function notificationChannels()
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function maintenanceWindows()
    {
        return $this->hasMany(MaintenanceWindow::class);
    }

    public function invitations()
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'team_id');
    }

    public function isPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function canAddMonitor(): bool
    {
        $owner = $this->owner;
        if ($owner && $owner->hasRole('super-admin')) {
            return true;
        }
        return $this->monitors()->count() < $this->max_monitors;
    }

    public function isTrialActive(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }
}
