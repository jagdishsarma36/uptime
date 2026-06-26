<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $table = 'notification_log';
    public $timestamps = false;

    protected $fillable = [
        'monitor_id',
        'notification_channel_id',
        'channel_type',
        'status',
        'message',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function monitor()
    {
        return $this->belongsTo(Monitor::class);
    }

    public function channel()
    {
        return $this->belongsTo(NotificationChannel::class);
    }
}
