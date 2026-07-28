<?php

namespace App\Services;

use App\Http\Controllers\ClickPesaController;
use App\Http\Controllers\SmsController;
use App\Models\AdminWallet;
use App\Models\Coaster;
use App\Models\SpecialHireOrder;
use App\Models\SpecialHirePaymentIntent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpecialHireOrderPaymentService
{
    /**
     * Apply verified ClickPesa payment to a special hire order (deposit or balance).
     */
    public function confirmFromVerifiedReference(SpecialHireOrder $order, string $type, object $verifyResponse, ?string $gatewayReference = null): void
    {
        $sanitizedRef = preg_replace(
            '/[^a-zA-Z0-9]/',
            '',
            (string) ($gatewayReference ?? $verifyResponse->reference ?? '')
        );

        DB::transaction(function () use ($order, $type, $verifyResponse, $sanitizedRef) {
            $order = SpecialHireOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($type === 'deposit') {
                if ($order->deposit_paid_at) {
                    return;
                }
                $expected = (float) ($order->deposit_amount ?? 0);
                $collected = (float) ($verifyResponse->amount ?? 0);
                if ($expected > 0 && $collected + 1 < $expected * 0.95) {
                    Log::warning('Special hire deposit amount mismatch', [
                        'order_id' => $order->id,
                        'expected' => $expected,
                        'collected' => $collected,
                    ]);
                }
                $fullPaidViaDeposit = (float) ($order->balance_amount ?? 0) <= 0;
                $depositUpdate = [
                    'deposit_paid_at' => now(),
                    'payment_method' => 'clickpesa',
                    'clickpesa_deposit_ref' => $sanitizedRef ?: $order->clickpesa_deposit_ref,
                ];
                if ($fullPaidViaDeposit) {
                    $owner = User::query()->find($order->user_id);
                    $pct = $order->platform_commission_percent;
                    if ($pct === null && $owner) {
                        $pct = (float) ($owner->special_hire_platform_percent ?? 0);
                    }
                    $pct = max(0, min(100, (float) $pct));
                    $platformFee = round(((float) $order->total_amount) * ($pct / 100), 2);

                    $depositUpdate['payment_status'] = 'paid';
                    $depositUpdate['platform_commission_percent'] = $pct;
                    $depositUpdate['platform_commission_amount'] = $platformFee;
                    if ($order->order_status !== 'cancelled') {
                        $depositUpdate['order_status'] = 'confirmed';
                    }
                    if (! $order->owner_accepted_at) {
                        $depositUpdate['owner_accepted_at'] = now();
                    }
                }
                $order->update($depositUpdate);

                if ($fullPaidViaDeposit) {
                    $platformFee = (float) ($order->fresh()->platform_commission_amount ?? 0);
                    if ($platformFee > 0) {
                        $wallet = AdminWallet::query()->find(1);
                        if ($wallet) {
                            $wallet->increment('balance', $platformFee);
                        }
                    }
                    $this->notifyDriverOfNewHire($order->fresh('coaster'));
                    $this->notifyCustomerOfConfirmedHire($order->fresh('coaster'));
                }

                return;
            }

            if ($type === 'balance') {
                if ($order->balance_paid_at) {
                    return;
                }
                if (! $order->owner_accepted_at) {
                    throw new \RuntimeException('Cannot confirm balance before the driver accepts the hire.');
                }
                $expected = (float) ($order->balance_amount ?? 0);
                $collected = (float) ($verifyResponse->amount ?? 0);
                if ($expected > 0 && $collected + 1 < $expected * 0.95) {
                    Log::warning('Special hire balance amount mismatch', [
                        'order_id' => $order->id,
                        'expected' => $expected,
                        'collected' => $collected,
                    ]);
                }

                $owner = User::query()->find($order->user_id);
                $pct = $order->platform_commission_percent;
                if ($pct === null && $owner) {
                    $pct = (float) ($owner->special_hire_platform_percent ?? 0);
                }
                $pct = max(0, min(100, (float) $pct));
                $platformFee = round(((float) $order->total_amount) * ($pct / 100), 2);

                $balanceUpdate = [
                    'balance_paid_at' => now(),
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    'payment_method' => 'clickpesa',
                    'clickpesa_balance_ref' => $sanitizedRef ?: $order->clickpesa_balance_ref,
                    'platform_commission_percent' => $pct,
                    'platform_commission_amount' => $platformFee,
                ];
                if (! $order->owner_accepted_at) {
                    $balanceUpdate['owner_accepted_at'] = now();
                }
                $order->update($balanceUpdate);

                if ($platformFee > 0) {
                    $wallet = AdminWallet::query()->find(1);
                    if ($wallet) {
                        $wallet->increment('balance', $platformFee);
                    }
                }
                $this->notifyDriverOfNewHire($order->fresh('coaster'));
                $this->notifyCustomerOfConfirmedHire($order->fresh('coaster'));
            }
        });
    }

    /**
     * Resolve payment type from ClickPesa reference stored on the order.
     */
    public static function resolveTypeFromReference(SpecialHireOrder $order, string $sanitizedReference): ?string
    {
        $dep = $order->clickpesa_deposit_ref ? preg_replace('/[^a-zA-Z0-9]/', '', $order->clickpesa_deposit_ref) : '';
        $bal = $order->clickpesa_balance_ref ? preg_replace('/[^a-zA-Z0-9]/', '', $order->clickpesa_balance_ref) : '';

        if ($dep !== '' && $dep === $sanitizedReference) {
            return 'deposit';
        }
        if ($bal !== '' && $bal === $sanitizedReference) {
            return 'balance';
        }

        return null;
    }

    /**
     * Start ClickPesa USSD push for deposit or balance; stores reference on the order.
     *
     * @return array{ok: bool, error?: string, response?: object}
     */
    public function initiateUssd(SpecialHireOrder $order, string $type, string $phone, string $firstName, string $lastName, string $email): array
    {
        $order = $order->fresh();
        $amount = $type === 'deposit'
            ? (float) ($order->deposit_amount ?? 0)
            : (float) ($order->balance_amount ?? 0);

        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Invalid payment amount'];
        }

        $minTzs = (float) env('CLICKPESA_MIN_AMOUNT_TZS', 908);
        if ($amount < $minTzs) {
            return ['ok' => false, 'error' => "Amount must be at least {$minTzs} TZS for ClickPesa mobile money."];
        }

        $suffix = (string) random_int(1000, 999999);
        $orderId = $type === 'deposit'
            ? 'SHDEP' . $order->id . 'T' . $suffix
            : 'SHBAL' . $order->id . 'T' . $suffix;

        $cp = new ClickPesaController();
        $resp = $cp->createCheckoutSession([
            'amount' => (int) round($amount),
            'order_id' => $orderId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'redirect_url' => route('clickpesa.callback'),
            'cancel_url' => route('clickpesa.cancel'),
        ]);

        if (is_string($resp)) {
            return ['ok' => false, 'error' => $resp];
        }

        $ref = isset($resp->orderReference)
            ? preg_replace('/[^a-zA-Z0-9]/', '', (string) $resp->orderReference)
            : preg_replace('/[^a-zA-Z0-9]/', '', $orderId);

        if ($type === 'deposit') {
            $order->update(['clickpesa_deposit_ref' => $ref]);
        } else {
            $order->update(['clickpesa_balance_ref' => $ref]);
        }

        return ['ok' => true, 'response' => $resp, 'order_reference' => $ref];
    }

    /**
     * Start ClickPesa USSD for a payment intent (no SpecialHireOrder yet).
     *
     * @return array{ok: bool, error?: string, response?: object, order_reference?: string}
     */
    public function initiateIntentUssd(
        SpecialHirePaymentIntent $intent,
        string $phone,
        string $firstName,
        string $lastName,
        string $email
    ): array {
        $amount = (float) $intent->amount;
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Invalid payment amount'];
        }

        $minTzs = (float) env('CLICKPESA_MIN_AMOUNT_TZS', 908);
        if ($amount < $minTzs) {
            return ['ok' => false, 'error' => "Amount must be at least {$minTzs} TZS for ClickPesa mobile money."];
        }

        $suffix = (string) random_int(1000, 999999);
        $orderId = 'SHPAY'.$intent->id.'T'.$suffix;

        $cp = new ClickPesaController();
        $resp = $cp->createCheckoutSession([
            'amount' => (int) round($amount),
            'order_id' => $orderId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'email' => $email,
            'redirect_url' => route('clickpesa.callback'),
            'cancel_url' => route('clickpesa.cancel'),
        ]);

        if (is_string($resp)) {
            return ['ok' => false, 'error' => $resp];
        }

        $ref = isset($resp->orderReference)
            ? preg_replace('/[^a-zA-Z0-9]/', '', (string) $resp->orderReference)
            : preg_replace('/[^a-zA-Z0-9]/', '', $orderId);

        $intent->update([
            'clickpesa_ref' => $ref,
            'phone' => $phone,
        ]);

        return ['ok' => true, 'response' => $resp, 'order_reference' => $ref];
    }

    /**
     * After ClickPesa verifies paid: create the hire order (already paid) and notify driver.
     * Idempotent if intent already consumed.
     */
    public function finalizePaidIntent(SpecialHirePaymentIntent $intent, object $verifyResponse, ?string $gatewayReference = null): SpecialHireOrder
    {
        return DB::transaction(function () use ($intent, $verifyResponse, $gatewayReference) {
            $intent = SpecialHirePaymentIntent::query()->whereKey($intent->id)->lockForUpdate()->firstOrFail();

            if ($intent->status === 'consumed' && $intent->special_hire_order_id) {
                return SpecialHireOrder::query()->with('coaster')->findOrFail($intent->special_hire_order_id);
            }

            if ($intent->status === 'expired' || $intent->isExpired()) {
                $intent->update(['status' => 'expired']);
                throw new \RuntimeException('Payment session expired. Start payment again.');
            }

            $sanitizedRef = preg_replace(
                '/[^a-zA-Z0-9]/',
                '',
                (string) ($gatewayReference ?? $verifyResponse->reference ?? $intent->clickpesa_ref ?? '')
            );

            $payload = $intent->payload ?? [];
            $coaster = Coaster::with(['pricing', 'driver'])->findOrFail($intent->coaster_id);
            $customer = User::query()->findOrFail($intent->customer_user_id);

            $winStart = Carbon::parse($payload['hire_date'])->startOfDay();
            $winEnd = Carbon::parse($payload['return_date'] ?? $payload['hire_date'])->startOfDay();
            if ($coaster->hasHireScheduleConflict($winStart, $winEnd)) {
                throw new \RuntimeException('This vehicle is no longer available for the selected hire dates.');
            }

            $distanceKm = (float) ($payload['distance_km'] ?? 0);
            $totalAmount = round((float) ($payload['total_amount'] ?? $intent->amount), 2);
            $priceData = $coaster->pricing
                ? $coaster->pricing->calculatePrice($distanceKm, $payload['hire_date'], $payload['hire_time'])
                : ['breakdown' => [
                    'km_amount' => 0,
                    'surcharge_percent' => 0,
                    'surcharge_amount' => 0,
                ]];
            $breakdown = $priceData['breakdown'] ?? $priceData;

            $owner = User::query()->find($coaster->user_id);
            $pct = $owner ? (float) ($owner->special_hire_platform_percent ?? 0) : 0.0;
            $pct = max(0, min(100, $pct));
            $platformFee = round($totalAmount * ($pct / 100), 2);

            $collected = (float) ($verifyResponse->amount ?? $totalAmount);
            if ($totalAmount > 0 && $collected + 1 < $totalAmount * 0.95) {
                Log::warning('Special hire intent amount mismatch', [
                    'intent_id' => $intent->id,
                    'expected' => $totalAmount,
                    'collected' => $collected,
                ]);
            }

            $order = SpecialHireOrder::create([
                'user_id' => $coaster->user_id,
                'customer_user_id' => $customer->id,
                'coaster_id' => $coaster->id,
                'customer_name' => $customer->name,
                'customer_phone' => $payload['customer_phone'] ?? $intent->phone,
                'customer_email' => $customer->email,
                'pickup_location' => $payload['pickup_location'],
                'pickup_latitude' => $payload['pickup_latitude'] ?? null,
                'pickup_longitude' => $payload['pickup_longitude'] ?? null,
                'dropoff_location' => $payload['dropoff_location'],
                'dropoff_latitude' => $payload['dropoff_latitude'] ?? null,
                'dropoff_longitude' => $payload['dropoff_longitude'] ?? null,
                'hire_date' => $payload['hire_date'],
                'hire_time' => $payload['hire_time'],
                'return_date' => $payload['return_date'] ?? null,
                'return_time' => $payload['return_time'] ?? null,
                'passengers_count' => $payload['passengers_count'],
                'purpose' => $payload['purpose'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'distance_km' => $distanceKm,
                'base_price' => 0,
                'price_per_km' => $coaster->pricing->price_per_km ?? 0,
                'km_amount' => $breakdown['km_amount'] ?? 0,
                'surcharge_percent' => $breakdown['surcharge_percent'] ?? 0,
                'surcharge_amount' => $breakdown['surcharge_amount'] ?? 0,
                'total_amount' => $totalAmount,
                'deposit_amount' => $totalAmount,
                'balance_amount' => 0,
                'deposit_paid_at' => now(),
                'payment_method' => (string) ($verifyResponse->payment_method ?? 'clickpesa'),
                'clickpesa_deposit_ref' => $sanitizedRef ?: $intent->clickpesa_ref,
                'payment_status' => 'paid',
                'order_status' => 'confirmed',
                'owner_accepted_at' => now(),
                'platform_commission_percent' => $pct,
                'platform_commission_amount' => $platformFee,
            ]);

            if ($platformFee > 0) {
                $wallet = AdminWallet::query()->find(1);
                if ($wallet) {
                    $wallet->increment('balance', $platformFee);
                }
            }

            $intent->update([
                'status' => 'consumed',
                'paid_at' => now(),
                'special_hire_order_id' => $order->id,
                'clickpesa_ref' => $sanitizedRef ?: $intent->clickpesa_ref,
            ]);

            $freshOrder = $order->fresh('coaster');
            $this->notifyDriverOfNewHire($freshOrder);
            $this->notifyCustomerOfConfirmedHire($freshOrder);

            return $freshOrder;
        });
    }

    public function notifyDriverOfNewHire(SpecialHireOrder $order): void
    {
        $order->loadMissing('coaster.driver');
        $coaster = $order->coaster;
        $driver = $coaster?->driver;
        if (! $driver) {
            return;
        }

        $phone = $driver->contact ?: $driver->phone;
        if ($phone) {
            $msg = 'HISGC: New special hire '.$order->order_code.' on '.($coaster->name ?? 'your vehicle').'. Open the Driver app to Accept or Decline.';
            app(SmsController::class)->sms_send($phone, $msg);
        }

        try {
            app(\App\Services\FcmService::class)->sendToUser(
                $driver,
                'New hire request',
                'New special hire '.$order->order_code.' on '.($coaster->name ?? 'your vehicle').'. Tap to Accept or Decline.',
                [
                    'type' => 'hire_request',
                    'order_id' => (string) $order->id,
                    'order_code' => (string) $order->order_code,
                ],
                'bushire_driver'
            );
        } catch (\Throwable $e) {
            Log::warning('FCM driver notify failed: '.$e->getMessage());
        }
    }

    public function notifyCustomerOfConfirmedHire(SpecialHireOrder $order): void
    {
        $order->loadMissing('coaster.user');
        $coaster = $order->coaster;
        $companyName = $coaster?->user?->name ?? 'HIGHLINK';
        $plateNumber = $coaster?->plate_number ?? 'N/A';
        $pickup = $order->pickup_location ?? 'N/A';
        $dropoff = $order->dropoff_location ?? 'N/A';
        $hireDate = $order->hire_date ? $order->hire_date->format('d/m/Y') : 'N/A';
        $hireTime = $order->hire_time ?? 'N/A';
        $reportTime = $hireTime;
        if ($hireTime !== 'N/A') {
            try {
                $reportTime = Carbon::parse($hireDate . ' ' . $hireTime)->subMinutes(30)->format('h:i A');
            } catch (\Exception $e) {
                $reportTime = $hireTime;
            }
        }

        $msg = "Mpendwa {$order->customer_name}, Karibu {$companyName}, wewe na wenzako mtasafiri na basi namba {$plateNumber} kutoka {$pickup} Kwenda {$dropoff} Tarehe {$hireDate}. Abiria wote mnaombwa kuwasili {$pickup} angalau saa {$reportTime} tayari kwa safari. namba yako ni {$order->order_code}. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.";

        if (!empty($order->customer_phone)) {
            try {
                app(SmsController::class)->sms_send($order->customer_phone, $msg);
            } catch (\Exception $e) {
                Log::warning('Special hire customer SMS failed: ' . $e->getMessage(), ['order_id' => $order->id]);
            }
        }
    }
}
