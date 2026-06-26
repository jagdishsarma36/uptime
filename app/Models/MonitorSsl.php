<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorSsl extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'monitor_ssl';

    protected $fillable = [
        'monitor_id',
        'domain',
        'ssl_expiry_date',
        'days_left',
        'issuer',
        'serial_number',
        'is_valid',
        'error_message',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'ssl_expiry_date' => 'date',
            'days_left' => 'integer',
            'is_valid' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }
}
