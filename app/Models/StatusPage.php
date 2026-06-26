<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StatusPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'slug',
        'public_key',
        'theme',
        'custom_domain',
        'password',
        'is_published',
        'custom_css',
        'custom_logo',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'custom_css' => 'array',
            'custom_logo' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (StatusPage $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title) . '-' . Str::random(6);
            }
            if (empty($page->public_key)) {
                $page->public_key = Str::random(64);
            }
        });
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function monitors()
    {
        return $this->belongsToMany(Monitor::class, 'status_page_monitors')
            ->withPivot('sort_order', 'is_visible')
            ->withTimestamps();
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function activeIncidents()
    {
        return $this->incidents()->where('status', '!=', 'resolved');
    }

    public function resolve()
    {
        return route('status.public', $this->slug);
    }
}
