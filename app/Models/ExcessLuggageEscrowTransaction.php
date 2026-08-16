<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcessLuggageEscrowTransaction extends Model
{
    protected $table = 'excess_luggage_escrow_transactions';

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_TOP_UP = 'top_up';
    public const TYPE_RELEASE_ADMIN = 'release_admin';
    public const TYPE_RELEASE_GOVERNMENT = 'release_government';
    public const TYPE_RELEASE_OWNER = 'release_owner';
    public const TYPE_REFUND = 'refund';

    protected $casts = [
        'amount' => 'float',
        'meta' => 'array',
    ];

    protected $fillable = [
        'escrow_id',
        'booking_id',
        'type',
        'amount',
        'reference',
        'meta',
    ];

    public function escrow(): BelongsTo
    {
        return $this->belongsTo(ExcessLuggageEscrow::class, 'escrow_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
