<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialHirePaymentIntent extends Model
{
    protected $fillable = [
        'customer_user_id',
        'coaster_id',
        'payload',
        'amount',
        'phone',
        'clickpesa_ref',
        'status',
        'special_hire_order_id',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function coaster(): BelongsTo
    {
        return $this->belongsTo(Coaster::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SpecialHireOrder::class, 'special_hire_order_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }
}
