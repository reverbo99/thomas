<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Campany;
use App\Models\Disbursement;
use App\Models\DisbursementItem;
use App\Models\Refund;
use App\Models\Setting;
use App\Models\SpecialHireOrder;
use App\Models\SpecialHireWithdrawalRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VenderBalance;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DisbursementService
{
    public function __construct(private ClickPesaPayoutService $gateway)
    {
    }

    /* ---------------------------------------------------------------------
     | Collection
     * ------------------------------------------------------------------- */

    /**
     * Create (if needed) and fill the batch for a calendar date.
     *
     * Today's batch is topped up on every call (each scheduled tick, page
     * load and "Collect now") so a refund or payout created during the day is
     * visible and payable on the disbursements page immediately. Past batches
     * are frozen once collected unless $force is passed.
     */
    public function collectForDate(CarbonInterface|string|null $date = null, bool $force = false): Disbursement
    {
        $day = $date instanceof CarbonInterface ? Carbon::parse($date) : Carbon::parse($date ?: 'today');
        $day = $day->startOfDay();

        $batch = Disbursement::query()
            ->whereDate('batch_date', $day->toDateString())
            ->first();

        if (! $batch) {
            try {
                $batch = Disbursement::create([
                    'batch_date' => $day->toDateString(),
                    'reference' => $this->batchReference($day),
                    'currency' => $this->gateway->currency(),
                    'status' => Disbursement::STATUS_OPEN,
                ]);
            } catch (Throwable $e) {
                // Another request created today's batch between the lookup and
                // the insert (batch_date is unique); use that one.
                $batch = Disbursement::query()
                    ->whereDate('batch_date', $day->toDateString())
                    ->first();

                if (! $batch) {
                    throw $e;
                }
            }
        }

        // Only freeze a batch once it belongs to a past day. The collectors are
        // idempotent (one item per source), so re-running them for today cannot
        // duplicate an obligation.
        if (! $force && $batch->collected_at !== null && ! $day->isToday()) {
            return $batch->refreshTotals();
        }

        $errors = [];

        foreach ([
            'booking_refunds' => fn () => $this->collectBookingRefunds($batch),
            'excess_luggage' => fn () => $this->collectExcessLuggageRefunds($batch),
            'payout_transactions' => fn () => $this->collectPayoutTransactions($batch),
            'special_hire_withdrawals' => fn () => $this->collectSpecialHireWithdrawals($batch),
            'special_hire_refunds' => fn () => $this->collectSpecialHireRefunds($batch),
        ] as $source => $collector) {
            try {
                $collector();
            } catch (Throwable $e) {
                // One broken source must not stop the other obligations from
                // being collected for the day.
                $errors[] = $source . ': ' . $e->getMessage();
                Log::error('Disbursement collection failed for source', [
                    'source' => $source,
                    'batch_id' => $batch->id,
                    'batch_date' => $batch->batch_date?->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($errors !== []) {
            $batch->update([
                'notes' => trim((string) $batch->notes . "\nCollection errors:\n- " . implode("\n- ", $errors)),
            ]);
        }

        $batch->update(['collected_at' => now()]);
        $this->refreshBatch($batch->fresh());

        return $batch->fresh();
    }

    public function ensureTodayBatch(): Disbursement
    {
        return $this->collectForDate(Carbon::today());
    }

    /**
     * Approve a pending excess-luggage refund and put it in today's batch.
     *
     * Approval must not mark the booking "refunded" on its own: that hid the
     * refund from the disbursements page and the passenger was never paid.
     * The source record is only finalised after the payout succeeds.
     */
    public function approveExcessLuggageRefund(Booking $booking, ?User $actor = null): DisbursementItem
    {
        $booking = Booking::query()->whereKey($booking->getKey())->first();

        if (! $booking) {
            throw new \RuntimeException('Booking not found.');
        }

        $existing = $this->findItem(DisbursementItem::SOURCE_EXCESS_LUGGAGE, $booking->id, true);
        if ($existing) {
            return $existing;
        }

        if ($booking->luggage_payment_status !== ExcessLuggageService::PAYMENT_REFUND_PENDING) {
            throw new \RuntimeException(__('vender/luggage.refund_not_pending'));
        }

        app(ExcessLuggageService::class)->markRefundApproved($booking, $actor);

        $this->collectForDate(Carbon::today());

        $item = $this->findItem(DisbursementItem::SOURCE_EXCESS_LUGGAGE, $booking->id, true);
        if (! $item) {
            throw new \RuntimeException(__('vender/luggage.no_refund_due'));
        }

        return $item;
    }

    /**
     * Queue a luggage refund that is already approved (or refunded but never
     * batched) without changing the authoritative source state.
     */
    public function queueExcessLuggageRefund(Booking $booking): ?DisbursementItem
    {
        $this->collectForDate(Carbon::today());

        return $this->findItem(DisbursementItem::SOURCE_EXCESS_LUGGAGE, $booking->id, true);
    }

    /**
     * Queue a special-hire customer refund in today's batch.
     */
    public function queueSpecialHireRefund(SpecialHireOrder $order): ?DisbursementItem
    {
        $this->collectForDate(Carbon::today());

        return $this->findItem(DisbursementItem::SOURCE_SPECIAL_HIRE_REFUND, $order->id, true);
    }

    /**
     * Stop queued payouts for a source that is no longer owed (for example a
     * refund request rejected after today's batch was already filled), so the
     * batch cannot pay out a cancelled refund.
     */
    public function cancelPendingItems(string $sourceType, int $sourceId, string $reason = 'Source is no longer owed.'): int
    {
        $items = DisbursementItem::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->whereIn('status', [
                DisbursementItem::STATUS_PENDING,
                DisbursementItem::STATUS_FAILED,
                DisbursementItem::STATUS_NEEDS_DETAILS,
                DisbursementItem::STATUS_REVERSED,
            ])
            ->whereNull('finalized_at')
            ->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $batchIds = $items->pluck('disbursement_id')->unique();

        DisbursementItem::query()
            ->whereKey($items->modelKeys())
            ->update([
                'status' => DisbursementItem::STATUS_SKIPPED,
                'last_error' => $reason,
                'processed_at' => now(),
                'next_attempt_at' => null,
            ]);

        foreach ($batchIds as $batchId) {
            $this->refreshBatch(Disbursement::find($batchId));
        }

        return $items->count();
    }

    private function findItem(string $sourceType, int $sourceId, bool $reopen = false): ?DisbursementItem
    {
        $item = DisbursementItem::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        // A refund that was rejected once (item skipped) or failed earlier must
        // be payable again when it is re-requested and approved. The unique
        // source row prevents creating a second item, so reopen it instead.
        if ($item && $reopen && $item->finalized_at === null && in_array($item->status, [
            DisbursementItem::STATUS_SKIPPED,
            DisbursementItem::STATUS_FAILED,
            DisbursementItem::STATUS_REVERSED,
            DisbursementItem::STATUS_NEEDS_DETAILS,
        ], true)) {
            $item->update([
                'status' => DisbursementItem::STATUS_PENDING,
                'last_error' => null,
                'next_attempt_at' => null,
                'processed_at' => null,
            ]);

            $this->refreshBatch($item->disbursement);
            $item->refresh();
        }

        return $item;
    }

    private function collectBookingRefunds(Disbursement $batch): void
    {
        if (! Schema::hasTable('refund')) {
            return;
        }

        $query = Refund::query()
            ->whereIn('status', ['Pending', 'pending', 'Approved', 'approved'])
            ->whereNull('disbursed_at')
            ->orderBy('id');

        foreach ($query->cursor() as $refund) {
            if ($this->itemExists(DisbursementItem::SOURCE_BOOKING_REFUND, $refund->id)) {
                continue;
            }

            $amount = round((float) $refund->amount, 2);
            if ($amount <= 0) {
                continue;
            }

            $booking = null;
            if (! empty($refund->booking_code)) {
                $booking = Booking::where('booking_code', $refund->booking_code)->first();
            }
            if (! $booking && ! empty($refund->booking_id)) {
                $booking = Booking::find($refund->booking_id);
            }

            $name = trim((string) ($refund->fullname ?: ($booking->customer_name ?? '')));
            $number = trim((string) ($refund->phone ?: ($booking->customer_phone ?? '')));
            $channel = $this->resolveChannel(
                $refund->payout_method ?: null,
                $number,
                $refund->bank_name ?: null,
                $refund->bank_bic ?: null
            );

            $item = $this->createItem($batch, [
                'source_type' => DisbursementItem::SOURCE_BOOKING_REFUND,
                'source_id' => $refund->id,
                'source_reference' => $refund->booking_code ?: ('REF-' . $refund->id),
                'booking_code' => $refund->booking_code,
                'beneficiary_type' => 'customer',
                'beneficiary_name' => $name !== '' ? $name : null,
                'payout_channel' => $channel['channel'],
                'payout_number' => $channel['number'],
                'bank_name' => $channel['bank_name'],
                'bank_bic' => $channel['bank_bic'],
                'amount' => $amount,
                'status' => $channel['ready'] ? DisbursementItem::STATUS_PENDING : DisbursementItem::STATUS_NEEDS_DETAILS,
                'last_error' => $channel['error'],
            ]);

            // Auto-approve the source request once it is in a batch.
            if ($item && strtolower((string) $refund->status) === 'pending') {
                $refund->update([
                    'status' => 'Approved',
                    'payout_method' => $channel['channel'],
                    'bank_name' => $channel['bank_name'],
                    'bank_bic' => $channel['bank_bic'],
                    'disbursement_item_id' => $item->id,
                ]);
            } elseif ($item && ! $refund->disbursement_item_id) {
                $refund->update(['disbursement_item_id' => $item->id]);
            }
        }
    }

    private function collectExcessLuggageRefunds(Disbursement $batch): void
    {
        // "refunded" rows are included on purpose: the admin approval button
        // used to mark a refund as processed without ever queueing a payout, so
        // those records have no disbursement item yet. Including them catches
        // up every luggage refund that is owed but was never batched; rows that
        // were paid through a batch already have an item and are skipped.
        $bookings = Booking::query()
            ->whereIn('luggage_payment_status', [
                ExcessLuggageService::PAYMENT_REFUND_PENDING,
                ExcessLuggageService::PAYMENT_REFUNDED,
            ])
            ->whereNotNull('luggage_refund_amount')
            ->where('luggage_refund_amount', '<', 0)
            ->orderBy('id')
            ->cursor();

        foreach ($bookings as $booking) {
            if ($this->itemExists(DisbursementItem::SOURCE_EXCESS_LUGGAGE, $booking->id)) {
                continue;
            }

            $amount = round(abs((float) $booking->luggage_refund_amount), 2);
            if ($amount <= 0) {
                continue;
            }

            $channel = $this->resolveChannel('mobile_money', (string) $booking->customer_phone);

            $this->createItem($batch, [
                'source_type' => DisbursementItem::SOURCE_EXCESS_LUGGAGE,
                'source_id' => $booking->id,
                'source_reference' => $booking->booking_code,
                'booking_code' => $booking->booking_code,
                'beneficiary_type' => 'customer',
                'beneficiary_name' => $booking->customer_name,
                'payout_channel' => $channel['channel'],
                'payout_number' => $channel['number'],
                'amount' => $amount,
                'status' => $channel['ready'] ? DisbursementItem::STATUS_PENDING : DisbursementItem::STATUS_NEEDS_DETAILS,
                'last_error' => $channel['error'],
            ]);
        }
    }

    private function collectPayoutTransactions(Disbursement $batch): void
    {
        $transactions = Transaction::query()
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->with(['user.VenderAccount', 'campany.busOwnerAccount', 'campany.user'])
            ->orderBy('id')
            ->cursor();

        foreach ($transactions as $transaction) {
            if ($this->itemExists(DisbursementItem::SOURCE_PAYOUT_TRANSACTION, $transaction->id)) {
                continue;
            }

            $amount = round((float) $transaction->amount, 2);
            if ($amount <= 0) {
                continue;
            }

            $isCompany = (int) ($transaction->campany_id ?? 0) > 0;
            $beneficiary = transaction_vendor_user($transaction);
            if (! $beneficiary && $isCompany && $transaction->campany) {
                $beneficiary = $transaction->campany->user;
            }

            $venderAccount = $beneficiary?->VenderAccount;
            $ownerAccount = $transaction->campany?->busOwnerAccount;
            $isBank = transaction_is_bank_payment_method($transaction->payment_method);

            if ($isBank) {
                $bankName = $venderAccount?->bank_name ?: $ownerAccount?->bank_name;
                $number = $venderAccount?->bank_number ?: $ownerAccount?->bank_number ?: $transaction->payment_number;
                $channel = $this->resolveChannel('bank', (string) $number, $bankName);
                $accountName = $beneficiary?->name ?: ($transaction->campany?->name ?? null);
            } else {
                $channel = $this->resolveChannel('mobile_money', (string) $transaction->payment_number);
                $accountName = $beneficiary?->name ?: ($transaction->campany?->name ?? null);
            }

            $this->createItem($batch, [
                'source_type' => DisbursementItem::SOURCE_PAYOUT_TRANSACTION,
                'source_id' => $transaction->id,
                'source_reference' => 'PAY-' . $transaction->id,
                'booking_code' => null,
                'beneficiary_type' => $isCompany ? 'bus_owner' : 'vendor',
                'beneficiary_user_id' => $beneficiary?->id ?? $transaction->user_id,
                'beneficiary_name' => $accountName,
                'payout_channel' => $channel['channel'],
                'payout_number' => $channel['number'],
                'bank_name' => $channel['bank_name'],
                'bank_bic' => $channel['bank_bic'],
                'amount' => $amount,
                'status' => $channel['ready'] ? DisbursementItem::STATUS_PENDING : DisbursementItem::STATUS_NEEDS_DETAILS,
                'last_error' => $channel['error'],
            ]);
        }
    }

    private function collectSpecialHireWithdrawals(Disbursement $batch): void
    {
        $withdrawals = SpecialHireWithdrawalRequest::query()
            ->where('status', SpecialHireWithdrawalRequest::STATUS_PENDING)
            ->with('user.VenderAccount')
            ->orderBy('id')
            ->cursor();

        foreach ($withdrawals as $withdrawal) {
            if ($this->itemExists(DisbursementItem::SOURCE_SPECIAL_HIRE_WITHDRAWAL, $withdrawal->id)) {
                continue;
            }

            $amount = round((float) $withdrawal->amount, 2);
            if ($amount <= 0) {
                continue;
            }

            $method = (string) $withdrawal->payment_method;
            $isBank = $this->looksLikeBankMethod($method);
            $bankName = $isBank
                ? ($this->bankNameFromMethod($method) ?: $withdrawal->user?->VenderAccount?->bank_name)
                : null;

            $channel = $this->resolveChannel(
                $isBank ? 'bank' : 'mobile_money',
                (string) $withdrawal->payment_number,
                $bankName
            );

            $item = $this->createItem($batch, [
                'source_type' => DisbursementItem::SOURCE_SPECIAL_HIRE_WITHDRAWAL,
                'source_id' => $withdrawal->id,
                'source_reference' => 'WDR-' . $withdrawal->id,
                'beneficiary_type' => 'special_hire_owner',
                'beneficiary_user_id' => $withdrawal->user_id,
                'beneficiary_name' => $withdrawal->user?->name,
                'payout_channel' => $channel['channel'],
                'payout_number' => $channel['number'],
                'bank_name' => $channel['bank_name'],
                'bank_bic' => $channel['bank_bic'],
                'amount' => $amount,
                'status' => $channel['ready'] ? DisbursementItem::STATUS_PENDING : DisbursementItem::STATUS_NEEDS_DETAILS,
                'last_error' => $channel['error'],
            ]);

            if ($item && $withdrawal->status === SpecialHireWithdrawalRequest::STATUS_PENDING) {
                $withdrawal->update(['status' => SpecialHireWithdrawalRequest::STATUS_APPROVED]);
            }
        }
    }

    private function collectSpecialHireRefunds(Disbursement $batch): void
    {
        // "refunded" rows without a batch item were marked refunded outside the
        // disbursement pipeline (operator action / API); they are still owed and
        // must appear on the disbursements page. Rows already paid have an item
        // and are skipped below.
        $orders = SpecialHireOrder::query()
            ->whereIn('payment_status', ['refund_pending', 'refunded'])
            ->orderBy('id')
            ->cursor();

        foreach ($orders as $order) {
            if ($this->itemExists(DisbursementItem::SOURCE_SPECIAL_HIRE_REFUND, $order->id)) {
                continue;
            }

            $amount = $this->specialHireRefundAmount($order);
            if ($amount <= 0) {
                continue;
            }

            $notes = (string) ($order->notes ?? '');
            $phone = $this->matchNote($notes, '/Refund phone:\s*([0-9+\s]+)/i');
            $bankRaw = $this->matchNote($notes, '/Refund bank:\s*([^|\n]+)/i');

            if ($bankRaw !== '') {
                $bankName = $this->bankNameFromMethod($bankRaw);
                $channel = $this->resolveChannel('bank', $bankRaw, $bankName);
            } else {
                $channel = $this->resolveChannel(
                    'mobile_money',
                    $phone !== '' ? $phone : (string) ($order->customer_phone ?: $order->customer?->phone)
                );
            }

            $this->createItem($batch, [
                'source_type' => DisbursementItem::SOURCE_SPECIAL_HIRE_REFUND,
                'source_id' => $order->id,
                'source_reference' => $order->order_code,
                'booking_code' => $order->order_code,
                'beneficiary_type' => 'customer',
                'beneficiary_user_id' => $order->customer_user_id,
                'beneficiary_name' => $order->customer_name,
                'payout_channel' => $channel['channel'],
                'payout_number' => $channel['number'],
                'bank_name' => $channel['bank_name'],
                'bank_bic' => $channel['bank_bic'],
                'amount' => $amount,
                'status' => $channel['ready'] ? DisbursementItem::STATUS_PENDING : DisbursementItem::STATUS_NEEDS_DETAILS,
                'last_error' => $channel['error'],
            ]);
        }
    }

    private function specialHireRefundAmount(SpecialHireOrder $order): float
    {
        $paid = 0.0;
        if ($order->deposit_paid_at) {
            $paid += (float) ($order->deposit_amount ?? 0);
        }
        if ($order->balance_paid_at) {
            $paid += (float) ($order->balance_amount ?? 0);
        }

        if ($paid <= 0) {
            $paid = (float) ($order->total_amount ?? 0);
        }

        return round(max(0, $paid), 2);
    }

    private function matchNote(string $notes, string $pattern): string
    {
        if (preg_match($pattern, $notes, $matches)) {
            return trim((string) $matches[1]);
        }

        return '';
    }

    /**
     * Decide mobile vs bank and whether the item has enough detail to pay.
     *
     * @return array{channel: string, number: string|null, bank_name: string|null, bank_bic: string|null, ready: bool, error: string|null}
     */
    public function resolveChannel(
        ?string $preferred,
        string $number,
        ?string $bankName = null,
        ?string $bankBic = null
    ): array {
        $number = trim($number);
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        $channel = strtolower(trim((string) $preferred)) === DisbursementItem::CHANNEL_BANK
            ? DisbursementItem::CHANNEL_BANK
            : null;

        if ($channel === null) {
            $normalized = $this->gateway->normalizeMsisdn($number);
            if ($normalized['ok']) {
                return [
                    'channel' => DisbursementItem::CHANNEL_MOBILE,
                    'number' => $normalized['phone'],
                    'bank_name' => null,
                    'bank_bic' => null,
                    'ready' => true,
                    'error' => null,
                ];
            }

            // A bank account is only inferred when the value cannot be a phone.
            if ($digits !== '' && strlen($digits) >= 5 && strlen($digits) <= 25) {
                $channel = DisbursementItem::CHANNEL_BANK;
            }
        }

        if ($channel === DisbursementItem::CHANNEL_BANK) {
            $bankBic = $bankBic ?: $this->gateway->resolveBankBic($bankName);

            if ($digits === '') {
                return [
                    'channel' => DisbursementItem::CHANNEL_BANK,
                    'number' => null,
                    'bank_name' => $bankName,
                    'bank_bic' => $bankBic,
                    'ready' => false,
                    'error' => 'Missing bank account number.',
                ];
            }

            if (! $bankBic) {
                return [
                    'channel' => DisbursementItem::CHANNEL_BANK,
                    'number' => $digits,
                    'bank_name' => $bankName,
                    'bank_bic' => null,
                    'ready' => false,
                    'error' => 'Bank BIC could not be resolved. Set the bank on this item.',
                ];
            }

            return [
                'channel' => DisbursementItem::CHANNEL_BANK,
                'number' => $digits,
                'bank_name' => $bankName,
                'bank_bic' => $bankBic,
                'ready' => true,
                'error' => null,
            ];
        }

        $normalized = $this->gateway->normalizeMsisdn($number);

        return [
            'channel' => DisbursementItem::CHANNEL_MOBILE,
            'number' => $normalized['phone'] ?: $digits,
            'bank_name' => null,
            'bank_bic' => null,
            'ready' => $normalized['ok'],
            'error' => $normalized['ok'] ? null : ($normalized['error'] ?: 'Missing mobile money number.'),
        ];
    }

    private function itemExists(string $sourceType, int $sourceId): bool
    {
        return DisbursementItem::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->exists();
    }

    private function createItem(Disbursement $batch, array $attributes): ?DisbursementItem
    {
        $sourceType = (string) $attributes['source_type'];
        $sourceId = (int) $attributes['source_id'];

        if ($this->itemExists($sourceType, $sourceId)) {
            return null;
        }

        $attributes['disbursement_id'] = $batch->id;
        $attributes['currency'] = $batch->currency ?: $this->gateway->currency();
        $attributes['reference'] = $this->itemReference($batch, $sourceType, $sourceId);

        try {
            return DisbursementItem::create($attributes);
        } catch (Throwable $e) {
            Log::warning('Disbursement item could not be created', [
                'batch_id' => $batch->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function itemReference(Disbursement $batch, string $sourceType, int $sourceId): string
    {
        $prefix = match ($sourceType) {
            DisbursementItem::SOURCE_BOOKING_REFUND => 'RFD',
            DisbursementItem::SOURCE_EXCESS_LUGGAGE => 'XLG',
            DisbursementItem::SOURCE_PAYOUT_TRANSACTION => 'PAY',
            DisbursementItem::SOURCE_SPECIAL_HIRE_WITHDRAWAL => 'WDR',
            DisbursementItem::SOURCE_SPECIAL_HIRE_REFUND => 'SHR',
            default => 'DSB',
        };

        $base = 'DSB' . $batch->batch_date->format('Ymd') . $prefix . $sourceId;
        $reference = preg_replace('/[^A-Za-z0-9]/', '', $base) ?: ('DSB' . $sourceId);

        return substr($reference, 0, 64);
    }

    private function batchReference(Carbon $day): string
    {
        return 'DSB-' . $day->format('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    /* ---------------------------------------------------------------------
     | Execution
     * ------------------------------------------------------------------- */

    /**
     * Process due batches: sync in-flight payouts, then send at most
     * $maxCreates new payouts (ClickPesa allows one create per 60 seconds).
     *
     * @return array{processed:int, skipped:int, reason:string|null, batches:int}
     */
    public function process(bool $force = false, ?int $maxCreates = null): array
    {
        // Serialise every entry point (scheduler, admin "Run now", artisan) so
        // the same item can never be sent twice.
        $lock = Cache::lock('disbursements:process', 120);

        if (! $lock->get()) {
            return ['processed' => 0, 'skipped' => 0, 'reason' => 'locked', 'batches' => 0];
        }

        try {
            return $this->runProcess($force, $maxCreates);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{processed:int, skipped:int, reason:string|null, batches:int}
     */
    private function runProcess(bool $force = false, ?int $maxCreates = null): array
    {
        if (! $force && ! $this->isEnabled()) {
            return ['processed' => 0, 'skipped' => 0, 'reason' => 'disabled', 'batches' => 0];
        }

        // Catch-up for missed midnight runs (e.g. the PC/server was off at
        // 00:00). Building/reactivating today's batch here means the day's
        // obligations are never silently skipped.
        if (! $force) {
            try {
                $this->collectForDate(Carbon::today());
            } catch (Throwable $e) {
                Log::warning('Disbursement catch-up collection failed', ['error' => $e->getMessage()]);
            }
        }

        if (! $force && ! $this->isDueNow()) {
            return ['processed' => 0, 'skipped' => 0, 'reason' => 'not_due', 'batches' => 0];
        }

        $maxCreates = max(1, (int) ($maxCreates ?? config('disbursements.max_creates_per_run', 1)));

        $batches = $this->dueBatches($force);
        if ($batches->isEmpty()) {
            return ['processed' => 0, 'skipped' => 0, 'reason' => 'no_batches', 'batches' => 0];
        }

        $this->syncProcessingItems((int) config('disbursements.status_sync_limit', 25));

        $processed = 0;
        $stop = false;

        foreach ($batches as $batch) {
            if ($processed >= $maxCreates || $stop) {
                break;
            }

            if ($batch->status === Disbursement::STATUS_COMPLETED) {
                continue;
            }

            $candidates = $batch->items()
                ->where('status', DisbursementItem::STATUS_PENDING)
                ->where(function ($q) {
                    $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
                })
                ->orderBy('id')
                ->limit($maxCreates - $processed)
                ->get();

            foreach ($candidates as $item) {
                if ($processed >= $maxCreates) {
                    break 2;
                }

                if ($this->cooldownActive()) {
                    $stop = true;
                    break 2;
                }

                if (! $batch->started_at) {
                    $batch->update([
                        'started_at' => now(),
                        'status' => Disbursement::STATUS_PROCESSING,
                    ]);
                }

                $result = $this->sendPayout($item);
                $processed++;

                if (! empty($result['stop'])) {
                    $stop = true;
                    break 2;
                }
            }

            $this->refreshBatch($batch->fresh());
        }

        foreach ($batches as $batch) {
            $this->refreshBatch($batch->fresh());
        }

        return [
            'processed' => $processed,
            'skipped' => 0,
            'reason' => $stop ? 'halted' : null,
            'batches' => $batches->count(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Disbursement>
     */
    private function dueBatches(bool $force)
    {
        $query = Disbursement::query()
            ->whereIn('status', [Disbursement::STATUS_OPEN, Disbursement::STATUS_PROCESSING])
            ->whereDate('batch_date', '<=', Carbon::today())
            ->orderBy('batch_date')
            ->orderBy('id');

        if (! $force) {
            $lookback = (int) config('disbursements.lookback_days', 30);
            if ($lookback > 0) {
                $query->whereDate('batch_date', '>=', Carbon::today()->subDays($lookback));
            }
        }

        return $query->get();
    }

    /**
     * Send one item to ClickPesa (or simulate in dry-run mode).
     *
     * @return array{ok: bool, status: string, stop: bool, error: string|null}
     */
    public function sendPayout(DisbursementItem $item): array
    {
        $item->refresh();

        if ($item->status === DisbursementItem::STATUS_PAID) {
            return ['ok' => true, 'status' => 'paid', 'stop' => false, 'error' => null];
        }

        if ($item->amount <= 0) {
            $item->update([
                'status' => DisbursementItem::STATUS_FAILED,
                'last_error' => 'Amount must be greater than zero.',
                'processed_at' => now(),
            ]);

            return ['ok' => false, 'status' => 'failed', 'stop' => false, 'error' => 'Amount must be greater than zero.'];
        }

        if ($this->effectiveDryRun()) {
            $item->update([
                'status' => DisbursementItem::STATUS_PROCESSING,
                'gateway_status' => 'DRY_RUN',
                'gateway_response' => ['dry_run' => true, 'reference' => $item->reference],
                'attempts' => $item->attempts + 1,
                'processed_at' => now(),
                'last_error' => null,
                'next_attempt_at' => null,
            ]);

            $this->finalizeItem($item->fresh());

            return ['ok' => true, 'status' => 'paid', 'stop' => false, 'error' => null];
        }

        $response = $item->payout_channel === DisbursementItem::CHANNEL_BANK
            ? $this->sendBankPayout($item)
            : $this->sendMobilePayout($item);

        return $response;
    }

    private function sendMobilePayout(DisbursementItem $item): array
    {
        $normalized = $this->gateway->normalizeMsisdn((string) $item->payout_number);

        if (! $normalized['ok']) {
            $item->update([
                'status' => DisbursementItem::STATUS_FAILED,
                'attempts' => $item->attempts + 1,
                'last_error' => $normalized['error'],
                'processed_at' => now(),
                'next_attempt_at' => null,
            ]);

            return ['ok' => false, 'status' => 'failed', 'stop' => false, 'error' => $normalized['error']];
        }

        $attempt = $item->attempts + 1;
        $reference = $this->referenceForAttempt($item, $attempt);

        $response = $this->gateway->createMobilePayout(
            $reference,
            $normalized['phone'],
            (float) $item->amount,
            $item->currency ?: null
        );

        return $this->handleGatewayResponse($item, $response, $reference);
    }

    private function sendBankPayout(DisbursementItem $item): array
    {
        $bic = $item->bank_bic ?: $this->gateway->resolveBankBic($item->bank_name);

        if (! $bic) {
            $item->update([
                'status' => DisbursementItem::STATUS_NEEDS_DETAILS,
                'last_error' => 'Bank BIC is required for a bank payout.',
            ]);

            return ['ok' => false, 'status' => 'needs_details', 'stop' => false, 'error' => 'Bank BIC is required.'];
        }

        $digits = preg_replace('/\D+/', '', (string) $item->payout_number) ?? '';
        if ($digits === '') {
            $item->update([
                'status' => DisbursementItem::STATUS_NEEDS_DETAILS,
                'last_error' => 'Bank account number is missing.',
            ]);

            return ['ok' => false, 'status' => 'needs_details', 'stop' => false, 'error' => 'Bank account number is missing.'];
        }

        $attempt = $item->attempts + 1;
        $reference = $this->referenceForAttempt($item, $attempt);

        $response = $this->gateway->createBankPayout(
            $reference,
            $digits,
            (string) ($item->beneficiary_name ?: 'HIGHLINK ISGC'),
            $bic,
            (float) $item->amount,
            $item->currency ?: null
        );

        return $this->handleGatewayResponse($item, $response, $reference);
    }

    private function referenceForAttempt(DisbursementItem $item, int $attempt): string
    {
        if ($item->attempts === 0) {
            return $item->reference;
        }

        $base = substr($item->reference, 0, 50);

        return $base . 'A' . $attempt;
    }

    /**
     * @param  array{ok: bool, http: int, data: array|null, error: string|null, raw: string}  $response
     */
    private function handleGatewayResponse(DisbursementItem $item, array $response, string $reference): array
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $status = strtoupper(trim((string) ($data['status'] ?? '')));
        $message = (string) ($response['error'] ?? '');

        $this->assertCooldownFromMessage($message);

        if (! empty($response['ok']) && (isset($data['id']) || $status !== '')) {
            $item->update([
                'reference' => $reference,
                'status' => DisbursementItem::STATUS_PROCESSING,
                'gateway_id' => isset($data['id']) ? (string) $data['id'] : $item->gateway_id,
                'gateway_status' => $status ?: 'AUTHORIZED',
                'gateway_fee' => isset($data['fee']) ? (float) $data['fee'] : null,
                'gateway_response' => $data ?: null,
                'attempts' => $item->attempts + 1,
                'processed_at' => now(),
                'next_attempt_at' => null,
                'last_error' => null,
            ]);

            $item->refresh();

            if ($this->gateway->isSuccessStatus($status)) {
                $this->finalizeItem($item);

                return ['ok' => true, 'status' => 'paid', 'stop' => false, 'error' => null];
            }

            if ($this->gateway->isFailureStatus($status)) {
                $item->update([
                    'status' => $status === 'REVERSED'
                        ? DisbursementItem::STATUS_REVERSED
                        : DisbursementItem::STATUS_FAILED,
                    'last_error' => (string) ($data['notes'] ?? 'ClickPesa rejected the payout.'),
                ]);

                return ['ok' => false, 'status' => 'failed', 'stop' => false, 'error' => $item->last_error];
            }

            return ['ok' => true, 'status' => 'processing', 'stop' => false, 'error' => null];
        }

        return $this->handleGatewayFailure($item, $response);
    }

    /**
     * @param  array{ok: bool, http: int, data: array|null, error: string|null, raw: string}  $response
     */
    private function handleGatewayFailure(DisbursementItem $item, array $response): array
    {
        $message = trim((string) ($response['error'] ?? '')) ?: ('ClickPesa HTTP ' . ($response['http'] ?? 0));
        $attempts = $item->attempts + 1;
        $lower = strtolower($message);

        $this->assertCooldownFromMessage($message);

        // Order reference was already used: the earlier request may have been
        // accepted. Check it before retrying with a new reference.
        if (str_contains($lower, 'already used') || str_contains($lower, 'already been used')) {
            $query = $this->gateway->queryPayout($item->reference);
            $row = $this->firstPayoutRow($query['data'] ?? []);
            $status = strtoupper((string) ($row['status'] ?? ''));

            if ($query['ok'] && $status !== '') {
                $item->update([
                    'gateway_id' => (string) ($row['id'] ?? $item->gateway_id),
                    'gateway_status' => $status,
                    'gateway_response' => $row ?: null,
                    'attempts' => $attempts,
                ]);

                if ($this->gateway->isSuccessStatus($status)) {
                    $this->finalizeItem($item->fresh());

                    return ['ok' => true, 'status' => 'paid', 'stop' => false, 'error' => null];
                }

                if ($this->gateway->isPendingStatus($status)) {
                    return ['ok' => true, 'status' => 'processing', 'stop' => false, 'error' => null];
                }
            }
        }

        // Permanent configuration / validation errors stop the batch.
        $permanent = str_contains($lower, 'insufficient balance')
            || str_contains($lower, 'no access to payout api')
            || str_contains($lower, 'no balance account is linked')
            || str_contains($lower, 'valid client id is required')
            || str_contains($lower, 'application has no access');

        if ($permanent) {
            $item->update([
                'status' => DisbursementItem::STATUS_FAILED,
                'attempts' => $attempts,
                'last_error' => $message,
                'processed_at' => now(),
                'next_attempt_at' => null,
            ]);

            return ['ok' => false, 'status' => 'failed', 'stop' => true, 'error' => $message];
        }

        // Cooldown ("already in progress, retry after N seconds") is short;
        // other transient failures use the normal backoff.
        $cooldownSeconds = 0;
        if (preg_match('/retry after (\d+) seconds?/i', $message, $matches)) {
            $cooldownSeconds = max(1, (int) $matches[1]);
        }

        $maxAttempts = max(1, (int) config('disbursements.max_attempts', 5));

        if ($attempts >= $maxAttempts && $cooldownSeconds === 0) {
            $item->update([
                'status' => DisbursementItem::STATUS_FAILED,
                'attempts' => $attempts,
                'last_error' => $message,
                'processed_at' => now(),
                'next_attempt_at' => null,
            ]);

            return ['ok' => false, 'status' => 'failed', 'stop' => false, 'error' => $message];
        }

        $delay = $cooldownSeconds > 0
            ? $cooldownSeconds + 2
            : (max(1, (int) config('disbursements.retry_delay_minutes', 10)) * $attempts) * 60;

        $item->update([
            'status' => DisbursementItem::STATUS_PENDING,
            'attempts' => $attempts,
            'last_error' => $message,
            'next_attempt_at' => now()->addSeconds($delay),
        ]);

        return ['ok' => false, 'status' => 'pending', 'stop' => false, 'error' => $message];
    }

    /**
     * Re-check in-flight payouts and finalise successful ones.
     */
    public function syncProcessingItems(int $limit = 25): int
    {
        $items = DisbursementItem::query()
            ->where('status', DisbursementItem::STATUS_PROCESSING)
            ->whereNull('finalized_at')
            ->whereNotNull('gateway_status')
            ->where('gateway_status', '!=', 'DRY_RUN')
            ->orderBy('processed_at')
            ->limit(max(1, $limit))
            ->get();

        $synced = 0;

        foreach ($items as $item) {
            try {
                $response = $this->gateway->queryPayout($item->reference);
                if (empty($response['ok'])) {
                    continue;
                }

                $row = $this->firstPayoutRow($response['data'] ?? []);
                $status = strtoupper(trim((string) ($row['status'] ?? '')));

                if ($status === '') {
                    continue;
                }

                $item->update([
                    'gateway_id' => (string) ($row['id'] ?? $item->gateway_id),
                    'gateway_status' => $status,
                    'gateway_fee' => isset($row['fee']) ? (float) $row['fee'] : $item->gateway_fee,
                    'gateway_response' => $row ?: $item->gateway_response,
                ]);

                if ($this->gateway->isSuccessStatus($status)) {
                    $this->finalizeItem($item->fresh());
                    $synced++;
                } elseif ($this->gateway->isFailureStatus($status)) {
                    $item->update([
                        'status' => $status === 'REVERSED'
                            ? DisbursementItem::STATUS_REVERSED
                            : DisbursementItem::STATUS_FAILED,
                        'last_error' => (string) ($row['notes'] ?? 'Payout was not completed.'),
                        'processed_at' => now(),
                    ]);
                    $synced++;
                }
            } catch (Throwable $e) {
                Log::warning('Disbursement status sync failed', [
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($items->pluck('disbursement_id')->unique() as $batchId) {
            $batch = Disbursement::find($batchId);
            if ($batch) {
                $this->refreshBatch($batch);
            }
        }

        return $synced;
    }

    private function firstPayoutRow($data): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $data[0];
        }

        return $data;
    }

    /* ---------------------------------------------------------------------
     | Finalisation (source records)
     * ------------------------------------------------------------------- */

    public function finalizeItem(DisbursementItem $item, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($item, $actor) {
            /** @var DisbursementItem $locked */
            $locked = DisbursementItem::whereKey($item->id)->lockForUpdate()->first();
            if (! $locked) {
                return false;
            }

            if ($locked->finalized_at !== null) {
                if ($locked->status !== DisbursementItem::STATUS_PAID) {
                    $locked->update(['status' => DisbursementItem::STATUS_PAID, 'processed_at' => now()]);
                }

                return true;
            }

            $this->applySourceEffects($locked, $actor);

            $locked->update([
                'status' => DisbursementItem::STATUS_PAID,
                'finalized_at' => now(),
                'processed_at' => $locked->processed_at ?: now(),
                'last_error' => null,
                'next_attempt_at' => null,
            ]);

            return true;
        });
    }

    private function applySourceEffects(DisbursementItem $item, ?User $actor): void
    {
        switch ($item->source_type) {
            case DisbursementItem::SOURCE_BOOKING_REFUND:
                $this->finalizeBookingRefund($item);
                break;

            case DisbursementItem::SOURCE_EXCESS_LUGGAGE:
                $this->finalizeExcessLuggage($item, $actor);
                break;

            case DisbursementItem::SOURCE_PAYOUT_TRANSACTION:
                $this->finalizePayoutTransaction($item);
                break;

            case DisbursementItem::SOURCE_SPECIAL_HIRE_WITHDRAWAL:
                $this->finalizeSpecialHireWithdrawal($item);
                break;

            case DisbursementItem::SOURCE_SPECIAL_HIRE_REFUND:
                $this->finalizeSpecialHireRefund($item);
                break;
        }
    }

    private function finalizeBookingRefund(DisbursementItem $item): void
    {
        $refund = Refund::whereKey($item->source_id)->lockForUpdate()->first();
        if (! $refund || $refund->disbursed_at !== null) {
            return;
        }

        $refund->update([
            'status' => 'Approved',
            'disbursed_at' => now(),
            'disbursement_item_id' => $item->id,
        ]);

        $booking = null;
        if (! empty($refund->booking_code)) {
            $booking = Booking::where('booking_code', $refund->booking_code)->lockForUpdate()->first();
        }
        if (! $booking && ! empty($refund->booking_id)) {
            $booking = Booking::whereKey($refund->booking_id)->lockForUpdate()->first();
        }

        if (! $booking) {
            return;
        }

        // A manual approval (Refunds page) already flipped the booking to
        // "Refund" and debited the company balance; do not debit it twice when
        // the batched payout finalises.
        $alreadySettled = strtolower((string) $booking->payment_status) === 'refund';

        $booking->update([
            'payment_status' => 'Refund',
            'refund_id' => $refund->id,
        ]);

        if (! $alreadySettled && $booking->campany_id) {
            $campany = Campany::with('balance')->find($booking->campany_id);
            if ($campany && $campany->balance) {
                $campany->balance->decrement('amount', (float) $item->amount);
            }
        }
    }

    private function finalizeExcessLuggage(DisbursementItem $item, ?User $actor): void
    {
        $booking = Booking::whereKey($item->source_id)->lockForUpdate()->first();
        if (! $booking || $booking->luggage_payment_status !== ExcessLuggageService::PAYMENT_REFUND_PENDING) {
            return;
        }

        app(ExcessLuggageService::class)->approveRefund($booking, $actor, (float) $item->amount);
    }

    private function finalizePayoutTransaction(DisbursementItem $item): void
    {
        $transaction = Transaction::whereKey($item->source_id)->lockForUpdate()->first();
        if (! $transaction || strtolower((string) $transaction->status) !== 'pending') {
            return;
        }

        $transaction->update([
            'status' => 'Completed',
            'reference_number' => $item->reference,
        ]);

        // Company balances were already debited when the request was created.
        // Vendor balances are debited when the payout is confirmed.
        if ((int) ($transaction->campany_id ?? 0) === 0) {
            $userId = (int) ($transaction->vender_id ?: $transaction->user_id);
            $balance = VenderBalance::where('user_id', $userId)->lockForUpdate()->first();
            if ($balance) {
                $balance->decrement('amount', (float) $item->amount);
            }
        }
    }

    private function finalizeSpecialHireWithdrawal(DisbursementItem $item): void
    {
        $withdrawal = SpecialHireWithdrawalRequest::whereKey($item->source_id)->lockForUpdate()->first();
        if (! $withdrawal || $withdrawal->status === SpecialHireWithdrawalRequest::STATUS_PAID) {
            return;
        }

        $note = trim((string) $withdrawal->admin_note);
        $note = $note === ''
            ? 'Auto-disbursed via ClickPesa (' . $item->reference . ')'
            : $note . ' | Auto-disbursed via ClickPesa (' . $item->reference . ')';

        $withdrawal->update([
            'status' => SpecialHireWithdrawalRequest::STATUS_PAID,
            'processed_at' => now(),
            'admin_note' => $note,
        ]);
    }

    private function finalizeSpecialHireRefund(DisbursementItem $item): void
    {
        $order = SpecialHireOrder::whereKey($item->source_id)->lockForUpdate()->first();
        if (! $order || $order->payment_status !== 'refund_pending') {
            return;
        }

        $order->update(['payment_status' => 'refunded']);
    }

    /* ---------------------------------------------------------------------
     | Admin actions
     * ------------------------------------------------------------------- */

    public function markPaidManually(DisbursementItem $item, ?string $reference = null, ?User $actor = null): void
    {
        $item->refresh();

        if ($item->status === DisbursementItem::STATUS_PAID || $item->finalized_at !== null) {
            return;
        }

        $item->update([
            'status' => DisbursementItem::STATUS_PROCESSING,
            'gateway_status' => 'MANUAL',
            'gateway_id' => $reference ?: $item->gateway_id,
            'processed_at' => now(),
            'last_error' => null,
        ]);

        $this->finalizeItem($item->fresh(), $actor);
        $this->refreshBatch($item->disbursement);
    }

    public function retryItem(DisbursementItem $item): void
    {
        $item->refresh();

        // Never re-send a payout that already went out, or one that ClickPesa
        // is still processing.
        if (in_array($item->status, [
            DisbursementItem::STATUS_PAID,
            DisbursementItem::STATUS_PROCESSING,
        ], true) || $item->finalized_at !== null) {
            return;
        }

        $item->update([
            'status' => DisbursementItem::STATUS_PENDING,
            'next_attempt_at' => null,
            'last_error' => null,
        ]);

        $this->refreshBatch($item->disbursement);
    }

    public function skipItem(DisbursementItem $item, string $reason = ''): void
    {
        $item->refresh();

        if (in_array($item->status, [
            DisbursementItem::STATUS_PAID,
            DisbursementItem::STATUS_PROCESSING,
        ], true) || $item->finalized_at !== null) {
            return;
        }

        $item->update([
            'status' => DisbursementItem::STATUS_SKIPPED,
            'last_error' => $reason !== '' ? $reason : $item->last_error,
            'processed_at' => now(),
        ]);

        $this->refreshBatch($item->disbursement);
    }

    /**
     * Admin fixes payout details (channel, number, bank, BIC, name).
     */
    public function updateItemDetails(DisbursementItem $item, array $data): void
    {
        $item->refresh();

        $channel = ($data['payout_channel'] ?? $item->payout_channel) === DisbursementItem::CHANNEL_BANK
            ? DisbursementItem::CHANNEL_BANK
            : DisbursementItem::CHANNEL_MOBILE;

        $resolved = $this->resolveChannel(
            $channel,
            (string) ($data['payout_number'] ?? $item->payout_number),
            $data['bank_name'] ?? $item->bank_name,
            $data['bank_bic'] ?? $item->bank_bic
        );

        $attributes = [
            'beneficiary_name' => $data['beneficiary_name'] ?? $item->beneficiary_name,
            'payout_channel' => $resolved['channel'],
            'payout_number' => $resolved['number'],
            'bank_name' => $resolved['bank_name'],
            'bank_bic' => $resolved['bank_bic'],
        ];

        // Editing a settled (or in-flight) payout must never queue it again.
        if (! in_array($item->status, [
            DisbursementItem::STATUS_PAID,
            DisbursementItem::STATUS_PROCESSING,
        ], true) && $item->finalized_at === null) {
            $attributes['status'] = $resolved['ready']
                ? DisbursementItem::STATUS_PENDING
                : DisbursementItem::STATUS_NEEDS_DETAILS;
            $attributes['last_error'] = $resolved['error'];
            $attributes['next_attempt_at'] = null;
        }

        $item->update($attributes);

        $this->refreshBatch($item->disbursement);
    }

    public function refreshBatch(?Disbursement $batch): void
    {
        if (! $batch) {
            return;
        }

        $batch = $batch->fresh();
        if (! $batch) {
            return;
        }

        $counts = $batch->items()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $pending = (int) ($counts[DisbursementItem::STATUS_PENDING] ?? 0)
            + (int) ($counts[DisbursementItem::STATUS_PROCESSING] ?? 0);
        $needsDetails = (int) ($counts[DisbursementItem::STATUS_NEEDS_DETAILS] ?? 0);
        $failed = (int) ($counts[DisbursementItem::STATUS_FAILED] ?? 0)
            + (int) ($counts[DisbursementItem::STATUS_REVERSED] ?? 0);

        if ($pending > 0) {
            $status = Disbursement::STATUS_PROCESSING;
        } elseif ($needsDetails > 0 || $failed > 0) {
            $status = Disbursement::STATUS_COMPLETED_WITH_ERRORS;
        } else {
            $status = Disbursement::STATUS_COMPLETED;
        }

        $batch->status = $status;
        $batch->completed_at = in_array($status, [
            Disbursement::STATUS_COMPLETED,
            Disbursement::STATUS_COMPLETED_WITH_ERRORS,
        ], true)
            ? ($batch->completed_at ?: now())
            : null;
        $batch->save();

        $batch->refreshTotals();
    }

    /* ---------------------------------------------------------------------
     | Settings / helpers
     * ------------------------------------------------------------------- */

    public function isEnabled(): bool
    {
        $value = $this->setting('disbursement_enabled', config('disbursements.enabled', true));

        return (bool) $value;
    }

    public function isLive(): bool
    {
        return (bool) $this->setting('disbursement_live', false);
    }

    public function effectiveDryRun(): bool
    {
        return (bool) config('disbursements.dry_run', true) || ! $this->isLive();
    }

    public function runTime(): string
    {
        $time = (string) $this->setting('disbursement_run_time', config('disbursements.run_time', '00:00'));

        return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '00:00';
    }

    public function isDueNow(): bool
    {
        [$hour, $minute] = array_map('intval', explode(':', $this->runTime()));

        if (now()->gte(Carbon::today()->setTime($hour, $minute))) {
            return true;
        }

        // Keep draining an unfinished backlog from previous days even before
        // today's run time, otherwise the queue would freeze after midnight.
        return Disbursement::query()
            ->whereIn('status', [Disbursement::STATUS_OPEN, Disbursement::STATUS_PROCESSING])
            ->whereDate('batch_date', '<', Carbon::today())
            ->exists();
    }

    public function lastRunAt(): ?Carbon
    {
        $value = $this->setting('disbursement_last_run_at', null);

        return $value ? Carbon::parse($value) : null;
    }

    public function markRun(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'disbursement_last_run_at')) {
            Setting::query()->update(['disbursement_last_run_at' => now()]);
        }
    }

    private function cooldownActive(): bool
    {
        return Cache::has('disbursement:create_cooldown');
    }

    private function assertCooldownFromMessage(string $message): void
    {
        if (preg_match('/retry after (\d+) seconds?/i', $message, $matches)) {
            $seconds = max(1, (int) $matches[1]);
            Cache::put(
                'disbursement:create_cooldown',
                true,
                now()->addSeconds($seconds + 2)
            );

            return;
        }

        // Default spacing after any create attempt.
        Cache::put(
            'disbursement:create_cooldown',
            true,
            now()->addSeconds(max(1, (int) config('disbursements.create_cooldown_seconds', 60)))
        );
    }

    private function looksLikeBankMethod(?string $method): bool
    {
        $method = strtolower(trim((string) $method));

        if ($method === '') {
            return false;
        }

        if (str_contains($method, 'mobile') || str_contains($method, 'mpesa')
            || str_contains($method, 'm-pesa') || str_contains($method, 'tigo')
            || str_contains($method, 'mixx') || str_contains($method, 'airtel')
            || str_contains($method, 'halo')) {
            return false;
        }

        foreach ([
            'bank', 'crdb', 'nmb', 'nbc', 'equity', 'gtbank', 'stanbic', 'absa',
            'exim', 'tpb', 'access', 'diamond', 'dcb', 'azania', 'amana', 'pbz',
            'ecobank', 'kcb', 'standard chartered', 'citibank', 'habib', 'tib',
        ] as $keyword) {
            if (str_contains($method, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function bankNameFromMethod(?string $method): ?string
    {
        $method = trim((string) $method);
        if ($method === '') {
            return null;
        }

        // Prefer the human bank name when the method is "Bank - CRDB" or similar.
        $parts = preg_split('/[-–—|:]+/', $method) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && $this->looksLikeBankMethod($part) && ! is_numeric($part)) {
                return $part;
            }
        }

        return $this->looksLikeBankMethod($method) ? $method : null;
    }

    private function setting(string $key, mixed $default): mixed
    {
        try {
            if (Schema::hasTable('settings') && Schema::hasColumn('settings', $key)) {
                $value = Setting::query()->value($key);

                return $value ?? $default;
            }
        } catch (Throwable $e) {
            // Fall back to config.
        }

        return $default;
    }
}
