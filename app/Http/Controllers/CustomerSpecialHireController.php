<?php

namespace App\Http\Controllers;

use App\Models\Coaster;
use App\Models\SpecialHireOrder;
use App\Services\SpecialHireCustomerActionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class CustomerSpecialHireController extends Controller
{
    public function index(Request $request)
    {
        $orders = SpecialHireOrder::where('customer_user_id', Auth::id())
            ->with('coaster')
            ->orderByDesc('hire_date')
            ->orderByDesc('hire_time')
            ->paginate(15);

        return view('customer.special_hire.index', compact('orders'));
    }

    public function show(int $id)
    {
        $order = $this->ownedOrder($id);
        $coasters = Coaster::available()
            ->where('id', '!=', $order->coaster_id)
            ->orderBy('name')
            ->get();

        $actions = app(SpecialHireCustomerActionsService::class);
        $canReceipt = $actions->canDownloadReceipt($order);
        $canTransfer = ! in_array($order->order_status, ['completed', 'cancelled'], true);
        $canRefund = in_array((string) $order->payment_status, ['paid'], true)
            || $order->deposit_paid_at
            || $order->balance_paid_at;
        if (in_array((string) $order->payment_status, ['refunded', 'refund_pending'], true)) {
            $canRefund = false;
        }

        $reorderPrefill = session('special_hire_reorder_prefill');

        return view('customer.special_hire.show', compact(
            'order',
            'coasters',
            'canReceipt',
            'canTransfer',
            'canRefund',
            'reorderPrefill'
        ));
    }

    public function reorder(int $id)
    {
        $order = $this->ownedOrder($id);
        $prefill = app(SpecialHireCustomerActionsService::class)->reorderPrefill($order);

        return redirect()
            ->route('customer.special_hire.show', $order->id)
            ->with('success', __('customer/special_hire.reorder_ready'))
            ->with('special_hire_reorder_prefill', $prefill);
    }

    public function transfer(Request $request, int $id)
    {
        $order = $this->ownedOrder($id);

        $request->validate([
            'coaster_id' => 'required|integer|exists:coasters,id',
        ]);

        try {
            $updated = app(SpecialHireCustomerActionsService::class)
                ->transferToCoaster($order, (int) $request->coaster_id);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('customer.special_hire.show', $updated->id)
            ->with('success', __('customer/special_hire.transfer_success', [
                'coaster' => $updated->coaster->name ?? '',
            ]));
    }

    public function refundRequest(Request $request, int $id)
    {
        $order = $this->ownedOrder($id);

        $request->validate([
            'reason' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'bank' => 'nullable|string|max:255',
        ]);

        try {
            app(SpecialHireCustomerActionsService::class)->requestRefund($order, [
                'reason' => $request->input('reason'),
                'phone' => $request->input('phone'),
                'bank' => $request->input('bank'),
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('customer.special_hire.show', $order->id)
            ->with('success', __('customer/special_hire.refund_requested'));
    }

    public function receiptPdf(int $id)
    {
        $order = $this->ownedOrder($id);

        try {
            return app(SpecialHireCustomerActionsService::class)
                ->customerReceiptPdf($order, 'attachment');
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receiptPrint(int $id)
    {
        $order = $this->ownedOrder($id);

        try {
            return app(SpecialHireCustomerActionsService::class)
                ->customerReceiptPdf($order, 'inline');
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    protected function ownedOrder(int $id): SpecialHireOrder
    {
        return SpecialHireOrder::where('customer_user_id', Auth::id())
            ->with(['coaster', 'user'])
            ->findOrFail($id);
    }
}
