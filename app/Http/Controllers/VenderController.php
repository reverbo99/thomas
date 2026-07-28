<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MixByYassController;
use App\Http\Controllers\PDOController;
use App\Http\Controllers\TigosecureController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\ClickPesaController;
use App\Http\Controllers\status\Fees;
use App\Http\Controllers\status\Vender;
use App\Mail\SendEmail;
use App\Models\Bima;
use App\Models\Booking;
use App\Models\bus;
use App\Models\CancelledBookings;
use App\Models\City;
use App\Models\Discount;
use App\Models\Parcel;
use App\Models\PaymentFees;
use App\Models\route;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\SystemBalance;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Services\FareFormulaService;
use Illuminate\Validation\Rule;

class VenderController extends Controller
{
    const EXCESS_LUGGAGE_FEE = 2500; // TSh. 2,500 for excess luggage

    public function index(Request $request)
    {
        $venderId = auth()->user()->id;
        $filter = $request->get('filter', 'month'); // Default to 'month'

        // Today's Bookings: leo tu, za vender huyu, zilizolipwa (Paid)
        $TodayBookings = Booking::whereDate('created_at', Carbon::today())
            ->where('vender_id', $venderId)
            ->where('payment_status', 'Paid')
            ->get();

        // Weekly Bookings: wiki hii, za vender huyu, zilizolipwa (Paid)
        $WeekBookings = Booking::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])
            ->where('vender_id', $venderId)
            ->where('payment_status', 'Paid')
            ->get();

        $bookings = Booking::where('vender_id', $venderId)
            ->latest()
            ->get();

        // ── Fee statistics for vendor dashboard ──────────────────────────
        // Base: all Paid bookings for this vendor
        $paidQuery = Booking::where('vender_id', $venderId)
            ->where('payment_status', 'Paid');

        $totalVenderFee = (float) (clone $paidQuery)->sum('vender_fee');
        $totalVenderService = (float) (clone $paidQuery)->sum('vender_service');
        $totalExcessLuggageFee = (float) (clone $paidQuery)
            ->where('has_excess_luggage', 1)
            ->sum('excess_luggage_fee');

        // Parcel fees: sum of all parcel amounts paid through this vendor
        $totalParcelFee = (float) Parcel::where('vender_id', $venderId)
            ->where('status', 'delivered')
            ->sum('amount_paid');

        // Cancellation fees: sum of cancelled booking amounts attributed to this vendor
        $totalCancellationFee = (float) CancelledBookings::whereHas('booking', function ($q) use ($venderId) {
            $q->where('vender_id', $venderId);
        })->sum('amount');

        // Prepare label/data based on filter
        $monthlyLabels = [];
        $monthlyData = [];

        switch ($filter) {
            case 'today':
                $labels = [];
                $data = [];

                foreach (range(0, 23) as $hour) {
                    $label = sprintf('%02d:00', $hour);
                    $labels[] = $label;

                    $count = Booking::where('vender_id', $venderId)
                        ->whereDate('created_at', Carbon::today())
                        ->where('payment_status', 'Paid')
                        ->whereRaw('HOUR(created_at) = ?', [$hour])
                        ->count();

                    $data[] = $count;
                }

                $monthlyLabels = $labels;
                $monthlyData = $data;
                break;

            case 'week':
                $labels = [];
                $data = [];

                foreach (range(0, 6) as $i) {
                    $day = Carbon::now()->startOfWeek()->addDays($i);
                    $labels[] = $day->format('D');

                    $count = Booking::where('vender_id', $venderId)
                        ->whereDate('created_at', $day)
                        ->where('payment_status', 'Paid')
                        ->count();

                    $data[] = $count;
                }
                $monthlyLabels = $labels;
                $monthlyData = $data;
                break;

            case 'year':
                $stats = Booking::select(
                    DB::raw("MONTH(created_at) as month"),
                    DB::raw("COUNT(*) as total")
                )
                    ->whereYear('created_at', Carbon::now()->year)
                    ->where('vender_id', $venderId)
                    ->where('payment_status', 'Paid')
                    ->groupBy(DB::raw("MONTH(created_at)"))
                    ->orderBy('month')
                    ->get();

                foreach (range(1, 12) as $month) {
                    $monthlyLabels[] = Carbon::create()->month($month)->format('M');
                    $monthlyData[] = $stats->firstWhere('month', $month)->total ?? 0;
                }
                break;

            case 'month':
            default:
                $stats = Booking::select(
                    DB::raw("DAY(created_at) as day"),
                    DB::raw("COUNT(*) as total")
                )
                    ->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->where('vender_id', $venderId)
                    ->where('payment_status', 'Paid')
                    ->groupBy(DB::raw("DAY(created_at)"))
                    ->orderBy('day')
                    ->get();

                $daysInMonth = Carbon::now()->daysInMonth;

                foreach (range(1, $daysInMonth) as $day) {
                    $monthlyLabels[] = $day;
                    $monthlyData[] = $stats->firstWhere('day', $day)->total ?? 0;
                }
                break;
        }

        return view('vender.index', compact(
            'TodayBookings',
            'WeekBookings',
            'bookings',
            'monthlyLabels',
            'monthlyData',
            'filter',
            'totalVenderFee',
            'totalVenderService',
            'totalParcelFee',
            'totalExcessLuggageFee',
            'totalCancellationFee'
        ));
    }

    public function route(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'departure_city' => 'required|exists:cities,id',
            'arrival_city' => 'required|exists:cities,id|different:departure_city',
            'departure_date' => 'required|date|after_or_equal:today',
            'passengers' => 'sometimes|integer|min:1',
        ]);

        // Retrieve city names and normalize departure date
        $departureCityName = City::findOrFail($validated['departure_city'])->name;
        $arrivalCityName = City::findOrFail($validated['arrival_city'])->name;
        $departure_date = Carbon::parse($validated['departure_date'])->toDateString();

        session()->put('departure_date', $departure_date);

        // Query buses with relationships and filter by route
        $busList = Bus::with([
            'busname' => function ($query) {
                $query->where('status', 1);
            },
            'route.via',
            'schedule' => function ($query) use ($departureCityName, $arrivalCityName, $departure_date) {
                $query->where('from', $departureCityName)
                    ->where('to', $arrivalCityName)
                    ->whereDate('schedule_date', $departure_date);
            },
            'booking' => function ($query) use ($departure_date) {
                $query->where('travel_date', $departure_date)
                    ->where('payment_status', 'Paid');
            }
        ])
            ->whereHas('busname', function ($query) {
                $query->where('status', 1);
            })
            ->whereHas('schedule', function ($query) use ($departureCityName, $arrivalCityName, $departure_date) {
                $query->where('from', $departureCityName)
                    ->where('to', $arrivalCityName)
                    ->whereDate('schedule_date', $departure_date);
            })
            ->get()
            ->map(function ($bus) {
                // Ensure total_seats is available
                $total_seats = $bus->total_seats ?? $bus->busname->total_seats ?? 0;

                // Calculate booked seats from pre-loaded bookings
                $booked_seats = $bus->booking
                    ->flatMap(function ($booking) {
                        // Handle comma-separated seats, trim whitespace, and filter valid seats
                        return array_filter(array_map('trim', explode(',', $booking->seat)));
                    })
                    ->unique()
                    ->count();

                $bus->booked_seats = $booked_seats;
                $bus->remain_seats = $total_seats - $booked_seats;

                // Ensure remain_seats is not negative
                $bus->remain_seats = max(0, $bus->remain_seats);

                return $bus;
            }); // Convert to array to avoid dynamic property warnings

        // Debugging: Uncomment to inspect the data
        // return response()->json($busList);

        return view('vender.begin', compact('busList', 'departureCityName', 'arrivalCityName', 'departure_date'));
    }

    public function mybooking_search(Request $request)
    {
        if ($request->filled('departure_city') && $request->filled('arrival_city')) {
            return $this->by_route_search($request);
        }

        if ($request->filled('bus_id')) {
            return app(RouteController::class)->bus_name($request);
        }

        return view('vender.route');
    }

    public function by_route_search(Request $request)
    {
        $request->attributes->set('_booking_view', 'vender.by_route_search');

        return app(BookingController::class)->by_route_search($request);
    }

    public function booking_form($id, $from, $to)
    {
        $date = session()->get('departure_date');
        $car = Bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($from, $to) {
                $query->where('from', $from)->where('to', $to);
            },
            'route.points'
        ])->find($id);

        if ($car === null || $car->route === null || $car->schedule === null) {
            return redirect()->route('vender.route')->with(
                'error',
                __('all.trip_not_available')
            );
        }

        $time = [
            'start' => $car->route->route_start,
            'end' => $car->route->route_end,
        ];

        session()->put('time', $time);

        // Initialize a filtered points collection
        $filteredPoints = collect();

        // Post-process the points if route and schedule exist
        if ($car->schedule->schedule_date === $date) {
            $route = $car->route;
            $schedule = $car->schedule;

            // Filter points based on route and schedule comparison
            if ($route->from === $schedule->from && $route->to === $schedule->to) {
                // Keep only points with return = 'no'
                $filteredPoints = $car->route->points->filter(function ($point) {
                    return $point->state === 'no';
                });
            } elseif ($route->from === $schedule->to && $route->to === $schedule->from) {
                // Keep only points with return = 'yes'
                $filteredPoints = $car->route->points->filter(function ($point) {
                    return $point->state === 'yes';
                });
            }
        } else {
            // If no valid schedule or date match, use all points
            $filteredPoints = $car->route->points ?? collect();
        }

        // Add filtered points as a new attribute to the car object
        $car->filtered_points = $filteredPoints;
        apply_booking_filtered_points($car);

        return view('vender.booking_form', compact('car'));
        //return $car;
    }

    public function get_form(Request $request)
    {

        //return $request->all();
        if ($request->route_distance < 1) {
            return back()->with('error', __('all.calculate_distance_before_continue'));
        }
        $route = Route::find($request->route_id);
        $schedule = Schedule::find($request->schedule_id);
        $bus_info = [
            'bus_id' => $request->bus_id,
            'from' => $schedule ? $schedule->from : $route->from,
            'to' => $schedule ? $schedule->to : $route->to,
            'schedule_id' => $request->schedule_id,
            'route_id' => $request->route_id,
            'pickup_point' => $request->pickup_point ?? ($schedule ? $schedule->from : $route->from),
            'dropping_point' => $request->dropping_point ?? ($schedule ? $schedule->to : $route->to),
            'travel_date' => session()->get('departure_date') ?? now()->format('Y-m-d'),
            'dropping_point_amount' => $request->dropping_point_amount ?? ($route ? $route->price : 0),
            'route_distance' => $request->route_distance ?? 0
        ];

        // Store in session
        session()->put('booking_form', $bus_info);
        //return session()->get('booking_form');
        // Redirect to seats route
        return redirect()->route('seates.vender');
    }

    public function seates()
    {
        $booking_form = session()->get('booking_form');
        $bus_id = $booking_form['bus_id'];
        $travel_date = $booking_form['travel_date'];
        $price = $booking_form['dropping_point_amount'];

        $info = session()->get('booking_form');
        $car = Bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($travel_date) {
                $query->where('schedule_date', $travel_date);
            },
            'route.points'
        ])->find($bus_id);
        $car = Bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($travel_date) {
                $query->where('schedule_date', $travel_date);
            },
        ])->find($bus_id);

        // Fetch booked seats for the bus and travel date (include Paid, Reserved, and resaved)
        $booked_seats = Booking::where('bus_id', $bus_id)
            ->where('travel_date', $travel_date)
            ->whereIn('payment_status', ['Paid', 'Reserved', 'resaved'])
            ->pluck('seat') // Get the 'seat' column (comma-separated seat numbers)
            ->flatMap(function ($seats) {
                return explode(',', $seats); // Split comma-separated seats into an array
            })
            ->unique() // Remove duplicates
            ->values()
            ->toArray();

        return view('vender.seates', compact('price', 'booked_seats', 'car'));

        //return  $car;
    }

    public function get_seats(Request $request)
    {
        $seats = $request->selected_seats;
        $price = $request->total_amount;

        $bus_info = session()->get('booking_form', []);
        if (empty($bus_info['bus_id']) || empty($bus_info['travel_date'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }

        $selected = is_array($seats) ? $seats : (is_string($seats) ? array_map('trim', explode(',', $seats)) : []);
        $selected = array_filter($selected);

        if (empty($selected)) {
            return redirect()->route('seates.vender')->with('error', __('all.select_at_least_one_seat'));
        }

        $booked = Booking::where('bus_id', $bus_info['bus_id'])
            ->where('travel_date', $bus_info['travel_date'])
            ->whereIn('payment_status', ['Paid', 'Reserved', 'resaved'])
            ->pluck('seat')
            ->flatMap(fn ($s) => explode(',', $s))
            ->map(fn ($s) => trim($s))
            ->unique()
            ->values()
            ->toArray();

        $alreadyBooked = array_intersect($selected, $booked);
        if (!empty($alreadyBooked)) {
            return redirect()->route('seates.vender')->with('error', __('all.seats_no_longer_available_named', [
                'seats' => implode(', ', array_slice($alreadyBooked, 0, 3)),
            ]));
        }

        $bus_info['total_amount'] = $price;
        $bus_info['total_amount_before_coupon'] = $price;
        $bus_info['seats'] = $seats;

        session()->put('booking_form', $bus_info);

        return redirect()->route('vender.pay');
    }

    public function payment()
    {
        $setting = Setting::first();
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }
        $price = session()->get('booking_form')['total_amount'];
        $seats = session()->get('booking_form')['seats'];
        $car = Bus::with([
            'busname',
            'route.via'
        ])->find(session()->get('booking_form')['bus_id']);
        $info = session()->get('booking_form');
        $time = session()->get('time');
        $date = session()->get('booking_form')['travel_date'];
        $formulaService = app(FareFormulaService::class);
        $bookingForm = session()->get('booking_form', []);
        $fees = $formulaService->calculateTravellerServiceFee(
            $formulaService->busFareForServiceFeeFromBookingForm($bookingForm),
            $setting,
            $formulaService->seatCountFromBookingForm($bookingForm)
        );
        $distance = session()->get('booking_form')['route_distance'] ?? 0;
        //return $info;
        return view('vender.payment', compact('price', 'seats', 'info', 'car', 'time', 'date', 'fees', 'distance'));
    }

    public function payment_info(Request $request)
    {
        //return $bus_info = session()->get('booking_form', []);
        $bus_info = session()->get('booking_form', []);
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }

        $bus_info['customer_name'] = $request->customer;
        $bus_info['gender'] = $request->gender;
        $bus_info['age'] = $request->age;
        $bus_info['infant_child'] = $request->infant_child ?? 0;
        $bus_info['age_group'] = $request->age_group;
        $bus_info['category'] = $request->category;
        $bus_info['start'] = session()->get('time')['start'];
        $bus_info['end'] = session()->get('time')['end'];
        $bus_info['discount'] = $request->discount ?? '';
        $bus_info['cancel_amount'] = $request->amount_cancel ?? 0;
        $bus_info['cancel_key'] = $request->key ?? '';
        $bus_info['excess_luggage'] = $request->excess_luggage ?? 0; // Add excess luggage checkbox value
        $bus_info['excess_luggage_description'] = $request->excess_luggage_description ?? null; // Add excess luggage description
        $bus_info['estimated_weight'] = $request->estimated_weight ?? null; // Customer-declared weight, for the excess luggage receipt
        session()->put('booking_form', $bus_info);

        $insuranceError = process_booking_insurance_input($request, $bus_info);
        session()->put('booking_form', $bus_info);
        if ($insuranceError) {
            return redirect()->route('vender.pay')->with('error', __($insuranceError));
        }

        if (!empty($bus_info['discount'])) {
            $couponCheck = Discount::where('code', $bus_info['discount'])->first();
            if (!$couponCheck) {
                return redirect()->route('vender.pay')->with('error', __('all.invalid_coupon_code'));
            }
            if (!$couponCheck->isValid()) {
                return redirect()->route('vender.pay')->with('error', __('all.coupon_expired_or_limit'));
            }
        }

        $ins = (float) ($bus_info['bima_amount'] ?? 0);
        $dis = 0;
        $setting = Setting::first();

        $total_amount = session()->get('booking_form')['total_amount'];
        $excessLuggageFee = 0;

        if (session()->get('booking_form')['excess_luggage'] == 1) {
            $excessLuggageFee = self::EXCESS_LUGGAGE_FEE;
            $bus_info = session()->get('booking_form', []);
            $bus_info['excess_luggage_fee'] = $excessLuggageFee;
            session()->put('booking_form', $bus_info);
        }

        if (!is_null(session()->get('booking_form')['discount'])) {
            $base = session()->get('booking_form')['total_amount_before_coupon'] ?? $total_amount;
            $discountedFare = $this->applyDiscount($base);
            $price = $discountedFare + $ins + $excessLuggageFee - $bus_info['cancel_amount'];
            $dis = $base - $discountedFare;

            $bus_info = session()->get('booking_form', []);
            $bus_info['dispo'] = $discountedFare;
            session()->put('booking_form', $bus_info);
        } else {
            $price = $total_amount + $ins + $excessLuggageFee - $bus_info['cancel_amount'];
        }

        Session::put('cancel', $bus_info['cancel_amount']);

        $formulaService = app(FareFormulaService::class);
        $bus_info = session()->get('booking_form', []);
        $fees = $formulaService->calculateTravellerServiceFee(
            $formulaService->busFareForServiceFeeFromBookingForm($bus_info),
            $setting,
            $formulaService->seatCountFromBookingForm($bus_info)
        );
        $bus_info['discount_amount'] = $dis;
        $bus_info['payable_amount'] = round($price + $fees);
        session()->put('booking_form', $bus_info);

        // Make luggage fee available to the view
        $excess_luggage_fee = $excessLuggageFee;
        $test_mode = (bool) ($setting->test_mode ?? false);

        return view('vender.payment_details', compact('price', 'ins', 'fees', 'dis', 'excess_luggage_fee', 'test_mode'));
    }

    public function get_payment(Request $request)
    {
        $request->validate([
            'contactNumber' => ['required', 'string'],
            'contactEmail' => ['nullable', 'email'],
        ]);
        $bus_info = session()->get('booking_form', []);
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }

        $contactNumber = normalize_tanzania_phone_for_booking((string) $request->contactNumber);
        $paymentRaw = trim((string) ($request->payment_contact ?? ''));
        $paymentContact = $paymentRaw !== '' ? normalize_tanzania_phone_for_booking($paymentRaw) : '';

        $bus_info['customer_number'] = $contactNumber;
        $bus_info['customer_email'] = $request->contactEmail;
        $bus_info['customer_payment_number'] = $paymentContact !== '' ? $paymentContact : $contactNumber;
        $bus_info['countrycode'] = $request->countrycode;

        $user = $request->user_id ?? "";

        session()->put('booking_form', $bus_info);
        $payment_method = $request->payment_method;

        $isResave = $request->has('resave_ticket') && $request->input('resave_ticket') == '1';

        $canonicalAmount = session()->get('booking_form')['payable_amount'] ?? $request->amount;
        return $this->pay($canonicalAmount, $user, $payment_method, $isResave);
    }

    private function generateRandomId()
    {
        $characters = "abcdefghijklmnopqrstuvwxyz0123456789";
        $randomString = "";
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        $randomNumber = rand(100, 999);

        return $randomString . "-" . $randomNumber;
    }

    public function pay($amount, $user, $method, $isResave = false)
    {
        // Check if test mode is enabled
        $settings = \App\Models\Setting::first();
        if ($settings && ($settings->test_mode ?? false)) {
            // Test mode is enabled - use test payment processing
            return $this->processTestPayment($amount, $user, $method, $isResave);
        }

        $tigo = new TigosecureController();
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }
        $bookingForm = session()->get('booking_form');
        $bima = $bookingForm['bima'] ?? 0;
        $xcode = $this->generateRandomId();
        $data = [
            'account' => session()->get('booking_form')['customer_payment_number'],
            'countryCode' => '255',
            'country' => 'TZA',
            'firstName' => session()->get('booking_form')['customer_name'],
            'lastName' => '',
            'email' => session()->get('booking_form')['customer_email'],
            'currency' => 'TZS',
            'amount' => round($amount),
            'transactionRefId' => $xcode,
        ];
        // Generate unique booking code
        $bookingCode = $this->generateRandomCode();
        $bus = Bus::with(['busname', 'campany.balance'])->find(session()->get('booking_form')['bus_id']);

        // Prepare booking data with payment_status as Unpaid
        $pop = '';
        if (auth()->check()) {
            if (auth()->user()->role == 'vender') {
                $pop = auth()->user()->id;
            } else {
                $pop = '';
            }
        }

        $bookingData = [
            'booking_code' => $bookingCode,
            'campany_id' => $bus->campany->id,
            'bus_id' => $bookingForm['bus_id'],
            'route_id' => $bookingForm['route_id'],
            'pickup_point' => $bookingForm['pickup_point'],
            'dropping_point' => $bookingForm['dropping_point'],
            'travel_date' => $bookingForm['travel_date'],
            'seat' => $bookingForm['seats'],
            'amount' => round($amount),
            'gender' => $bookingForm['gender'] ?? null,
            'age' => $bookingForm['age'] ?? null,
            'infant_child' => $bookingForm['infant_child'] ?? 0,
            'age_group' => $bookingForm['age_group'] ?? null,
            'payment_status' => $isResave ? 'resaved' : 'Unpaid',
            'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
            'customer_phone' => $bookingForm['customer_number'],
            'customer_name' => $bookingForm['customer_name'],
            'passengers' => booking_passengers_for_storage($bookingForm),
            'customer_email' => $bookingForm['customer_email'],
            'bima' => $bookingForm['bima'] ?? 0,
            'insuranceDate' => $bookingForm['insuranceDate'] ?? null,
            'vender_id' => $pop,
            'discount' => $bookingForm['discount'] ?? '',
            'discount_amount' => $bookingForm['discount_amount'] ?? 0,
            'distance' => $bookingForm['route_distance'] ?? 0,
            'busFee' => $bookingForm['dispo'] ?? $bookingForm['total_amount'],
            'schedule_id' => $bookingForm['schedule_id'] ?? null,
            'has_excess_luggage' => $bookingForm['has_excess_luggage'] ?? ($bookingForm['excess_luggage'] ?? 0),
            'excess_luggage_fee' => (int) ($bookingForm['excess_luggage_fee'] ?? 0),
            'excess_luggage_description' => $bookingForm['excess_luggage_description'] ?? null,
            'estimated_weight' => $bookingForm['estimated_weight'] ?? null,
        ];

        if ($bima == 1) {
            $bookingData['bima_amount'] = session()->get('booking_form')['bima_amount'];
        } else {
            $bookingData['bima_amount'] = 0;
        }

        // Create booking with Unpaid status
        try {
            $booking = Booking::create($bookingData);
        } catch (\Exception $e) {
            Log::channel('tigo')->error('Failed to create unpaid booking', [
                'error' => $e->getMessage(),
                'data' => $bookingData,
            ]);
            return response()->json(['status' => 'error', 'message' => __('all.failed_create_booking')], 500);
        }

        if ($isResave) {
            session()->forget('booking_form');

            return redirect()->route('vender.resaved.tickets')->with(
                'success',
                __('all.ticket_reserved_success_24h')
            );
        }

        // Initiate payment and get transactionRefId
        if ($method == 'mixx') {
            try {
                $paymentResponse = $tigo->payment($data);
                // Store transactionRefId in booking
                $booking->update(['transaction_ref_id' => $paymentResponse['transactionRefId']]);
                // Clear session data
                session()->forget('booking_form');
                // Redirect to payment URL
                return redirect($paymentResponse['redirectUrl']);
            } catch (\Exception $e) {
                Log::channel('tigo')->error('Payment initiation failed', [
                    'error' => $e->getMessage(),
                    'booking_id' => $booking->id,
                ]);
                return response()->json(['status' => 'error', 'message' => __('all.payment_initiation_failed')], 500);
            }
        } elseif ($method == 'dpo') {

            try {
                $dpo = new PDOController();
                Session::put('booking', $booking);
                //return "haha";
                return $dpo->initiatePayment(
                    round($amount),
                    session()->get('booking_form')['customer_name'],
                    session()->get('booking_form')['customer_name'],
                    session()->get('booking_form')['customer_number'],
                    session()->get('booking_form')['customer_email'],
                    $xcode
                );
            } catch (\Exception $e) {
                // Log the error
                Log::error('DPO Payment initiation failed: ' . $e->getMessage());
                // Optionally, redirect the user back with an error message
                return $e->getMessage();
            }
        } elseif ($method == 'cash') {

            try {
                Session::put('booking', $booking);
                $data = Session::get('booking');
                $cash = new CashController();
                //return $data;
                return $cash->cash($data, $xcode);
            } catch (\Exception $e) {
                // Log the error
                Log::error('DPO Payment initiation failed: ' . $e->getMessage());
                // Optionally, redirect the user back with an error message
                return $e->getMessage();
            }
        } elseif ($method == 'clickpesa') {
            try {
                // ClickPesa charges the mobile-money number entered for payment, not the contact number.
                $clickpesaPhone = session()->get('booking_form')['customer_payment_number']
                    ?? session()->get('booking_form')['customer_number'];
                $normalized = ClickPesaController::normalizeTanzaniaMsisdnForClickPesa((string) $clickpesaPhone);
                if (!$normalized['ok']) {
                    return redirect()->route('vender.pay')
                        ->with('error', __('all.clickpesa_payment_failed', ['error' => $normalized['error'] ?? __('all.invalid_mobile_money_number')]))
                        ->withErrors(['payment_error' => $normalized['error'] ?? __('all.invalid_mobile_money_number')]);
                }

                $clickpesa = new ClickPesaController();
                Session::forget(['vender', 'amount']);
                Session::put('booking', $booking);
                // Use the same initiatePayment method that works correctly for customers
                return $clickpesa->initiatePayment(
                    round($amount),
                    session()->get('booking_form')['customer_name'],
                    session()->get('booking_form')['customer_name'],
                    $normalized['phone'],
                    session()->get('booking_form')['customer_email'],
                    $xcode
                );
            } catch (\Throwable $e) {
                Log::error('Vender ClickPesa payment failed: ' . $e->getMessage(), [
                    'booking_id' => $booking->id ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                $msg = strlen($e->getMessage()) > 200 ? substr($e->getMessage(), 0, 200) . '…' : $e->getMessage();
                return redirect()->route('vender.pay')->with('error', __('all.clickpesa_error_prefix', ['error' => $msg]))->withErrors(['payment_error' => $msg]);
            }
        }
    }

    /**
     * Process payment in test mode - bypasses real payment gateways
     *
     * @param float $amount
     * @param string $user
     * @param string $method
     * @return \Illuminate\Http\RedirectResponse
     */
    private function processTestPayment($amount, $user, $method, $isResave = false)
    {
        $bookingForm = session()->get('booking_form');
        $bima = $bookingForm['bima'] ?? 0;
        $xcode = 'TEST-' . strtoupper(uniqid() . rand(1000, 9999));

        // Generate unique booking code
        $bookingCode = $this->generateRandomCode();
        $bus = Bus::with(['busname', 'campany.balance'])->find($bookingForm['bus_id']);

        // Get vender ID
        $pop = '';
        if (auth()->check() && auth()->user()->role == 'vender') {
            $pop = auth()->user()->id;
        }

        // Prepare booking data
        $bookingData = [
            'booking_code' => $bookingCode,
            'campany_id' => $bus->campany->id,
            'bus_id' => $bookingForm['bus_id'],
            'route_id' => $bookingForm['route_id'],
            'pickup_point' => $bookingForm['pickup_point'],
            'dropping_point' => $bookingForm['dropping_point'],
            'travel_date' => $bookingForm['travel_date'],
            'seat' => $bookingForm['seats'],
            'amount' => round($amount),
            'gender' => $bookingForm['gender'] ?? null,
            'age' => $bookingForm['age'] ?? null,
            'infant_child' => $bookingForm['infant_child'] ?? 0,
            'age_group' => $bookingForm['age_group'] ?? null,
            'payment_status' => $isResave ? 'resaved' : 'Unpaid',
            'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
            'customer_phone' => $bookingForm['customer_number'],
            'customer_name' => $bookingForm['customer_name'],
            'passengers' => booking_passengers_for_storage($bookingForm),
            'customer_email' => $bookingForm['customer_email'],
            'bima' => $bookingForm['bima'] ?? 0,
            'insuranceDate' => $bookingForm['insuranceDate'] ?? null,
            'vender_id' => $pop,
            'discount' => $bookingForm['discount'] ?? '',
            'discount_amount' => $bookingForm['discount_amount'] ?? 0,
            'distance' => $bookingForm['route_distance'] ?? 0,
            'busFee' => $bookingForm['dispo'] ?? $bookingForm['total_amount'],
            'schedule_id' => $bookingForm['schedule_id'] ?? null,
            'has_excess_luggage' => $bookingForm['has_excess_luggage'] ?? ($bookingForm['excess_luggage'] ?? 0),
            'excess_luggage_fee' => (int) ($bookingForm['excess_luggage_fee'] ?? 0),
            'excess_luggage_description' => $bookingForm['excess_luggage_description'] ?? null,
            'estimated_weight' => $bookingForm['estimated_weight'] ?? null,
            'transaction_ref_id' => $xcode,
            'payment_method' => 'test_mode',
        ];

        if ($bima == 1) {
            $bookingData['bima_amount'] = $bookingForm['bima_amount'];
        } else {
            $bookingData['bima_amount'] = 0;
        }

        // Create booking
        try {
            $booking = Booking::create($bookingData);
        } catch (\Exception $e) {
            Log::error('Test mode: Failed to create booking', [
                'error' => $e->getMessage(),
                'data' => $bookingData,
            ]);
            return redirect()->route('home')->with('error', __('all.failed_create_booking_test_mode'));
        }

        if ($isResave) {
            session()->forget('booking_form');

            return redirect()->route('vender.resaved.tickets')->with(
                'success',
                __('all.ticket_reserved_success_24h')
            );
        }

        // Store booking in session for test payment controller
        Session::put('booking', $booking);

        // Clear booking form session
        session()->forget('booking_form');

        // Redirect to test payment processing
        return redirect()->route('test.payment.process');
    }

    private function generateRandomCode()
    {
        do {
            // Generate 2 random letters
            $letters = '';
            for ($i = 0; $i < 2; $i++) {
                $letters .= chr(rand(65, 90)); // A-Z
            }

            // Generate 8 random digits
            $numbers = '';
            for ($i = 0; $i < 8; $i++) {
                $numbers .= rand(0, 9);
            }

            // Combine with # prefix
            $code = $letters . $numbers;
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    public function bus_route()
    {
        $schedule = Schedule::with(['bus.campany', 'bus.route', 'route'])
            ->where('schedule_date', '>', Carbon::now()->format('Y-m-d'))
            ->orderBy('schedule_date', 'asc')
            ->orderBy('start', 'asc')
            ->get();

        // Booked seats per schedule for the seat-arrangement modal.
        $seatMaps = schedule_seat_maps($schedule);
        foreach ($schedule as $sched) {
            $sched->booked_seat_map = $seatMaps[$sched->id] ?? [];
        }

        return view('vender.bus_route', compact('schedule'));
    
    }

    public function transaction(Request $request)
    {
        $built = $this->buildVendorTransactionQuery($request);
        $period = $built['period'];
        $startDate = $built['startDate'];
        $endDate = $built['endDate'];
        $coll = $built['query']->orderByDesc('created_at')->get();

        // Calculate summary statistics
        $accept = Transaction::where('vender_id', auth()->user()->id)
            ->where('status', 'Completed')
            ->sum('amount');
        $pending = Transaction::where('vender_id', auth()->user()->id)
            ->where('status', 'Pending')
            ->sum('amount');
        $cancel = Transaction::where('vender_id', auth()->user()->id)
            ->where('status', 'Cancelled')
            ->sum('amount');

        return view('vender.transaction', compact('coll', 'accept', 'pending', 'cancel', 'period', 'startDate', 'endDate'));
    }

    public function transactionExportPdf(Request $request)
    {
        $built = $this->buildVendorTransactionQuery($request);
        $transactions = $built['query']->orderByDesc('created_at')->get();

        if ($transactions->isEmpty()) {
            return redirect()
                ->route('vender.transaction', $request->only(['period', 'start_date', 'end_date']))
                ->with('error', __('assistance/transaction.no_transactions_export'));
        }

        $rows = $transactions->map(fn (Transaction $transaction) => $this->mapVendorTransactionExportRow($transaction));

        $pdf = Pdf::loadView('print.vendor_transactions', [
            'title' => __('assistance/transaction.report_title'),
            'filterLabel' => $this->vendorTransactionFilterLabel($built['period'], $built['startDate'], $built['endDate']),
            'vendorName' => auth()->user()->name,
            'rows' => $rows->values()->all(),
            'totalAmount' => (float) $transactions->sum('amount'),
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('vendor_transactions_' . now()->format('Ymd_His') . '.pdf');
    }

    public function transactionExportCsv(Request $request)
    {
        $built = $this->buildVendorTransactionQuery($request);
        $transactions = $built['query']->orderByDesc('created_at')->get();

        if ($transactions->isEmpty()) {
            return redirect()
                ->route('vender.transaction', $request->only(['period', 'start_date', 'end_date']))
                ->with('error', __('assistance/transaction.no_transactions_export'));
        }

        $rows = $transactions->map(fn (Transaction $transaction) => $this->mapVendorTransactionExportRow($transaction));

        return $this->streamVendorTransactionCsv(
            'vendor_transactions_' . now()->format('Ymd_His') . '.csv',
            array_values($this->vendorTransactionExportHeaders()),
            $rows->map(fn (array $row) => array_values($row))
        );
    }

    private function buildVendorTransactionQuery(Request $request): array
    {
        $query = Transaction::where('vender_id', auth()->id())
            ->with(['user', 'user.VenderAccount']);

        $dateFilter = apply_booking_history_date_filter($query, $request);
        $period = $dateFilter['period'] ?? $request->get('period', 'today');
        $startDate = $dateFilter['startDate'];
        $endDate = $dateFilter['endDate'];

        if (! $request->has('period') && ! $request->filled('start_date') && ! $request->filled('end_date')) {
            $query->whereDate('created_at', today());
            $period = $period ?? 'today';
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $period = 'custom';
        }

        return [
            'query' => $query,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    private function vendorTransactionFilterLabel(?string $period, ?string $startDate = null, ?string $endDate = null): string
    {
        if ($period === 'custom' && $startDate && $endDate) {
            return $startDate . ' – ' . $endDate;
        }

        return match ($period) {
            'week' => __('assistance/transaction.this_week'),
            'month' => __('assistance/transaction.this_month'),
            'year' => __('assistance/transaction.this_year'),
            default => __('assistance/transaction.today'),
        };
    }

    private function mapVendorTransactionExportRow(Transaction $transaction): array
    {
        return [
            'vendor' => $transaction->user?->name ?? __('system.common.unknown'),
            'payment_method' => $transaction->payment_method ?? '—',
            'payment_details' => transaction_payment_detail($transaction),
            'date' => optional($transaction->created_at)->format('Y-m-d H:i') ?? '—',
            'amount' => number_format((float) $transaction->amount, 2),
            'reference_number' => $transaction->reference_number ?? '—',
            'status' => $transaction->status ?? '—',
        ];
    }

    private function vendorTransactionExportHeaders(): array
    {
        return [
            'vendor' => __('assistance/transaction.vender'),
            'payment_method' => __('assistance/transaction.payment_method'),
            'payment_details' => __('assistance/transaction.payment_details'),
            'date' => __('assistance/transaction.date'),
            'amount' => __('assistance/transaction.amount'),
            'reference_number' => __('assistance/transaction.reference_no'),
            'status' => __('assistance/transaction.status'),
        ];
    }

    private function streamVendorTransactionCsv(string $filename, array $headers, $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, array_values($row instanceof \Illuminate\Support\Collection ? $row->all() : (array) $row));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function transaction_request(Request $request)
    {
        $user = auth()->user();

        // Vendor chooses one payout channel: bank OR mobile money. When bank is
        // selected they enter the bank name + account number directly; for mobile
        // money they enter/confirm the payout number.
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'max:50'],
            'bank_name' => ['required_if:payment_method,bank', 'nullable', 'string', 'max:100'],
            'bank_number' => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
            'payment_number' => ['required_unless:payment_method,bank', 'nullable', 'string', 'max:50'],
        ]);

        // Check if the vendor commission balance is sufficient
        if ($request->amount > $user->VenderBalances->amount) {
            return back()->with('error', __('assistance/transaction.insufficient_balance'));
        }

        $paymentMethod = strtolower(trim((string) $request->payment_method));
        if ($paymentMethod === 'bank') {
            $bankName = trim((string) $request->bank_name);
            $bankNumber = trim((string) $request->bank_number);
            if ($bankNumber === '' || in_array(strtolower($bankNumber), ['n/a', 'haipatikani'], true)) {
                return back()->with('error', __('assistance/transaction.bank_account_required'))->withInput();
            }

            // Store the entered details on the vendor profile for reuse next time
            $this->storeVendorBankAccount($user, $bankName, $bankNumber);

            // Snapshot: store bank name + account so admin always sees bank details
            $paymentNumber = $bankName !== '' ? $bankName . ' — ' . $bankNumber : $bankNumber;
        } else {
            $paymentNumber = trim((string) ($request->payment_number ?? ''));
            if ($paymentNumber === '' || in_array(strtolower($paymentNumber), ['n/a', 'haipatikani'], true)) {
                return back()->with('error', __('assistance/transaction.payment_number_required'))->withInput();
            }

            // Save the mobile money number on the wallet for reuse next time
            if ($user->VenderBalances) {
                $user->VenderBalances->update(['payment_number' => $paymentNumber]);
            }
        }

        // Create the transaction
        try {
            $transaction = Transaction::create([
                'vender_id' => $user->id, // Update to company_id after migration
                'user_id' => $user->id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_number' => $paymentNumber,
                'status' => 'Pending',
            ]);

            return back()->with('success', __('assistance/transaction.transaction_request_sent'));
        } catch (\Exception $e) {
            Log::error('Vendor transaction request failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', __('assistance/transaction.transaction_request_failed'));
        }
    }

    /**
     * Persist the vendor's bank name + account number to their profile so the
     * fields are pre-filled next time and admins see current details.
     */
    private function storeVendorBankAccount($user, string $bankName, string $bankNumber): void
    {
        if ($user->VenderAccount) {
            $user->VenderAccount->update([
                'bank_name' => $bankName,
                'bank_number' => $bankNumber,
            ]);
        } else {
            $user->VenderAccount()->create([
                'user_id' => $user->id,
                'bank_name' => $bankName,
                'bank_number' => $bankNumber,
            ]);
        }
    }

    public function history(Request $request)
    {
        $built = $this->buildVendorBookingHistoryQuery($request);
        $bookings = $built['query']->where('payment_status', 'Paid')->latest()->paginate(20)->withQueryString();

        return view('vender.history', [
            'bookings' => $bookings,
            'period' => $built['period'],
            'startDate' => $built['startDate'],
            'endDate' => $built['endDate'],
        ]);
    }

    public function historyExportPdf(Request $request)
    {
        $built = $this->buildVendorBookingHistoryQuery($request);
        $bookings = $built['query']->where('payment_status', 'Paid')->latest()->get();

        if ($bookings->isEmpty()) {
            return redirect()
                ->route('vender.history', $request->only(['period', 'start_date', 'end_date']))
                ->with('error', __('vender/history.no_bookings_export'));
        }

        $rows = $bookings->map(fn (Booking $booking) => $this->mapVendorBookingHistoryExportRow($booking));

        $pdf = Pdf::loadView('print.vendor_booking_history', [
            'title' => __('vender/history.report_title'),
            'filterLabel' => $this->vendorHistoryFilterLabel($built['period'], $built['startDate'], $built['endDate']),
            'vendorName' => auth()->user()->name,
            'rows' => $rows->values()->all(),
            'totalAmount' => $rows->sum(fn (array $row) => (float) ($row['total'] ?? 0)),
            'bookingCount' => $rows->count(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('vendor_booking_history_' . now()->format('Ymd_His') . '.pdf');
    }

    public function historyExportCsv(Request $request)
    {
        $built = $this->buildVendorBookingHistoryQuery($request);
        $bookings = $built['query']->where('payment_status', 'Paid')->latest()->get();

        if ($bookings->isEmpty()) {
            return redirect()
                ->route('vender.history', $request->only(['period', 'start_date', 'end_date']))
                ->with('error', __('vender/history.no_bookings_export'));
        }

        $rows = $bookings->map(fn (Booking $booking) => $this->mapVendorBookingHistoryExportRow($booking));

        return $this->streamVendorTransactionCsv(
            'vendor_booking_history_' . now()->format('Ymd_His') . '.csv',
            array_values($this->vendorBookingHistoryExportHeaders()),
            $rows->map(fn (array $row) => collect($this->vendorBookingHistoryExportHeaders())->keys()->map(fn (string $key) => $row[$key] ?? '')->all())
        );
    }

    private function buildVendorBookingHistoryQuery(Request $request): array
    {
        $query = Booking::with(['campany', 'schedule', 'user', 'bus.route', 'vender', 'governmentLeviesOnService'])
            ->where('vender_id', auth()->id());

        $dateFilter = apply_booking_history_date_filter($query, $request);
        $period = $dateFilter['period'] ?? $request->get('period', 'today');
        $startDate = $dateFilter['startDate'];
        $endDate = $dateFilter['endDate'];

        if (! $request->has('period') && ! $request->filled('start_date') && ! $request->filled('end_date')) {
            $query->whereDate('created_at', today());
            $period = $period ?? 'today';
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $period = 'custom';
        }

        return [
            'query' => $query,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    private function vendorHistoryFilterLabel(?string $period, ?string $startDate = null, ?string $endDate = null): string
    {
        if ($period === 'custom' && $startDate && $endDate) {
            return $startDate . ' – ' . $endDate;
        }

        return match ($period) {
            'week' => __('assistance/dashboard.this_week'),
            'month' => __('assistance/dashboard.this_month'),
            'year' => __('assistance/dashboard.this_year'),
            default => __('assistance/dashboard.today'),
        };
    }

    private function mapVendorBookingHistoryExportRow(Booking $booking): array
    {
        $row = booking_to_report_row($booking);
        $payment = (float) ($booking->amount ?? 0) + (float) ($booking->vat ?? 0);

        return [
            'booking_code' => $row['booking_code'],
            'company_name' => $row['company_name'],
            'route' => $row['route_from'] . ' → ' . $row['route_to'],
            'bus_number' => $row['bus_number'],
            'travel_date' => $row['travel_date'],
            'seat' => $row['seat'],
            'pickup_drop' => ($row['pickup_point'] ?? '—') . ' → ' . ($row['dropping_point'] ?? '—'),
            'customer_name' => $row['customer_name'],
            'customer_phone' => $row['customer_phone'],
            'payment' => number_format($payment, 2),
            'commission' => $row['commision'], // fee + vender_fee (matches history page; VAT is separate column)
            'discount' => $row['manifest_discount'],
            'vat' => is_numeric($row['vat']) ? number_format((float) $row['vat'], 2) : (string) $row['vat'],
            'total' => $row['total'],
            'paid_at' => $row['issue_date'],
        ];
    }

    private function vendorBookingHistoryExportHeaders(): array
    {
        return [
            'booking_code' => __('vender/history.booking_id'),
            'company_name' => __('vender/history.company'),
            'route' => __('vender/history.bus_route'),
            'bus_number' => __('vender/history.bus_number'),
            'travel_date' => __('vender/history.travel_date'),
            'seat' => __('vender/history.seat_label'),
            'pickup_drop' => __('vender/history.pickup_drop'),
            'customer_name' => __('vender/history.passenger'),
            'customer_phone' => __('vender/history.phone'),
            'payment' => __('vender/history.seats_payment'),
            'commission' => __('vender/history.commission'),
            'discount' => __('vender/history.discount_label'),
            'vat' => __('vender/history.vat_label'),
            'total' => __('vender/history.total'),
            'paid_at' => __('vender/history.paid_time'),
        ];
    }

    public function resavedTickets()
    {
        $bookings = Booking::with(['campany', 'schedule', 'bus.busname', 'bus.route'])
            ->where('vender_id', auth()->id())
            ->where('payment_status', 'resaved')
            ->latest()
            ->get();

        $ticketRows = collect(group_ticket_list_rows($bookings));
        $page = max(1, (int) request()->get('page', 1));
        $perPage = 20;

        $bookings = new \Illuminate\Pagination\LengthAwarePaginator(
            $ticketRows->forPage($page, $perPage)->values(),
            $ticketRows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('vender.resaved_tickets', compact('bookings'));
    }

    public function cancelResavedTicket($id)
    {
        $booking = Booking::where('id', $id)
            ->where('vender_id', auth()->id())
            ->where('payment_status', 'resaved')
            ->firstOrFail();

        $booking->update(['payment_status' => 'Cancel']);

        $partner = round_trip_resaved_partner($booking);
        if ($partner !== null && ($partner->payment_status ?? '') === 'resaved') {
            $partner->update(['payment_status' => 'Cancel']);
        }

        return redirect()->route('vender.resaved.tickets')
            ->with('success', __('vender/resaved_tickets.cancelled_success'));
    }

    public function payResavedTicket($id)
    {
        $booking = Booking::with(['route_name', 'bus.busname', 'schedule', 'campany'])
            ->where('id', $id)
            ->where('vender_id', auth()->id())
            ->where('payment_status', 'resaved')
            ->firstOrFail();

        $partner = round_trip_resaved_partner($booking);
        if ($partner !== null && ($partner->payment_status ?? '') === 'resaved') {
            $legs = sort_round_trip_resaved_legs([$booking, $partner]);
            app(RoundTripController::class)->loadResavedRoundTripCheckout($legs[0], $legs[1]);

            return redirect()->to(round_trip_route('checkout'));
        }

        $setting = Setting::first();
        $formulaService = app(FareFormulaService::class);
        $seatCount = max(1, count(array_filter(array_map('trim', explode(',', (string) $booking->seat)))));
        $price = (float) $booking->amount;
        $fees = $formulaService->calculateTravellerServiceFee(
            (float) ($booking->busFee ?: $booking->amount),
            $setting,
            $seatCount
        );
        $dis = $booking->discount_amount ?? 0;
        $ins = $booking->bima_amount ?? 0;
        $test_mode = (bool) ($setting->test_mode ?? false);

        return view('vender.pay_resaved', compact('booking', 'price', 'fees', 'dis', 'ins', 'test_mode'));
    }

    public function editResavedTicket($id)
    {
        $booking = Booking::where('id', $id)
            ->where('vender_id', auth()->id())
            ->where('payment_status', 'resaved')
            ->firstOrFail();

        return view('vender.edit_resaved', compact('booking'));
    }

    public function updateResavedTicket(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('vender_id', auth()->id())
            ->where('payment_status', 'resaved')
            ->firstOrFail();

        $booking->update([
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'customer_phone' => normalize_tanzania_phone_for_booking((string) $request->phone),
        ]);

        return redirect()->route('vender.resaved.tickets')
            ->with('success', __('vender/resaved_tickets.updated_success'));
    }


    public function print(Request $request)
    {
        $venderId = Auth::id();
        $data = null;

        if ($request->filled('booking_ids')) {
            $ids = is_array($request->booking_ids) ? $request->booking_ids : (array) json_decode($request->booking_ids, true);
            $ids = array_filter(array_map('intval', $ids));
            if (empty($ids)) {
                return redirect()->back()->with('error', __('vender/history.no_booking_data_income'));
            }
            $bookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService'])
                ->where('vender_id', $venderId)
                ->whereIn('id', $ids)
                ->where('payment_status', 'Paid')
                ->get();
            $data = $this->bookingsToReportArray($bookings);
        } else {
            $raw = $request->data;
            if (empty($raw)) {
                return redirect()->back()->with('error', __('vender/history.no_data_income'));
            }
            $data = json_decode($raw, true);
            if ($data === null || !is_array($data)) {
                return redirect()->back()->with('error', __('vender/history.invalid_data_format'));
            }
        }

        if (empty($data)) {
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
        //return $data;
        $pdf = Pdf::loadView('print.report', ['bookings' => $data]);

        return $pdf->download('income-' . now() . '.pdf');
    }

    public function manifest(Request $request)
    {
        $venderId = Auth::id();
        $data = null;

        if ($request->filled('booking_ids')) {
            $ids = is_array($request->booking_ids) ? $request->booking_ids : (array) json_decode($request->booking_ids, true);
            $ids = array_filter(array_map('intval', $ids));
            if (empty($ids)) {
                return redirect()->back()->with('error', __('vender/history.no_booking_data_manifest'));
            }
            $bookings = Booking::with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService', 'vender'])
                ->where('vender_id', $venderId)
                ->whereIn('id', $ids)
                ->where('payment_status', 'Paid')
                ->orderBy('seat')
                ->get();
            $data = $this->bookingsToReportArray($bookings);
        } else {
            $raw = $request->data;
            if (empty($raw)) {
                return redirect()->back()->with('error', __('vender/history.no_data_manifest'));
            }
            $data = json_decode($raw, true);
            if ($data === null || !is_array($data)) {
                return redirect()->back()->with('error', __('vender/history.invalid_data_format'));
            }
        }

        if (empty($data) || !isset($data[0])) {
            return redirect()->back()->with('error', __('vender/history.no_booking_data_manifest'));
        }

        if (!isset($data[0]['bus_number']) || empty(trim($data[0]['bus_number'] ?? ''))) {
            return redirect()->back()->with('error', __('vender/history.bus_number_not_found'));
        }

        $bus = bus::where('bus_number', $data[0]['bus_number'])->first();

        if (!$bus) {
            return redirect()->back()->with('error', __('vender/history.bus_not_found_number', ['number' => $data[0]['bus_number']]));
        }

        $pdf = Pdf::loadView('print.manifest', ['bookings' => $data, 'bus' => $bus]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('manifest-' . now() . '.pdf');
    }

    public function profile()
    {
        return view('vender.profile');
    }

    public function update_profile(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
            'contact' => ['nullable', 'string', 'max:20'],
            'tin' => ['nullable', 'string', 'max:50'],
            'house_number' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'altenative_number' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'work' => ['nullable', 'string', 'max:100'],
            'bank_number' => ['nullable', 'string', 'max:50'],
            'payment_number' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            // Get the authenticated user
            $user = Auth::user();

            // Update user fields
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->contact = $validated['contact'];

            // Update password only if provided
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }

            // Save user
            $user->save();

            // Update or create VenderAccount
            if ($user->VenderAccount) {
                $user->VenderAccount->update([
                    'tin' => $validated['tin'] ?? $user->VenderAccount->tin,
                    'house_number' => $validated['house_number'],
                    'street' => $validated['street'],
                    'town' => $validated['town'],
                    'city' => $validated['city'],
                    'province' => $validated['province'],
                    'country' => $validated['country'],
                    'altenative_number' => $validated['altenative_number'],
                    'bank_name' => $validated['bank_name'] ?? $user->VenderAccount->bank_name,
                    'bank_number' => $validated['bank_number'] ?? $user->VenderAccount->bank_number,
                ]);
            } else {
                $user->VenderAccount()->create([
                    'tin' => $validated['tin'],
                    'house_number' => $validated['house_number'],
                    'street' => $validated['street'],
                    'town' => $validated['town'],
                    'city' => $validated['city'],
                    'province' => $validated['province'],
                    'country' => $validated['country'],
                    'altenative_number' => $validated['altenative_number'],
                    'bank_name' => $validated['bank_name'],
                    'work' => $validated['work'],
                    'bank_number' => $validated['bank_number'],
                    'user_id' => $user->id,
                ]);
            }

            // Update or create VenderBalance
            if ($user->VenderBalances) {
                $user->VenderBalances->update([
                    'payment_number' => $validated['payment_number'],
                ]);
            } else {
                $row = [
                    'payment_number' => $validated['payment_number'],
                    'user_id' => $user->id,
                    'amount' => 0,
                    'fees' => 0,
                ];
                if (\Illuminate\Support\Facades\Schema::hasColumn('vender_balances', 'sell_cash_amount')) {
                    $row['sell_cash_amount'] = 0;
                }
                $user->VenderBalances()->create($row);
            }

            return back()->with('success', __('vender/profile.profile_updated_success'));
        } catch (\Exception $e) {
            return back()->with('error', __('vender/profile.profile_update_failed', ['error' => $e->getMessage()]))->withInput();
        }
    }

    public function sendEmail(Request $request)
    {
        $data = [
            'name' => $request->input('name', 'User'),
            'email' => $request->input('email', 'user@example.com')
        ];

        // Check if email notifications are enabled
        $settings = Setting::first();
        $sendEmail = $settings ? (bool) ($settings->enable_customer_email_notifications ?? true) : true;

        if (!$sendEmail) {
            return response()->json(['message' => 'Email notifications are disabled'], 200);
        }

        if (empty($data['email'])) {
            return response()->json(['message' => 'Email address is required'], 400);
        }

        try {
            Mail::to($data['email'])->send(new SendEmail($data));
            return response()->json(['message' => 'Email sent successfully']);
        } catch (\Exception $e) {
            Log::error("Failed to send email: " . $e->getMessage(), [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to send email. Please try again later.'], 500);
        }
    }

    private function applyDiscount($amount)
    {
        $coupon = session()->get('booking_form')['discount'] ?? '';
        if (empty($coupon)) {
            return session()->get('booking_form')['total_amount'];
        }
        $discount = Discount::where('code', $coupon)->first();
        if (is_null($discount) || !$discount->isValid()) {
            return session()->get('booking_form')['total_amount'];
        }
        $bus_info = session()->get('booking_form', []);
        $base = isset($bus_info['total_amount_before_coupon']) && (float) $bus_info['total_amount_before_coupon'] > 0
            ? (float) $bus_info['total_amount_before_coupon']
            : (float) $amount;
        if (!isset($bus_info['total_amount_before_coupon']) || (float) $bus_info['total_amount_before_coupon'] <= 0) {
            $bus_info['total_amount_before_coupon'] = $base;
        }
        $new = $base * (1 - $discount->percentage / 100);
        $bus_info['total_amount'] = $new;
        session()->put('booking_form', $bus_info);
        return $new;
    }
}
