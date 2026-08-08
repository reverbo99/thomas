<?php

namespace App\Services;

use App\Models\Discount;

class DiscountService
{
    /**
     * Look up a coupon that is valid and applies to the given product.
     *
     * @return array{ok: bool, discount?: Discount, message?: string}
     */
    public function resolve(string $code, string $product): array
    {
        $code = trim($code);
        if ($code === '') {
            return ['ok' => false, 'message' => __('all.invalid_coupon_code')];
        }

        $discount = Discount::where('code', $code)->first();
        if (!$discount) {
            return ['ok' => false, 'message' => __('all.invalid_coupon_code')];
        }

        if (!$discount->isValid()) {
            return ['ok' => false, 'message' => __('all.coupon_expired_or_limit')];
        }

        if (!$discount->appliesTo($product)) {
            return ['ok' => false, 'message' => __('all.coupon_not_applicable')];
        }

        return ['ok' => true, 'discount' => $discount];
    }

    /**
     * Percentage of full amount (not stacked with other reductions).
     */
    public function reduceAmount(float $amount, float $percentage): float
    {
        $amount = max(0, $amount);
        $percentage = max(0, min(100, $percentage));

        return round($amount * (1 - $percentage / 100), 2);
    }

    /**
     * Apply coupon to ticket checkout: optional fare + optional luggage (% of full).
     * Insurance is never discounted.
     *
     * @return array{
     *   ok: bool,
     *   message?: string,
     *   discount?: ?Discount,
     *   fare: float,
     *   luggage_fee: float,
     *   luggage_fee_before: float,
     *   fare_before: float,
     *   discount_amount: float,
     *   price: float
     * }
     */
    public function applyToBookingCheckout(
        ?string $couponCode,
        float $fareBase,
        float $insurance,
        float $luggageFee,
        float $cancelAmount = 0
    ): array {
        $fareBase = max(0, $fareBase);
        $luggageFee = max(0, $luggageFee);
        $insurance = max(0, $insurance);
        $hasLuggage = $luggageFee > 0;

        $discount = null;
        $code = trim((string) $couponCode);

        if ($code !== '') {
            $discount = Discount::where('code', $code)->first();
            if (!$discount) {
                return ['ok' => false, 'message' => __('all.invalid_coupon_code')];
            }
            if (!$discount->isValid()) {
                return ['ok' => false, 'message' => __('all.coupon_expired_or_limit')];
            }
            if (!$discount->isApplicableToBookingCheckout($hasLuggage)) {
                return ['ok' => false, 'message' => __('all.coupon_not_applicable')];
            }
        }

        $discountedFare = $fareBase;
        $discountedLuggage = $luggageFee;

        if ($discount) {
            if ($discount->appliesTo(Discount::PRODUCT_TICKET)) {
                $discountedFare = $this->reduceAmount($fareBase, (float) $discount->percentage);
            }
            if ($hasLuggage && $discount->appliesTo(Discount::PRODUCT_LUGGAGE)) {
                $discountedLuggage = $this->reduceAmount($luggageFee, (float) $discount->percentage);
            }
        }

        $discountAmount = round(($fareBase - $discountedFare) + ($luggageFee - $discountedLuggage), 2);
        $price = $discountedFare + $insurance + $discountedLuggage - $cancelAmount;

        return [
            'ok' => true,
            'discount' => $discount,
            'fare' => $discountedFare,
            'fare_before' => $fareBase,
            'luggage_fee' => $discountedLuggage,
            'luggage_fee_before' => $luggageFee,
            'discount_amount' => $discountAmount,
            'price' => $price,
        ];
    }

    /**
     * @return array{ok: bool, message?: string, amount?: float, discount_amount?: float, amount_before?: float, code?: ?string, discount?: Discount}
     */
    public function applyToParcelAmount(float $grossAmount, ?string $couponCode): array
    {
        $grossAmount = max(0, $grossAmount);
        $code = trim((string) $couponCode);

        if ($code === '') {
            return [
                'ok' => true,
                'amount' => $grossAmount,
                'discount_amount' => 0,
                'amount_before' => $grossAmount,
                'code' => null,
            ];
        }

        $resolved = $this->resolve($code, Discount::PRODUCT_PARCEL);
        if (!$resolved['ok']) {
            return $resolved;
        }

        /** @var Discount $discount */
        $discount = $resolved['discount'];
        $amount = $this->reduceAmount($grossAmount, (float) $discount->percentage);

        return [
            'ok' => true,
            'amount' => $amount,
            'discount_amount' => round($grossAmount - $amount, 2),
            'amount_before' => $grossAmount,
            'code' => $discount->code,
            'discount' => $discount,
        ];
    }

    /**
     * Reduce special hire total; caller should recompute deposit/balance from returned total.
     *
     * @return array{ok: bool, message?: string, total?: float, discount_amount?: float, total_before?: float, code?: ?string, discount?: Discount}
     */
    public function applyToSpecialHireTotal(float $totalAmount, ?string $couponCode): array
    {
        $totalAmount = max(0, $totalAmount);
        $code = trim((string) $couponCode);

        if ($code === '') {
            return [
                'ok' => true,
                'total' => $totalAmount,
                'discount_amount' => 0,
                'total_before' => $totalAmount,
                'code' => null,
            ];
        }

        $resolved = $this->resolve($code, Discount::PRODUCT_SPECIAL_HIRE);
        if (!$resolved['ok']) {
            return $resolved;
        }

        /** @var Discount $discount */
        $discount = $resolved['discount'];
        $total = $this->reduceAmount($totalAmount, (float) $discount->percentage);

        return [
            'ok' => true,
            'total' => $total,
            'discount_amount' => round($totalAmount - $total, 2),
            'total_before' => $totalAmount,
            'code' => $discount->code,
            'discount' => $discount,
        ];
    }
}
