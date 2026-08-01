<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ClickPesaController;
use App\Http\Controllers\PDOController;
use App\Http\Controllers\TigosecureController;
use App\Http\Controllers\CashController; // Added this line
use App\Http\Controllers\BookingController;
use App\Models\Booking;
use App\Models\bus;
use App\Models\City;
use App\Models\Discount;
use App\Models\TempWallet;
use App\Models\route;
use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Services\FareFormulaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    const EXCESS_LUGGAGE_FEE = 2500; // TSh. 2,500 for excess luggage

    public function index()
    {
        return $this->dashboard();
    }

    public function dashboard()
    {
        // Fetch booking counts
        $paidCount = Booking::where('user_id', Auth::user()->id)
            ->where('payment_status', 'Paid')
            ->count();

        $failedCount = Booking::where('user_id', Auth::user()->id)
            ->where('payment_status', 'Fail')
            ->count();

        $unpaidCount = Booking::where('user_id', Auth::user()->id)
            ->where('payment_status', 'Unpaid')
            ->count();

        $cancelledCount = Booking::where('user_id', Auth::user()->id)
            ->where('payment_status', 'Cancel')
            ->count();

        return view('customer.index', compact('paidCount', 'failedCount', 'unpaidCount', 'cancelledCount'));
    }

    

    public function mybooking(Request $request)
    {
        $user = Auth::user();
        $this->claimGuestBookingsForCustomer($user);
        $query = $this->customerBookingQuery($user);

        $period = $request->query('period', '');
        $startDate = $request->query('start_date', '');
        $endDate = $request->query('end_date', '');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            apply_booking_history_date_filter($query, $request);
            $period = 'custom';
        } elseif ($period) {
            apply_booking_history_date_filter($query, $request);
        }

        $booking = $query->latest()->get();
        $ticketRows = group_ticket_list_rows($booking);

        return view('customer.mybooking', compact('booking', 'ticketRows', 'period', 'startDate', 'endDate'));
    }

    public function printReport(Request $request)
    {
        $user = Auth::user();
        $this->claimGuestBookingsForCustomer($user);
        $query = $this->customerBookingQuery($user)
            ->with(['campany', 'schedule', 'bus.route', 'governmentLeviesOnService']);

        if ($request->filled('booking_ids')) {
            $ids = is_array($request->booking_ids)
                ? $request->booking_ids
                : (array) json_decode($request->booking_ids, true);
            $ids = array_filter(array_map('intval', $ids));
            if (empty($ids)) {
                return redirect()->back()->with('error', __('customer/myticket.no_booking_found'));
            }
            $query->whereIn('id', $ids);
        } elseif ($request->filled('period') || ($request->filled('start_date') && $request->filled('end_date'))) {
            apply_booking_history_date_filter($query, $request);
        }

        $bookings = $query->latest()->get();

        if ($bookings->isEmpty()) {
            return redirect()->back()->with('error', __('customer/myticket.no_booking_found'));
        }

        $data = $bookings->map(fn ($booking) => booking_to_report_row($booking))->all();

        $pdf = Pdf::loadView('print.customer_tickets', [
            'bookings' => $data,
            'customerName' => trim((string) ($user->name ?? $user->fname ?? '')) ?: 'Customer',
            'generatedAt' => now(),
        ]);

        return $pdf->download('my-tickets-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Attach unowned guest bookings that match this customer's contact details.
     * Never claim bookings already owned by another user or created by a vendor.
     */
    private function claimGuestBookingsForCustomer($user): void
    {
        $email = trim((string) ($user->email ?? ''));
        $phone = $user->contact ?? $user->phone ?? null;
        $normalizedPhone = !empty($phone)
            ? normalize_tanzania_phone_for_booking((string) $phone)
            : '';

        if ($email === '' && $normalizedPhone === '') {
            return;
        }

        Booking::query()
            ->where(function ($q) {
                $q->whereNull('user_id')->orWhere('user_id', 0);
            })
            ->where(function ($q) {
                $q->whereNull('vender_id')
                    ->orWhere('vender_id', '')
                    ->orWhere('vender_id', 0);
            })
            ->where(function ($q) use ($email, $normalizedPhone) {
                if ($email !== '') {
                    $q->orWhere('customer_email', $email);
                }
                if ($normalizedPhone !== '') {
                    $q->orWhere('customer_phone', $normalizedPhone);
                }
            })
            ->update(['user_id' => $user->id]);
    }

    private function customerBookingQuery($user)
    {
        // Only tickets owned by this account — never match other users by shared email/phone.
        return Booking::with('bus.route', 'vender', 'campany.busOwnerAccount')
            ->where('user_id', $user->id);
    }

    private function findOwnedBookingOrFail($id): Booking
    {
        return Booking::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    public function mybooking_search(Request $request)
    {
        if ($request->filled('departure_city') && $request->filled('arrival_city')) {
            return $this->by_route_search($request);
        }

        return view('customer.by_route');
    }

    public function by_route()
    {
        return view('customer.by_route');
    }

    public function by_route_search(Request $request)
    {
        $request->attributes->set('_booking_view', 'customer.by_route_search');

        return app(BookingController::class)->by_route_search($request);
    }

    public function booking_form($id, $from, $to)
    {

        $car = Bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($from, $to) {
                $query->where('from', $from)->where('to', $to);
            },
            'route.points'
        ])->find($id);

        if ($car === null || $car->route === null || $car->schedule === null) {
            return redirect()->route('customer.mybooking.search')->with(
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

        //$filteredPoints = $car->route->points;

        if ($car->route->from == $car->schedule->from) {
            $filteredPoints = $car->route->points->filter(function ($point) {
                return $point->state === 'no';
            });
        } else {
            $filteredPoints = $car->route->points->filter(function ($point) {
                return $point->state === 'yes';
            });
        }

        // Add filtered points as a new attribute to the car object
        $car->filtered_points = $filteredPoints;
        apply_booking_filtered_points($car);

        return view('customer.bookingform', compact('car'));
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
            'route_id' => $request->route_id,
            'pickup_point' => $request->pickup_point ?? ($schedule ? $schedule->from : $route->from),
            'dropping_point' => $request->dropping_point ?? ($schedule ? $schedule->to : $route->to),
            'travel_date' => session()->get('departure_date') ?? now()->format('Y-m-d'),
            'dropping_point_amount' => $request->dropping_point_amount ?? ($route ? $route->price : 0),
            'route_distance' => $request->route_distance ?? 0,
            'schedule_id' => $request->schedule_id,
        ];


        // Store in session
        session()->put('booking_form', $bus_info);
        //return session()->get('booking_form');
        // Redirect to seats route
        return redirect()->route('customer.seats');
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

        return view('customer.seats', compact('price', 'booked_seats', 'car'));

        //return  $car;
    }

    public function get_seats(Request $request)
    {
        $seats = $request->selected_seats;
        if ((auth()->user()->temp_wallets->amount ?? 0) > 0) {
            $price  = $request->total_amount - auth()->user()->temp_wallets->amount;
            $bus_info['cancel_key'] = auth()->user()->temp_wallets->user_key ?? '';
            $bus_info['cancel_amount'] = auth()->user()->temp_wallets->amount ?? 0;
        } else {
            $price = $request->total_amount;
        }

        $bus_info = session()->get('booking_form', []);
        if (empty($bus_info['bus_id']) || empty($bus_info['travel_date'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }

        $selected = is_array($seats) ? $seats : (is_string($seats) ? array_map('trim', explode(',', $seats)) : []);
        $selected = array_filter($selected);

        if (empty($selected)) {
            return redirect()->route('customer.seats')->with('error', __('all.select_at_least_one_seat'));
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
            return redirect()->route('customer.seats')->with('error', __('all.seats_no_longer_available_named', [
                'seats' => implode(', ', array_slice($alreadyBooked, 0, 3)),
            ]));
        }

        $bus_info['seats'] = $seats;

        $bus_info['total_amount'] = $price;
        $bus_info['total_amount_before_coupon'] = $price;

        session()->put('booking_form', $bus_info);

        if (session('rebook') !== null) {
            $rebook = Booking::find(session('rebook')->id);
            //return $rebook;
            if ($rebook->busFee < $price) {
                return redirect()->route('customer.seats')->with('error', __('all.rebooking_amount_for_seat', [
                    'amount' => convert_money($rebook->busFee),
                    'currency' => app('currency'),
                ]));
            } else {
                $new = new RebookController();
                return $new->rebook_data(session()->get('booking_form'));
            }
        }

        if ($seats == null || $seats == []) {
            return back()->with('error', __('all.seats_not_selected'));
        }

        return redirect()->route('customer.pay');
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
        return view('customer.payment', compact('price', 'seats', 'info', 'car', 'time', 'date', 'fees', 'distance'));
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


    public function payment_info(Request $request)
    {
        //return $request->all();
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }

        $bus_info = session()->get('booking_form', []);
        $bus_info['customer_name'] = $request->customer;
        $bus_info['gender'] = $request->gender;
        $bus_info['age'] = $request->age;
        $bus_info['infant_child'] = $request->infant_child ?? 0;
        $bus_info['age_group'] = $request->age_group;
        $bus_info['category'] = $request->category;
        $bus_info['start'] = session()->get('time')['start'];
        $bus_info['end'] = session()->get('time')['end'];
        $bus_info['discount'] = $request->discount ?? '';
        $bus_info['cancel_amount'] = $request->amount_cancel ?? ($bus_info['cancel_amount'] ?? 0);
        $bus_info['cancel_key'] = $request->key ?? ($bus_info['cancel_key'] ?? '');
        $bus_info['excess_luggage'] = $request->excess_luggage ?? 0; // Add excess luggage checkbox value
        $bus_info['excess_luggage_description'] = $request->excess_luggage_description ?? null; // Add excess luggage description
        $bus_info['estimated_weight'] = $request->estimated_weight ?? null; // Customer-declared weight, for the excess luggage receipt
        session()->put('booking_form', $bus_info);

        $insuranceError = process_booking_insurance_input($request, $bus_info);
        session()->put('booking_form', $bus_info);
        if ($insuranceError) {
            return redirect()->route('customer.pay')->with('error', __($insuranceError));
        }

        if (!empty($bus_info['discount'])) {
            $couponCheck = Discount::where('code', $bus_info['discount'])->first();
            if (!$couponCheck) {
                return redirect()->route('customer.pay')->with('error', __('all.invalid_coupon_code'));
            }
            if (!$couponCheck->isValid()) {
                return redirect()->route('customer.pay')->with('error', __('all.coupon_expired_or_limit'));
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
            $price = $discountedFare + $ins + $excessLuggageFee;
            $dis = $base - $discountedFare;

            $bus_info = session()->get('booking_form', []);
            $bus_info['dispo'] = $discountedFare;
            session()->put('booking_form', $bus_info);
        } else {
            $price = $total_amount + $ins + $excessLuggageFee;
        }

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

        return view('customer.payment_details', compact('price', 'ins', 'fees', 'dis', 'excess_luggage_fee', 'test_mode'));
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

    public function get_payment(Request $request)
    {
        $request->validate([
            'contactNumber' => ['required', 'string'],
            'contactEmail' => ['nullable', 'email'],
        ]);
        //return $request->all();


        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->route('home')->with('error', __('all.session_expired_try_again'));
        }
        $bus_info = session()->get('booking_form', []);

        $contactNumber = normalize_tanzania_phone_for_booking((string) $request->contactNumber);
        $paymentRaw = trim((string) ($request->payment_contact ?? ''));
        $paymentContact = $paymentRaw !== '' ? normalize_tanzania_phone_for_booking($paymentRaw) : '';
        $bus_info['customer_number'] = $contactNumber;
        $bus_info['customer_payment_number'] = $paymentContact !== '' ? $paymentContact : $contactNumber;

        $bus_info['customer_email'] = $request->contactEmail ?: (auth()->user()->email ?? '');
        $bus_info['countrycode'] = $request->countrycode ?? '';

        $user = $request->user_id ?? "";

        $payment_method =  $request->payment_method;

        session()->put('booking_form', $bus_info);

        $isResave = $request->has('resave_ticket') && $request->input('resave_ticket') == '1';

        $canonicalAmount = session()->get('booking_form')['payable_amount'] ?? $request->amount;
        return $this->pay($canonicalAmount, $user, $payment_method, $isResave);

        //return $request->all();
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
        if ($method === 'wallet') {
            if (!auth()->check() || !auth()->user()->isCustomer()) {
                return redirect()->back()->with('error', __('all.payment_not_allowed') ?? 'Wallet payment is available for customers only.');
            }
            $walletBalance = (float) (auth()->user()->temp_wallets->amount ?? 0);
            if ($walletBalance + 0.0001 < (float) $amount) {
                return redirect()->back()->with('error', __('system/messages.insufficient_balance') ?? 'Insufficient wallet balance.');
            }
        }
        $bookingForm = session()->get('booking_form');
        $bima = session()->get('booking_form')['bima'];
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
            'bus_id' => session()->get('booking_form')['bus_id'],
            'route_id' => session()->get('booking_form')['route_id'],
            'pickup_point' => session()->get('booking_form')['pickup_point'],
            'dropping_point' => session()->get('booking_form')['dropping_point'],
            'travel_date' => session()->get('booking_form')['travel_date'],
            'seat' => session()->get('booking_form')['seats'],
            'amount' => round($amount),
            'gender' => session()->get('booking_form')['gender'],
            'age' => session()->get('booking_form')['age'],
            'infant_child' => session()->get('booking_form')['infant_child'],
            'age_group' => session()->get('booking_form')['age_group'],
            'payment_status' => $isResave ? 'resaved' : 'Unpaid', // Set initial status to Unpaid or resaved
            'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
            'customer_phone' => session()->get('booking_form')['customer_number'],
            'customer_name' => session()->get('booking_form')['customer_name'],
            'passengers' => booking_passengers_for_storage(session()->get('booking_form', [])),
            'customer_email' => session()->get('booking_form')['customer_email'],
            'user_id' => auth()->user()->id,
            'bima' => session()->get('booking_form')['bima'],
            'insuranceDate' => session()->get('booking_form')['insuranceDate'],
            'vender_id' => $pop,
            'discount' => session()->get('booking_form')['discount'],
            'discount_amount' => session()->get('booking_form')['discount_amount'],
            'distance' => session()->get('booking_form')['route_distance'],
            'busFee' => session()->get('booking_form')['dispo'] ?? session()->get('booking_form')['total_amount'],
            'schedule_id' => session()->get('booking_form')['schedule_id'],
            'excess_luggage' => session()->get('booking_form')['excess_luggage'], // Add excess luggage
            'has_excess_luggage' => session()->get('booking_form')['excess_luggage'] ?? 0, // Canonical DB flag
            'excess_luggage_fee' => session()->get('booking_form')['excess_luggage_fee'] ?? 0, // Keep luggage out of the service fee
            'excess_luggage_description' => session()->get('booking_form')['excess_luggage_description'], // Add excess luggage description
            'estimated_weight' => session()->get('booking_form')['estimated_weight'] ?? null,
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
            Log::channel('tigo')->error('Failed to create booking', [
                'error' => $e->getMessage(),
                'data' => $bookingData,
            ]);
            return response()->json(['status' => 'error', 'message' => __('all.failed_create_booking')], 500);
        }

        if ($isResave) {
            session()->forget('booking_form');
            return redirect()->route('customer.mybooking')->with('success', __('all.ticket_resaved_success_24h'));
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
                // ClickPesa charges the mobile-money number the customer entered for payment.
                // Validate/normalize it up-front so we return a friendly error instead of a rejected push.
                $clickpesaPhone = session()->get('booking_form')['customer_payment_number']
                    ?? session()->get('booking_form')['customer_number'];
                $normalized = ClickPesaController::normalizeTanzaniaMsisdnForClickPesa((string) $clickpesaPhone);
                if (!$normalized['ok']) {
                    return redirect()->back()
                        ->with('error', __('all.clickpesa_payment_failed', ['error' => $normalized['error'] ?? __('all.invalid_mobile_money_number')]))
                        ->withErrors(['payment_error' => $normalized['error'] ?? __('all.invalid_mobile_money_number')]);
                }

                $clickpesa = new ClickPesaController();
                Session::put('booking', $booking);
                return $clickpesa->initiatePayment(
                    round($amount),
                    session()->get('booking_form')['customer_name'],
                    session()->get('booking_form')['customer_name'],
                    $normalized['phone'],
                    session()->get('booking_form')['customer_email'],
                    $xcode
                );
            } catch (\Throwable $e) {
                Log::error('Customer ClickPesa payment failed: ' . $e->getMessage(), [
                    'booking_id' => $booking->id ?? null,
                    'trace' => $e->getTraceAsString(),
                ]);
                $msg = strlen($e->getMessage()) > 200 ? substr($e->getMessage(), 0, 200) . '…' : $e->getMessage();
                return redirect()->back()->with('error', __('all.clickpesa_error_prefix', ['error' => $msg]))->withErrors(['payment_error' => $msg]);
            }
        } elseif ($method == 'wallet') {
            return $this->processWalletPayment($booking, (float) $amount);
        }

        return redirect()->back()->with('error', __('customer/busroot.payment_error') ?? 'Payment method not supported.');
    }

    private function processWalletPayment(Booking $booking, float $amount)
    {
        if (!auth()->check()) {
            return redirect()->back()->with('error', __('all.session_expired_try_again'));
        }

        $payableAmount = max(0, round($amount, 2));

        DB::beginTransaction();
        try {
            $booking = Booking::lockForUpdate()->find($booking->id);
            if (!$booking || !in_array($booking->payment_status, ['Unpaid', 'resaved'], true)) {
                DB::rollBack();
                return redirect()->back()->with('error', __('customer/busroot.payment_error') ?? 'Payment cannot be processed.');
            }

            $wallet = TempWallet::where('user_id', auth()->id())->lockForUpdate()->first();
            $walletBalance = (float) ($wallet->amount ?? 0);
            if ($walletBalance + 0.0001 < $payableAmount) {
                DB::rollBack();
                return redirect()->back()->with('error', __('system/messages.insufficient_balance') ?? 'Insufficient wallet balance.');
            }

            $settlementService = app(\App\Services\BookingSettlementService::class);
            $settled = $settlementService->settlePaidBooking($booking, [
                'trans_status' => 'success',
                'trans_token' => 'WALLET-' . strtoupper(uniqid()),
                'payment_method' => 'wallet',
                'cancel_amount' => Session::get('cancel', 0),
                'skip_cancel_wallet_consumption' => true,
            ]);
            $booking = $settled['booking'];

            $wallet->update([
                'amount' => max(0, $walletBalance - $payableAmount),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Customer wallet payment failed', [
                'booking_id' => $booking->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', __('all.payment_initiation_failed'));
        }

        try {
            $tra = new \App\Services\TraVfdService();
            $tra->fiscalize($booking->refresh());
        } catch (\Throwable $e) {
            Log::error('Customer wallet TRA fiscalization failed: ' . $e->getMessage(), [
                'booking_id' => $booking->id ?? null,
            ]);
        }

        Session::forget('booking');
        Session::forget('booking_form');
        Session::forget('cancel');

        $keyController = new FunctionsController();
        $keyController->delete_key($booking);

        $redirectController = new RedirectController();
        return $redirectController->_redirect($booking->id);
    }

    /**
     * Process payment in test mode - bypasses real payment gateways
     *
     * @param float $amount
     * @param string $user
     * @param string $method
     * @param bool $isResave
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
            'gender' => $bookingForm['gender'],
            'age' => $bookingForm['age'],
            'infant_child' => $bookingForm['infant_child'],
            'age_group' => $bookingForm['age_group'],
            'payment_status' => $isResave ? 'resaved' : 'Unpaid',
            'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
            'customer_phone' => $bookingForm['customer_number'],
            'customer_name' => $bookingForm['customer_name'],
            'passengers' => booking_passengers_for_storage($bookingForm),
            'customer_email' => $bookingForm['customer_email'],
            'user_id' => auth()->id(),
            'bima' => $bookingForm['bima'],
            'insuranceDate' => $bookingForm['insuranceDate'],
            'vender_id' => '',
            'discount' => $bookingForm['discount'],
            'discount_amount' => $bookingForm['discount_amount'],
            'distance' => $bookingForm['route_distance'],
            'busFee' => $bookingForm['dispo'] ?? $bookingForm['total_amount'],
            'schedule_id' => $bookingForm['schedule_id'],
            'cancel_key' => $bookingForm['cancel_key'] ?? null,
            'excess_luggage' => $bookingForm['excess_luggage'],
            'has_excess_luggage' => $bookingForm['excess_luggage'] ?? 0, // Canonical DB flag
            'excess_luggage_fee' => $bookingForm['excess_luggage_fee'] ?? 0, // Keep luggage out of the service fee
            'excess_luggage_description' => $bookingForm['excess_luggage_description'],
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

            return redirect()->route('customer.mybooking')->with(
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

    public function update_profile(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
            'contact' => ['nullable', 'string', 'max:20'],
            'payment_number' => ['nullable', 'string', 'max:50'], // Adjust max length as needed
            'password' => ['nullable', 'string', 'min:8'], // Requires password_confirmation field
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


            return back()->with('success', __('customer/profile.profile_updated_success'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('customer/profile.profile_update_failed', ['error' => $e->getMessage()])])->withInput();
        }
    }

    public function edit($id)
    {
        $booking = $this->findOwnedBookingOrFail($id);
        return view('customer.edit', compact('booking'));
    }

    public function update(Request $request)
    {
        $booking = $this->findOwnedBookingOrFail($request->booking_id);
        $booking->update([
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'customer_phone' => $request->phone,
        ]);

        return redirect()->back()->with('success', __('all.payment_details_updated_success'));
    }

    public function cancelResavedTicket($id)
    {
        $booking = Booking::where('id', $id)
                          ->where('user_id', Auth::id())
                          ->where('payment_status', 'resaved')
                          ->firstOrFail();

        $booking->update(['payment_status' => 'Cancel']);

        $partner = round_trip_resaved_partner($booking);
        if ($partner !== null && ($partner->payment_status ?? '') === 'resaved') {
            $partner->update(['payment_status' => 'Cancel']);
        }

        return redirect()->route('customer.mybooking')->with('success', __('all.resaved_ticket_cancelled_success'));
    }

    public function payResavedTicket($id)
    {
        $booking = Booking::with(['route_name', 'bus.busname', 'schedule', 'campany'])
                          ->where('id', $id)
                          ->where('user_id', Auth::id())
                          ->where('payment_status', 'resaved')
                          ->firstOrFail();

        $partner = round_trip_resaved_partner($booking);
        if ($partner !== null && ($partner->payment_status ?? '') === 'resaved') {
            $legs = sort_round_trip_resaved_legs([$booking, $partner]);
            app(RoundTripController::class)->loadResavedRoundTripCheckout($legs[0], $legs[1]);

            return redirect()->to(round_trip_route('checkout'));
        }

        $setting = Setting::first();
        $price = $booking->amount; // Use the amount from the resaved booking
        $fees = $setting->service + ($setting->service_percentage / 100 * ($booking->amount * 100 / 118));
        $dis = $booking->discount_amount ?? 0;
        $ins = $booking->bima_amount ?? 0;

        // We need to set up a session for the payment process, similar to the initial booking flow.
        // This is a simplified version, as the full booking_form session might not be available.
        // For now, we'll pass direct values to the view.
        // If the payment gateway requires a full booking_form session, we might need to reconstruct it.

        $test_mode = (bool) ($setting->test_mode ?? false);

        return view('customer.pay_resaved', compact('booking', 'price', 'fees', 'dis', 'ins', 'test_mode'));
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
