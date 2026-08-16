<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExcessLuggageEscrow extends Model
{
    protected $table = 'excess_luggage_escrow';

    public const STATUS_HELD = 'held';
    public const STATUS_AWAITING_TOPUP = 'awaiting_topup';
    public const STATUS_RELEASED = 'released';
    public const STATUS_SURPLUS_HELD = 'surplus_held';
    public const STATUS_REFUND_PENDING = 'refund_pending';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    protected $casts = [
        'estimated_weight' => 'float',
        'estimated_fee' => 'float',
        'held_amount' => 'float',
        'actual_weight' => 'float',
        'actual_fee' => 'float',
        'delta_amount' => 'float',
        'released_fee' => 'float',
        'surplus_amount' => 'float',
        'admin_share' => 'float',
        'government_share' => 'float',
        'owner_share' => 'float',
        'refund_amount' => 'float',
        'weighed_at' => 'datetime',
        'released_at' => 'datetime',
        'refund_requested_at' => 'datetime',
        'refund_approved_at' => 'datetime',
    ];

    protected $fillable = [
        'booking_id',
        'booking_code',
        'estimated_weight',
        'estimated_fee',
        'held_amount',
        'actual_weight',
        'actual_fee',
        'delta_amount',
        'weight_verdict',
        'released_fee',
        'surplus_amount',
        'admin_share',
        'government_share',
        'owner_share',
        'status',
        'refund_amount',
        'weighed_at',
        'released_at',
        'refund_requested_at',
        'refund_approved_at',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ExcessLuggageEscrowTransaction::class, 'escrow_id');
    }

    public function isAssignable(): bool
    {
        return in_array($this->status, [
            self::STATUS_RELEASED,
            self::STATUS_SURPLUS_HELD,
        ], true);
    }
}
