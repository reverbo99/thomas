<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenderWalletDeposit extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'amount',
        'payment_method',
        'reference',
        'status',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
