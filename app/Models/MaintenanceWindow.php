<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceWindow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'is_recurring',
        'recurrence_pattern',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_recurring' => 'boolean',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function monitors()
    {
        return $this->belongsToMany(Monitor::class, 'maintenance_monitors');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' ||
            ($this->status === 'scheduled' && $this->starts_at->isPast() && $this->ends_at->isFuture());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->orWhere(function ($q) {
                $q->where('status', 'scheduled')
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>=', now());
            });
    }
}
