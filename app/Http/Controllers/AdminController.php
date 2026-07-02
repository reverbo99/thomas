<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\bus;
use App\Models\City;
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
use App\Services\FareFormulaService;
use Milon\Barcode\DNS2D;
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
                'status' => 'pending',
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

    private function getFormattedEarnings(array $bus_ids, Carbon $date): string
    {
        $earnings = Booking::whereDate('travel_date', $date)
            ->whereIn('bus_id', $bus_ids)
            ->where('payment_status', 'Paid')
            ->sum('amount');
        return 'Tsh ' . number_format($earnings, 0, '.', ',');
    }

    private function calculateEarningsChange(array $bus_ids, Carbon $today): string
    {
        $todayEarnings = Booking::whereDate('travel_date', $today)
            ->whereIn('bus_id', $bus_ids)
            ->where('payment_status', 'Paid')
            ->sum('amount');
        $yesterdayEarnings = Booking::whereDate('travel_date', $today->copy()->subDay())
            ->whereIn('bus_id', $bus_ids)
            ->where('payment_status', 'Paid')
            ->sum('amount');

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
        $query = Booking::with(['campany', 'schedule', 'user', 'bus.route', 'vender', 'campany.busOwnerAccount', 'governmentLeviesOnService'])
            ->whereHas('campany', function ($q) {
$q->where('id', auth()->user()->campany->id);
            });

        // Prefer explicit date range over period (date picker on history page)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay(),
            ]);
        } elseif ($request->has('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        $bookings = $query->where('payment_status', 'Paid')->latest()->get();

        $totalPayment = 0;
        $totalDiscount = 0;
        $totalVAT = 0;
        $grandTotal = 0;
        foreach ($bookings as $b) {
            $totalPayment += (float)($b->amount ?? 0) + (float)($b->vat ?? 0);
            $totalDiscount += (float)($b->discount_amount ?? 0);
            $totalVAT += (float)($b->vat ?? 0);
            $grandTotal += round((float)($b->fee ?? 0) + (float)($b->vender_fee ?? 0) + (float)($b->amount ?? 0) + (float)($b->vat ?? 0) + (float)($b->fee_vat ?? 0));
        }

        return view('controller.history', compact('bookings', 'totalPayment', 'totalDiscount', 'totalVAT', 'grandTotal'));
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

        $data = $this->getEarningsData($bus_ids, $period, $start_date, $end_date);

        session()->put('export_data', $data);

        return view('controller.erning', compact('data'));
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

        // Redirect with all inputs to preserve them
        return redirect()->route('erning', [
            'period' => $request->period,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);
    }

    private function getEarningsData($bus_ids, $period, $start_date = null, $end_date = null)
    {
        $query = Transaction::with('campany')->where('campany_id', Auth::user()->campany->id);

        // Apply time period filter
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

        // Filter transactions by date range
        $transactions = $query->whereBetween('created_at', [$start, $end])->get();

        return [
            'earnings' => $this->getFormattedEarning($bus_ids, $start, $end),
            'request' => $transactions->sum('amount'),
            'success' => $transactions->where('status', 'Completed')->sum('amount'),
            'transactions' => $transactions,
        ];
    }

    private function getFormattedEarning($bus_ids, $start, $end)
    {
        // Adjust the earnings calculation to use the date range
        $earnings = Booking::whereIn('bus_id', $bus_ids)
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'Paid')
            ->sum('amount'); // Adjust based on your Booking model

        return 'Tsh ' . number_format($earnings, 2, '.', ',');
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
            $rowTotal = round(($b->fee ?? 0) + ($b->vender_fee ?? 0) + ($b->amount ?? 0) + ($b->vat ?? 0) + ($b->fee_vat ?? 0));
            $out[] = [
                'booking_code' => $b->booking_code ?? 'N/A',
                'company_name' => optional($b->campany)->name ?? 'N/A',
                'route_from' => optional($b->schedule)->from ?? optional(optional($b->bus)->route)->from ?? 'N/A',
                'route_to' => optional($b->schedule)->to ?? optional(optional($b->bus)->route)->to ?? 'N/A',
                'bus_number' => optional($b->bus)->bus_number ?? 'N/A',
                'travel_date' => $b->travel_date ? Carbon::parse($b->travel_date)->format('Y-m-d') : 'N/A',
                'seat' => $b->seat ?? 'N/A',
                'pickup_point' => $b->pickup_point ?? 'N/A',
                'customer_name' => $b->customer_name ?? 'N/A',
                'customer_phone' => $b->customer_phone ?? 'N/A',
                'amount' => $b->amount ?? '0',
                'commision' => (string) round(($b->fee ?? 0) + ($b->vender_fee ?? 0)),
                'service' => $b->vender_fee ?? 'N/A',
                'vendor_service' => $b->vender_service ?? 'N/A',
                'discount' => $b->discount_amount ?? 'N/A',
                'gov_levy' => (string) (float) ($b->government_levy ?? 0),
                'gov_levy_service' => (string) (float) $b->governmentLeviesOnService->sum('amount'),
                'vat' => $b->vat ?? 'N/A',
                'total' => (string) $rowTotal,
                'gender' => $b->gender ?? 'N/A',
                'age' => $b->age ?? 'N/A',
                'age_group' => $b->age_group ?? 'N/A',
                'infant_child' => $b->infant_child ?? 0,
                'excess_luggage' => $b->excess_luggage ?? 0,
                'excess_luggage_description' => $b->excess_luggage_description ?? null,
                'excess_luggage_fee' => $b->excess_luggage_fee ?? null,
            ];
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

        if ($request->filled('start_date') && $request->filled('end_date') && $companyId) {
            $bookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService'])
                ->where('campany_id', $companyId)
                ->where('payment_status', 'Paid')
                ->whereBetween('created_at', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay(),
                ])
                ->orderBy('seat')
                ->latest()
                ->get();
            $data = $this->bookingsToReportArray($bookings);
        } elseif ($request->filled('booking_ids')) {
            $ids = is_array($request->booking_ids) ? $request->booking_ids : (array) json_decode($request->booking_ids, true);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $query = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService'])
                    ->whereIn('id', $ids)
                    ->where('payment_status', 'Paid')
                    ->orderBy('seat');
                if ($companyId) {
                    $query->where('campany_id', $companyId);
                }
                $bookings = $query->get();
                $data = $this->bookingsToReportArray($bookings);
            }
        }

        if ($data === null && $request->filled('data')) {
            $data = json_decode($request->data, true);
        }

        if ($data === null && $companyId) {
            $bookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService'])
                ->where('campany_id', $companyId)
                ->where('payment_status', 'Paid')
                ->orderBy('seat')
                ->latest()
                ->get();
            $data = $this->bookingsToReportArray($bookings);
        }

        if (empty($data) || !is_array($data) || !isset($data[0])) {
            return redirect()->back()->with('error', __('vender/history.no_booking_data_manifest'));
        }

        if (!isset($data[0]['bus_number']) || empty(trim($data[0]['bus_number'] ?? ''))) {
            return redirect()->back()->with('error', __('vender/history.bus_number_not_found'));
        }

        $number = $data[0]['bus_number'];
        $bus = bus::where('bus_number', $number)->first();

        if (!$bus) {
            return redirect()->back()->with('error', __('vender/history.bus_not_found_number', ['number' => $number]));
        }

        return $this->generateManifest($data, $bus);
    }

    public function generateManifest($data,$bus)
    {
        $pdf = Pdf::loadView('print.manifest', ['bookings' => $data, 'bus' => $bus]);

        return $pdf->download('manifest-' . now() . '.pdf');
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
        $data = json_decode($request->data);

        $pdf = Pdf::loadView('print.vender', ['data' => $data]);

        $pdf->setPaper([0, 0, 4 * 72, 7 * 72], 'portrait');

        return $pdf->download($data->user->name . '.pdf');
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

        $dns2d = new DNS2D();

        // Generate as HTML (easiest for Blade)
        $qrCode = $dns2d->getBarcodeHTML($data->booking_code, 'QRCODE', 6, 6, 'blue');

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

        $bookings = Booking::whereHas('bus', function ($query) use ($companyId) {
            $query->where('campany_id', $companyId);
        })
            ->whereIn('payment_status', ['Paid', 'Reserved', 'resaved'])
            ->with('bus.busname', 'route_name')
            ->orderByDesc('travel_date')
            ->get();

        $buses = Bus::where('campany_id', $companyId)->with('busname', 'route')->get();
        $schedules = collect();

        $selectedBooking = null;
        if ($booking_id) {
            $selectedBooking = Booking::with('bus.busname', 'route_name', 'route.schedule')->find($booking_id);
            if (!$selectedBooking || $selectedBooking->bus->campany_id !== $companyId) {
                $selectedBooking = null;
                return redirect()->route('booking.transfer.form')
                    ->with('error', __('vender/transfer.booking_not_found_or_unauthorized'));
            }
            if (!in_array($selectedBooking->payment_status, ['Paid', 'Reserved', 'resaved'], true)) {
                $selectedBooking = null;
                return redirect()->route('booking.transfer.form')
                    ->with('error', __('vender/transfer.booking_not_transferable'));
            }
        }

        return view('controller.transfer_booking', compact('bookings', 'buses', 'schedules', 'selectedBooking'));
    }

    public function resavedTickets()
    {
        $user = Auth::user();
        $companyId = $user->campany ? $user->campany->id : null;

        if (!$companyId) {
            return redirect()->back()->with('error', __('vender/earning.no_company_account'));
        }

        $resavedBookings = Booking::whereHas('bus', function ($query) use ($companyId) {
            $query->where('campany_id', $companyId);
        })
        ->where('payment_status', 'resaved')
        ->with(['bus.busname', 'schedule', 'user'])
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
        $busId = $request->input('bus_id');
        $travelDate = $request->input('travel_date');

        if (!$busId || !$travelDate) {
            return response()->json([], 400);
        }

        $schedules = Schedule::where('bus_id', $busId)
                             ->whereDate('schedule_date', $travelDate)
                             ->orderBy('start')
                             ->get()
                             ->unique('id')
                             ->values();

        return response()->json($schedules);
    }

    public function calculateTransferAmounts(Request $request)
    {
        $request->validate([
            'bus_id' => 'required|exists:buses,id',
            'schedule_id' => 'required|exists:schedules,id',
            'travel_date' => 'required|date',
            'pickup_point' => 'required|string',
            'dropping_point' => 'required|string',
            'original_booking_id' => 'required|exists:bookings,id',
        ]);

        $originalBooking = Booking::find($request->original_booking_id);
        $newBus = Bus::with('route', 'campany')->find($request->bus_id);
        $newSchedule = Schedule::find($request->schedule_id);

        if (!$originalBooking || !$newBus || !$newSchedule) {
            return response()->json(['error' => 'Invalid data provided'], 400);
        }
        if ((int) $newSchedule->bus_id !== (int) $newBus->id || (string) $newSchedule->schedule_date !== (string) $request->travel_date) {
            return response()->json(['error' => __('vender/transfer.invalid_schedule_for_bus_date')], 422);
        }
        if ((int) $originalBooking->campany_id !== (int) ($newBus->campany->id ?? 0)) {
            return response()->json(['error' => __('vender/transfer.new_bus_company_mismatch')], 422);
        }

        $formulaService = app(FareFormulaService::class);
        $numberOfSeats = $formulaService->seatCountFromSeatString($originalBooking->seat);
        $basePrice = max(0, (float) ($newBus->route->price ?? 0) * $numberOfSeats);
        $newAmount = $basePrice;
        $newDiscountAmount = min(max((float) ($originalBooking->discount_amount ?? 0), 0), $newAmount);
        $discountedFare = max(0, $newAmount - $newDiscountAmount);

        $setting = Setting::first();
        $fees = $formulaService->calculateTravellerServiceFee((float) $discountedFare, $setting, $numberOfSeats);
        $distance = \App\Services\RouteDistanceService::resolveForBooking(
            null,
            (string) $request->pickup_point,
            (string) $request->dropping_point,
            (float) ($newBus->route->distance ?? 0)
        );

        // Keep legacy VAT baseline aligned with transfer commit logic.
        $newVat = $newAmount * (0.5 / 100);

        return response()->json([
            'new_amount' => round($newAmount, 2),
            'new_busFee' => round($newAmount, 2),
            'new_discount_amount' => round($newDiscountAmount, 2),
            'new_distance' => round($distance, 2),
            'new_bima_amount' => round((float) ($originalBooking->bima_amount ?? 0), 2),
            'new_vat' => round($newVat, 2),
            'new_fee' => round($fees, 2),
            'new_service' => round((float) ($originalBooking->service ?? 0), 2),
            'new_vender_fee' => round((float) ($originalBooking->vender_fee ?? 0), 2),
            'new_vender_service' => round((float) ($originalBooking->vender_service ?? 0), 2),
            'new_campany_id' => $newBus->campany->id,
            'new_route_id' => $newBus->route->id,
        ]);
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
