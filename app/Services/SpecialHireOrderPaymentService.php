<?php

namespace App\Services;

use App\Http\Controllers\ClickPesaController;
use App\Models\AdminWallet;
use App\Models\SpecialHireOrder;
use App\Models\User;
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
                    $order->refresh();
                    $order->markCompletedIfHireFlowDone();
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

                $order->update([
                    'balance_paid_at' => now(),
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    'payment_method' => 'clickpesa',
                    'clickpesa_balance_ref' => $sanitizedRef ?: $order->clickpesa_balance_ref,
                    'platform_commission_percent' => $pct,
                    'platform_commission_amount' => $platformFee,
                ]);

                if ($platformFee > 0) {
                    $wallet = AdminWallet::query()->find(1);
                    if ($wallet) {
                        $wallet->increment('balance', $platformFee);
                    }
                }

                $order->refresh();
                $order->markCompletedIfHireFlowDone();
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
}
