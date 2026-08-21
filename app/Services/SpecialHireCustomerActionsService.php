<?php

namespace App\Services;

use App\Models\Coaster;
use App\Models\SpecialHireOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Shared customer actions for special-hire orders: reorder prefill, transfer,
 * refund request, and customer receipt PDF (API + thin web).
 */
class SpecialHireCustomerActionsService
{
    /**
     * Fields to start a new booking from an existing order (no payment / no new row).
     *
     * @return array<string, mixed>
     */
    public function reorderPrefill(SpecialHireOrder $order): array
    {
        return [
            'source_order_id' => $order->id,
            'source_order_code' => $order->order_code,
            'coaster_id' => $order->coaster_id,
            'pickup_location' => $order->pickup_location,
            'pickup_latitude' => $order->pickup_latitude,
            'pickup_longitude' => $order->pickup_longitude,
            'dropoff_location' => $order->dropoff_location,
            'dropoff_latitude' => $order->dropoff_latitude,
            'dropoff_longitude' => $order->dropoff_longitude,
            'hire_date' => $order->hire_date?->format('Y-m-d'),
            'hire_time' => $order->hire_time
                ? (is_string($order->hire_time) ? substr($order->hire_time, 0, 5) : $order->hire_time)
                : null,
            'return_date' => $order->return_date?->format('Y-m-d'),
            'return_time' => $order->return_time
                ? (is_string($order->return_time) ? substr($order->return_time, 0, 5) : $order->return_time)
                : null,
            'passengers_count' => (int) $order->passengers_count,
            'purpose' => $order->purpose,
            'notes' => $order->notes,
            'distance_km' => $order->distance_km !== null ? (float) $order->distance_km : null,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
        ];
    }

    /**
     * Reassign order to another coaster (mirrors admin transfer): keep pricing,
     * update coaster_id + user_id (operator). Blocked when completed/cancelled.
     */
    public function transferToCoaster(SpecialHireOrder $order, int $newCoasterId): SpecialHireOrder
    {
        if (in_array($order->order_status, ['completed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Completed or cancelled orders cannot be transferred.');
        }

        if ((int) $order->coaster_id === $newCoasterId) {
            throw new InvalidArgumentException('Choose a different coaster than the current one.');
        }

        $newCoaster = Coaster::find($newCoasterId);
        if (! $newCoaster) {
            throw new InvalidArgumentException('Coaster not found.');
        }

        // Customer transfer: coaster must exist and be available (admin transfer lists any
        // coaster; customer flow requires available status to avoid dead fleet units).
        if (! $newCoaster->isAvailable()) {
            throw new InvalidArgumentException('Selected coaster is not available for transfer.');
        }

        $order->update([
            'coaster_id' => $newCoaster->id,
            'user_id' => $newCoaster->user_id,
        ]);

        return $order->fresh(['coaster', 'user']);
    }

    /**
     * Customer refund request for a paid (or deposit-paid) order.
     * Sets payment_status to refund_pending when supported; otherwise notes + refund_requested_at.
     *
     * @param  array{reason?: string|null, phone?: string|null, bank?: string|null, bank_account?: string|null}  $payload
     */
    public function requestRefund(SpecialHireOrder $order, array $payload = []): SpecialHireOrder
    {
        $status = (string) ($order->payment_status ?? '');
        if (in_array($status, ['refunded', 'refund_pending'], true)) {
            throw new InvalidArgumentException(
                $status === 'refunded'
                    ? 'This booking is already refunded.'
                    : 'A refund request is already pending.'
            );
        }

        $isPaid = $status === 'paid'
            || $order->deposit_paid_at
            || $order->balance_paid_at;

        if (! $isPaid) {
            throw new InvalidArgumentException('Refund can only be requested for paid orders.');
        }

        if (in_array($order->order_status, ['cancelled'], true) && $status !== 'paid') {
            throw new InvalidArgumentException('Cannot request a refund for this booking.');
        }

        $reason = trim((string) ($payload['reason'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $bank = trim((string) ($payload['bank'] ?? ($payload['bank_account'] ?? '')));

        $noteParts = [];
        if ($reason !== '') {
            $noteParts[] = 'Refund reason: ' . $reason;
        }
        if ($phone !== '') {
            $noteParts[] = 'Refund phone: ' . $phone;
        }
        if ($bank !== '') {
            $noteParts[] = 'Refund bank: ' . $bank;
        }
        $noteParts[] = 'Refund requested at ' . now()->toDateTimeString();

        $append = implode(' | ', $noteParts);
        $existingNotes = trim((string) ($order->notes ?? ''));
        $notes = $existingNotes === '' ? $append : ($existingNotes . "\n" . $append);

        $update = [
            'payment_status' => 'refund_pending',
            'notes' => $notes,
        ];

        if (Schema::hasColumn('special_hire_orders', 'refund_requested_at')) {
            $update['refund_requested_at'] = now();
        }

        $order->update($update);

        return $order->fresh(['coaster', 'user']);
    }

    /**
     * Customer hire receipt PDF (same view as admin customer receipt).
     * Allowed when fully paid or deposit has been collected.
     *
     * @return \Barryvdh\DomPDF\PDF|\Illuminate\Http\Response
     */
    public function customerReceiptPdf(SpecialHireOrder $order, string $disposition = 'attachment')
    {
        if (! $this->canDownloadReceipt($order)) {
            throw new InvalidArgumentException('Receipt is available after deposit or full payment.');
        }

        $hireOrder = $order->loadMissing(['user', 'coaster']);
        $pdf = Pdf::loadView('print.special_hire_customer_receipt', compact('hireOrder'));
        $filename = 'special_hire_receipt_' . $hireOrder->order_code . '.pdf';

        if ($disposition === 'inline') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    public function canDownloadReceipt(SpecialHireOrder $order): bool
    {
        if (($order->payment_status ?? '') === 'paid') {
            return true;
        }
        if (($order->payment_status ?? '') === 'refund_pending') {
            return true;
        }

        return (bool) $order->deposit_paid_at || (bool) $order->balance_paid_at;
    }
}
