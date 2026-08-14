<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ClickPesaController;
use App\Models\Booking;
use App\Services\ExcessLuggageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;

class ExcessLuggageController extends Controller
{
    public function __construct(private ExcessLuggageService $luggage)
    {
    }

    public function index(Request $request)
    {
        $ctx = $this->resolveContext();
        $query = $this->baseQuery($ctx)
            ->with(['bus.campany', 'bus.route', 'schedule', 'route'])
            ->where(function ($q) {
                $q->where('has_excess_luggage', 1)
                    ->orWhere('excess_luggage_fee', '>', 0)
                    ->orWhereNotNull('luggage_status');
            });

        if ($status = $request->query('status')) {
            if ($status === ExcessLuggageService::STATUS_DECLARED) {
                $query->where(function ($q) {
                    $q->where('luggage_status', ExcessLuggageService::STATUS_DECLARED)
                        ->orWhere(function ($q2) {
                            $q2->whereNull('luggage_status')
                                ->where(function ($q3) {
                                    $q3->where('has_excess_luggage', 1)
                                        ->orWhere('excess_luggage_fee', '>', 0);
                                });
                        });
                });
            } else {
                $query->where('luggage_status', $status);
            }
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhere('verification_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $bookings = $query->latest('id')->paginate(20)->withQueryString();

        return view($ctx['index_view'], [
            'bookings' => $bookings,
            'ctx' => $ctx,
            'filters' => [
                'status' => $status,
                'q' => $search,
            ],
            'luggageService' => $this->luggage,
        ]);
    }

    public function lookupForm()
    {
        $ctx = $this->resolveContext();

        return view($ctx['lookup_view'], compact('ctx'));
    }

    public function lookup(Request $request)
    {
        $ctx = $this->resolveContext();
        $request->validate([
            'ticket_code' => 'required|string|max:100',
        ]);

        $code = trim($request->ticket_code);
        $booking = $this->baseQuery($ctx)
            ->with(['bus.campany', 'schedule'])
            ->where('payment_status', 'Paid')
            ->where(function ($q) use ($code) {
                $q->where('booking_code', $code)
                    ->orWhere('verification_code', $code);
            })
            ->first();

        if (!$booking) {
            return back()
                ->withInput()
                ->with('error', __('vender/luggage.booking_not_found'));
        }

        return redirect()->route($ctx['show_route'], $booking->id);
    }

    public function show($bookingId)
    {
        $ctx = $this->resolveContext();
        $booking = $this->findAuthorizedBooking($bookingId, $ctx, true);

        return view($ctx['show_view'], [
            'booking' => $booking,
            'ctx' => $ctx,
            'status' => $this->luggage->normalizeStatus($booking),
            'amountDue' => $this->luggage->amountDue($booking),
            'refundAmount' => $this->luggage->refundAmount($booking),
            'luggageService' => $this->luggage,
        ]);
    }

    public function weighIn(Request $request, $bookingId)
    {
        $ctx = $this->resolveContext();
        $booking = $this->findAuthorizedBooking($bookingId, $ctx);

        $data = $request->validate([
            'luggage_action' => 'required|in:set,remove',
            'excess_luggage_fee' => 'required_if:luggage_action,set|nullable|numeric|min:0',
            'excess_luggage_description' => 'nullable|string|max:500',
            'actual_weight' => 'nullable|numeric|min:0',
            'actual_length' => 'nullable|numeric|min:0',
            'actual_height' => 'nullable|numeric|min:0',
            'actual_width' => 'nullable|numeric|min:0',
            // Verdict + delta are computed server-side from actual vs estimated weight.
            'luggage_weight_verdict' => 'nullable|in:underestimated,overestimated,correct',
            'luggage_refund_amount' => 'nullable|numeric',
        ]);

        if ($data['luggage_action'] === 'remove') {
            $this->luggage->clear($booking);

            return redirect()
                ->route($ctx['show_route'], $booking->id)
                ->with('success', __('vender/luggage.removed_success'));
        }

        $this->luggage->weighIn($booking, $data, Auth::user());

        return redirect()
            ->route($ctx['show_route'], $booking->id)
            ->with('success', __('vender/luggage.weigh_in_saved'));
    }

    public function pay(Request $request, $bookingId)
    {
        $ctx = $this->resolveContext();
        $booking = $this->findAuthorizedBooking($bookingId, $ctx);

        $amountDue = $this->luggage->amountDue($booking);
        if ($amountDue <= 0) {
            return redirect()
                ->route($ctx['show_route'], $booking->id)
                ->with('error', __('vender/luggage.no_amount_due'));
        }

        $data = $request->validate([
            'phone' => 'nullable|string|max:20',
        ]);

        $phone = $data['phone'] ?: $booking->customer_phone;
        $normalized = ClickPesaController::normalizeTanzaniaMsisdnForClickPesa((string) $phone);
        if (!$normalized['ok']) {
            return back()->with('error', $normalized['error'] ?? __('vender/luggage.phone_required'));
        }
        $phone = $normalized['phone'];

        $orderRef = $this->luggage->buildPaymentReference($booking);
        $booking->update([
            'luggage_payment_ref' => $orderRef,
            'luggage_payment_status' => ExcessLuggageService::PAYMENT_PENDING,
            'luggage_status' => ExcessLuggageService::STATUS_AWAITING_PAYMENT,
        ]);

        Session::put('excess_luggage_payment', [
            'booking_id' => $booking->id,
            'order_ref' => $orderRef,
            'return_route' => $ctx['show_route'],
        ]);
        Session::forget(['booking', 'vender', 'booking1', 'booking2', 'parcel_payment']);

        $name = $booking->customer_name ?: 'Passenger';
        $parts = preg_split('/\s+/', trim($name), 2);
        $first = $parts[0] ?? 'Passenger';
        $last = $parts[1] ?? $first;
        $email = $booking->customer_email ?: 'noreply@highlink.local';

        $clickpesa = new ClickPesaController();

        return $clickpesa->initiatePayment(
            (int) round($amountDue),
            $first,
            $last,
            $phone,
            $email,
            $orderRef
        );
    }

    /**
     * Staff requests admin approval for a luggage overpayment refund
     * (actual weight lower than estimated — negative luggage_refund_amount).
     */
    public function requestRefund(Request $request, $bookingId)
    {
        $ctx = $this->resolveContext();
        $booking = $this->findAuthorizedBooking($bookingId, $ctx);

        $data = $request->validate([
            'phone' => 'nullable|string|max:20',
            'fullname' => 'nullable|string|max:255',
        ]);

        try {
            $this->luggage->requestRefund($booking, Auth::user(), $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route($ctx['show_route'], $booking->id)
            ->with('success', __('vender/luggage.refund_request_sent'));
    }

    public function assign(Request $request, $bookingId)
    {
        $ctx = $this->resolveContext();
        $booking = $this->findAuthorizedBooking($bookingId, $ctx);

        try {
            $this->luggage->assignToBus($booking, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route($ctx['show_route'], $booking->id)
            ->with('success', __('vender/luggage.assigned_success'));
    }

    public function reclaim(Request $request, $bookingId)
    {
        $ctx = $this->resolveContext();
        $booking = $this->findAuthorizedBooking($bookingId, $ctx);

        $data = $request->validate([
            'qr_payload' => 'required|string|max:200',
        ]);

        if (!$this->luggage->matchesCompanyQr($booking, $data['qr_payload'])) {
            return back()->with('error', __('vender/luggage.qr_mismatch'));
        }

        try {
            $this->luggage->reclaim($booking, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route($ctx['show_route'], $booking->id)
            ->with('success', __('vender/luggage.reclaimed_success'));
    }

    /**
     * Destination exit: scan company luggage QR (booking_code|XLUG) and mark retrieved.
     */
    public function scanForm()
    {
        $ctx = $this->resolveContext();

        return view($ctx['scan_view'], compact('ctx'));
    }

    public function scanExit(Request $request)
    {
        $ctx = $this->resolveContext();
        $data = $request->validate([
            'qr_payload' => 'required|string|max:200',
        ]);

        $booking = $this->luggage->findByCompanyQr($data['qr_payload']);
        if (!$booking) {
            return back()
                ->withInput()
                ->with('error', __('vender/luggage.qr_invalid'));
        }

        $authorized = $this->baseQuery($ctx)->whereKey($booking->id)->exists();
        if (!$authorized) {
            return back()
                ->withInput()
                ->with('error', __('vender/luggage.qr_unauthorized'));
        }

        $hasLuggage = (int) ($booking->has_excess_luggage ?? 0) === 1
            || (float) ($booking->excess_luggage_fee ?? 0) > 0
            || !empty($booking->luggage_status);

        if (!$hasLuggage) {
            return back()
                ->withInput()
                ->with('error', __('vender/luggage.qr_no_luggage'));
        }

        try {
            $this->luggage->reclaim($booking, Auth::user());
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route($ctx['show_route'], $booking->id)
            ->with('success', __('vender/luggage.reclaimed_success'));
    }

    public function printReceipt($bookingId)
    {
        $ctx = $this->resolveContext();
        $booking = $this->findAuthorizedBooking($bookingId, $ctx, true);

        if (!$this->luggage->canPrintReceipt($booking)) {
            return redirect()
                ->route($ctx['show_route'], $booking->id)
                ->with('error', __('vender/luggage.print_payment_required'));
        }

        $busCompany = optional(optional($booking->bus)->campany ?? $booking->campany);
        $busOwnerAccount = optional($busCompany)->busOwnerAccount;

        $traQrCode = null;
        if (!empty($booking->tra_qr_url)) {
            $traQrPng = DNS2D::getBarcodePNG($booking->tra_qr_url, 'QRCODE', 4, 4, [0, 0, 0]);
            $traQrCode = $traQrPng
                ? '<img src="data:image/png;base64,' . $traQrPng . '" alt="TRA QR" width="68" height="68">'
                : null;
        }

        $luggageQrPayload = $this->luggage->buildCompanyQrPayload($booking);
        $luggageQrPng = DNS2D::getBarcodePNG($luggageQrPayload, 'QRCODE', 4, 4, [0, 0, 0]);
        $luggageQrCode = $luggageQrPng
            ? '<img src="data:image/png;base64,' . $luggageQrPng . '" alt="Luggage QR" width="68" height="68">'
            : null;

        $status = $this->luggage->normalizeStatus($booking);

        $pdf = Pdf::loadView('print.excess_luggage_receipt', compact(
            'booking',
            'busOwnerAccount',
            'busCompany',
            'traQrCode',
            'luggageQrCode',
            'status'
        ));
        $pdf->setPaper([0, 0, 4 * 72, 9 * 72], 'portrait');

        return $pdf->stream('excess-luggage-receipt-' . $booking->booking_code . '.pdf');
    }

    /** System admin tracking (all companies). */
    public function adminIndex(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && (
            $user->hasAccess(\App\Models\Access::LINKS['SYSTEM_INCOME'])
            || $user->hasAccess(\App\Models\Access::LINKS['BOOKING_HISTORY'])
        ), 403);

        $query = Booking::query()
            ->with(['bus.campany', 'bus.route', 'campany', 'route'])
            ->where(function ($q) {
                $q->where('has_excess_luggage', 1)
                    ->orWhere('excess_luggage_fee', '>', 0)
                    ->orWhereNotNull('luggage_status');
            });

        if ($status = $request->query('status')) {
            $query->where('luggage_status', $status);
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhere('verification_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Surface pending luggage refunds first for admin processing.
        $bookings = $query
            ->orderByRaw("CASE WHEN luggage_payment_status = ? THEN 0 ELSE 1 END", [
                ExcessLuggageService::PAYMENT_REFUND_PENDING,
            ])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('system.excess_luggage.index', [
            'bookings' => $bookings,
            'filters' => ['status' => $status, 'q' => $search],
            'luggageService' => $this->luggage,
            'isAdmin' => true,
        ]);
    }

    public function adminApproveRefund($bookingId)
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && (
            $user->hasAccess(\App\Models\Access::LINKS['SYSTEM_INCOME'])
            || $user->hasAccess(\App\Models\Access::LINKS['BOOKING_HISTORY'])
            || $user->hasAccess(\App\Models\Access::LINKS['REFUNDS'])
        ), 403);

        $booking = Booking::findOrFail($bookingId);

        try {
            $this->luggage->approveRefund($booking, $user);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('vender/luggage.refund_approved'));
    }

    public function adminRejectRefund($bookingId)
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && (
            $user->hasAccess(\App\Models\Access::LINKS['SYSTEM_INCOME'])
            || $user->hasAccess(\App\Models\Access::LINKS['BOOKING_HISTORY'])
            || $user->hasAccess(\App\Models\Access::LINKS['REFUNDS'])
        ), 403);

        $booking = Booking::findOrFail($bookingId);

        try {
            $this->luggage->rejectRefund($booking, $user);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('error', __('vender/luggage.refund_rejected'));
    }

    private function resolveContext(): array
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'isVender') && $user->isVender()) {
            return [
                'role' => 'vender',
                'index_view' => 'vender.excess_luggage.index',
                'lookup_view' => 'vender.excess_luggage.lookup',
                'scan_view' => 'vender.excess_luggage.scan',
                'show_view' => 'vender.excess_luggage.show',
                'index_route' => 'vender.excess_luggage.index',
                'lookup_route' => 'vender.excess_luggage.lookup',
                'lookup_post' => 'vender.excess_luggage.lookup.post',
                'scan_route' => 'vender.excess_luggage.scan',
                'scan_post' => 'vender.excess_luggage.scan.post',
                'show_route' => 'vender.excess_luggage.show',
                'weigh_route' => 'vender.excess_luggage.weigh',
                'pay_route' => 'vender.excess_luggage.pay',
                'refund_route' => 'vender.excess_luggage.refund',
                'assign_route' => 'vender.excess_luggage.assign',
                'reclaim_route' => 'vender.excess_luggage.reclaim',
                'print_route' => 'vender.excess_luggage.print',
                'layout' => 'vender.app',
            ];
        }

        return [
            'role' => 'bus_owner',
            'index_view' => 'bus_owner.excess_luggage.index',
            'lookup_view' => 'bus_owner.excess_luggage.lookup',
            'scan_view' => 'bus_owner.excess_luggage.scan',
            'show_view' => 'bus_owner.excess_luggage.show',
            'index_route' => 'bus_owner.excess_luggage.index',
            'lookup_route' => 'bus_owner.excess_luggage.lookup',
            'lookup_post' => 'bus_owner.excess_luggage.lookup.post',
            'scan_route' => 'bus_owner.excess_luggage.scan',
            'scan_post' => 'bus_owner.excess_luggage.scan.post',
            'show_route' => 'bus_owner.excess_luggage.show',
            'weigh_route' => 'bus_owner.excess_luggage.weigh',
            'pay_route' => 'bus_owner.excess_luggage.pay',
            'refund_route' => 'bus_owner.excess_luggage.refund',
            'assign_route' => 'bus_owner.excess_luggage.assign',
            'reclaim_route' => 'bus_owner.excess_luggage.reclaim',
            'print_route' => 'bus_owner.excess_luggage.print',
            'layout' => 'admin.app',
        ];
    }

    private function baseQuery(array $ctx)
    {
        $user = Auth::user();

        if ($ctx['role'] === 'vender') {
            // Vendors may assist any paid passenger (ticket / verification lookup),
            // same operational model as parcels across companies.
            return Booking::query();
        }

        $companyId = $user->campany->id ?? null;
        abort_unless($companyId, 403);

        return Booking::query()->whereHas('bus', function ($q) use ($companyId) {
            $q->where('campany_id', $companyId);
        });
    }

    private function findAuthorizedBooking($bookingId, array $ctx, bool $withRelations = false): Booking
    {
        $query = $this->baseQuery($ctx);
        if ($withRelations) {
            $query->with(['bus.campany.busOwnerAccount', 'campany.busOwnerAccount', 'schedule', 'route', 'bus.route']);
        }

        $booking = $query->find($bookingId);
        abort_unless($booking, 404);

        return $booking;
    }
}
