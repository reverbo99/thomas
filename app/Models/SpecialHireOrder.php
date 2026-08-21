<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SpecialHireOrder extends Model
{
    use HasFactory;

    protected $table = 'special_hire_orders';

    protected $fillable = [
        'order_code',
        'user_id',
        'customer_user_id',
        'coaster_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'pickup_location',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_location',
        'dropoff_latitude',
        'dropoff_longitude',
        'hire_date',
        'hire_time',
        'return_date',
        'return_time',
        'purpose',
        'passengers_count',
        'distance_km',
        'base_price',
        'price_per_km',
        'km_amount',
        'surcharge_percent',
        'surcharge_amount',
        'total_amount',
        'discount_code',
        'discount_amount',
        'total_before_discount',
        'deposit_amount',
        'balance_amount',
        'deposit_paid_at',
        'balance_paid_at',
        'owner_accepted_at',
        'passenger_seats',
        'clickpesa_deposit_ref',
        'clickpesa_balance_ref',
        'platform_commission_percent',
        'platform_commission_amount',
        'payment_method',
        'payment_status',
        'order_status',
        'notes',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'return_date' => 'date',
        'distance_km' => 'decimal:2',
        'base_price' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'km_amount' => 'decimal:2',
        'surcharge_percent' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_before_discount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'deposit_paid_at' => 'datetime',
        'balance_paid_at' => 'datetime',
        'owner_accepted_at' => 'datetime',
        'passenger_seats' => 'array',
        'platform_commission_percent' => 'decimal:2',
        'platform_commission_amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_code)) {
                $order->order_code = self::generateOrderCode();
            }
        });
    }

    /**
     * Generate unique order code.
     */
    public static function generateOrderCode()
    {
        $date = Carbon::now()->format('Ymd');
        $count = self::whereDate('created_at', Carbon::today())->count() + 1;
        return 'SH-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the user (special_hire owner) that owns this order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer user account for this order.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    /**
     * Get the coaster for this order.
     */
    public function coaster()
    {
        return $this->belongsTo(Coaster::class);
    }

    /**
     * Scope to get orders by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('order_status', 'pending');
    }

    /**
     * Scope to get confirmed orders.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('order_status', 'confirmed');
    }

    /**
     * Scope to get completed orders.
     */
    public function scopeCompleted($query)
    {
        return $query->where('order_status', 'completed');
    }

    /**
     * Scope to get today's orders.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('hire_date', Carbon::today());
    }

    /**
     * Scope to get paid orders.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Check if order is pending.
     */
    public function isPending()
    {
        return $this->order_status === 'pending';
    }

    /**
     * Check if order is paid.
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor()
    {
        return match($this->order_status) {
            'pending' => 'yellow',
            'confirmed' => 'blue',
            'in_progress' => 'purple',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get payment status badge color.
     */
    public function getPaymentStatusColor()
    {
        return match($this->payment_status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'refund_pending' => 'orange',
            'refunded' => 'red',
            default => 'gray',
        };
    }

    /**
     * Ordered lifecycle steps for UI steppers (excludes terminal / side paths).
     *
     * @return list<string>
     */
    public static function orderStatusPipeline(): array
    {
        return ['pending', 'confirmed', 'in_progress', 'completed'];
    }

    /**
     * Human label for order_status.
     */
    public static function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Next order_status values suggested for one-click advance (linear flow + cancel).
     *
     * @return list<string>
     */
    public function allowedNextOrderStatuses(): array
    {
        if (in_array($this->order_status, ['completed', 'cancelled'], true)) {
            return [];
        }

        $pipeline = self::orderStatusPipeline();
        $idx = array_search($this->order_status, $pipeline, true);
        $next = [];
        if ($idx !== false && $idx < count($pipeline) - 1) {
            $next[] = $pipeline[$idx + 1];
        }
        if ($this->order_status !== 'cancelled') {
            $next[] = 'cancelled';
        }

        return array_values(array_unique($next));
    }

    /**
     * Index of current status in pipeline, or -1 if not in main line (e.g. cancelled).
     */
    public function orderStatusPipelineIndex(): int
    {
        $pipeline = self::orderStatusPipeline();
        $idx = array_search($this->order_status, $pipeline, true);

        return $idx === false ? -1 : (int) $idx;
    }

    /**
     * Customer-app hire flow: optional deposit → (if balance > 0) driver accept → balance → passenger names → done.
     * When deposit_amount is null or zero, the deposit step is skipped.
     * When balance_amount is null or zero, wait_owner / pay_balance are skipped
     * (full payment via deposit → enter_passengers).
     * Legacy rows (both amounts null) use payment_status only.
     *
     * @return string pay_deposit|wait_owner|pay_balance|enter_passengers|done|legacy_pending
     */
    public function customerHireNextStep(): string
    {
        if ($this->deposit_amount === null && $this->balance_amount === null) {
            return $this->payment_status === 'paid' ? 'done' : 'legacy_pending';
        }

        $depositRequired = (float) ($this->deposit_amount ?? 0) > 0;
        if ($depositRequired && ! $this->deposit_paid_at) {
            return 'pay_deposit';
        }

        $balanceRequired = (float) ($this->balance_amount ?? 0) > 0;
        if ($balanceRequired && ! $this->owner_accepted_at) {
            return 'wait_owner';
        }
        if ($balanceRequired && ! $this->balance_paid_at) {
            return 'pay_balance';
        }

        $names = is_array($this->passenger_seats) ? $this->passenger_seats : [];
        if (count($names) < (int) $this->passengers_count) {
            return 'enter_passengers';
        }

        return 'done';
    }

    /**
     * Ensure the order can collect the full hire amount as ClickPesa deposit now
     * (confirm-booking / mobile pay-at-create flow).
     *
     * Older creates used deposit=null and balance=total → next step wait_owner,
     * which blocked pay-deposit. Converts unpaid orders to deposit=total, balance=0.
     *
     * @return bool true when the order was updated
     */
    public function ensureUpfrontDepositPayment(): bool
    {
        if ($this->deposit_paid_at || $this->balance_paid_at) {
            return false;
        }
        if (in_array($this->order_status, ['cancelled', 'completed'], true)) {
            return false;
        }

        $total = round((float) ($this->total_amount ?? 0), 2);
        if ($total <= 0) {
            return false;
        }

        $deposit = (float) ($this->deposit_amount ?? 0);
        $balance = (float) ($this->balance_amount ?? 0);

        // Already configured for full upfront deposit.
        if ($deposit >= $total - 0.01 && $balance <= 0.01 && ! $this->deposit_paid_at) {
            return false;
        }

        // Only rewrite when nothing has been collected and deposit is missing
        // (legacy: deposit null/0, full amount on balance → wait_owner).
        if ($deposit > 0.01 && $this->customerHireNextStep() === 'pay_deposit') {
            return false;
        }

        $this->update([
            'deposit_amount' => $total,
            'balance_amount' => 0,
        ]);
        $this->refresh();

        return true;
    }

    /**
     * Assigned driver may accept (records owner_accepted_at for hire pipeline).
     */
    public function canDriverAcceptHire(): bool
    {
        if ($this->owner_accepted_at) {
            return false;
        }
        if (in_array($this->order_status, ['cancelled', 'completed'], true)) {
            return false;
        }
        $depositRequired = (float) ($this->deposit_amount ?? 0) > 0;

        return ! $depositRequired || $this->deposit_paid_at;
    }

    /**
     * Assigned driver may decline before acceptance (or while waiting deposit if operator policy allows).
     */
    public function canDriverDeclineHire(): bool
    {
        if ($this->owner_accepted_at) {
            return false;
        }
        if (in_array($this->order_status, ['cancelled', 'completed'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Customer hire paperwork (paid + passengers) must NOT complete the trip.
     * Only the driver start/complete API sets order_status to in_progress / completed.
     * Kept as a no-op so legacy call sites remain harmless.
     */
    public function markCompletedIfHireFlowDone(): void
    {
        return;
    }

    /**
     * Net hire amount credited to operator (after platform commission on paid orders).
     */
    public function operatorNetAmount(): float
    {
        $total = (float) $this->total_amount;
        $fee = (float) ($this->platform_commission_amount ?? 0);

        return max(0, $total - $fee);
    }

    /**
     * Preview platform commission on a hire total (same percentage rule as balance settlement).
     *
     * @return array{platform_commission_percent: float, platform_commission_amount: float, operator_net_after_platform: float}
     */
    public static function previewPlatformCommission(float $hireTotal, ?float $ownerPlatformPercent): array
    {
        $pct = max(0.0, min(100.0, (float) ($ownerPlatformPercent ?? 0)));
        $amount = round($hireTotal * ($pct / 100.0), 2);

        return [
            'platform_commission_percent' => $pct,
            'platform_commission_amount' => $amount,
            'operator_net_after_platform' => round(max(0, $hireTotal - $amount), 2),
        ];
    }
}

