<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ClickPesaController;
use App\Models\bus;
use App\Models\City;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ParcelFlowService;
use App\Services\TraVfdService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;

class ParcelController extends Controller
{
    public function __construct(private ParcelFlowService $flow)
    {
    }

    public function index(Request $request)
    {
        $venderId = auth()->id();
        $parcels = Parcel::with('bus.campany', 'bus.schedule')
            ->where('vender_id', $venderId)
            ->latest()
            ->paginate(15);

        $parcelStats = [
            'total' => Parcel::where('vender_id', $venderId)->count(),
            'amount' => (float) Parcel::where('vender_id', $venderId)->where('payment_status', ParcelFlowService::PAY_PAID)->sum('amount_paid'),
            'today' => Parcel::where('vender_id', $venderId)->whereDate('created_at', today())->count(),
            'assigned' => Parcel::where('vender_id', $venderId)->whereIn('status', [
                ParcelFlowService::STATUS_REGISTERED,
                ParcelFlowService::STATUS_PENDING,
                ParcelFlowService::STATUS_IN_TRANSIT,
            ])->count(),
        ];

        return view('vender.parcels.index', [
            'parcels' => $parcels,
            'parcelStats' => $parcelStats,
            'flow' => $this->flow,
        ]);
    }

    public function searchBus(Request $request)
    {
        $query = $request->get('query');
        $fromCityId = $request->get('from');
        $toCityId = $request->get('to');
        $departureDate = $request->get('departure_date');
        $isOwner = $this->isBusOwnerContext();

        $fromName = $fromCityId ? (City::find($fromCityId)->name ?? null) : null;
        $toName = $toCityId ? (City::find($toCityId)->name ?? null) : null;

        $busQuery = bus::with([
            'campany',
            'route',
            'schedule' => function ($q) use ($fromName, $toName, $departureDate) {
                if ($fromName) {
                    $q->where('from', $fromName);
                }
                if ($toName) {
                    $q->where('to', $toName);
                }
                if ($departureDate) {
                    $q->whereDate('schedule_date', $departureDate);
                }
            },
        ])
            ->where('accept_parcels', true)
            ->whereHas('campany', function ($q) {
                $q->where('status', 1);
            });

        if ($isOwner) {
            $companyId = Auth::user()->campany->id ?? null;
            abort_unless($companyId, 403);
            $busQuery->where('campany_id', $companyId);
        }

        if ($query) {
            $busQuery->where(function ($q) use ($query) {
                $q->where('bus_number', 'LIKE', "%{$query}%")
                    ->orWhereHas('campany', function ($cq) use ($query) {
                        $cq->where('name', 'LIKE', "%{$query}%");
                    });
            });
        }

        if ($fromName || $toName || $departureDate) {
            $busQuery->where(function ($outer) use ($fromName, $toName, $departureDate) {
                $outer->whereHas('schedules', function ($q) use ($fromName, $toName, $departureDate) {
                    if ($fromName) {
                        $q->where('from', $fromName);
                    }
                    if ($toName) {
                        $q->where('to', $toName);
                    }
                    if ($departureDate) {
                        $q->whereDate('schedule_date', $departureDate);
                    }
                });

                // Also match buses whose declared route from/to aligns when no date filter.
                if (($fromName || $toName) && !$departureDate) {
                    $outer->orWhereHas('route', function ($q) use ($fromName, $toName) {
                        if ($fromName) {
                            $q->where('from', $fromName);
                        }
                        if ($toName) {
                            $q->where('to', $toName);
                        }
                    });
                }
            });
        }

        $buses = $busQuery->get()->map(function ($bus) {
            $bus->parcel_weight_used = (float) Parcel::where('bus_id', $bus->id)
                ->whereNotIn('status', [
                    ParcelFlowService::STATUS_CANCELLED,
                    ParcelFlowService::STATUS_COMPLETED,
                    ParcelFlowService::STATUS_AWAITING_PAYMENT,
                ])
                ->sum('weight');

            return $bus;
        });

        $cities = City::orderBy('name')->get(['id', 'name']);
        $view = $isOwner ? 'bus_owner.parcels.find_bus' : 'vender.parcels.find_bus';

        return view($view, compact('buses', 'cities'));
    }

    public function create($bus_id)
    {
        $bus = bus::with(['campany', 'route', 'schedule'])->findOrFail($bus_id);
        $this->assertCanUseBus($bus);

        try {
            $this->flow->assertBusAcceptsParcels($bus);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $view = $this->isBusOwnerContext() ? 'bus_owner.parcels.create' : 'vender.parcels.create';
        $storeRoute = $this->isBusOwnerContext() ? 'bus_owner.parcels.store' : 'vender.parcels.store';

        return view($view, compact('bus', 'storeRoute'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'parcel_number' => 'required|string|unique:parcels,parcel_number',
            'parcel_type' => 'required|string',
            'description' => 'nullable|string',
            'amount_paid' => 'required|numeric|min:1',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'sender_name' => 'required|string',
            'sender_contact' => 'required|string',
            'parcel_instructions' => 'required|in:collection,delivery',
            'receiver_name' => 'required|string',
            'receiver_contact_1' => 'required|string',
            'receiver_contact_2' => 'nullable|string',
            'receiver_delivery_address' => 'required|string',
            'receiving_agent_name' => 'nullable|string|max:150',
            'receiving_agent_phone' => 'nullable|string|max:40',
            'delivery_rider_name' => 'nullable|string|max:150',
            'delivery_rider_phone' => 'nullable|string|max:40',
            'phone' => 'nullable|string|max:20',
        ]);

        $bus = bus::with('campany')->findOrFail($data['bus_id']);
        $this->assertCanUseBus($bus);

        try {
            $this->flow->assertBusAcceptsParcels($bus);
            $this->flow->assertCapacity($bus, isset($data['weight']) ? (float) $data['weight'] : null);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $isVendor = Auth::user()->isVender();

        $isDelivery = ($data['parcel_instructions'] ?? '') === 'delivery';

        try {
            $parcel = Parcel::create([
                'bus_id' => $data['bus_id'],
                'parcel_number' => $data['parcel_number'],
                'parcel_type' => $data['parcel_type'],
                'description' => $data['description'] ?? null,
                'amount_paid' => $data['amount_paid'],
                'weight' => $data['weight'] ?? null,
                'height' => $data['height'] ?? null,
                'width' => $data['width'] ?? null,
                'length' => $data['length'] ?? null,
                'status' => ParcelFlowService::STATUS_AWAITING_PAYMENT,
                'payment_status' => ParcelFlowService::PAY_UNPAID,
                'vender_id' => $isVendor ? Auth::id() : null,
                'created_by' => Auth::id(),
                'sender_name' => $data['sender_name'],
                'sender_contact' => $data['sender_contact'],
                'parcel_instructions' => $data['parcel_instructions'],
                'receiver_name' => $data['receiver_name'],
                'receiver_contact_1' => $data['receiver_contact_1'],
                'receiver_contact_2' => $data['receiver_contact_2'] ?? null,
                'receiver_delivery_address' => $data['receiver_delivery_address'],
                'receiving_agent_name' => $isDelivery ? ($data['receiving_agent_name'] ?? null) : null,
                'receiving_agent_phone' => $isDelivery ? ($data['receiving_agent_phone'] ?? null) : null,
                'delivery_rider_name' => $isDelivery ? ($data['delivery_rider_name'] ?? null) : null,
                'delivery_rider_phone' => $isDelivery ? ($data['delivery_rider_phone'] ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Parcel register failed before ClickPesa', [
                'user_id' => Auth::id(),
                'bus_id' => $data['bus_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with(
                'error',
                __('vender/parcels.register_failed')
            );
        }

        return $this->startClickPesaPayment($parcel, $data['phone'] ?? $parcel->sender_contact);
    }

    public function pay(Request $request, $id)
    {
        $parcel = $this->findAuthorizedParcel($id);
        if ($parcel->payment_status === ParcelFlowService::PAY_PAID) {
            return redirect($this->showUrl($parcel))->with('success', __('vender/parcels.already_paid'));
        }

        $request->validate(['phone' => 'nullable|string|max:20']);

        return $this->startClickPesaPayment($parcel, $request->phone ?: $parcel->sender_contact);
    }

    public function show($id)
    {
        $parcel = $this->findAuthorizedParcel($id, true);
        $view = $this->isBusOwnerContext() ? 'bus_owner.parcels.show' : 'vender.parcels.show';

        return view($view, [
            'parcel' => $parcel,
            'flow' => $this->flow,
            'status' => $this->flow->normalizeStatus($parcel),
        ]);
    }

    public function assign(Request $request, $id)
    {
        $parcel = $this->findAuthorizedParcel($id);

        if (($parcel->parcel_instructions ?? '') === 'collection') {
            // Collection: agent/rider assignment is not used.
            $data = $request->validate([
                'bus_id' => 'nullable|exists:buses,id',
            ]);
            $data['receiving_agent_name'] = null;
            $data['receiving_agent_phone'] = null;
            $data['delivery_rider_name'] = null;
            $data['delivery_rider_phone'] = null;
        } else {
            $data = $request->validate([
                'bus_id' => 'nullable|exists:buses,id',
                'receiving_agent_name' => 'nullable|string|max:150',
                'receiving_agent_phone' => 'nullable|string|max:40',
                'delivery_rider_name' => 'nullable|string|max:150',
                'delivery_rider_phone' => 'nullable|string|max:40',
            ]);
        }

        if (!empty($data['bus_id'])) {
            $bus = bus::findOrFail($data['bus_id']);
            $this->assertCanUseBus($bus);
            try {
                $this->flow->assertBusAcceptsParcels($bus);
                $this->flow->assertCapacity($bus, (float) $parcel->weight, $parcel->id);
            } catch (\RuntimeException $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        $this->flow->assignReceivingAgent($parcel, $data, Auth::user());

        return back()->with('success', __('vender/parcels.assigned_success'));
    }

    public function depart(Request $request, $id)
    {
        $parcel = $this->findAuthorizedParcel($id);
        try {
            $this->flow->markDeparted($parcel);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('vender/parcels.departed_success'));
    }

    public function arrive(Request $request, $id)
    {
        $parcel = $this->findAuthorizedParcel($id);
        try {
            $this->flow->markArrived($parcel);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('vender/parcels.arrived_success'));
    }

    public function collect(Request $request, $id)
    {
        $parcel = $this->findAuthorizedParcel($id);
        $request->validate([
            'tracking_number' => 'required|string|max:100',
        ]);

        try {
            $this->flow->collect($parcel, $request->tracking_number, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('vender/parcels.collected_success'));
    }

    public function print($id)
    {
        $parcel = $this->findAuthorizedParcel($id, true);

        if (!$this->flow->canPrintReceipt($parcel)) {
            return redirect($this->showUrl($parcel))
                ->with('error', __('vender/parcels.print_payment_required'));
        }

        $busCompany = $parcel->bus->campany ?? null;
        $busOwnerAccount = $busCompany->busOwnerAccount ?? null;

        $traQrCode = null;
        if (!empty($parcel->tra_qr_url)) {
            $traQrPng = DNS2D::getBarcodePNG($parcel->tra_qr_url, 'QRCODE', 4, 4, [0, 0, 0]);
            $traQrCode = $traQrPng
                ? '<img src="data:image/png;base64,' . $traQrPng . '" alt="TRA QR" width="68" height="68">'
                : null;
        }

        $pdf = Pdf::loadView('print.parcel_receipt', compact('parcel', 'busCompany', 'busOwnerAccount', 'traQrCode'));
        $pdf->setPaper([0, 0, 4 * 72, 9 * 72], 'portrait');

        return $pdf->stream('parcel-receipt-' . $parcel->parcel_number . '.pdf');
    }

    public function updateStatus(Request $request, $id)
    {
        $parcel = $this->findAuthorizedParcel($id);

        $request->validate([
            'status' => 'required|in:pending,registered,in_transit,arrived,completed,cancelled,awaiting_payment',
        ]);

        // Prefer dedicated lifecycle actions; keep for simple cancel.
        if ($request->status === 'cancelled') {
            $parcel->update(['status' => ParcelFlowService::STATUS_CANCELLED]);
        } else {
            $parcel->update(['status' => $request->status]);
        }

        return back()->with('success', __('vender/parcels.parcel_status_updated'));
    }

    public function toggleAcceptance(Request $request)
    {
        $bus = bus::findOrFail($request->bus_id);

        if ($bus->campany_id !== auth()->user()->campany->id) {
            return back()->with('error', __('vender/parcels.unauthorized_action'));
        }

        $bus->accept_parcels = !$bus->accept_parcels;
        $bus->save();

        return back()->with('success', __('vender/parcels.parcel_acceptance_updated'));
    }

    public function updateCapacity(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'max_parcel_weight_kg' => 'nullable|numeric|min:0',
        ]);

        $bus = bus::findOrFail($request->bus_id);
        if ($bus->campany_id !== (Auth::user()->campany->id ?? null)) {
            return back()->with('error', __('vender/parcels.unauthorized_action'));
        }

        $bus->update(['max_parcel_weight_kg' => $request->max_parcel_weight_kg]);

        return back()->with('success', __('vender/parcels.capacity_updated'));
    }

    public function manifest(Request $request)
    {
        $this->assertBusOwner();
        $companyId = Auth::user()->campany->id;

        $query = Parcel::with(['bus.campany', 'bus.schedule', 'bus.route'])
            ->whereHas('bus', fn ($q) => $q->where('campany_id', $companyId))
            ->where(function ($q) {
                $q->where('payment_status', ParcelFlowService::PAY_PAID)
                    ->orWhereNull('payment_status');
            })
            ->where('status', '!=', ParcelFlowService::STATUS_CANCELLED);

        if ($busId = $request->query('bus_id')) {
            $query->where('bus_id', $busId);
        }
        if ($date = $request->query('travel_date')) {
            $query->whereHas('bus.schedule', function ($q) use ($date) {
                $q->whereDate('schedule_date', $date);
            });
        }
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($qq) use ($q) {
                $qq->where('parcel_number', 'like', "%{$q}%")
                    ->orWhere('sender_name', 'like', "%{$q}%")
                    ->orWhere('receiver_name', 'like', "%{$q}%");
            });
        }

        $parcels = $query->latest()->paginate(50)->withQueryString();
        $buses = bus::where('campany_id', $companyId)->orderBy('bus_number')->get();

        if ($request->query('print') == '1') {
            $pdf = Pdf::loadView('print.parcel_manifest', [
                'parcels' => $parcels->getCollection(),
                'filters' => $request->only(['bus_id', 'travel_date', 'q']),
                'company' => Auth::user()->campany,
            ]);

            return $pdf->stream('parcel-manifest.pdf');
        }

        return view('bus_owner.parcels.manifest', compact('parcels', 'buses'));
    }

    public function adminIndex(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && (
            $user->hasAccess(\App\Models\Access::LINKS['SYSTEM_INCOME'])
            || $user->hasAccess(\App\Models\Access::LINKS['BOOKING_HISTORY'])
        ), 403);

        $query = Parcel::with(['bus.campany', 'bus.route', 'vender'])->latest();
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($qq) use ($q) {
                $qq->where('parcel_number', 'like', "%{$q}%")
                    ->orWhere('sender_name', 'like', "%{$q}%")
                    ->orWhere('receiver_name', 'like', "%{$q}%");
            });
        }

        $parcels = $query->paginate(25)->withQueryString();

        return view('system.parcels.index', [
            'parcels' => $parcels,
            'flow' => $this->flow,
            'filters' => ['status' => $status ?? '', 'q' => $q ?? ''],
        ]);
    }

    public function adminManifest(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && $user->hasAccess(\App\Models\Access::LINKS['SYSTEM_INCOME']), 403);

        $query = Parcel::with(['bus.campany', 'bus.schedule', 'bus.route'])
            ->where(function ($q) {
                $q->where('payment_status', ParcelFlowService::PAY_PAID)
                    ->orWhereNull('payment_status');
            })
            ->where('status', '!=', ParcelFlowService::STATUS_CANCELLED);

        if ($busId = $request->query('bus_id')) {
            $query->where('bus_id', $busId);
        }
        if ($date = $request->query('travel_date')) {
            $query->whereHas('bus.schedule', fn ($q) => $q->whereDate('schedule_date', $date));
        }

        $parcels = $query->latest()->limit(500)->get();

        $pdf = Pdf::loadView('print.parcel_manifest', [
            'parcels' => $parcels,
            'filters' => $request->only(['bus_id', 'travel_date']),
            'company' => null,
        ]);

        return $pdf->stream('admin-parcel-manifest.pdf');
    }

    /** Called after ClickPesa confirms parcel payment. */
    public function finalizeAfterPayment(Parcel $parcel): void
    {
        try {
            (new TraVfdService())->fiscalize($parcel->refresh());
        } catch (\Throwable $e) {
            Log::error('Parcel TRA fiscalize failed after payment', [
                'parcel_id' => $parcel->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->flow->notifyRegistered($parcel->fresh(['bus.campany', 'bus.route']));
    }

    private function startClickPesaPayment(Parcel $parcel, ?string $phone)
    {
        $normalized = ClickPesaController::normalizeTanzaniaMsisdnForClickPesa((string) $phone);
        if (!$normalized['ok']) {
            return redirect($this->showUrl($parcel))->with(
                'error',
                $normalized['error'] ?? __('vender/parcels.phone_required')
            );
        }
        $phone = $normalized['phone'];

        $orderRef = $this->flow->buildPaymentReference($parcel);
        $parcel->update([
            'payment_ref' => $orderRef,
            'payment_status' => ParcelFlowService::PAY_PENDING,
            'status' => ParcelFlowService::STATUS_AWAITING_PAYMENT,
        ]);

        Session::put('parcel_payment', [
            'parcel_id' => $parcel->id,
            'order_ref' => $orderRef,
            'return_route' => $this->isBusOwnerContext()
                ? 'bus_owner.parcels.show'
                : 'vender.parcels.show',
        ]);
        Session::forget(['booking', 'vender', 'booking1', 'booking2', 'excess_luggage_payment']);

        $name = $parcel->sender_name ?: 'Sender';
        $parts = preg_split('/\s+/', trim($name), 2);

        return (new ClickPesaController())->initiatePayment(
            (int) round((float) $parcel->amount_paid),
            $parts[0] ?? 'Sender',
            $parts[1] ?? ($parts[0] ?? 'Sender'),
            $phone,
            'noreply@highlink.local',
            $orderRef
        );
    }

    private function isBusOwnerContext(): bool
    {
        $user = Auth::user();

        return $user && !$user->isVender() && ($user->isBusCampany() || $user->isLocalBusOwner());
    }

    private function assertBusOwner(): void
    {
        abort_unless($this->isBusOwnerContext(), 403);
    }

    private function assertCanUseBus(bus $bus): void
    {
        if ($this->isBusOwnerContext()) {
            abort_unless($bus->campany_id === (Auth::user()->campany->id ?? null), 403);
        }
    }

    private function findAuthorizedParcel($id, bool $with = false): Parcel
    {
        $query = Parcel::query();
        if ($with) {
            $query->with(['bus.campany.busOwnerAccount', 'bus.schedule', 'bus.route', 'vender']);
        }
        $parcel = $query->findOrFail($id);
        $user = Auth::user();

        if ($user->isVender()) {
            abort_unless(
                $parcel->vender_id === $user->id || $parcel->created_by === $user->id,
                403
            );

            return $parcel;
        }

        if ($this->isBusOwnerContext()) {
            abort_unless(
                $user->campany && $parcel->bus && $parcel->bus->campany_id === $user->campany->id,
                403
            );

            return $parcel;
        }

        abort(403);
    }

    private function showUrl(Parcel $parcel): string
    {
        $route = $this->isBusOwnerContext() ? 'bus_owner.parcels.show' : 'vender.parcels.show';

        return route($route, $parcel->id);
    }
}
