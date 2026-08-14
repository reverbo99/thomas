<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PDOController;
use App\Http\Controllers\TigosecureController;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\VenderBalance;
use App\Services\VenderWalletDepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class VenderWalletController extends Controller
{
    public function showDepositForm()
    {
        $testMode = Setting::isTestMode();
        $testDepositReference = null;

        if ($testMode) {
            $testDepositReference = 'TESTVWDEP'
                . auth()->id()
                . 'T'
                . now()->format('YmdHis')
                . strtoupper(Str::random(6));
            Session::put('vendor_wallet_test_deposit_reference', $testDepositReference);
        } else {
            Session::forget('vendor_wallet_test_deposit_reference');
        }

        return view('vender.deposit', [
            'test_mode' => $testMode,
            'testDepositReference' => $testDepositReference,
        ]);
    }

    public function deposit(Request $request)
    {
        $testMode = Setting::isTestMode();
        $rules = [
            'amount' => 'required|numeric|min:1|max:999999999999.99',
            'deposit_phone' => 'nullable|string|max:30',
        ];
        $rules['payment_method'] = $testMode
            ? 'required|in:test_mode'
            : 'required|in:tigosecure,pdo,clickpesa';
        if ($testMode) {
            $rules['test_deposit_reference'] = 'required|string|max:64';
        }
        $request->validate($rules);

        $user = auth()->user();
        if ($testMode) {
            return $this->processTestDeposit($request, $user);
        }

        if ($request->payment_method == 'pdo') {
            $phone = $user->contact;
            $email = $user->email;
            $name = $user->name;
            $amount = $request->amount;

            Session::put('amount', $amount);

            $pdo = new PDOController();

            return $pdo->VenderinitiatePayment($amount, $name, 'vender', $phone, $email);
        }

        if ($request->payment_method === 'clickpesa') {
            $amount = (float) $request->amount;
            $minTzs = (float) env('CLICKPESA_MIN_AMOUNT_TZS', 908);
            if ($amount < $minTzs) {
                return back()->withInput()->with('error', __('assistance/transaction.clickpesa_min_amount', ['min' => $minTzs]));
            }

            $phone = $request->input('deposit_phone') ?: $user->contact ?: $user->phone;
            if (!$phone) {
                return back()->withInput()->with('error', __('assistance/transaction.clickpesa_enter_mobile'));
            }

            $msisdn = ClickPesaController::normalizeTanzaniaMsisdnForClickPesa((string) $phone);
            if (!$msisdn['ok']) {
                return back()->withInput()->with('error', $msisdn['error'] ?? __('assistance/transaction.invalid_phone_clickpesa'));
            }
            $phone = $msisdn['phone'];

            Session::forget(['booking', 'booking1', 'booking2', 'booking_form', 'is_round']);
            Session::put('amount', $amount);
            Session::put('vender', 'vender');

            $parts = preg_split('/\s+/', trim((string) $user->name), 2);
            $first = $parts[0] ?: 'Vendor';
            $last = $parts[1] ?? '';

            $orderId = 'VWDEP' . $user->id . 'T' . time();
            $clickpesa = new ClickPesaController();

            return $clickpesa->initiatePayment(
                (int) round($amount),
                $first,
                $last,
                $phone,
                $user->email ?? '',
                $orderId
            );
        }

        return back()->with('error', __('assistance/transaction.select_payment_method_error'));
    }

    private function processTestDeposit(Request $request, $user)
    {
        $sessionReference = (string) Session::get('vendor_wallet_test_deposit_reference', '');
        $requestReference = (string) $request->input('test_deposit_reference', '');

        if ($sessionReference === '' || !hash_equals($sessionReference, $requestReference)) {
            return back()
                ->withInput()
                ->with('error', __('assistance/transaction.test_mode_deposit_expired'));
        }

        $amount = round((float) $request->amount, 2);

        try {
            app(VenderWalletDepositService::class)->settleTestDeposit(
                (int) $user->id,
                $amount,
                $requestReference
            );
        } catch (\Throwable $e) {
            Log::error('Vendor wallet test-mode deposit failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', __('assistance/transaction.test_mode_deposit_failed'));
        }

        Session::forget(['vendor_wallet_test_deposit_reference', 'amount', 'vender']);

        return redirect()
            ->route('vender.transaction')
            ->with('success', __('assistance/transaction.test_mode_deposit_success', [
                'amount' => number_format($amount, 2),
            ]));
    }

    public function returned()
    {
        $amount = Session::get('amount');
        $user = auth()->user();
        if ($amount && $user->VenderBalances) {
            if (Schema::hasColumn('vender_balances', 'sell_cash_amount')) {
                $user->VenderBalances()->increment('sell_cash_amount', $amount);
            } else {
                $user->VenderBalances()->increment('amount', $amount);
            }
        }
        Session::forget(['amount', 'vender']);

        return redirect()->route('vender.transaction')->with('success', __('assistance/transaction.payment_processed_success'));
    }

    /**
     * PDO success redirect target (route exists; credits cash wallet).
     */
    public function depositSuccess()
    {
        return $this->returned();
    }

    /**
     * PDO cancel / failure.
     */
    public function depositFail()
    {
        Session::forget('amount');

        return redirect()->route('vender.wallet.deposit')->with('error', __('assistance/transaction.deposit_not_completed'));
    }

    /**
     * Move funds between commission wallet (`amount`) and cash wallet (`sell_cash_amount`).
     */
    public function transferInternal(Request $request)
    {
        $request->validate([
            'direction' => 'required|in:to_sell_cash,to_commission',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();
        $vb = $user->VenderBalances;
        if (!$vb || !Schema::hasColumn('vender_balances', 'sell_cash_amount')) {
            return back()->with('error', __('assistance/transaction.wallet_split_unavailable'));
        }

        $amt = round((float) $request->amount, 2);
        try {
            DB::transaction(function () use ($vb, $request, $amt) {
                $locked = VenderBalance::query()->whereKey($vb->id)->lockForUpdate()->firstOrFail();
                if ($request->direction === 'to_sell_cash') {
                    if ((float) $locked->amount < $amt) {
                        throw new \RuntimeException(__('assistance/transaction.insufficient_commission_balance'));
                    }
                    $locked->decrement('amount', $amt);
                    $locked->increment('sell_cash_amount', $amt);
                } else {
                    if ((float) $locked->sell_cash_amount < $amt) {
                        throw new \RuntimeException(__('assistance/transaction.insufficient_cash_balance'));
                    }
                    $locked->decrement('sell_cash_amount', $amt);
                    $locked->increment('amount', $amt);
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('assistance/transaction.transfer_completed'));
    }

    /**
     * One-time style migration: move the entire commission wallet balance to the cash wallet
     * when the cash wallet is still zero (typical right after the wallet split migration).
     */
    public function migrateLegacyBalanceToCash()
    {
        $user = auth()->user();
        $vb = $user->VenderBalances;
        if (!$vb || !Schema::hasColumn('vender_balances', 'sell_cash_amount')) {
            return back()->with('error', __('assistance/transaction.wallet_split_unavailable'));
        }

        try {
            DB::transaction(function () use ($vb) {
                $locked = VenderBalance::query()->whereKey($vb->id)->lockForUpdate()->firstOrFail();
                if ((float) ($locked->sell_cash_amount ?? 0) > 0) {
                    throw new \RuntimeException(__('assistance/transaction.cash_wallet_in_use'));
                }
                $amt = round((float) $locked->amount, 2);
                if ($amt <= 0) {
                    throw new \RuntimeException(__('assistance/transaction.no_commission_balance_to_move'));
                }
                $locked->decrement('amount', $amt);
                $locked->increment('sell_cash_amount', $amt);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('assistance/transaction.legacy_balance_moved'));
    }
}
