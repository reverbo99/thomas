<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\bus;
use App\Services\TraVfdService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;

class ParcelController extends Controller
{
    public function index(Request $request)
    {
        $venderId = auth()->id();
        $parcels = Parcel::with('bus.campany', 'bus.schedule')
            ->where('vender_id', $venderId)
            ->latest()
            ->paginate(10);

        $parcelStats = [
            'total' => Parcel::where('vender_id', $venderId)->count(),
            'amount' => (float) Parcel::where('vender_id', $venderId)->sum('amount_paid'),
            'today' => Parcel::where('vender_id', $venderId)->whereDate('created_at', today())->count(),
            'assigned' => Parcel::where('vender_id', $venderId)->where('status', 'pending')->count(),
        ];

        return view('vender.parcels.index', compact('parcels', 'parcelStats'));
    }

    public function searchBus(Request $request)
    {
        $query = $request->get('query');
        
        $busQuery = bus::with(['campany', 'schedule', 'route'])
            ->whereHas('campany', function($q) {
                $q->where('status', 1);
            });

        if ($query) {
            $busQuery->where(function($q) use ($query) {
                $q->where('bus_number', 'LIKE', "%{$query}%")
                  ->orWhereHas('campany', function($cq) use ($query) {
                      $cq->where('name', 'LIKE', "%{$query}%");
                  });
            });
        }

        $buses = $busQuery->get();
            
        return view('vender.parcels.find_bus', compact('buses'));
    }

    public function create($bus_id)
    {
        $bus = bus::with('campany')->findOrFail($bus_id);
        return view('vender.parcels.create', compact('bus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'parcel_number' => 'required|string|unique:parcels,parcel_number',
            'parcel_type' => 'required|string',
            'description' => 'nullable|string',
            'amount_paid' => 'required|numeric|min:0',
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
        ]);

        $parcel = Parcel::create([
            'bus_id' => $request->bus_id,
            'parcel_number' => $request->parcel_number,
            'parcel_type' => $request->parcel_type,
            'description' => $request->description,
            'amount_paid' => $request->amount_paid,
            'weight' => $request->weight,
            'height' => $request->height,
            'width' => $request->width,
            'length' => $request->length,
            'status' => 'pending',
            'vender_id' => auth()->id(),
            'sender_name' => $request->sender_name,
            'sender_contact' => $request->sender_contact,
            'parcel_instructions' => $request->parcel_instructions,
            'receiver_name' => $request->receiver_name,
            'receiver_contact_1' => $request->receiver_contact_1,
            'receiver_contact_2' => $request->receiver_contact_2,
            'receiver_delivery_address' => $request->receiver_delivery_address,
        ]);

        (new TraVfdService())->fiscalize($parcel->refresh());

        try {
            $parcel->loadMissing('bus.campany', 'bus.route');
            $busCompanyName = $parcel->bus->campany->name ?? 'N/A';
            $busCity = $parcel->bus->route->from ?? 'N/A';
            $deliveryAddress = $parcel->receiver_delivery_address ?? 'N/A';

            $parcelSms = "Mpendwa {$parcel->receiver_name}, Mzigo wako nambari {$parcel->parcel_number} umepokelewa katika ofisi za {$busCompanyName} hapa {$busCity} tayari kusafirishwa kuelekea {$deliveryAddress} wa kupokelea. Utapokea taarifa kutoka {$busCompanyName} mara baada ya mzigo wako utakapowasili.";

            if (!empty($parcel->receiver_contact_1)) {
                (new SmsController())->sms_send($parcel->receiver_contact_1, $parcelSms);
            }
        } catch (\Exception $e) {
            Log::warning('Parcel SMS failed: ' . $e->getMessage(), ['parcel_id' => $parcel->id]);
        }

        return redirect()->route('vender.parcels.index')->with('success', __('vender/parcels.parcel_added_success'));
    }

    public function print($id)
    {
        $user = Auth::user();

        $parcel = Parcel::with(['bus.campany.busOwnerAccount', 'bus.schedule', 'vender'])->findOrFail($id);

        $ownsAsVendor = $parcel->vender_id === $user->id;
        $ownsAsBusOwner = $user->campany && $parcel->bus && $parcel->bus->campany_id === $user->campany->id;

        if (!$ownsAsVendor && !$ownsAsBusOwner) {
            abort(403);
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
        $parcel = Parcel::findOrFail($id);
        
        // Ensure proper authorization - only bus owner company should initiate this for now based on requirement
        // Assuming this route is protected by bus-company middleware or similar check
        
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $parcel->update(['status' => $request->status]);

        return back()->with('success', __('vender/parcels.parcel_status_updated'));
    }

    public function toggleAcceptance(Request $request)
    {
        $bus = Bus::findOrFail($request->bus_id);
        
        // Authorization check needed here ideally to ensure user owns the bus
        if ($bus->campany_id !== auth()->user()->campany->id) {
             return back()->with('error', __('vender/parcels.unauthorized_action'));
        }

        $bus->accept_parcels = !$bus->accept_parcels;
        $bus->save();

        return back()->with('success', __('vender/parcels.parcel_acceptance_updated'));
    }
}
