<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'status_page_id',
        'title',
        'description',
        'impact',
        'status',
        'started_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function statusPage()
    {
        return $this->belongsTo(StatusPage::class);
    }

    public function monitors()
    {
        return $this->belongsToMany(Monitor::class, 'incident_monitors');
    }

    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function resolve(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }
}
