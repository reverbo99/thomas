<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'sms_logs';

    protected $fillable = [
        'driver',
        'destination',
        'message',
        'message_id',
        'status',
        'failure_reason',
        'cost',
        'currency',
        'delivered_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];
}
