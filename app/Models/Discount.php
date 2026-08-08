<?php

namespace App\Models;

use App\Services\ParcelFlowService;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    public const PRODUCT_TICKET = 'ticket';
    public const PRODUCT_LUGGAGE = 'luggage';
    public const PRODUCT_PARCEL = 'parcel';
    public const PRODUCT_SPECIAL_HIRE = 'special_hire';

    protected $table = 'discount';

    protected $fillable = [
        'code',
        'used',
        'percentage',
        'expires_at',
        'applies_to_ticket',
        'applies_to_luggage',
        'applies_to_parcel',
        'applies_to_special_hire',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'applies_to_ticket' => 'boolean',
        'applies_to_luggage' => 'boolean',
        'applies_to_parcel' => 'boolean',
        'applies_to_special_hire' => 'boolean',
    ];

    /**
     * Check if the coupon is still valid (not expired and usage limit not reached).
     */
    public function isValid(): bool
    {
        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return false;
        }

        return $this->usedCount() < (int) $this->used;
    }

    /**
     * Total redeem count across tickets, parcels, and special hire (paid only).
     * Does not double-count unpaid drafts.
     */
    public function usedCount(): int
    {
        $bookings = $this->booking()->where('payment_status', 'Paid')->count();

        $parcels = Parcel::query()
            ->where('discount_code', $this->code)
            ->where('payment_status', ParcelFlowService::PAY_PAID)
            ->count();

        // Count once when deposit is paid or order is fully paid.
        $specialHires = SpecialHireOrder::query()
            ->where('discount_code', $this->code)
            ->where(function ($q) {
                $q->whereNotNull('deposit_paid_at')
                    ->orWhere('payment_status', 'paid');
            })
            ->count();

        return $bookings + $parcels + $specialHires;
    }

    public function appliesTo(string $product): bool
    {
        return match ($product) {
            self::PRODUCT_TICKET => (bool) ($this->applies_to_ticket ?? true),
            self::PRODUCT_LUGGAGE => (bool) ($this->applies_to_luggage ?? false),
            self::PRODUCT_PARCEL => (bool) ($this->applies_to_parcel ?? false),
            self::PRODUCT_SPECIAL_HIRE => (bool) ($this->applies_to_special_hire ?? false),
            default => false,
        };
    }

    /**
     * Coupon may be used on a ticket checkout if it targets fare and/or
     * luggage when excess luggage is on the booking.
     */
    public function isApplicableToBookingCheckout(bool $hasExcessLuggage): bool
    {
        if ($this->appliesTo(self::PRODUCT_TICKET)) {
            return true;
        }

        return $hasExcessLuggage && $this->appliesTo(self::PRODUCT_LUGGAGE);
    }

    public function appliesToLabels(): array
    {
        $labels = [];
        if ($this->appliesTo(self::PRODUCT_TICKET)) {
            $labels[] = __('system.pages.applies_to_ticket');
        }
        if ($this->appliesTo(self::PRODUCT_LUGGAGE)) {
            $labels[] = __('system.pages.applies_to_luggage');
        }
        if ($this->appliesTo(self::PRODUCT_PARCEL)) {
            $labels[] = __('system.pages.applies_to_parcel');
        }
        if ($this->appliesTo(self::PRODUCT_SPECIAL_HIRE)) {
            $labels[] = __('system.pages.applies_to_special_hire');
        }

        return $labels ?: [__('system.pages.applies_to_ticket')];
    }

    public function booking()
    {
        return $this->hasMany(Booking::class, 'discount', 'code');
    }

    public function parcels()
    {
        return $this->hasMany(Parcel::class, 'discount_code', 'code');
    }

    public function specialHireOrders()
    {
        return $this->hasMany(SpecialHireOrder::class, 'discount_code', 'code');
    }
}
