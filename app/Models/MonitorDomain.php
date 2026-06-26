<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorDomain extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'monitor_domain';

    protected $fillable = [
        'monitor_id',
        'domain',
        'expiry_date',
        'days_left',
        'registrar',
        'error_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'days_left' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
