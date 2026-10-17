<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disbursement extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';

    protected $fillable = [
        'batch_date',
        'reference',
        'currency',
        'status',
        'total_items',
        'total_amount',
        'paid_items',
        'paid_amount',
        'failed_items',
        'needs_details_items',
        'collected_at',
        'started_at',
        'completed_at',
        'last_attempt_at',
        'notes',
    ];

    protected $casts = [
        'batch_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'collected_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DisbursementItem::class);
    }

    public function pendingItems(): HasMany
    {
        return $this->items()->whereIn('status', [
            DisbursementItem::STATUS_PENDING,
            DisbursementItem::STATUS_PROCESSING,
        ]);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_COMPLETED_WITH_ERRORS,
        ], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PROCESSING => __('system.disbursements.status_processing'),
            self::STATUS_COMPLETED => __('system.disbursements.status_completed'),
            self::STATUS_COMPLETED_WITH_ERRORS => __('system.disbursements.status_completed_with_errors'),
            default => __('system.disbursements.status_open'),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_COMPLETED_WITH_ERRORS => 'amber',
            default => 'gray',
        };
    }

    /**
     * Recalculate counters from the item rows.
     */
    public function refreshTotals(): self
    {
        $items = $this->items()->get();

        $this->total_items = $items->count();
        $this->total_amount = round((float) $items->sum('amount'), 2);
        $this->paid_items = $items->where('status', DisbursementItem::STATUS_PAID)->count();
        $this->paid_amount = round((float) $items
            ->where('status', DisbursementItem::STATUS_PAID)
            ->sum('amount'), 2);
        $this->failed_items = $items->whereIn('status', [
            DisbursementItem::STATUS_FAILED,
            DisbursementItem::STATUS_REVERSED,
        ])->count();
        $this->needs_details_items = $items->where('status', DisbursementItem::STATUS_NEEDS_DETAILS)->count();
        $this->save();

        return $this;
    }
}
