<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckResult extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'monitor_id',
        'status',
        'http_code',
        'response_time_ms',
        'message',
        'checked_from',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'http_code' => 'integer',
            'response_time_ms' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function scopeForMonitor($query, int $monitorId)
    {
        return $query->where('monitor_id', $monitorId);
    }

    public function scopeSince($query, $date)
    {
        return $query->where('checked_at', '>=', $date);
    }

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }
}
