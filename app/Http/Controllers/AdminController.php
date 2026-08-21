<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Access;
use App\Models\Campany;
use App\Models\ExcessLuggageEscrow;
use App\Models\bus;
use App\Models\City;
use App\Models\Parcel;
use App\Models\Point;
use App\Models\route;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Via;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Setting; // Added this line
use App\Services\BookingTransferService;
use App\Services\FareFormulaService;
use App\Services\ParcelFlowService;
use Milon\Barcode\Facades\DNS2DFacade as DNS2D;
use PhpParser\Node\Expr\FuncCall;
use Yoeunes\Toastr\Toastr;

class AdminController extends Controller
{
    public function index()
    {
        // Get bus IDs for the authenticated user's company
        //return auth()->user()->access;
        $user = auth()->user();
        $companyId = $user->campany ? $user->campany->id : null;
        if (!$companyId) {
            return view('controller.home', [
                'summary' => [],
                'recentBookings' => collect([]),
                'todaysTrips' => collect([]),
                'error' => __('vender/earning.no_company_account'),
            ]);
        }

        $bus_ids = Bus::where('campany_id', $companyId)->pluck('id')->toArray();
        if (empty($bus_ids)) {
            return view('controller.home', [
                'summary' => [],
                'recentBookings' => collect([]),
                'todaysTrips' => collect([]),
                'error' => 'No buses found for your company.',
            ]);
        }

        // Summary Cards Data
        $today = Carbon::today();
        $summary = [
            'earnings' => $this->getFormattedEarnings($bus_ids, $today),
            'earnings_change' => $this->calculateEarningsChange($bus_ids, $today),
            'bookings' => Booking::whereDate('travel_date', $today)
                ->whereIn('bus_id', $bus_ids)
                ->where('payment_status', 'Paid')
                ->count(),
            'bookings_change' => $this->calculateBookingsChange($bus_ids, $today),
            'active_buses' => $this->getActiveBuses($bus_ids, $today),
            'buses_status' => $this->getMaintenanceStatus($bus_ids, $today),
            'passengers' => Booking::whereDate('travel_date', $today)
                ->whereIn('bus_id', $bus_ids)
                ->where('payment_status', 'Paid')
                ->count(),
            'occupancy' => $this->calculateOccupancy($bus_ids, $today),
        ];

        // Recent Bookings Data
        $recentBookings = Booking::whereDate('travel_date', $today)
            ->whereIn('bus_id', $bus_ids)
            ->where('payment_status', 'Paid')
            ->with(['route_name', 'bus'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($booking) => [
                'name' => $booking->customer_name ?? 'Unknown',
                'route' => $booking->route_name ? "{$booking->route_name->from}-{$booking->route_name->to}" : 'Unknown',
                'time' => $booking->travel_date ? Carbon::parse($booking->travel_date)->format('h:i A') : 'N/A',
                'amount' => 'T
                sh ' . number_format($booking->busFee, 0, '.', ','),
                'status' => $booking->payment_status ?? 'Pending',
                'status_class' => $this->getStatusClass($booking->payment_status),
                'icon_class' => $this->getIconClass($booking->payment_status),
            ]);

        // Today's Trips Data
        $todaysTrips = Schedule::whereDate('schedule_date', $today)
            ->whereIn('bus_id', $bus_ids)
            ->with(['bus', 'route'])
            ->get()
            ->map(fn($schedule) => [
                'bus' => $schedule->bus->bus_number ?? 'Unknown',
                'route' => $schedule->route ? "{$schedule->route->from}-{$schedule->route->to}" : 'Unknown',
                'time' => $schedule->schedule_date ? Carbon::parse($schedule->schedule_date)->format('h:i A') : 'N/A',
                'status' => $this->determineTripStatus($schedule),
                'status_class' => $this->determineTripStatusClass($schedule),
                'start' => $schedule->schedule_date ? Carbon::parse($schedule->start)->format('h:i A') : 'N/A',
                'schedule_date' => $schedule->schedule_date ? Carbon::parse($schedule->schedule_date)->format('d-m-Y') : 'N/A',
            ]);

        return view('controller.home', compact('summary', 'recentBookings', 'todaysTrips'));
    }

    public function edit_bus($id)
    {
        // Find the bus by ID and load its relationships
        $bus = Bus::with(['busname', 'route', 'schedule'])->findOrFail($id);

        // Return the view with the bus data
        return view('controller.edit_bus', compact('bus'));
    }

    public function transaction_request(Request $request)
    {
        $user = auth()->user();
        // Check if the company balance is sufficient
        if ($request->amount > $user->campany->balance->amount) {
            return back()->with('error', __('vender/earning.insufficient_balance'));
        }
        // Create the transaction and deduct amount from balance (pending state)
        try {
            \DB::beginTransaction();
            
            $transaction = Transaction::create([
                'campany_id' => $user->campany->id, // Update to company_id after migration
                'user_id' => $user->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_number' => $request->payment_number,
                'status' => 'Pending',
            ]);

            // Deduct the amount from balance when request is created (so it's in pending state)
            $balance = $user->campany->balance;
            if ($balance) {
                $balance->amount -= $request->amount;
                $balance->save();
            }

            \DB::commit();
            return back()->with('success', __('vender/earning.transaction_request_sent'));
        } catch (\Exception $e) {
            \DB::rollBack();
            // Log the error for debugging

            return back()->with('error', __('vender/earning.transaction_request_failed'));
        }
    }

    /**
     * Paid booking earnings for the bus owner: fare share (bookings.amount after
     * settlement) + owner share of excess luggage (bus_owner_luggage_fee).
     * Matches wallet credit in BookingSettlementService / ExcessLuggageService top-ups.
     */
    private function sumBusOwnerBookingEarnings($query): float
    {
        return (float) $query
            ->with('vender.VenderAccount')
            ->get(['id', 'amount', 'has_excess_luggage', 'excess_luggage_fee', 'vender_id'])
            ->sum(fn ($booking) => (float) ($booking->amount ?? 0) + bus_owner_luggage_fee($booking));
    }

    /**
     * Bus owner ticket fare share only (bookings.amount after settlement).
     */
    private function sumBusOwnerTicketEarnings($query): float
    {
        return (float) $query->sum('amount');
    }

    /**
     * Released excess luggage owner share credited to company wallet.
     */
    private function sumBusOwnerReleasedLuggageEarnings(int $companyId, Carbon $start, Carbon $end, ?Request $request = null): float
    {
        $query = ExcessLuggageEscrow::query()
            ->whereIn('status', [
                ExcessLuggageEscrow::STATUS_RELEASED,
                ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
            ])
            ->whereBetween('released_at', [$start, $end])
            ->whereHas('booking.bus', fn ($q) => $q->where('campany_id', $companyId));

        if ($request) {
            $query->whereHas('booking', function ($q) use ($request) {
                apply_booking_history_column_filters($q, $request);
            });
        }

        return (float) $query->sum('owner_share');
    }

    /**
     * Bus-owner parcel wallet share for paid/settled parcels in the period.
     */
    private function sumBusOwnerParcelEarnings(array $busIds, Carbon $start, Carbon $end, ?Request $request = null): float
    {
        $query = Parcel::query()
            ->whereIn('bus_id', $busIds)
            ->where('payment_status', ParcelFlowService::PAY_PAID)
            ->whereNotNull('settled_at')
            ->whereBetween('settled_at', [$start, $end]);

        if ($request) {
            apply_bus_relation_column_filters($query, $request);
        }

        $systemPct = (float) (Setting::first()->parcel_commission_percentage ?? 0);

        return (float) $query->get(['amount_paid', 'vender_id'])->sum(
            fn ($parcel) => ParcelFlowService::ownerShareAmount(
                (float) ($parcel->amount_paid ?? 0),
                $parcel->vender_id,
                $systemPct
            )
        );
    }

    private function earningsFilterInputs(Request $request): array
    {
        return $request->only([
            'period',
            'start_date',
            'end_date',
            'bus_number',
            'departure_date',
            'departure_time',
            'arrival_date',
            'arrival_time',
            'driver',
            'conductor',
        ]);
    }

    private function companyTicketEarningsQuery(array $busIds, int $companyId)
    {
        return Booking::query()
            ->where(function ($q) use ($busIds, $companyId) {
                $q->where('campany_id', $companyId)
                    ->orWhereIn('bus_id', $busIds);
            });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parseEarningsPeriod(string $period, ?string $start_date = null, ?string $end_date = null): array
    {
        switch ($period) {
            case 'today':
                $start = Carbon::today();
                $end = Carbon::today()->endOfDay();
                break;
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                break;
            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                break;
            case 'year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                break;
            case 'custom':
                $start = $start_date ? Carbon::parse($start_date)->startOfDay() : Carbon::now()->startOfMonth();
                $end = $end_date ? Carbon::parse($end_date)->endOfDay() : Carbon::now()->endOfMonth();
                break;
            default:
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
        }

        return [$start, $end];
    }

    /**
     * @return array<int>|null
     */
    private function resolveCompanyBusIds(): ?array
    {
        $companyId = Auth::user()->campany?->id;
        if (!$companyId) {
            return null;
        }

        $bus_ids = Bus::where('campany_id', $companyId)->pluck('id')->toArray();

        return empty($bus_ids) ? null : $bus_ids;
    }

    private function getFormattedEarnings(array $bus_ids, Carbon $date): string
    {
        $earnings = $this->sumBusOwnerBookingEarnings(
            Booking::whereDate('travel_date', $date)
                ->whereIn('bus_id', $bus_ids)
                ->where('payment_status', 'Paid')
        );

        return 'Tsh ' . number_format($earnings, 0, '.', ',');
    }

    private function calculateEarningsChange(array $bus_ids, Carbon $today): string
    {
        $todayEarnings = $this->sumBusOwnerBookingEarnings(
            Booking::whereDate('travel_date', $today)
                ->whereIn('bus_id', $bus_ids)
                ->where('payment_status', 'Paid')
        );
        $yesterdayEarnings = $this->sumBusOwnerBookingEarnings(
            Booking::whereDate('travel_date', $today->copy()->subDay())
                ->whereIn('bus_id', $bus_ids)
                ->where('payment_status', 'Paid')
        );

        if ($yesterdayEarnings == 0) {
            return $todayEarnings > 0 ? '+100% from yesterday' : 'No change';
        }

        $percentage = (($todayEarnings - $yesterdayEarnings) / $yesterdayEarnings) * 100;
        return sprintf('%+d%% from yesterday', $percentage);
    }

    private function calculateBookingsChange(array $bus_ids, Carbon $today): string
    {
        $todayBookings = Booking::whereDate('travel_date', $today)
            ->whereIn('bus_id', $bus_ids)
            ->where('payment_status', 'Paid')
            ->count();
        $yesterdayBookings = Booking::whereDate('travel_date', $today->copy()->subDay())
            ->whereIn('bus_id', $bus_ids)
            ->where('payment_status', 'Paid')
            ->count();

        return sprintf('%+d bookings', $todayBookings - $yesterdayBookings);
    }

    private function getActiveBuses(array $bus_ids, Carbon $today): string
    {
        $active = Bus::whereIn('id', $bus_ids)
            ->whereHas('schedules', fn($q) => $q->whereDate('schedule_date', $today))
            ->count();
        $total = count($bus_ids);
        return "$active/$total";
    }

    private function getMaintenanceStatus(array $bus_ids, Carbon $today): string
    {
        $inactive = Bus::whereIn('id', $bus_ids)
            ->whereDoesntHave('schedules', fn($q) => $q->whereDate('schedule_date', $today))
            ->count();
        return "$inactive in maintenance";
    }

    private function calculateOccupancy(array $bus_ids, Carbon $today): string
    {
        $totalSeats = Bus::whereIn('id', $bus_ids)->sum('total_seats');
        $bookedSeats = Booking::whereDate('travel_date', $today)
            ->where('payment_status', 'Paid')
            ->whereIn('bus_id', $bus_ids)
            ->count();

        if ($totalSeats == 0) {
            return 'N/A';
        }

        $occupancy = ($bookedSeats / $totalSeats) * 100;
        return sprintf('Avg. %.0f%% occupancy', $occupancy);
    }

    private function determineTripStatus($schedule): string
    {
        $now = Carbon::now();
        $scheduleTime = Carbon::parse($schedule->schedule_date);

        if ($now->greaterThan($scheduleTime->copy()->addMinutes(15))) {
            return 'Delayed (15 min)';
        } elseif ($now->greaterThanOrEqualTo($scheduleTime)) {
            return 'Boarding';
        }

        return 'On Time';
    }

    private function determineTripStatusClass($schedule): string
    {
        return match ($this->determineTripStatus($schedule)) {
            'On Time' => 'success',
            'Boarding' => 'info',
            'Delayed (15 min)' => 'warning',
            default => 'info',
        };
    }

    private function getStatusClass(?string $status): string
    {
        return match ($status ?? 'Pending') {
            'Paid' => 'success',
            'Pending' => 'warning',
            'Failed' => 'danger',
            'VIP' => 'success',
            default => 'warning',
        };
    }

    private function getIconClass(?string $status): string
    {
        return match ($status ?? 'Pending') {
            'Paid' => 'primary',
            'Pending' => 'warning',
            'Failed' => 'danger',
            'VIP' => 'success',
            default => 'warning',
        };
    }
    //////////////////////////////////////////////////////////////////////////
    public function buses()
    {
        $buses = bus::with('busname', 'route')->where('campany_id', auth()->user()->campany->id)->get();
        return view('controller.buses', compact('buses'));
    }
    //////add_buss///////////////
    public function add_bus()
    {
        $cities = City::all();
        return view('controller.add_bus', compact('cities'));
    }

    public function get_bus(Request $request)
    {
        //return $request->all();

        $validated = $request->validate([
            'bus_number' => 'required|string|unique:buses,bus_number',
            'bus_type' => 'required|string',
            'total_seats' => 'required|integer|min:1',
            'conductor_phone' => 'required|string',
            'driver_name' => 'nullable|string|max:255',
            'driver_contact' => 'nullable|string|max:255',
            'conductor_name' => 'nullable|string|max:255',
            'customer_service_name_1' => 'nullable|string|max:255',
            'customer_service_contact_1' => 'nullable|string|max:255',
            'customer_service_name_2' => 'nullable|string|max:255',
            'customer_service_contact_2' => 'nullable|string|max:255',
            'customer_service_name_3' => 'nullable|string|max:255',
            'customer_service_contact_3' => 'nullable|string|max:255',
            'customer_service_name_4' => 'nullable|string|max:255',
            'customer_service_contact_4' => 'nullable|string|max:255',
            'bus_model' => 'nullable|string|max:255',
            'seate_json' => 'nullable|string',
            'route_from' => 'required|string',
            'route_to' => 'required|string',
            'route_start' => 'nullable',
            'route_end' => 'nullable|after_or_equal:route_start',
            'route_price' => 'required|numeric|min:0',
        ]);

        if ($request->total_seats % 4 === 0 || $request->total_seats % 4 === 1) {

            $contactNumber = $request->conductor_phone;
            if (substr($contactNumber, 0, 1) === '0') {
                $contactNumber = '255' . substr($contactNumber, 1);
            }

            $data = [
                'campany_id' => auth()->user()->campany->id,
                'bus_number' => $request->bus_number,
                'bus_type' => $request->bus_type,
                'total_seats' => $request->total_seats,
                'conductor' => $contactNumber,
                'driver_name' => $request->driver_name,
                'driver_contact' => $request->driver_contact,
                'conductor_name' => $request->conductor_name,
                'customer_service_name_1' => $request->customer_service_name_1,
                'customer_service_contact_1' => $request->customer_service_contact_1,
                'customer_service_name_2' => $request->customer_service_name_2,
                'customer_service_contact_2' => $request->customer_service_contact_2,
                'customer_service_name_3' => $request->customer_service_name_3,
                'customer_service_contact_3' => $request->customer_service_contact_3,
                'customer_service_name_4' => $request->customer_service_name_4,
                'customer_service_contact_4' => $request->customer_service_contact_4,
                'bus_model' => $request->bus_model,
                'seate_json' => $request->seate_json,
            ];

            $bus = bus::create($data);
            $bus_id = $bus->id;


            $info = [
                'bus_id' => $bus_id,
                'from' => $request->route_from,
                'to' => $request->route_to,
                'route_start' => $request->route_start ?? '',
                'route_end' => $request->route_end ?? '',
                'price' => $request->route_price,
                'distance' => $request->route_distance ?? 0, // Optional distance field
            ];

            $res = $bus->route()->create($info);

            if ($res) {
                return redirect()->route('buses')->with('success', __('vender/mybus.bus_added_success'));
            } else {
                return redirect()->back()->with('error', __('vender/mybus.failed_add_bus'));
            }
        } else {
            return back()->with('error', __('vender/mybus.seats_divisible_error'));
        }
    }

    public function update_bus(Request $request)
    {
        $validated = $request->validate([
            'bus_number' => 'required|string',
            'bus_type' => 'required|string',
            'total_seats' => 'required|integer|min:1',
            'conductor_phone' => 'required|string',
            'driver_name' => 'nullable|string|max:255',
            'driver_contact' => 'nullable|string|max:255',
            'conductor_name' => 'nullable|string|max:255',
            'customer_service_name_1' => 'nullable|string|max:255',
            'customer_service_contact_1' => 'nullable|string|max:255',
            'customer_service_name_2' => 'nullable|string|max:255',
            'customer_service_contact_2' => 'nullable|string|max:255',
            'customer_service_name_3' => 'nullable|string|max:255',
            'customer_service_contact_3' => 'nullable|string|max:255',
            'customer_service_name_4' => 'nullable|string|max:255',
            'customer_service_contact_4' => 'nullable|string|max:255',
            'bus_model' => 'nullable|string|max:255',
            'route_from' => 'required|string',
            'route_to' => 'required|string',
            'route_start' => 'nullable',
            'route_end' => 'nullable|after_or_equal:route_start',
            'route_price' => 'required|numeric|min:0',
        ]);

        $contactNumber = $request->conductor_phone;
        if (substr($contactNumber, 0, 1) === '0') {
            $contactNumber = '255' . substr($contactNumber, 1);
        }

        $data = [
            'bus_number' => $request->bus_number,
            'bus_type' => $request->bus_type,
            'total_seats' => $request->total_seats,
            'bus_features' => $request->bus_features,
            'conductor' => $contactNumber,
            'driver_name' => $request->driver_name,
            'driver_contact' => $request->driver_contact,
            'conductor_name' => $request->conductor_name,
            'customer_service_name_1' => $request->customer_service_name_1,
            'customer_service_contact_1' => $request->customer_service_contact_1,
            'customer_service_name_2' => $request->customer_service_name_2,
            'customer_service_contact_2' => $request->customer_service_contact_2,
            'customer_service_name_3' => $request->customer_service_name_3,
            'customer_service_contact_3' => $request->customer_service_contact_3,
            'customer_service_name_4' => $request->customer_service_name_4,
            'customer_service_contact_4' => $request->customer_service_contact_4,
            'bus_model' => $request->bus_model,
            'seate_json' => $request->seate_json,
        ];

        bus::where('id', $request->bus_id)->update($data);

        $info = [
            'from' => $request->route_from,
            'to' => $request->route_to,
            'route_start' => $request->route_start ?? '',
            'route_end' => $request->route_end ?? '',
            'price' => $request->route_price ?? '',
        ];

        $existingRoute = route::where('bus_id', $request->bus_id)->first();
        if ($existingRoute) {
            $existingRoute->update($info);
        } else {
            $info['bus_id'] = $request->bus_id;
            $info['distance'] = 0;
            route::create($info);
        }

        return back()->with('success', __('vender/mybus.update_successful'));
    }

    public function delete_bus(Request $request)
    {
        bus::where('id', $request->bus_id)->delete();
        route::where('bus_id', $request->bus_id)->delete();
        Via::where('bus_id', $request->bus_id)->delete();
        Point::where('bus_id', $request->bus_id)->delete();
        schedule::where('bus_id', $request->bus_id)->delete();

        return back()->with('success', __('vender/mybus.bus_delete_successful'));
    }
    /////////////////////////////

    ///////route//////////////
    public function route_page()
    {
        $mybus = bus::with('busname', 'routes.via')->where('campany_id', auth()->user()->campany->id)->get();
        return view('controller.route_page', compact('mybus'));
    }
    public function route()
    {
        $buses = Bus::with(['busname', 'route', 'point'])
            ->where('campany_id', auth()->user()->campany->id)
            ->whereDoesntHave('point')
            ->get();
        return view('controller.route', compact('buses'));
        //return $buses;
    }

    public function get_route(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'route_from' => 'required',
            'points' => 'required|array',
            'points.*.mode' => 'required|numeric|min:0',
            'points.*.name' => 'required|string|max:255',
            'points.*.amount' => 'nullable|numeric|min:0',
            'via' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Create the route
        $bus = Bus::find($request->bus_id);
        //$bus->route_id = $request->route_id;
        //$bus->save();
        Via::create([
            'bus_id' => $request->bus_id,
            'route_id' => $request->route_id,
            'name' => $request->via,
        ]);

        $route = route::find($request->route_id);

        // Save route points
        foreach ($request->points as $point) {
            $route->points()->create([
                'point_mode' => $point['mode'],
                'state' => $request->return ?? "no",
                'point' => $point['name'],
                'amount' => $point['amount'] ?? 0,
                'route_id' => $request->route_id,
                'bus_id' => $request->bus_id,
            ]);
        }

        return back()->with('success', __('vender/route.route_created_success'));
    }

    public function edit_route($id)
    {
        $route = route::with('bus', 'points.city', 'via')
            ->where('id', $id)
            ->first();

        //return $route;
        return view('controller.edit_route', compact('route'));
    }

    public function update(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'route_from' => 'required',
            'points' => 'required|array',
            'points.*.mode' => 'required|numeric|min:0',
            'points.*.name' => 'required|string|max:255',
            'points.*.amount' => 'nullable|numeric|min:0',
            'points.*.state' => 'nullable',
            'via' => 'required|string|max:255',
            'route_id' => 'required|exists:routes,id',
            'bus_id' => 'required|exists:buses,id'

        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update the route
        $bus = Bus::find($request->bus_id);
        $route = Route::find($request->route_id);

        // Update Via
        $via = Via::where('bus_id', $request->bus_id)
            ->where('route_id', $request->route_id)
            ->first();

        if ($via) {
            $via->update([
                'name' => $request->via,
            ]);
        } else {
            Via::create([
                'bus_id' => $request->bus_id,
                'route_id' => $request->route_id,
                'name' => $request->via,
            ]);
        }

        // Delete existing points and create new ones
        $route->points()->where('route_id', $request->route_id)->delete();

        // Save updated route points
        foreach ($request->points as $point) {
            $route->points()->create([
                'point_mode' => $point['mode'],
                'state' => $point['state'],
                'point' => $point['name'],
                'amount' => $point['amount'] ?? 0,
                'route_id' => $request->route_id,
                'bus_id' => $request->bus_id,
            ]);
        }

        return back()->with('success', __('vender/route.route_updated_success'));
    }

    public function delete_route(Request $request)
    {
        route::where('id', $request->route_id)->delete();
        Via::where('route_id', $request->route_id)->delete();
        Point::where('route_id', $request->route_id)->delete();
        schedule::where('route_id', $request->route_id)->delete();

        return back()->with('success', __('vender/route.route_deleted_success'));
    }

    //////////////////////////

    /////////////history///////////////
    public function history(Request $request)
    {
        // Warn (but don't redirect) when a custom range is missing its dates.
        // Redirecting back to history?period=custom re-triggers this same guard
        // and causes an infinite redirect loop (ERR_TOO_MANY_REDIRECTS). The date
        // filter helper safely applies no filter when the custom dates are absent.
        if ($request->get('period') === 'custom' && (! $request->filled('start_date') || ! $request->filled('end_date'))) {
            session()->flash('error', __('system.pages.custom_range_requires_dates'));
        }

        $query = Booking::with(['campany', 'schedule', 'user', 'bus.route', 'vender', 'campany.busOwnerAccount', 'governmentLeviesOnService'])
            ->whereHas('campany', function ($q) {
$q->where('id', auth()->user()->campany->id);
            });

        $dateFilter = apply_booking_history_date_filter($query, $request);
        $period = $dateFilter['period'];
        $startDate = $dateFilter['startDate'];
        $endDate = $dateFilter['endDate'];

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $period = 'custom';
        }

        apply_booking_history_column_filters($query, $request);

        $bookings = $query->where('payment_status', 'Paid')->latest()->get();

        $totalPayment = 0;
        $totalDiscount = 0;
        $totalVAT = 0;
        $grandTotal = 0;
        foreach ($bookings as $b) {
            $totalPayment += (float)($b->amount ?? 0) + (float)($b->vat ?? 0);
            $totalDiscount += (float)($b->discount_amount ?? 0);
            $totalVAT += (float)($b->vat ?? 0);
            // Grand total is the gross bus fare (bookings.busFee) — the same
            // value shown per-row — not fare + commission + VAT stacked on top,
            // which previously inflated this figure (e.g. 38,000 instead of 36,000).
            $grandTotal += round((float) ($b->busFee ?? 0));
        }

        return view('controller.history', compact('bookings', 'totalPayment', 'totalDiscount', 'totalVAT', 'grandTotal', 'period', 'startDate', 'endDate'));
    }

    public function search(Request $request)
    {
        $query = Booking::with(['bus_name', 'route_name', 'user'])->where('payment_status', 'Paid');

        // Search by keyword
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('bus_name', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by date
        if ($date = $request->date) {
            $query->whereDate('created_at', $date);
        }

        $bookings = $query->latest()->get();

        // Return HTML for table rows
        $html = view('admin.bookings.partials.table_rows', compact('bookings'))->render();

        return response()->json([
            'html' => $html
        ]);
    }

    public function show($id)
    {
        $booking = Booking::with(['bus_name', 'schedule', 'user'])->where('payment_status', 'Paid')->findOrFail($id);

        $html = view('admin.bookings.partials.modal_content', compact('booking'))->render();

        return response()->json([
            'html' => $html
        ]);
    }
    ////////////////////////////////////
    public function erning(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->campany ? $user->campany->id : null;

        if (!$companyId) {
            return view('controller.home', [
                'summary' => [],
                'recentBookings' => collect([]),
                'todaysTrips' => collect([]),
                'error' => __('vender/earning.no_company_account'),
            ]);
        }

        $bus_ids = Bus::where('campany_id', $companyId)->pluck('id')->toArray();

        if (empty($bus_ids)) {
            return view('controller.home', [
                'summary' => [],
                'recentBookings' => collect([]),
                'todaysTrips' => collect([]),
                'error' => 'No buses found for your company.',
            ]);
        }

        // Get period from request or default to 'month'
        $period = $request->input('period', 'month');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $data = $this->getEarningsData($bus_ids, $period, $start_date, $end_date, $request);
        $filters = $this->earningsFilterInputs($request);

        session()->put('export_data', $data);

        return view('controller.erning', compact('data', 'period', 'start_date', 'end_date', 'filters'));
    }

    public function filterEarnings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->route('erning')
                ->withErrors($validator)
                ->withInput();
        }

        return redirect()->route('erning', $this->earningsFilterInputs($request));
    }

    private function getEarningsData($bus_ids, $period, $start_date = null, $end_date = null, ?Request $request = null)
    {
        $request = $request ?? request();
        $query = Transaction::with('campany')->where('campany_id', Auth::user()->campany->id);

        [$start, $end] = $this->parseEarningsPeriod($period, $start_date, $end_date);

        $transactions = $query->whereBetween('created_at', [$start, $end])->get();

        $companyId = (int) Auth::user()->campany->id;

        $ticketQuery = $this->companyTicketEarningsQuery($bus_ids, $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'Paid');
        apply_booking_history_column_filters($ticketQuery, $request);

        return [
            'ticket_earnings' => $this->sumBusOwnerTicketEarnings($ticketQuery),
            'luggage_earnings' => $this->sumBusOwnerReleasedLuggageEarnings($companyId, $start, $end, $request),
            'parcel_earnings' => $this->sumBusOwnerParcelEarnings($bus_ids, $start, $end, $request),
            'request' => $transactions->sum('amount'),
            'success' => $transactions->where('status', 'Completed')->sum('amount'),
            'transactions' => $transactions,
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
        ];
    }

    public function earningsTicketsData(Request $request)
    {
        $bus_ids = $this->resolveCompanyBusIds();
        $draw = (int) $request->input('draw', 1);

        if (!$bus_ids) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $companyId = (int) Auth::user()->campany->id;

        [$periodStart, $periodEnd] = $this->parseEarningsPeriod(
            $request->input('period', 'month'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        $baseQuery = $this->companyTicketEarningsQuery($bus_ids, $companyId)
            ->with(['schedule', 'bus'])
            ->where('payment_status', 'Paid')
            ->whereBetween('created_at', [$periodStart, $periodEnd]);
        apply_booking_history_column_filters($baseQuery, $request);

        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $filteredQuery->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('schedule', function ($sq) use ($search) {
                        $sq->where('from', 'like', "%{$search}%")
                            ->orWhere('to', 'like', "%{$search}%");
                    });
            });
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $orderMap = [
            0 => 'booking_code',
            1 => 'travel_date',
            2 => 'travel_date',
            3 => 'customer_name',
            4 => 'amount',
            5 => 'created_at',
        ];
        $orderCol = (int) $request->input('order.0.column', 5);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $filteredQuery->orderBy($orderMap[$orderCol] ?? 'created_at', $orderDir);

        $offset = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length < 0) {
            $length = 100000;
        }

        $bookings = $filteredQuery->skip($offset)->take($length)->get();
        $currency = session('currency', 'Tzs');

        $data = $bookings->map(function ($booking) use ($currency) {
            $from = $booking->schedule->from ?? __('vender/earning.na');
            $to = $booking->schedule->to ?? __('vender/earning.na');

            return [
                'booking_code' => e($booking->booking_code ?? __('vender/earning.na')),
                'travel_date' => e($booking->travel_date ?? __('vender/earning.na')),
                'route' => e("{$from} → {$to}"),
                'customer_name' => e($booking->customer_name ?? __('vender/earning.na')),
                'amount' => (float) ($booking->amount ?? 0),
                'amount_display' => e($currency . ' ' . convert_money($booking->amount ?? 0)),
                'paid_at' => e($booking->created_at?->format('Y-m-d H:i') ?? __('vender/earning.na')),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function earningsLuggageData(Request $request)
    {
        $bus_ids = $this->resolveCompanyBusIds();
        $draw = (int) $request->input('draw', 1);
        $companyId = Auth::user()->campany?->id;

        if (!$bus_ids || !$companyId) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        [$periodStart, $periodEnd] = $this->parseEarningsPeriod(
            $request->input('period', 'month'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        $baseQuery = ExcessLuggageEscrow::query()
            ->with(['booking.schedule'])
            ->whereIn('status', [
                ExcessLuggageEscrow::STATUS_RELEASED,
                ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
            ])
            ->whereBetween('released_at', [$periodStart, $periodEnd])
            ->whereHas('booking', function ($q) use ($bus_ids, $request) {
                $q->whereIn('bus_id', $bus_ids);
                apply_booking_history_column_filters($q, $request);
            });

        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $filteredQuery->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('booking.schedule', function ($sq) use ($search) {
                        $sq->where('from', 'like', "%{$search}%")
                            ->orWhere('to', 'like', "%{$search}%");
                    });
            });
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $orderMap = [
            0 => 'booking_code',
            1 => 'released_fee',
            2 => 'owner_share',
            3 => 'status',
            4 => 'released_at',
            5 => 'released_at',
        ];
        $orderCol = (int) $request->input('order.0.column', 4);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $filteredQuery->orderBy($orderMap[$orderCol] ?? 'released_at', $orderDir);

        $offset = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length < 0) {
            $length = 100000;
        }

        $escrows = $filteredQuery->skip($offset)->take($length)->get();
        $currency = session('currency', 'Tzs');

        $data = $escrows->map(function ($escrow) use ($currency) {
            $from = $escrow->booking?->schedule?->from ?? __('vender/earning.na');
            $to = $escrow->booking?->schedule?->to ?? __('vender/earning.na');
            $statusKey = $escrow->status ?? '';
            $statusLabel = match ($statusKey) {
                ExcessLuggageEscrow::STATUS_RELEASED => __('vender/earning.luggage_status_released'),
                ExcessLuggageEscrow::STATUS_SURPLUS_HELD => __('vender/earning.luggage_status_surplus_held'),
                default => e($statusKey),
            };
            $statusClass = match ($statusKey) {
                ExcessLuggageEscrow::STATUS_RELEASED => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                ExcessLuggageEscrow::STATUS_SURPLUS_HELD => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                default => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-200',
            };

            return [
                'booking_code' => e($escrow->booking_code ?? $escrow->booking?->booking_code ?? __('vender/earning.na')),
                'released_fee_display' => e($currency . ' ' . convert_money($escrow->released_fee ?? 0)),
                'owner_share' => (float) ($escrow->owner_share ?? 0),
                'owner_share_display' => e($currency . ' ' . convert_money($escrow->owner_share ?? 0)),
                'status_html' => '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">' . e($statusLabel) . '</span>',
                'released_at' => e($escrow->released_at?->format('Y-m-d H:i') ?? __('vender/earning.na')),
                'route' => e("{$from} → {$to}"),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function earningsParcelsData(Request $request)
    {
        $bus_ids = $this->resolveCompanyBusIds();
        $draw = (int) $request->input('draw', 1);

        if (!$bus_ids) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        [$periodStart, $periodEnd] = $this->parseEarningsPeriod(
            $request->input('period', 'month'),
            $request->input('start_date'),
            $request->input('end_date')
        );

        $baseQuery = Parcel::query()
            ->with(['bus.campany'])
            ->whereIn('bus_id', $bus_ids)
            ->where('payment_status', ParcelFlowService::PAY_PAID)
            ->whereNotNull('settled_at')
            ->whereBetween('settled_at', [$periodStart, $periodEnd]);
        apply_bus_relation_column_filters($baseQuery, $request);

        $recordsTotal = (clone $baseQuery)->count();

        $filteredQuery = clone $baseQuery;
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $filteredQuery->where(function ($q) use ($search) {
                $q->where('parcel_number', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhereHas('bus', function ($bq) use ($search) {
                        $bq->where('bus_number', 'like', "%{$search}%");
                    });
            });
        }

        $recordsFiltered = (clone $filteredQuery)->count();

        $orderMap = [
            0 => 'parcel_number',
            1 => 'bus_id',
            2 => 'amount_paid',
            3 => 'amount_paid',
            4 => 'settled_at',
        ];
        $orderCol = (int) $request->input('order.0.column', 4);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $filteredQuery->orderBy($orderMap[$orderCol] ?? 'settled_at', $orderDir);

        $offset = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length < 0) {
            $length = 100000;
        }

        $parcels = $filteredQuery->skip($offset)->take($length)->get();
        $currency = session('currency', 'Tzs');
        $systemPct = (float) (Setting::first()->parcel_commission_percentage ?? 0);

        $data = $parcels->map(function ($parcel) use ($currency, $systemPct) {
            $ownerShare = ParcelFlowService::ownerShareAmount(
                (float) ($parcel->amount_paid ?? 0),
                $parcel->vender_id,
                $systemPct
            );

            return [
                'parcel_number' => e($parcel->parcel_number ?? __('vender/earning.na')),
                'bus_number' => e($parcel->bus->bus_number ?? __('vender/earning.na')),
                'amount_display' => e($currency . ' ' . convert_money($parcel->amount_paid ?? 0)),
                'owner_share' => $ownerShare,
                'owner_share_display' => e($currency . ' ' . convert_money($ownerShare)),
                'settled_at' => e($parcel->settled_at?->format('Y-m-d H:i') ?? __('vender/earning.na')),
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function report()
    {
        return view('controller.report');
    }

    //////////////////////////////////////////// ////

    public function schedules()
    {
        // Upcoming bus schedules from now (using app timezone so date column doesn't show past dates)
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        $schedules = Schedule::with(['bus.busname', 'route'])
            ->whereHas('bus', function ($query) {
                $query->where('campany_id', auth()->user()->campany->id);
            })
            ->where(function ($query) use ($today, $currentTime) {
                $query->where('schedule_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $currentTime) {
                        $q->where('schedule_date', $today)->where('start', '>', $currentTime);
                    });
            })
            ->orderBy('schedule_date', 'asc')
            ->orderBy('start', 'asc')
            ->get();

        // Booked seats per schedule for the seat-arrangement modal.
        $seatMaps = schedule_seat_maps($schedules);
        foreach ($schedules as $schedule) {
            $schedule->booked_seat_map = $seatMaps[$schedule->id] ?? [];
        }

        return view('controller.schedules', compact('schedules'));
    }

    public function add_schedule()
    {
        $buses = Bus::with('busname', 'route')->where('campany_id', auth()->user()->campany->id)->get();

        return view('controller.add_schedule', compact('buses'));
        //return  $buses;
    }

    public function store_schedule(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'bus_id' => 'required|exists:buses,id',
            'schedules' => 'required|array|min:1',
            'schedules.*.from' => 'required|string|max:255',
            'schedules.*.to' => 'required|string|max:255',
            'schedules.*.schedule_date' => 'required|date|after_or_equal:today',
            'schedules.*.start' => 'required|string|max:255',
            'schedules.*.end' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create schedules
        try {
            foreach ($request->schedules as $scheduleData) {
                Schedule::updateOrCreate(
                    [
                        'route_id'      => $request->route_id,
                        'bus_id'        => $request->bus_id,
                        'from'          => $scheduleData['from'],
                        'to'            => $scheduleData['to'],
                        'schedule_date' => $scheduleData['schedule_date'],
                    ],
                    [
                        'start' => $scheduleData['start'] ?? null,
                        'end'   => $scheduleData['end'] ?? null,
                    ]
                );
            }

            return redirect()->route('schedules')
                ->with('success', __('vender/schedule.schedules_created_success'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('vender/schedule.failed_create_schedules', ['error' => $e->getMessage()]))
                ->withInput();
        }
    }

    public function delete_schedule(Request $request)
    {
        schedule::where('id', $request->schedule_id)->delete();
        return back()->with('success', __('vender/schedule.schedule_delete_successful'));
    }

    public function getUnbookedSchedules(Request $request)
    {
        $busId = $request->query('bus_id');
        Log::info('Fetching unbooked schedules for bus_id: ' . $busId);

        $schedules = Schedule::where('bus_id', $busId)
            ->where('schedule_date', '>=', Carbon::today())
            ->get()
            ->map(function ($schedule) {
                return [
                    'from' => $schedule->from,
                    'to' => $schedule->to,
                    'schedule_date' => Carbon::parse($schedule->schedule_date)->format('Y-m-d'),
                    'start' => $schedule->start,
                    'end' => $schedule->end,
                ];
            });

        Log::info('Found schedules: ', ['count' => $schedules->count(), 'schedules' => $schedules->toArray()]);

        return response()->json(['schedules' => $schedules]);
    }

    ////////////////////////////////////////////////

    public function print(Request $request)
    {
        $companyId = Auth::user()->campany->id ?? null;
        if (!$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        $data = null;

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $bookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService'])
                ->where('campany_id', $companyId)
                ->where('payment_status', 'Paid')
                ->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ])
                ->latest()
                ->get();
            $data = $this->bookingsToReportArray($bookings);
        } elseif ($request->filled('booking_ids')) {
            $ids = is_array($request->booking_ids) ? $request->booking_ids : (array) json_decode($request->booking_ids, true);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $bookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService'])
                    ->where('campany_id', $companyId)
                    ->whereIn('id', $ids)
                    ->where('payment_status', 'Paid')
                    ->get();
                $data = $this->bookingsToReportArray($bookings);
            }
        }

        if ($data === null) {
            $raw = $request->data;
            if (!empty($raw)) {
                $data = json_decode($raw, true);
            }
        }

        if ($data === null) {
            $bookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService'])
                ->where('campany_id', $companyId)
                ->where('payment_status', 'Paid')
                ->latest()
                ->get();
            $data = $this->bookingsToReportArray($bookings);
        }

        if (empty($data) || !is_array($data)) {
            return redirect()->back()->with('error', __('vender/history.no_booking_data_income'));
        }

        return $this->generatePDF($data);
    }

    /**
     * Convert Booking models to the array format expected by print/report and print/manifest views.
     */
    private function bookingsToReportArray($bookings)
    {
        $out = [];
        foreach ($bookings as $b) {
            $out[] = booking_to_report_row($b);
        }
        return $out;
    }

    public function generatePDF($data)
    {
        // Ensure data is an array before passing to view
        if (!is_array($data)) {
            $data = [];
        }
        
        $pdf = Pdf::loadView('print.report', ['bookings' => $data]);

        return $pdf->download('income-' . now() . '.pdf');
    }

    public function manifest(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();
        $companyId = $isAdmin ? null : ($user->campany->id ?? null);

        if (!$isAdmin && !$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        if ($isAdmin && !$request->filled('data') && !$request->filled('booking_ids')) {
            return redirect()->back()->with('error', __('vender/transfer.please_select_manifest'));
        }

        $data = null;
        $loadedBookings = null;

        if ($request->filled('start_date') && $request->filled('end_date') && $companyId) {
            $loadedBookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService', 'vender'])
                ->where('campany_id', $companyId)
                ->where('payment_status', 'Paid')
                ->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ])
                ->orderBy('seat')
                ->latest()
                ->get();
            $data = $this->bookingsToReportArray($loadedBookings);
        } elseif ($request->filled('booking_ids')) {
            $ids = is_array($request->booking_ids) ? $request->booking_ids : (array) json_decode($request->booking_ids, true);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $query = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService', 'vender'])
                    ->whereIn('id', $ids)
                    ->where('payment_status', 'Paid')
                    ->orderBy('seat');
                if ($companyId) {
                    $query->where('campany_id', $companyId);
                }
                $loadedBookings = $query->get();
                $data = $this->bookingsToReportArray($loadedBookings);
            }
        }

        if ($data === null && $request->filled('data')) {
            $data = json_decode($request->data, true);
        }

        if ($data === null && $companyId) {
            $loadedBookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService', 'vender'])
                ->where('campany_id', $companyId)
                ->where('payment_status', 'Paid')
                ->orderBy('seat')
                ->latest()
                ->get();
            $data = $this->bookingsToReportArray($loadedBookings);
        }

        // Expand multi-passenger bookings into individual manifest rows
        // (including lap infants from bookings.infant_child).
        if ($loadedBookings !== null) {
            $data = expand_bookings_to_manifest_rows($loadedBookings, $data);
        }

        if (empty($data) || !is_array($data) || !isset($data[0])) {
            return redirect()->back()->with('error', __('vender/history.no_booking_data_manifest'));
        }

        // One printable section per trip (bus plate + travel date) so crew and
        // passengers stay scoped to the same departure.
        $sections = [];
        $grouped = collect($data)->groupBy(function ($row) {
            $busNumber = trim((string) ($row['bus_number'] ?? ''));
            $travelDate = trim((string) ($row['travel_date'] ?? ''));

            return $busNumber . '|' . $travelDate;
        });

        foreach ($grouped as $tripKey => $tripRows) {
            [$busNumber] = array_pad(explode('|', (string) $tripKey, 2), 2, '');
            $busNumber = trim((string) $busNumber);
            if ($busNumber === '') {
                continue;
            }

            $bus = $this->findManifestBus($busNumber, $companyId);
            if (! $bus) {
                continue;
            }

            $rows = $tripRows->sortBy(function ($row) {
                return [(string) ($row['seat'] ?? ''), (string) ($row['customer_name'] ?? '')];
            })->values()->all();

            if (empty($rows)) {
                continue;
            }

            $staffRows = $this->manifestStaffRows($bus);
            $sections[] = [
                'bus' => $bus,
                'bookings' => array_merge($staffRows, $rows),
            ];
        }

        if (empty($sections)) {
            return redirect()->back()->with('error', __('vender/history.no_booking_data_manifest'));
        }

        if (count($sections) === 1) {
            $pdf = Pdf::loadView('print.manifest', [
                'bookings' => $sections[0]['bookings'],
                'bus' => $sections[0]['bus'],
            ]);
            $pdf->setPaper('a4', 'landscape');

            return $pdf->download('manifest-' . now()->format('Ymd_His') . '.pdf');
        }

        $pdf = Pdf::loadView('print.manifest_all', compact('sections'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('manifest-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Resolve the bus for a manifest section, preferring the authenticated
     * company fleet when the caller is a bus owner.
     */
    private function findManifestBus(string $busNumber, ?int $companyId)
    {
        $query = Bus::with('campany')->where('bus_number', $busNumber);
        if ($companyId) {
            $query->where('campany_id', $companyId);
        }

        $bus = $query->first();
        if ($bus) {
            return $bus;
        }

        // Fall back without company scope (admin / shared plate edge cases).
        return Bus::with('campany')->where('bus_number', $busNumber)->first();
    }

    public function generateManifest($data,$bus)
    {
        // Prepend staff rows so they appear first in the manifest table.
        $staffRows = $this->manifestStaffRows($bus);
        $allRows = array_merge($staffRows, $data);

        $pdf = Pdf::loadView('print.manifest', ['bookings' => $allRows, 'bus' => $bus]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('manifest-' . now() . '.pdf');
    }

    /**
     * Build synthetic manifest rows for all staff assigned to a bus so they
     * appear on the passenger manifest alongside travellers.
     *
     * @return array<int, array<string, mixed>>
     */
    private function manifestStaffRows($bus): array
    {
        // Staff appear whenever a name OR a contact is on record. Only
        // conductor_phone is mandatory when a bus is created, so keying off the
        // name alone dropped crew members from the manifest.
        $staff = [
            ['DRIVER', $bus->driver_name ?? null, $bus->driver_contact ?? null],
            ['DRIVER', $bus->driver_name_2 ?? null, $bus->driver_contact_2 ?? null],
            ['CONDUCTOR', $bus->conductor_name ?? null, $bus->conductor ?? null],
        ];

        for ($i = 1; $i <= 4; $i++) {
            $staff[] = [
                'CUSTOMER SERVICE',
                $bus->{"customer_service_name_{$i}"} ?? null,
                $bus->{"customer_service_contact_{$i}"} ?? null,
            ];
        }

        $rows = [];
        foreach ($staff as [$role, $name, $contact]) {
            $name = trim((string) $name);
            $contact = trim((string) $contact);

            if ($name === '' && $contact === '') {
                continue;
            }

            $rows[] = $this->manifestStaffRow($role, $name !== '' ? $name : $role, $contact);
        }

        return $rows;
    }

    /**
     * One synthetic manifest row for a crew member. Fare/booking fields are blank
     * so staff never contribute to the manifest's revenue columns.
     *
     * @return array<string, mixed>
     */
    private function manifestStaffRow(string $role, string $name, string $contact): array
    {
        return [
            'seat' => '—',
            'route_label' => '',
            'customer_name' => strtoupper($name),
            'gender_code' => '',
            'customer_phone' => $contact,
            'passenger_type' => $role,
            'infant_child' => 0,
            'id_type' => '',
            'id_number' => '',
            'booking_code' => '',
            'issue_date' => '',
            'issue_by' => '',
            'pickup_point' => '',
            'dropping_point' => '',
            'base_fare' => '0',
            'manifest_discount' => '0',
            'paid_fare' => '0',
            'remarks' => '',
            'is_staff' => true,
        ];
    }

    public function export(Request $request)
    {
        $data = session()->get('export_data');

        $pdf = Pdf::loadView('print.transaction', ['data' => $data]);

        return $pdf->download('transaction_report.pdf');
    }


    public function profile()
    {
        return view('controller.profile');
    }

    public function update_profile(Request $request)
    {
        try {
            $request->validate([
                'password' => ['nullable', 'string', 'min:8'],
            ], [
                'password.min' => __('vender/profile.password_min_8'),
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // Update user fields
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->contact = $request->input('contact');

            // Update password only if provided
            if (!empty($request->input('password'))) {
                $user->password = bcrypt($request->input('password'));
            }

            // Save user
            $user->save();

            // Update or create company details
            if ($user->campany) {
                $user->campany->update([
                    'name' => $request->input('campany_name'),
                ]);
            } elseif ($request->input('campany_name')) {
                // Create a new company record if it doesn't exist and name is provided
                $user->campany()->create([
                    'name' => $request->input('campany_name'),
                ]);
            }

            // Update or create bus owner account details (used on printed tickets)
            if ($user->campany) {
                $existingAccount = $user->campany->busOwnerAccount;
                $accountData = [
                    'registration_number' => $request->input('registration_number'),
                    'tin' => $request->input('tin'),
                    'vrn' => $request->input('vrn'),
                    'office_number' => $request->input('office_number'),
                    'box' => $request->input('box'),
                    'street' => $request->input('street'),
                    'town' => $request->input('town'),
                    'city' => $request->input('city'),
                    'region' => $request->input('region'),
                    'whatsapp_number' => $request->input('whatsapp_number'),
                ];

                if ($existingAccount) {
                    $accountData['bank_name'] = $request->has('bank_name')
                        ? $request->input('bank_name')
                        : $existingAccount->bank_name;
                    $accountData['bank_number'] = $request->has('account_number')
                        ? $request->input('account_number')
                        : $existingAccount->bank_number;
                    $existingAccount->update($accountData);
                } else {
                    $accountData['bank_name'] = $request->input('bank_name');
                    $accountData['bank_number'] = $request->input('account_number');
                    $user->campany->busOwnerAccount()->create($accountData);
                }
            }

            return back()->with('success', __('vender/profile.profile_updated_success'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update profile: ' . $e->getMessage()])->withInput();
        }
    }


    public function cities()
    {
        return view('controller.cities');
    }

    public function store_city(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            // Create a new city
            if (City::where('name', $request->name)->exists()) {
                return back()->with('error', __('vender/cities.city_already_exists'));
            }
            City::create([
                'name' => $request->name,
            ]);

            return back()->with('success', __('vender/cities.city_created_success'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create city: ' . $e->getMessage()])->withInput();
        }
    }

    public function print_recipt(Request $request)
    {
        $data = json_decode($request->data);

        $pdf = Pdf::loadView('print.bus', ['data' => $data]);

        $pdf->setPaper([0, 0, 4 * 72, 7 * 72], 'portrait');

        return $pdf->download($data->campany->name . '.pdf');
    }

    public function print_recipt2(Request $request)
    {
        $payload = json_decode($request->data);
        $data = Transaction::with(['user.VenderAccount'])->find($payload->id ?? 0) ?? $payload;

        $pdf = Pdf::loadView('print.vender', ['data' => $data]);

        $pdf->setPaper([0, 0, 4 * 72, 7 * 72], 'portrait');

        $filename = optional(transaction_vendor_user($data))->name
            ?? (is_object($data->user ?? null) ? ($data->user->name ?? 'vendor-transaction') : 'vendor-transaction');

        return $pdf->download($filename . '.pdf');
    }

   public function print_service(Request $request)
    {
        $payload = json_decode($request->data);
        $bookingId = is_object($payload) && isset($payload->id) ? $payload->id : null;
        $bookingCode = is_object($payload) && isset($payload->booking_code) ? $payload->booking_code : null;

        // Load full booking with relations so schedule times are available
        $data = null;
        if ($bookingId) {
            $data = Booking::with(['bus.route', 'bus.campany.busOwnerAccount', 'campany.busOwnerAccount', 'schedule', 'vender.VenderBalances'])->find($bookingId);
        }
        if (!$data && $bookingCode) {
            $data = Booking::with(['bus.route', 'bus.campany.busOwnerAccount', 'campany.busOwnerAccount', 'schedule', 'vender.VenderBalances'])->where('booking_code', $bookingCode)->first();
        }
        if (!$data) {
            $data = $payload;
        }

        // Generate as HTML (easiest for Blade)
        $qrCode = DNS2D::getBarcodeHTML($data->booking_code, 'QRCODE', 6, 6, 'blue');

        $data->qrcode = $qrCode;

        $pdf = Pdf::loadView('print.service', ['data' => $data]);

        $pdf->setPaper([0, 0, 4 * 72, 10 * 72], 'portrait');

        return $pdf->download($data->customer_name . '.pdf');
    }

    public function localBusOwners()
    {
        $user = Auth::user();
        if (!$user->campany()) {
            return redirect()->back()->with('error', __('vender/earning.unauthorized_view'));
        }

        $companyId = $user->campany ? $user->campany->id : null;

        if (!$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        $localBusOwners = User::where('role', 'local_bus_owner')
                              ->where('campany_id', $companyId)
                              ->get();

        return view('controller.local_bus_owners', compact('localBusOwners'));
    }

    public function createLocalBusOwner(Request $request)
    {
        $user = Auth::user();
        if (!$user->campany()) {
            return redirect()->back()->with('error', __('vender/earning.unauthorized_action'));
        }

        $companyId = $user->campany ? $user->campany->id : null;

        if (!$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'contact' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => 'local_bus_owner',
                'contact' => $request->contact,
                'status' => 'accept',
                'campany_id' => $user->campany->id,
            ]);

            return redirect()->route('local.bus.owners')->with('success', __('local_bus_owners.local_bus_owner_created_successfully'));
        } catch (\Exception $e) {
            Log::error('Error creating local bus owner: ' . $e->getMessage());
            return redirect()->back()->with('error', __('local_bus_owners.failed_to_create_local_bus_owner'));
        }
    }

    public function updateLocalBusOwner(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->campany()) {
            return redirect()->back()->with('error', __('vender/earning.unauthorized_action'));
        }

        $localBusOwner = User::where('role', 'local_bus_owner')
                             ->where('campany_id', $user->campany->id)
                             ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($localBusOwner->id)],
            'contact' => 'nullable|string|max:255',
            'status' => 'required|in:accept,cancel',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $localBusOwner->update([
                'name' => $request->name,
                'email' => $request->email,
                'contact' => $request->contact,
                'status' => $request->status,
            ]);

            return redirect()->route('local.bus.owners')->with('success', __('local_bus_owners.local_bus_owner_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Error updating local bus owner: ' . $e->getMessage());
            return redirect()->back()->with('error', __('local_bus_owners.failed_to_update_local_bus_owner'));
        }
    }

        public function deleteLocalBusOwner($id)
    {
        $user = Auth::user();
        if (!$user->campany()) {
            return redirect()->back()->with('error', __('vender/earning.unauthorized_action'));
        }

        try {
            $localBusOwner = User::where('role', 'local_bus_owner')
                                 ->where('campany_id', $user->campany->id)
                                 ->findOrFail($id);
            $localBusOwner->delete();

            return redirect()->route('local.bus.owners')->with('success', __('local_bus_owners.local_bus_owner_deleted_successfully'));
        } catch (\Exception $e) {
            Log::error('Error deleting local bus owner: ' . $e->getMessage());
            return redirect()->back()->with('error', __('local_bus_owners.failed_to_delete_local_bus_owner'));
        }
    }

    public function viewOwnerPermissions()
    {
        // Logic to view permissions for local bus owners
        // This could involve fetching a list of local bus owners and their current permissions
        // For now, we'll return a simple view.
        return view('controller.owner_permissions_view');
    }

    public function editOwnerPermissions()
    {
        // Logic to edit permissions for local bus owners
        // This could involve fetching a specific local bus owner's permissions and allowing modification
        // For now, we'll return a simple view.
        return view('controller.owner_permissions_edit');
    }

    public function showTransferForm($booking_id = null)
    {
        $user = Auth::user();
        $companyId = $user->campany ? $user->campany->id : null;

        if (!$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        $booking_id = $booking_id ?? request('booking_id');
        $transferService = app(BookingTransferService::class);

        $company = Campany::find($companyId);
        $routes = $transferService->listCompanyRoutes((int) $companyId);
        $otherCompanies = Campany::query()
            ->where('id', '!=', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedBooking = null;
        $prefill = null;
        if ($booking_id) {
            $selectedBooking = Booking::with('bus.busname', 'bus.campany', 'route_name', 'schedule')->find($booking_id);
            if (!$selectedBooking || (int) ($selectedBooking->bus->campany_id ?? 0) !== (int) $companyId) {
                return redirect()->route('booking.transfer.form')
                    ->with('error', __('vender/transfer.booking_not_found_or_unauthorized'));
            }
            if (!in_array($selectedBooking->payment_status, BookingTransferService::TRANSFERABLE_STATUSES, true)) {
                return redirect()->route('booking.transfer.form')
                    ->with('error', __('vender/transfer.booking_not_transferable'));
            }

            $prefill = [
                'booking_id' => (int) $selectedBooking->id,
                'travel_date' => $selectedBooking->travel_date,
                'route_id' => $selectedBooking->route_id ?? $selectedBooking->bus->route_id ?? null,
                'company_id' => (int) $companyId,
                'bus_id' => (int) $selectedBooking->bus_id,
                'schedule_id' => $selectedBooking->schedule_id,
                'seat' => $selectedBooking->seat,
                'pickup_point' => $selectedBooking->pickup_point,
                'dropping_point' => $selectedBooking->dropping_point,
            ];
        }

        return view('controller.transfer_booking', compact(
            'company',
            'routes',
            'otherCompanies',
            'selectedBooking',
            'prefill'
        ));
    }

    public function getTransferSourceBuses(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->campany->id ?? null;
        if (!$companyId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'travel_date' => 'required|date',
            'route_id' => 'nullable|integer|exists:routes,id',
        ]);

        $buses = app(BookingTransferService::class)->listSourceBuses(
            (int) $companyId,
            (string) $request->travel_date,
            $request->filled('route_id') ? (int) $request->route_id : null
        );

        return response()->json($buses);
    }

    public function getTransferSourceSeats(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->campany->id ?? null;
        if (!$companyId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id',
            'travel_date' => 'required|date',
            'schedule_id' => 'nullable|integer|exists:schedules,id',
        ]);

        try {
            $payload = app(BookingTransferService::class)->sourceOccupiedSeats(
                (int) $companyId,
                (int) $request->bus_id,
                (string) $request->travel_date,
                $request->filled('schedule_id') ? (int) $request->schedule_id : null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function getTransferDestinationSeats(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->campany->id ?? null;
        if (!$companyId) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $request->validate([
            'bus_id' => 'required|integer|exists:buses,id',
            'travel_date' => 'required|date',
            'schedule_id' => 'nullable|integer|exists:schedules,id',
            'exclude_booking_ids' => 'nullable|array',
            'exclude_booking_ids.*' => 'integer',
        ]);

        try {
            $payload = app(BookingTransferService::class)->destinationSeatAvailability(
                (int) $request->bus_id,
                (string) $request->travel_date,
                $request->filled('schedule_id') ? (int) $request->schedule_id : null,
                $request->input('exclude_booking_ids', [])
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($payload);
    }

    public function resavedTickets()
    {
        $user = Auth::user();
        $companyId = $user->campany ? $user->campany->id : null;

        if (!$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        // Scope via booking.campany_id OR bus.campany_id, and include both
        // modern `resaved` and legacy `Reserved` payment statuses.
        $resavedBookings = Booking::query()
            ->where(function ($q) use ($companyId) {
                $q->where('campany_id', $companyId)
                    ->orWhereHas('bus', function ($busQuery) use ($companyId) {
                        $busQuery->where('campany_id', $companyId);
                    });
            })
            ->whereIn('payment_status', ['resaved', 'Reserved'])
            ->with(['bus.busname', 'schedule', 'user', 'route_name'])
            ->latest()
            ->paginate(15);

        return view('controller.resaved_tickets', compact('resavedBookings'));
    }

    public function printBusesPdf()
    {
        $user = Auth::user();
        $companyId = $user->campany ? $user->campany->id : null;

        if (!$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        $buses = Bus::with('busname', 'route')
                    ->where('campany_id', $companyId)
                    ->get();

        $pdf = Pdf::loadView('print.bus_list', compact('buses'));
        return $pdf->download('bus_list_' . now()->format('Ymd_His') . '.pdf');
    }

    public function getFilteredSchedules(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->campany->id ?? null;
        if (!$companyId) {
            return response()->json([], 403);
        }

        $busId = $request->input('bus_id');
        $travelDate = $request->input('travel_date');
        $routeId = $request->input('route_id');
        $emergency = $request->boolean('emergency');
        $destCompanyId = $request->input('dest_company_id');
        $transferService = app(BookingTransferService::class);

        $query = Schedule::query()->with(['bus.campany', 'bus.busname']);

        if ($busId) {
            $query->where('bus_id', $busId);
        } elseif ($emergency) {
            if ($destCompanyId) {
                $query->whereHas('bus', fn ($q) => $q->where('campany_id', $destCompanyId));
            }
        } else {
            $query->whereHas('bus', fn ($q) => $q->where('campany_id', $companyId));
        }

        if ($routeId) {
            $query->where(function ($q) use ($routeId) {
                $q->where('route_id', $routeId)
                    ->orWhereHas('bus.route', fn ($rq) => $rq->where('routes.id', $routeId));
            });
        }

        if ($travelDate) {
            $query->whereDate('schedule_date', $travelDate);
        } else {
            $query->where('schedule_date', '>=', Carbon::today()->format('Y-m-d'));
        }

        $schedules = $query->orderBy('schedule_date')
            ->orderBy('start')
            ->get()
            ->unique('id')
            ->values()
            ->map(fn ($schedule) => $transferService->formatTransferSchedule($schedule));

        return response()->json($schedules);
    }

    public function getTransferBuses(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->campany->id ?? null;
        if (!$companyId) {
            return response()->json([], 403);
        }

        $emergency = $request->boolean('emergency');
        $scheduleId = $request->input('schedule_id');
        $destCompanyId = $request->input('dest_company_id');
        $routeId = $request->input('route_id');
        $travelDate = $request->input('travel_date');
        $transferService = app(BookingTransferService::class);

        $schedule = $scheduleId ? Schedule::with('bus.campany', 'bus.route')->find($scheduleId) : null;

        $buses = $transferService->listDestinationBuses(
            BookingTransferService::ACTOR_BUS_OWNER,
            [
                'company_id' => (int) $companyId,
                'dest_company_id' => $destCompanyId,
                'emergency' => $emergency,
                'allow_emergency' => true,
                'schedule_id' => $scheduleId,
            ]
        );

        // When a schedule is chosen, trust that bus; otherwise filter by route/date.
        if (!$scheduleId) {
            if ($routeId) {
                $buses = $buses->filter(function ($bus) use ($routeId) {
                    $busRouteId = (int) ($bus->route?->id ?? 0);

                    return $busRouteId === (int) $routeId;
                })->values();
            }

            if ($travelDate) {
                $busIdsWithSchedule = Schedule::query()
                    ->whereDate('schedule_date', $travelDate)
                    ->when($routeId, function ($q) use ($routeId) {
                        $q->where(function ($inner) use ($routeId) {
                            $inner->where('route_id', $routeId)
                                ->orWhereHas('bus.route', fn ($rq) => $rq->where('routes.id', $routeId));
                        });
                    })
                    ->pluck('bus_id')
                    ->unique()
                    ->all();
                if (!empty($busIdsWithSchedule)) {
                    $buses = $buses->filter(
                        fn ($bus) => in_array((int) $bus->id, array_map('intval', $busIdsWithSchedule), true)
                    )->values();
                }
            }
        }

        $preferredBusId = $schedule->bus_id ?? null;

        return response()->json(
            $buses->map(fn ($bus) => $transferService->formatDestinationBus($bus, $preferredBusId))
        );
    }

    private function formatTransferSchedule(Schedule $schedule): array
    {
        return app(BookingTransferService::class)->formatTransferSchedule($schedule);
    }

    public function calculateTransferAmounts(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'original_booking_id' => 'required|exists:bookings,id',
            'pickup_point' => 'required|string',
            'dropping_point' => 'required|string',
            'schedule_id' => 'nullable|exists:schedules,id',
            'travel_date' => 'nullable|date',
            'emergency' => 'nullable|boolean',
        ]);

        $emergency = $request->boolean('emergency');
        $originalBooking = Booking::find($request->original_booking_id);
        $newBus = Bus::with('route', 'campany')->find($request->bus_id);
        $newSchedule = $request->schedule_id ? Schedule::find($request->schedule_id) : null;

        if (!$originalBooking || !$newBus) {
            return response()->json(['error' => 'Invalid data provided'], 400);
        }

        $userCompanyId = Auth::user()->campany->id ?? null;
        $crossCompany = (int) ($newBus->campany?->id ?? 0) !== (int) $userCompanyId;

        if (!$emergency && $crossCompany) {
            return response()->json(['error' => __('vender/transfer.new_bus_company_mismatch')], 422);
        }

        if (!$emergency && $newSchedule) {
            if ((int) $newSchedule->bus_id !== (int) $newBus->id || ($request->travel_date && (string) $newSchedule->schedule_date !== (string) $request->travel_date)) {
                return response()->json(['error' => __('vender/transfer.invalid_schedule_for_bus_date')], 422);
            }
        }

        return response()->json(
            app(BookingTransferService::class)->previewPricing(
                $originalBooking,
                $newBus,
                (string) $request->pickup_point,
                (string) $request->dropping_point,
                $emergency,
                $userCompanyId ? (int) $userCompanyId : null
            )
        );
    }

    /**
     * Record (or clear) an excess-luggage charge on an already-existing paid
     * booking. Until now excess luggage could only be declared by the
     * customer/vendor at the moment of booking — there was no way to add it
     * later (e.g. a conductor finds extra luggage at boarding). Reuses the
     * same has_excess_luggage/excess_luggage_fee/excess_luggage_description
     * columns already read everywhere else (system income, government levy,
     * manifest), so no other reporting code needs to change.
     */
    public function updateExcessLuggage(Request $request, $bookingId)
    {
        $this->authorizeBusOwnerExcessLuggageAccess();

        $user = Auth::user();
        $companyId = $user->campany->id ?? null;
        if (!$companyId) {
            return back()->with('error', __('vender/earning.no_company_account'));
        }

        $booking = Booking::whereHas('bus', function ($q) use ($companyId) {
            $q->where('campany_id', $companyId);
        })->find($bookingId);

        if (!$booking) {
            return back()->with('error', __('vender/transfer.booking_not_found_or_unauthorized'));
        }

        $request->validate([
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

        $svc = app(\App\Services\ExcessLuggageService::class);

        if ($request->luggage_action === 'remove') {
            $svc->clear($booking);

            return back()->with('success', __('vender/luggage.removed_success'));
        }

        try {
            $svc->weighIn($booking, $request->only([
                'excess_luggage_fee',
                'excess_luggage_description',
                'actual_weight',
                'actual_length',
                'actual_height',
                'actual_width',
            ]), $user);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('vender/luggage.saved_success'));
    }

    /**
     * Standalone excess-luggage receipt — separate from the main bus ticket,
     * covering the weigh-in reconciliation (estimated vs actual weight) that
     * the ticket itself has no room for. Reuses the same TRA/QR fields and
     * busOwnerAccount header already proven on print.ticket.
     */
    public function printExcessLuggageReceipt($bookingId)
    {
        $this->authorizeBusOwnerExcessLuggageAccess();

        $user = Auth::user();
        $companyId = $user->campany->id ?? null;
        if (!$companyId) {
            return back()->with('error', __('vender/earning.no_company_account'));
        }

        $booking = Booking::with([
                'bus.campany.busOwnerAccount',
                'bus.route',
                'campany.busOwnerAccount',
                'route',
                'schedule',
            ])
            ->whereHas('bus', function ($q) use ($companyId) {
                $q->where('campany_id', $companyId);
            })
            ->find($bookingId);

        if (!$booking) {
            return back()->with('error', __('vender/transfer.booking_not_found_or_unauthorized'));
        }

        $luggageService = app(\App\Services\ExcessLuggageService::class);
        if (!$luggageService->canPrintReceipt($booking)) {
            return back()->with('error', __('vender/luggage.print_payment_required'));
        }

        $busOwnerAccount = optional($booking->bus->campany ?? $booking->campany)->busOwnerAccount;
        $busCompany = $booking->bus->campany ?? $booking->campany;
        $status = $luggageService->normalizeStatus($booking);

        $traQrCode = null;
        if (!empty($booking->tra_qr_url)) {
            $traQrPng = DNS2D::getBarcodePNG($booking->tra_qr_url, 'QRCODE', 4, 4, [0, 0, 0]);
            $traQrCode = $traQrPng
                ? '<img src="data:image/png;base64,' . $traQrPng . '" alt="TRA QR" width="68" height="68">'
                : null;
        }

        $luggageQrPayload = $luggageService->buildCompanyQrPayload($booking);
        $luggageQrPng = DNS2D::getBarcodePNG($luggageQrPayload, 'QRCODE', 4, 4, [0, 0, 0]);
        $luggageQrCode = $luggageQrPng
            ? '<img src="data:image/png;base64,' . $luggageQrPng . '" alt="Luggage QR" width="68" height="68">'
            : null;

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

    private function authorizeBusOwnerExcessLuggageAccess(): void
    {
        $user = Auth::user();
        if (!$user || $user->isBusCampany()) {
            return;
        }

        abort_unless(
            $user->hasAccessTo(Access::BUS['EXCESS_LUGGAGE'])
                || $user->hasAccessTo(Access::BUS['BOOKING_HISTORY']),
            403
        );
    }

    public function busOwnerParcels()
    {
        $user = auth()->user();
        if ($user->campany) {
             $companyId = $user->campany->id;
             $buses = Bus::where('campany_id', $companyId)->get();
             $busIds = $buses->pluck('id');
     
             $parcels = \App\Models\Parcel::whereIn('bus_id', $busIds)
                 ->with('bus')
                 ->latest()
                 ->paginate(15);
     
             return view('bus_owner.parcels.index', compact('buses', 'parcels'));
        } else {
             return back()->with('error', __('vender/earning.no_company_account'));
        }
    }
}
