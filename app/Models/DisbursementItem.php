<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementItem extends Model
{
    use HasFactory;

    public const SOURCE_BOOKING_REFUND = 'booking_refund';
    public const SOURCE_EXCESS_LUGGAGE = 'excess_luggage';
    public const SOURCE_PAYOUT_TRANSACTION = 'payout_transaction';
    public const SOURCE_SPECIAL_HIRE_WITHDRAWAL = 'special_hire_withdrawal';
    public const SOURCE_SPECIAL_HIRE_REFUND = 'special_hire_refund';

    public const CHANNEL_MOBILE = 'mobile_money';
    public const CHANNEL_BANK = 'bank';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NEEDS_DETAILS = 'needs_details';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'disbursement_id',
        'source_type',
        'source_id',
        'source_reference',
        'booking_code',
        'beneficiary_type',
        'beneficiary_user_id',
        'beneficiary_name',
        'payout_channel',
        'payout_number',
        'bank_name',
        'bank_bic',
        'amount',
        'currency',
        'status',
        'reference',
        'gateway_id',
        'gateway_status',
        'gateway_fee',
        'gateway_response',
        'attempts',
        'next_attempt_at',
        'last_error',
        'processed_at',
        'finalized_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'gateway_response' => 'array',
        'next_attempt_at' => 'datetime',
        'processed_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
    }

    public function sourceLabel(): string
    {
        return match ($this->source_type) {
            self::SOURCE_BOOKING_REFUND => __('system.disbursements.source_booking_refund'),
            self::SOURCE_EXCESS_LUGGAGE => __('system.disbursements.source_excess_luggage'),
            self::SOURCE_PAYOUT_TRANSACTION => __('system.disbursements.source_payout_transaction'),
            self::SOURCE_SPECIAL_HIRE_WITHDRAWAL => __('system.disbursements.source_special_hire_withdrawal'),
            self::SOURCE_SPECIAL_HIRE_REFUND => __('system.disbursements.source_special_hire_refund'),
            default => ucfirst(str_replace('_', ' ', (string) $this->source_type)),
        };
    }

    public function statusLabel(): string
    {
        $key = 'system.disbursements.item_status_' . $this->status;
        $translated = __($key);

        return $translated === $key ? ucfirst(str_replace('_', ' ', (string) $this->status)) : $translated;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'green',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_FAILED, self::STATUS_REVERSED => 'red',
            self::STATUS_NEEDS_DETAILS => 'amber',
            self::STATUS_SKIPPED => 'gray',
            default => 'yellow',
        };
    }

    public function channelLabel(): string
    {
        return $this->payout_channel === self::CHANNEL_BANK
            ? __('system.disbursements.channel_bank')
            : __('system.disbursements.channel_mobile');
    }

    public function isRetryable(): bool
    {
        return in_array($this->status, [
            self::STATUS_FAILED,
            self::STATUS_REVERSED,
        ], true);
    }
}
