<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BimaController;
use App\Http\Controllers\PercentController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\status\Fees;
use App\Http\Controllers\status\Vender;
use App\Http\Controllers\TigosecureController;
use App\Mail\SendEmail;
use App\Models\AdminWallet;
use App\Models\Bima;
use App\Models\Booking;
use App\Models\bus;
use App\Models\Campany;
use App\Models\City;
use App\Models\Discount;
use App\Models\PaymentFees;
use App\Models\route;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\SystemBalance;
use App\Models\TempWallet;
use App\Models\User;
use App\Models\VenderBalance;
use App\Services\BookingSettlementService;
use App\Services\FareFormulaService;
use App\Services\RouteDistanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Milon\Barcode\DNS2D;

class BookingController extends Controller
{
    public function booking_info(Request $request)
    {
        $request->validate([
            'data' => 'required|string|min:3',
        ]);

        $data = trim((string) $request->data);

        if (filter_var($data, FILTER_VALIDATE_EMAIL)) {
            $email = strtolower($data);
            $bookingExists = Booking::whereRaw('LOWER(customer_email) = ?', [$email])->exists();

            if ($bookingExists) {
                $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = now()->addMinutes(15);

                $request->session()->put('booking_verification_email', $email);
                $request->session()->put('booking_verification_code', $verificationCode);
                $request->session()->put('booking_verification_expires_at', $expiresAt);

                $tempUser = (object) ['email' => $email, 'name' => 'Customer'];
                $verificationCodeForEmail = $verificationCode;
                dispatch(function () use ($email, $tempUser, $verificationCodeForEmail) {
                    try {
                        Mail::to($email)->send(new \App\Mail\EmailVerification($tempUser, $verificationCodeForEmail));
                    } catch (\Exception $e) {
                        Log::error('Failed to send verification email: ' . $e->getMessage());
                    }
                })->afterResponse();

                return redirect()->route('booking.verification.show')
                    ->with('email', $email)
                    ->with('status', __('all.booking_verification_sent'));
            }

            return redirect()->route('info')
                ->withErrors(['data' => __('all.no_bookings_found_for_contact')])
                ->withInput();
        }

        $bookings = $this->findGuestBookingsByPhone($data);

        if ($bookings->isEmpty()) {
            return redirect()->route('info')
                ->withErrors(['data' => __('all.no_bookings_found_for_contact')])
                ->withInput();
        }

        return view('booking_info', [
            'bookings' => $bookings,
            'searchQuery' => $data,
        ]);
    }

    /**
     * Find guest bookings by phone using canonical TZ formats and common DB variants.
     */
    private function findGuestBookingsByPhone(string $input)
    {
        $variants = [];
        $digits = preg_replace('/\D/', '', $input);

        $canonical = normalize_tanzania_phone_to_canonical($input);
        if ($canonical !== null) {
            $variants = array_merge($variants, tanzania_phone_booking_lookup_variants($canonical));
        }

        $legacy = normalize_tanzania_phone_for_booking($input);
        if ($legacy !== '') {
            $variants[] = $legacy;
            $legacyCanonical = normalize_tanzania_phone_to_canonical($legacy);
            if ($legacyCanonical !== null) {
                $variants = array_merge($variants, tanzania_phone_booking_lookup_variants($legacyCanonical));
            }
        }

        if ($digits !== '') {
            $variants[] = $digits;
            $variants[] = ltrim($input, '+');
        }

        $variants = array_values(array_unique(array_filter($variants)));

        $relations = ['campany', 'route_name', 'user', 'bus.route', 'vender', 'campany.busOwnerAccount', 'schedule'];

        $bookings = Booking::with($relations)
            ->whereIn('customer_phone', $variants)
            ->orderByDesc('travel_date')
            ->orderByDesc('id')
            ->get();

        if ($bookings->isNotEmpty()) {
            return $bookings;
        }

        if ($canonical !== null && strlen($canonical) >= 12) {
            $subscriber = substr($canonical, -9);

            return Booking::with($relations)
                ->where('customer_phone', 'like', '%' . $subscriber)
                ->orderByDesc('travel_date')
                ->orderByDesc('id')
                ->get();
        }

        return collect();
    }

    /**
     * Load bookings for a verified guest email.
     */
    private function findGuestBookingsByEmail(string $email)
    {
        return Booking::with(['campany', 'route_name', 'user', 'bus.route', 'vender', 'campany.busOwnerAccount', 'schedule'])
            ->whereRaw('LOWER(customer_email) = ?', [strtolower($email)])
            ->orderByDesc('travel_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Show booking verification form
     */
    public function showBookingVerificationForm()
    {
        return view('auth.booking-verification');
    }

    /**
     * Verify email for booking access
     */
    public function verifyBookingEmail(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string|size:6',
        ]);

        // Get verification data from session
        $email = $request->session()->get('booking_verification_email');
        $storedCode = $request->session()->get('booking_verification_code');
        $expiresAt = $request->session()->get('booking_verification_expires_at');

        if (!$email || !$storedCode || !$expiresAt) {
            return redirect()->route('info')->withErrors(['email' => __('all.verification_session_expired')]);
        }

        // Check if verification code is valid and not expired
        if ($request->verification_code !== $storedCode) {
            return back()->withErrors(['verification_code' => __('all.invalid_verification_code')])->withInput();
        }

        if (now()->isAfter($expiresAt)) {
            return back()->withErrors(['verification_code' => __('all.verification_code_expired')])->withInput();
        }

        // Clear verification session data
        $request->session()->forget(['booking_verification_email', 'booking_verification_code', 'booking_verification_expires_at']);

        // Get bookings for the verified email
        $bookings = $this->findGuestBookingsByEmail($email);

        if ($bookings->isEmpty()) {
            return redirect()->route('info')
                ->withErrors(['data' => __('all.no_bookings_found_for_contact')]);
        }

        return view('booking_info', [
            'bookings' => $bookings,
            'searchQuery' => $email,
        ])->with('success', __('all.booking_email_verified'));
    }

    /**
     * Resend verification code for booking access
     */
    public function resendBookingVerificationCode(Request $request)
    {
        // Get email from session
        $email = $request->session()->get('booking_verification_email');

        if (!$email) {
            return redirect()->route('info')->withErrors(['email' => __('all.verification_session_expired')]);
        }

        // Generate new verification code
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(15);

        // Update session with new verification data
        $request->session()->put('booking_verification_code', $verificationCode);
        $request->session()->put('booking_verification_expires_at', $expiresAt);

        $tempUser = (object) ['email' => $email, 'name' => 'Customer'];
        $code = $verificationCode;
        dispatch(function () use ($email, $tempUser, $code) {
            try {
                Mail::to($email)->send(new \App\Mail\EmailVerification($tempUser, $code));
            } catch (\Exception $e) {
                Log::error('Failed to resend verification email: ' . $e->getMessage());
            }
        })->afterResponse();

        return back()
                ->with('email', $email)
                ->with('status', __('all.new_verification_code_sent'));
    }

    public function form()
    {
        return view('form');
    }

    public function choose()
    {
        return view('choose');
    }

    public function booking(Request $request)
    {
        return view('booking');
    }

    public function search(Request $request)
    {

        $bus = Campany::with('bus.schedules')
            ->where('id', $request->campany_id)
            ->get();
        return view('booking', compact('bus'));
        //return $bus;
    }

    public function booking_form(Request $request, $id, $from, $to)
    {
        $car = $this->loadBookingFormBus($request, $id, $from, $to);

        if ($car === null) {
            return redirect()->route('booking')->with(
                'error',
                __('all.trip_not_available')
            );
        }

        return view('booking_form', compact('car'));
    }

    public function inlineBookingForm(Request $request, $id, $from, $to)
    {
        $car = $this->loadBookingFormBus($request, $id, $from, $to);

        if ($car === null) {
            return response()->json([
                'ok' => false,
                'message' => __('all.trip_not_available_try_another'),
            ], 404);
        }

        $inlineUid = 'ib_' . $car->id . '_' . substr(md5($from . $to . session('departure_date')), 0, 8);

        return view('test.partials.booking_form_inline', compact('car', 'inlineUid'));
    }

    private function loadBookingFormBus(Request $request, $id, $from, $to)
    {
        if ($request->filled('departure_date')) {
            session()->put('departure_date', Carbon::parse($request->departure_date)->toDateString());
        }

        $car = bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($from, $to) {
                $query->where('from', $from)->where('to', $to);
                if ($date = session('departure_date')) {
                    $query->where('schedule_date', $date);
                }
            },
            'route.points',
        ])->find($id);

        if ($car === null || $car->route === null || $car->schedule === null) {
            return null;
        }

        session()->put('time', [
            'start' => $car->schedule->start ?? $car->route->route_start,
            'end' => $car->schedule->end ?? $car->route->route_end,
        ]);

        if ($car->route->from == $car->schedule->from) {
            $car->filtered_points = $car->route->points->filter(fn ($point) => $point->state === 'no');
        } else {
            $car->filtered_points = $car->route->points->filter(fn ($point) => $point->state === 'yes');
        }

        return apply_booking_filtered_points($car);
    }

    private function isInlineBookingRequest(Request $request): bool
    {
        return $request->ajax()
            || $request->boolean('inline')
            || $request->header('X-Inline-Booking') === '1';
    }

    private function getSeatsContext(): ?array
    {
        $booking_form = session()->get('booking_form');
        if (empty($booking_form['bus_id']) || empty($booking_form['travel_date'])) {
            return null;
        }

        $bus_id = $booking_form['bus_id'];
        $travel_date = $booking_form['travel_date'];
        $price = $booking_form['dropping_point_amount'];
        $info = $booking_form;

        $car = bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($travel_date, $booking_form) {
                $query->where('schedule_date', $travel_date);
                if (!empty($booking_form['from']) && !empty($booking_form['to'])) {
                    $query->where('from', $booking_form['from'])->where('to', $booking_form['to']);
                }
            },
        ])->find($bus_id);

        if (!$car) {
            return null;
        }

        $booked_seats = Booking::where('bus_id', $bus_id)
            ->where('travel_date', $travel_date)
            ->whereIn('payment_status', ['Paid', 'Reserved', 'resaved'])
            ->pluck('seat')
            ->flatMap(fn ($seats) => explode(',', $seats))
            ->unique()
            ->values()
            ->toArray();

        $distance = $info['route_distance'] ?? 0;
        $setting = Setting::first();
        $formulaService = app(FareFormulaService::class);
        $provisionalForm = array_merge($info, ['seats' => 'A1', 'total_amount' => $price]);
        $fees = $formulaService->calculateTravellerServiceFee(
            $formulaService->busFareForServiceFeeFromBookingForm($provisionalForm),
            $setting,
            1
        );

        return compact('price', 'booked_seats', 'car', 'info', 'distance', 'fees');
    }

    public function inlineWalletLookup(Request $request)
    {
        $key = trim((string) $request->query('key', ''));
        if ($key === '') {
            return response()->json(['amount' => 0]);
        }

        $amount = TempWallet::where('user_key', $key)->value('amount') ?? 0;

        return response()->json(['amount' => (float) $amount]);
    }

    public function inlinePreparePayment(Request $request)
    {
        if (!$this->isInlineBookingRequest($request)) {
            return response()->json(['ok' => false, 'message' => __('all.invalid_request')], 400);
        }

        $bus_info = session()->get('booking_form', []);
        if (empty($bus_info['bus_id']) || empty($bus_info['travel_date'])) {
            return response()->json(['ok' => false, 'message' => __('all.session_expired_try_again')], 422);
        }

        $seats = $request->input('selected_seats');
        $price = $request->input('total_amount');
        $selected = is_array($seats) ? $seats : (is_string($seats) ? array_map('trim', explode(',', $seats)) : []);
        $selected = array_values(array_filter($selected));

        if (empty($selected) && !empty($bus_info['seats'])) {
            $selected = array_values(array_filter(array_map('trim', explode(',', (string) $bus_info['seats']))));
            if ($price === null || $price === '') {
                $price = $bus_info['total_amount'] ?? null;
            }
        }

        if (empty($selected)) {
            return response()->json(['ok' => false, 'message' => __('all.select_at_least_one_seat')], 422);
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
            return response()->json([
                'ok' => false,
                'message' => __('all.seats_no_longer_available'),
            ], 422);
        }

        $passengers = $request->input('passengers');
        if (is_string($passengers)) {
            $passengers = json_decode($passengers, true) ?: [];
        }
        if (!is_array($passengers) || count($passengers) !== count($selected)) {
            return response()->json(['ok' => false, 'message' => __('all.complete_seat_details_each')], 422);
        }

        foreach ($passengers as $passenger) {
            if (empty(trim($passenger['name'] ?? '')) || empty(trim($passenger['phone'] ?? ''))) {
                return response()->json(['ok' => false, 'message' => __('all.enter_name_phone_each_seat')], 422);
            }
            if (empty(trim($passenger['age_group'] ?? ''))) {
                return response()->json(['ok' => false, 'message' => __('all.select_age_group_each_passenger')], 422);
            }
        }

        $bus_info['total_amount'] = $price;
        $bus_info['total_amount_before_coupon'] = $price;
        $bus_info['seats'] = implode(',', $selected);
        $bus_info['passenger_details'] = $passengers;
        $bus_info['customer_name'] = $passengers[0]['name'];
        $bus_info['customer_number'] = $passengers[0]['phone'];
        $bus_info['age_group'] = $passengers[0]['age_group'];
        session()->put('booking_form', $bus_info);

        $merged = array_merge($request->all(), [
            'customer' => $passengers[0]['name'],
            'gender' => 'Male',
            'age' => 25,
            'age_group' => $passengers[0]['age_group'],
            'category' => '',
            'inline' => '1',
        ]);
        $paymentRequest = Request::create(route('payment_store'), 'POST', $merged);
        $paymentRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
        $paymentRequest->headers->set('X-Inline-Booking', '1');

        return $this->payment_info($paymentRequest);
    }

    public function get_form(Request $request)
    {
        $route = route::find($request->route_id);
        $schedule = Schedule::find($request->schedule_id);
        $pickupPoint = $request->pickup_point ?? ($schedule ? $schedule->from : ($route ? $route->from : null));
        $droppingPoint = $request->dropping_point ?? ($schedule ? $schedule->to : ($route ? $route->to : null));

        $routeDistance = RouteDistanceService::resolveForBooking(
            $request->route_distance,
            $pickupPoint,
            $droppingPoint,
            $route ? (float) ($route->distance ?? 0) : null
        );

        if ($routeDistance < 1) {
            if ($this->isInlineBookingRequest($request)) {
                return response()->json([
                    'ok' => false,
                    'message' => __('all.select_pickup_dropping_points'),
                ], 422);
            }

            return back()->with('error', __('all.calculate_distance_before_continue'));
        }

        $bus_info = [
            'bus_id' => $request->bus_id,
            'from' => $schedule ? $schedule->from : $route->from,
            'to' => $schedule ? $schedule->to : $route->to,
            'route_id' => $request->route_id,
            'pickup_point' => $pickupPoint,
            'dropping_point' => $droppingPoint,
            'travel_date' => session()->get('departure_date') ?? now()->format('Y-m-d'),
            'dropping_point_amount' => $request->dropping_point_amount ?? ($route ? $route->price : 0),
            'route_distance' => $routeDistance,
            'schedule_id' => $request->schedule_id,
        ];

        session()->put('booking_form', $bus_info);

        if ($this->isInlineBookingRequest($request)) {
            $context = $this->getSeatsContext();
            if (!$context) {
                return response()->json(['ok' => false, 'message' => __('all.session_expired_try_again')], 422);
            }

            $inlineUid = $request->input('inline_uid', 'ib_seats');

            return response()->json([
                'ok' => true,
                'step' => 2,
                'html' => view('test.partials.inline_checkout_wizard', array_merge($context, compact('inlineUid')))->render(),
            ]);
        }

        return redirect()->to(booking_route('seats'));
    }

    public function seates()
    {
        $context = $this->getSeatsContext();
        if (!$context) {
            return redirect()->to(booking_route('home'))->with('error', __('all.session_expired_try_again'));
        }

        return view('seates', $context);
    }

    public function get_seats(Request $request)
    {
        $seats = $request->selected_seats;
        $price = $request->total_amount;

        $bus_info = session()->get('booking_form', []);
        if (empty($bus_info['bus_id']) || empty($bus_info['travel_date'])) {
            if ($this->isInlineBookingRequest($request)) {
                return response()->json(['ok' => false, 'message' => __('all.session_expired_try_again')], 422);
            }
            return redirect()->to(booking_route('home'))->with('error', __('all.session_expired_try_again'));
        }

        $selected = is_array($seats) ? $seats : (is_string($seats) ? array_map('trim', explode(',', $seats)) : []);
        $selected = array_filter($selected);

        if (empty($selected)) {
            if ($this->isInlineBookingRequest($request)) {
                return response()->json(['ok' => false, 'message' => __('all.select_at_least_one_seat')], 422);
            }
            return redirect()->to(booking_route('seats'))->with('error', __('all.select_at_least_one_seat'));
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
            $msg = __('all.seats_no_longer_available');
            if ($this->isInlineBookingRequest($request)) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return redirect()->to(booking_route('seats'))->with('error', $msg);
        }

        $bus_info['total_amount'] = $price;
        $bus_info['total_amount_before_coupon'] = $price;
        $bus_info['seats'] = $seats;

        session()->put('booking_form', $bus_info);

        if ($this->isInlineBookingRequest($request)) {
            return response()->json([
                'ok' => true,
                'step' => 3,
                'redirect' => booking_route('pay'),
            ]);
        }

        return redirect()->to(booking_route('pay'));
    }

    public function payment()
    {
        $setting = Setting::first();
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->to(booking_route('home'))->with('error', __('all.session_expired_try_again'));
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
        return view('payment', compact('price', 'seats', 'info', 'car', 'time', 'date', 'fees', 'distance'));
    }

    public function payment_info(Request $request)
    {
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            if ($this->isInlineBookingRequest($request)) {
                return response()->json(['ok' => false, 'message' => __('all.session_expired_try_again')], 422);
            }
            return redirect()->to(booking_route('home'))->with('error', __('all.session_expired_try_again'));
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
        $bus_info['cancel_amount'] = $request->amount_cancel ?? 0;
        $bus_info['cancel_key'] = $request->key ?? '';
        $bus_info['excess_luggage'] = $request->excess_luggage ?? 0; // Add excess luggage checkbox value
        $bus_info['has_excess_luggage'] = $request->excess_luggage ?? 0; // Canonical DB flag
        $bus_info['excess_luggage_description'] = $request->excess_luggage_description ?? null; // Add excess luggage description
        session()->put('booking_form', $bus_info);

        $insuranceError = process_booking_insurance_input($request, $bus_info);
        session()->put('booking_form', $bus_info);
        if ($insuranceError) {
            if ($this->isInlineBookingRequest($request)) {
                return response()->json(['ok' => false, 'message' => __($insuranceError)], 422);
            }

            return redirect()->to(booking_route('pay'))->with('error', __($insuranceError));
        }

        if (!empty($bus_info['discount'])) {
            $couponCheck = Discount::where('code', $bus_info['discount'])->first();
            if (!$couponCheck) {
                if ($this->isInlineBookingRequest($request)) {
                    return response()->json(['ok' => false, 'message' => __('all.invalid_coupon_code')], 422);
                }
                return redirect()->to(booking_route('pay'))->with('error', __('all.invalid_coupon_code'));
            }
            if (!$couponCheck->isValid()) {
                if ($this->isInlineBookingRequest($request)) {
                    return response()->json(['ok' => false, 'message' => __('all.coupon_expired_or_limit')], 422);
                }
                return redirect()->to(booking_route('pay'))->with('error', __('all.coupon_expired_or_limit'));
            }
        }

        function discount($amount)
        {
            $coupon = session()->get('booking_form')['discount'];
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

        $ins = (float) ($bus_info['bima_amount'] ?? 0);
        $dis = 0;
        $setting = Setting::first();

        $total_amount = session()->get('booking_form')['total_amount'];

        // Handle excess luggage fee for public flow (same logic as customer/vendor controllers)
        $excessLuggageFee = 0;
        if ((session()->get('booking_form')['excess_luggage'] ?? 0) == 1) {
            $excessLuggageFee = 2500; // TSh. 2,500
            $bus_info = session()->get('booking_form', []);
            $bus_info['has_excess_luggage'] = 1;
            $bus_info['excess_luggage_fee'] = $excessLuggageFee;
            session()->put('booking_form', $bus_info);
        }

        if (!is_null(session()->get('booking_form')['discount'])) {
            $base = session()->get('booking_form')['total_amount_before_coupon'] ?? $total_amount;
            $discountedFare = discount($base);
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
        session()->put('booking_form', $bus_info);

        // Pass excess_luggage_fee explicitly to the view so it can be shown in Price Summary
        $excess_luggage_fee = $excessLuggageFee;
        $bus_info = session()->get('booking_form', []);
        $bus_info['payable_amount'] = round($price + $fees);
        session()->put('booking_form', $bus_info);

        $test_mode = (bool) ($setting->test_mode ?? false);

        $viewData = compact('price', 'ins', 'fees', 'dis', 'excess_luggage_fee', 'test_mode');

        if ($this->isInlineBookingRequest($request)) {
            $bus_info = session()->get('booking_form', []);
            $car = Bus::with(['busname', 'route.via'])->find($bus_info['bus_id'] ?? null);
            $time = session()->get('time');
            $seatList = array_values(array_filter(array_map('trim', explode(',', (string) ($bus_info['seats'] ?? '')))));
            $departTime = null;
            if (!empty($time['start'])) {
                try {
                    $departTime = \Carbon\Carbon::parse($time['start'])->subMinutes(30)->format('h:i A');
                } catch (\Throwable $e) {
                    $departTime = null;
                }
            }
            $summary = [
                'bus_name' => $car->busname->name ?? null,
                'bus_number' => $car->bus_number ?? null,
                'via' => $car->route->via->name ?? null,
                'pickup' => $bus_info['pickup_point'] ?? ($bus_info['from'] ?? null),
                'dropping' => $bus_info['dropping_point'] ?? ($bus_info['to'] ?? null),
                'travel_date' => $bus_info['travel_date'] ?? null,
                'depart_time' => $departTime,
                'seats' => $seatList,
                'passengers' => $bus_info['passenger_details'] ?? [],
            ];

            return response()->json([
                'ok' => true,
                'step' => 4,
                'html' => view('test.partials.payment_details_inline', array_merge($viewData, ['summary' => $summary]))->render(),
            ]);
        }

        return view('payment_details', $viewData);
    }

    public function get_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contactNumber' => ['required', 'string'],
            'contactEmail' => ['nullable', 'email'],
        ]);
        if ($validator->fails()) {
            return redirect()->to(booking_route('pay'))
                ->withErrors($validator)
                ->withInput();
        }
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->to(booking_route('home'))->with('error', __('all.session_expired_try_again'));
        }
        $bus_info = session()->get('booking_form', []);

        $contactNumber = normalize_tanzania_phone_for_booking((string) $request->contactNumber);
        $paymentRaw = trim((string) ($request->payment_contact ?? ''));
        $paymentContact = $paymentRaw !== '' ? normalize_tanzania_phone_for_booking($paymentRaw) : '';

        $bus_info['customer_number'] = $contactNumber;
        $bus_info['customer_email'] = $request->contactEmail;
        $bus_info['customer_payment_number'] = $paymentContact !== '' ? $paymentContact : $contactNumber;
        $bus_info['countrycode'] = $request->countrycode;

        $user = $request->user_id ?? "";
        $payment_method = $request->payment_method;

        session()->put('booking_form', $bus_info);

        $canonicalAmount = session()->get('booking_form')['payable_amount'] ?? $request->amount;
        return $this->pay($canonicalAmount, $user, $payment_method);
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

    public function pay($amount, $user, $method)
    {
        // Check if test mode is enabled
        $settings = \App\Models\Setting::first();
        if ($settings && ($settings->test_mode ?? false)) {
            // Test mode is enabled - redirect to test payment controller
            return $this->processTestPayment($amount, $user, $method);
        }

        $tigo = new TigosecureController();
        if (is_null(session()->get('booking_form')) || !isset(session()->get('booking_form')['total_amount'])) {
            return redirect()->to(booking_route('home'))->with('error', __('all.session_expired_try_again'));
        }
        $bookingForm = session()->get('booking_form');
        $bima = $bookingForm['bima'] ?? 0;
        $xcode = $this->generateRandomId();
        $data = [
            'account' => $bookingForm['customer_payment_number'],
            'countryCode' => '255',
            'country' => 'TZA',
            'firstName' => $bookingForm['customer_name'],
            'lastName' => '',
            'email' => $bookingForm['customer_email'],
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
            'payment_status' => 'Unpaid', // Set initial status to Unpaid
            'customer_phone' => session()->get('booking_form')['customer_number'],
            'customer_name' => session()->get('booking_form')['customer_name'],
            'customer_email' => session()->get('booking_form')['customer_email'],
            'bima' => $bookingForm['bima'] ?? 0,
            'insuranceDate' => $bookingForm['insuranceDate'] ?? null,
            'vender_id' => $pop,
            'discount' => $bookingForm['discount'] ?? '',
            'discount_amount' => $bookingForm['discount_amount'] ?? 0,
            'distance' => $bookingForm['route_distance'] ?? null,
            'busFee' => $bookingForm['dispo'] ?? $bookingForm['total_amount'],
            'schedule_id' => $bookingForm['schedule_id'],
            'cancel_key' => $bookingForm['cancel_key'] ?? null,
            'has_excess_luggage' => $bookingForm['has_excess_luggage'] ?? ($bookingForm['excess_luggage'] ?? 0),
            'excess_luggage_fee' => $bookingForm['excess_luggage_fee'] ?? 0,
            'excess_luggage_description' => session()->get('booking_form')['excess_luggage_description'], // Add excess luggage description
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
        } elseif ($method == 'clickpesa') {
            try {
                // ClickPesa charges the mobile-money number the customer entered for payment.
                // Validate/normalize it up-front so we return a friendly error instead of a rejected push.
                $clickpesaPhone = session()->get('booking_form')['customer_payment_number']
                    ?? session()->get('booking_form')['customer_number'];
                $normalized = ClickPesaController::normalizeTanzaniaMsisdnForClickPesa((string) $clickpesaPhone);
                if (!$normalized['ok']) {
                    return redirect()->to(booking_route('pay'))
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
            } catch (\Exception $e) {
                Log::error('ClickPesa Payment initiation failed: ' . $e->getMessage());
                return $e->getMessage();
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
    private function processTestPayment($amount, $user, $method)
    {
        $bookingForm = session()->get('booking_form');
        $bima = $bookingForm['bima'] ?? 0;
        $xcode = 'TEST-' . strtoupper(uniqid() . rand(1000, 9999));

        // Generate unique booking code
        $bookingCode = $this->generateRandomCode();
        $bus = Bus::with(['busname', 'campany.balance'])->find($bookingForm['bus_id']);

        // Prepare vendor ID
        $pop = '';
        if (auth()->check()) {
            if (auth()->user()->role == 'vender') {
                $pop = auth()->user()->id;
            }
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
            'gender' => $bookingForm['gender'],
            'age' => $bookingForm['age'],
            'infant_child' => $bookingForm['infant_child'],
            'age_group' => $bookingForm['age_group'],
            'payment_status' => 'Unpaid',
            'customer_phone' => $bookingForm['customer_number'],
            'customer_name' => $bookingForm['customer_name'],
            'customer_email' => $bookingForm['customer_email'],
            'bima' => $bookingForm['bima'],
            'insuranceDate' => $bookingForm['insuranceDate'],
            'vender_id' => $pop,
            'discount' => $bookingForm['discount'],
            'discount_amount' => $bookingForm['discount_amount'],
            'distance' => $bookingForm['route_distance'],
            'busFee' => $bookingForm['dispo'] ?? $bookingForm['total_amount'],
            'schedule_id' => $bookingForm['schedule_id'],
            'cancel_key' => $bookingForm['cancel_key'],
            'has_excess_luggage' => $bookingForm['has_excess_luggage'] ?? ($bookingForm['excess_luggage'] ?? 0),
            'excess_luggage_fee' => $bookingForm['excess_luggage_fee'] ?? 0,
            'excess_luggage_description' => $bookingForm['excess_luggage_description'],
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

        // Store booking in session for test payment controller
        Session::put('booking', $booking);

        // Clear booking form session
        session()->forget('booking_form');

        // Redirect to test payment processing
        return redirect()->route('test.payment.process');
    }

    public function handleCallback(Request $request)
    {
        try {
            // Log request details
            Log::channel('tigo')->info('Tigo Callback Request', [
                'method' => $request->method(),
                'data' => $request->all(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'timestamp' => now()->toDateTimeString(),
            ]);

            // Extract and validate parameters
            $data = $request->all();
            $transStatus = $data['trans_status'] ?? null;
            $transactionRefId = $data['transaction_ref_id'] ?? null;
            $mfsId = $data['mfs_id'] ?? null;
            $verificationCode = $data['verification_code'] ?? null;

            if (!$transactionRefId || !$transStatus) {
                Log::channel('tigo')->warning('Missing required parameters', [
                    'transaction_ref_id' => $transactionRefId,
                    'trans_status' => $transStatus,
                ]);
                return view('payments.failed', ['data' => null]);
            }

            $booking1 = session()->get('booking1');
            $booking2 = session()->get('booking2');
            if (!is_null($booking1) && !is_null($booking2)) {
                $round = new RoundpaymentController();
                $code1 = $booking1->booking_code ?? 'N/A';
                $code2 = $booking2->booking_code ?? 'N/A';
                $data1 = $round->roundtrip($transactionRefId, $transactionRefId, $verificationCode, $code1);
                $data2 = $round->roundtrip($transactionRefId, $transactionRefId, $verificationCode, $code2);
                // If roundtrip returned error (e.g. booking not found), show payment failed
                if (is_array($data1) && isset($data1['errorMessage'])) {
                    $go = new RoundTripController();
                    return $go->paymentFailed($data1['errorMessage'] ?? 'Booking not found');
                }
                if (is_array($data2) && isset($data2['errorMessage'])) {
                    $go = new RoundTripController();
                    return $go->paymentFailed($data2['errorMessage'] ?? 'Booking not found');
                }
                $red = new RedirectController();
                return $red->showRoundTripBookingStatus($data1, $data2);
            }

            // Retrieve booking
            $booking = Booking::where('transaction_ref_id', $transactionRefId)->first();

            if (!$booking) {
                Log::channel('tigo')->error('Booking not found', ['transaction_ref_id' => $transactionRefId]);
                return response()->json(['status' => 'error', 'message' => __('all.booking_not_found')], 400);
            }

            // Check for duplicate processing
            if ($booking->payment_status !== 'Unpaid') {
                Log::channel('tigo')->warning('Booking already processed', ['transaction_ref_id' => $transactionRefId]);
                return response()->json(['status' => 'received'], 200);
            }

            // Validate transaction status
            if (strtolower($transStatus) !== 'success') {
                Log::channel('tigo')->warning('Payment failed', [
                    'transaction_ref_id' => $transactionRefId,
                    'trans_status' => $transStatus,
                ]);
                $booking->update(['payment_status' => 'Failed']);
                return response()->json(['status' => 'received'], 200);
            }

            // Validate bus and company
            $bus = Bus::with(['busname', 'route', 'campany.balance'])->find($booking->bus_id);

            if (!$bus || $bus->busname->id != $booking->campany_id) {
                Log::channel('tigo')->error('Invalid bus or company', [
                    'bus_id' => $booking->bus_id,
                    'company_id' => $booking->campany_id,
                ]);
                return response()->json(['status' => 'error', 'message' => __('all.invalid_bus_or_company')], 400);
            }

            // Begin transaction
            DB::beginTransaction();

            try {
                $settlementService = app(BookingSettlementService::class);
                $settled = $settlementService->settlePaidBooking($booking, [
                    'trans_status' => $transStatus,
                    'mfs_id' => $mfsId,
                    'verification_code' => $verificationCode,
                    'payment_method' => 'mixx',
                    'cancel_amount' => Session::get('cancel', 0),
                ]);
                $booking = $settled['booking'];
                $bus = $settled['bus'];
                $busOwnerAmount = $settled['bus_owner_amount'];
                $systemBalanceAmount = $settled['system_balance_amount'];
                $paymentFeesAmount = $settled['payment_fees_amount'];
                $bimaAmount = $booking->bima_amount ?? 0;

                DB::commit();

                // --- TRA INTEGRATION ---
                try {
                    Log::channel('tigo')->info('TRA Fiscalization Starting (Mix by YAS Payment)', [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'payment_method' => 'mixx',
                        'amount' => $booking->amount,
                        'transaction_ref_id' => $transactionRefId,
                    ]);
                    
                    $tra = new \App\Services\TraVfdService();
                    // We need to refresh the booking to ensure we have latest data
                    $fiscalized = $tra->fiscalize($booking->refresh());
                    
                    if ($fiscalized) {
                        Log::channel('tigo')->info('TRA Fiscalization Successful (Mix by YAS Payment)', [
                            'booking_id' => $booking->id,
                            'booking_code' => $booking->booking_code,
                            'tra_status' => $booking->tra_status,
                            'tra_vnum' => $booking->tra_vnum ?? 'N/A',
                        ]);
                    } else {
                        Log::channel('tigo')->warning('TRA Fiscalization Returned False (Mix by YAS Payment)', [
                            'booking_id' => $booking->id,
                            'booking_code' => $booking->booking_code,
                            'tra_status' => $booking->tra_status ?? 'N/A',
                            'tra_error' => $booking->tra_error ?? 'N/A',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::channel('tigo')->error("TRA Fiscalization Failed (Mix by YAS Payment): " . $e->getMessage(), [
                        'booking_id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'transaction_ref_id' => $transactionRefId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                // -----------------------

                Log::channel('tigo')->info('Payment processed successfully', [
                    'booking_id' => $booking->id,
                    'company_id' => $bus->campany->id,
                    'company_balance_increment' => $busOwnerAmount,
                    'system_balance' => $systemBalanceAmount,
                    'payment_fees' => $paymentFeesAmount,
                    'vendor_fee_share' => $booking->vender_fee ?? 0,
                    'vendor_service_share' => $booking->vender_service ?? 0,
                    'bima_amount' => $bimaAmount,
                ]);
                Session::forget('booking');
                Session::forget('cancel');
                $key = new FunctionsController();
                $key->delete_key($booking);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::channel('tigo')->error('Failed to update records', [
                    'error' => $e->getMessage(),
                    'booking_id' => $booking->id,
                ]);
                return response()->json(['status' => 'error', 'message' => __('all.failed_update_records')], 500);
            }

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::channel('tigo')->error('Tigo Callback Error', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'timestamp' => now()->toDateTimeString(),
            ]);
            return response()->json(['status' => 'error', 'message' => __('all.server_error')], 500);
        } finally {
            // Clear only payment-related session data
            session()->forget('payment_data');
        }
    }

    public function handleRedirect($transactionRefId)
    {
        $url = new RedirectController();
        Session::forget('cancel');
        return $url->_redirect($transactionRefId);
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

    public function by_route()
    {
        $cities = City::orderBy('name', 'asc')->get();
        return view('by_route', compact('cities'));
    }

    /**
     * Show all bus schedules for today (used by "Explore All Routes").
     */
    public function schedulesToday()
    {
        $departure_date = Carbon::today()->toDateString();
        session()->put('departure_date', $departure_date);

        $schedules = Schedule::with(['bus.busname', 'route'])
            ->where('schedule_date', $departure_date)
            ->whereHas('bus.busname', function ($query) {
                $query->where('status', 1);
            })
            ->orderBy('start')
            ->get();

        $busList = $schedules->map(function ($schedule) use ($departure_date) {
            $bus = $schedule->bus;
            $total_seats = $bus->total_seats ?? $bus->busname->total_seats ?? 0;
            $booked_seats = Booking::where('bus_id', $bus->id)
                ->where('travel_date', $departure_date)
                ->where('payment_status', 'Paid')
                ->get()
                ->flatMap(function ($booking) {
                    return array_filter(array_map('trim', explode(',', $booking->seat)));
                })
                ->unique()
                ->count();
            $remain_seats = max(0, $total_seats - $booked_seats);

            $row = (object) [
                'id' => $bus->id,
                'bus_number' => $bus->bus_number ?? 'N/A',
                'bus_type' => $bus->bus_type ?? null,
                'busname' => $bus->busname,
                'schedule' => $schedule,
                'route' => $schedule->route,
                'remain_seats' => $remain_seats,
            ];
            return $row;
        });

        $departureCityName = 'All Routes';
        $arrivalCityName = '';
        return view('by_route_search', compact('busList', 'departureCityName', 'arrivalCityName', 'departure_date'));
    }

    public function by_route_search(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'departure_city' => 'required|exists:cities,id',
            'arrival_city' => 'required|exists:cities,id|different:departure_city',
            'departure_date' => 'required|date|after_or_equal:today',
            'bus_class' => 'sometimes|in:any,10,20,30,40',
            'passengers' => 'sometimes|integer|min:1',
        ]);

        // Retrieve city names and normalize departure date
        $departureCityName = City::findOrFail($validated['departure_city'])->name;
        $arrivalCityName = City::findOrFail($validated['arrival_city'])->name;
        $departure_date = Carbon::parse($validated['departure_date'])->toDateString();

        session()->put('departure_date', $departure_date);

        // Query buses with relationships and filter by route
        $busQuery = Bus::with([
            'busname' => function ($query) {
                $query->where('status', 1);
            },
            'route.via',
            'schedule' => function ($query) use ($departureCityName, $arrivalCityName, $departure_date) {
                $query->where('from', $departureCityName)
                    ->where('to', $arrivalCityName)
                    ->where('schedule_date', $departure_date);
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
                    ->where('schedule_date', $departure_date);
            });

        // Add bus class filter if specified and not "any"
        if (!empty($validated['bus_class']) && $validated['bus_class'] !== 'any') {
            $busQuery->where('bus_type', $validated['bus_class']);
        }

        $busList = $busQuery->get()
            ->map(function ($bus) {
                return tap($bus, function ($bus) {
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
                });
            })
            ->sortBy(fn ($bus) => $bus->schedule->start ?? '99:99')
            ->values();

        // Debugging: Uncomment to inspect the data
        //return $busList;

        return view($request->attributes->get('_booking_view', 'by_route_search'), compact('busList', 'departureCityName', 'arrivalCityName', 'departure_date'));
    }

    public function history(Request $request)
    {
        $query = Booking::with(['campany', 'route_name', 'user', 'governmentLeviesOnService'])
            ->whereHas('campany', function ($q) {
                $q->where('id', auth()->user()->campany->id);
            });

        if ($request->has('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('travel_date', today());
                    break;
                case 'week':
                    $query->whereBetween('travel_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('travel_date', now()->month)->whereYear('travel_date', now()->year);
                    break;
                case 'year':
                    $query->whereYear('travel_date', now()->year);
                    break;
            }
        }

        $bookings = $query->where('payment_status', 'Paid')->latest()->get();
        return view('controller.history', compact('bookings'));
    }


    // public function print_ticket(Request $request)
    // {
    //     //return json_decode($request->data);
    //     $data = json_decode($request->data);
    //     $dns2d = new DNS2D();

    //     // Generate QR code as HTML
    //     $qrCode = $dns2d->getBarcodeHTML($data->booking_code, 'QRCODE', 6, 6, 'black');
    //     $data->qrcode = $qrCode;

    //     // Load the view for the PDF
    //     $pdf = Pdf::loadView('print.ticket', ['data' => $data]);

    //     // Set paper size (4x10 inches converted to points)
    //     $pdf->setPaper([0, 0, 4 * 72, 10 * 72], 'portrait');

    //     // Get the Dompdf instance
    //     $dompdf = $pdf->getDomPDF();
    //     $canvas = $dompdf->getCanvas();
    //     $width = $canvas->get_width();
    //     $height = $canvas->get_height();

    //     // Add text watermark
    //     $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($width, $height) {
    //         $text = "Kilimanjaro Bus - Copy";
    //         $font = $fontMetrics->getFont('Helvetica', 'normal');
    //         $size = 20;
    //         $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
    //         $textHeight = $fontMetrics->getFontHeight($font, $size);

    //         // Position watermark diagonally across the page
    //         $x = ($width - $textWidth) / 2;
    //         $y = ($height - $textHeight) / 2;

    //         // Set opacity and rotation
    //         $canvas->set_opacity(0.3, 'Multiply');
    //         $canvas->page_text($x, $y, $text, $font, $size, [0.6, 0.6, 0.6], 0, 0, -45);
    //     });

    //     return $pdf->download($data->customer_name . '.pdf');
    // }

    public function print_ticket(Request $request)
    {
        $payload = json_decode($request->data);
        $bookingId = $payload->id ?? null;
        $bookingCode = $payload->booking_code ?? null;

        // Load full booking with relations so route/schedule times are available for the ticket
        // Include bus->campany->busOwnerAccount and bus->campany->user to get busowner profile data
        // Include user to get contact number as fallback
        $data = null;
        if ($bookingId) {
            $data = Booking::with(['bus.route', 'bus.campany.busOwnerAccount', 'bus.campany.user', 'campany.busOwnerAccount', 'campany.user', 'schedule', 'vender', 'user'])->find($bookingId);
        }
        if (!$data && $bookingCode) {
            $data = Booking::with(['bus.route', 'bus.campany.busOwnerAccount', 'bus.campany.user', 'campany.busOwnerAccount', 'campany.user', 'schedule', 'vender', 'user'])->where('booking_code', $bookingCode)->first();
        }
        if (!$data) {
            $data = $payload;
        }

        // Load transaction to get payment_number if customer_phone is not available
        // Handle both Booking model and stdClass object from payload
        $customerPhone = is_object($data) ? ($data->customer_phone ?? null) : ($data['customer_phone'] ?? null);
        
        if (empty($customerPhone) || $customerPhone == 'N/A' || !$customerPhone) {
            $transaction = null;
            $paymentNumber = null;
            
            // Get booking properties (handle both model and object)
            $transactionRefId = is_object($data) ? ($data->transaction_ref_id ?? null) : ($data['transaction_ref_id'] ?? null);
            $campanyId = is_object($data) ? ($data->campany_id ?? null) : ($data['campany_id'] ?? null);
            $userId = is_object($data) ? ($data->user_id ?? null) : ($data['user_id'] ?? null);
            $bookingCode = is_object($data) ? ($data->booking_code ?? null) : ($data['booking_code'] ?? null);
            $amount = is_object($data) ? ($data->amount ?? null) : ($data['amount'] ?? null);
            $createdAt = is_object($data) ? ($data->created_at ?? null) : ($data['created_at'] ?? null);
            
            // Try multiple methods to find the transaction and payment_number
            if ($transactionRefId) {
                // Method 1: Find by reference_number matching transaction_ref_id
                $transaction = \App\Models\Transaction::where('reference_number', $transactionRefId)->first();
                if ($transaction && $transaction->payment_number) {
                    $paymentNumber = $transaction->payment_number;
                }
            }
            
            // Method 2: If not found, try by campany_id and user_id (for bus owner withdrawals)
            if (!$paymentNumber && $campanyId && $userId) {
                $transaction = \App\Models\Transaction::where('campany_id', $campanyId)
                    ->where('user_id', $userId)
                    ->where('status', 'Completed')
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($transaction && $transaction->payment_number) {
                    $paymentNumber = $transaction->payment_number;
                }
            }
            
            // Method 3: Try by booking_code if available
            if (!$paymentNumber && $bookingCode) {
                // Some transactions might be linked by booking_code in reference_number
                $transaction = \App\Models\Transaction::where('reference_number', 'like', '%' . $bookingCode . '%')
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($transaction && $transaction->payment_number) {
                    $paymentNumber = $transaction->payment_number;
                }
            }
            
            // Method 4: Try to find transaction by matching amount and date (for payment transactions)
            if (!$paymentNumber && $amount && $createdAt) {
                try {
                    $createdDate = is_string($createdAt) ? \Carbon\Carbon::parse($createdAt)->format('Y-m-d') : $createdAt->format('Y-m-d');
                    $transaction = \App\Models\Transaction::where('amount', $amount)
                        ->where('campany_id', $campanyId ?? 0)
                        ->whereDate('created_at', $createdDate)
                        ->where('status', 'Completed')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($transaction && $transaction->payment_number) {
                        $paymentNumber = $transaction->payment_number;
                    }
                } catch (\Exception $e) {
                    // Ignore date parsing errors
                }
            }
            
            // Method 5: Get from user's contact if user exists
            if (!$paymentNumber && $userId) {
                $user = \App\Models\User::find($userId);
                if ($user && $user->contact) {
                    $paymentNumber = $user->contact;
                }
            }
            
            // Set payment_number if found (handle both model and object)
            if ($paymentNumber) {
                if (is_object($data) && !($data instanceof \App\Models\Booking)) {
                    // For stdClass objects from json_decode
                    $data->payment_number = $paymentNumber;
                } elseif (is_object($data) && ($data instanceof \App\Models\Booking)) {
                    // For Booking model instances - use setAttribute or dynamic property
                    $data->setAttribute('payment_number', $paymentNumber);
                    // Also set as dynamic property for easy access in view
                    $data->payment_number = $paymentNumber;
                } elseif (is_array($data)) {
                    // For arrays
                    $data['payment_number'] = $paymentNumber;
                }
            }
        }
        
        // Ensure payment_number is accessible even if not set above
        if (is_object($data) && !isset($data->payment_number)) {
            $data->payment_number = null;
        }

        $pdf = Pdf::loadView('print.ticket', ['data' => $data]);
        $pdf->setPaper([0, 0, 4 * 72, 10 * 72], 'portrait'); // 4"x10"

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $width = $canvas->get_width();
        $height = $canvas->get_height();

        // Add diagonal text watermark
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($width, $height) {
            $text = "HIGHLINK";
            $font = $fontMetrics->getFont('Helvetica', 'bold');
            $size = 24;
            $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
            $textHeight = $fontMetrics->getFontHeight($font, $size);

            $canvas->save();
            $canvas->set_opacity(0.2);
            $canvas->translate($width / 2, $height / 2);
            $canvas->rotate(-45, 0, 0);
            $canvas->translate(-$textWidth / 2, $textHeight / 4);
            $canvas->text(0, 0, $text, $font, $size, [0.7, 0.7, 0.7]);
            $canvas->restore();
        });

        return $pdf->download($data->customer_name . '.pdf');
    }

    public function edit($id)
    {
        $booking = Booking::find($id);
        return view('edit', compact('booking'));
    }

    public function update(Request $request)
    {
        //return $request->all();

        $booking = Booking::find($request->booking_id);
        $booking->update([
            'customer_name' => $request->name,
            'customer_email' => $request->email,
            'customer_phone' => $request->phone,
        ]);

        return redirect()->back()->with('success', 'updated successfully');
    }

    public function transferBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'new_bus_id' => 'required|exists:buses,id',
            'new_schedule_id' => 'required|exists:schedules,id',
            'new_travel_date' => 'required|date',
            'new_pickup_point' => 'required|string',
            'new_dropping_point' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $booking = Booking::whereKey($request->booking_id)->lockForUpdate()->first();
            if (!$booking) {
                return back()->with('error', __('vender/transfer.booking_not_found'));
            }

            $user = Auth::user();
            $companyId = $user->campany->id ?? null;
            if (!$companyId) {
                return back()->with('error', __('vender/earning.no_company_account'));
            }
            if ((int) $booking->campany_id !== (int) $companyId) {
                return back()->with('error', __('vender/transfer.booking_company_mismatch'));
            }

            if (!in_array($booking->payment_status, ['Paid', 'Reserved', 'resaved'], true)) {
                return back()->with('error', __('vender/transfer.booking_not_transferable'));
            }

            $originalPaymentStatus = $booking->payment_status;
            $newBus = Bus::with(['route', 'campany'])->whereKey($request->new_bus_id)->lockForUpdate()->first();
            $newSchedule = Schedule::whereKey($request->new_schedule_id)->lockForUpdate()->first();
            if (!$newBus || !$newSchedule || !$newBus->route || !$newBus->campany) {
                return back()->with('error', __('vender/transfer.new_bus_not_found'));
            }
            if ((int) $newBus->campany_id !== (int) $companyId) {
                return back()->with('error', __('vender/transfer.new_bus_company_mismatch'));
            }
            if ((int) $newSchedule->bus_id !== (int) $newBus->id || (string) $newSchedule->schedule_date !== (string) $request->new_travel_date) {
                return back()->with('error', __('vender/transfer.invalid_schedule_for_bus_date'));
            }

            $targetSeats = array_values(array_filter(array_map('trim', explode(',', (string) $booking->seat))));
            $occupiedSeats = Booking::query()
                ->where('id', '!=', $booking->id)
                ->where('bus_id', $newBus->id)
                ->where('travel_date', $request->new_travel_date)
                ->whereIn('payment_status', ['Paid', 'Reserved', 'resaved'])
                ->lockForUpdate()
                ->pluck('seat')
                ->flatMap(fn($seats) => explode(',', (string) $seats))
                ->map(fn($seat) => trim($seat))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (!empty(array_intersect($targetSeats, $occupiedSeats))) {
                return back()->with('error', __('vender/transfer.target_seats_unavailable'));
            }

            $pricing = $this->buildTransferPricing(
                $booking,
                $newBus,
                (string) $request->new_pickup_point,
                (string) $request->new_dropping_point
            );

            // Generate a new booking code
            $newBookingCode = $this->generateRandomCode();

            $booking->update([
                'bus_id' => $request->new_bus_id,
                'schedule_id' => $request->new_schedule_id,
                'route_id' => $newBus->route->id,
                'campany_id' => $newBus->campany->id,
                'travel_date' => $request->new_travel_date,
                'pickup_point' => $request->new_pickup_point,
                'dropping_point' => $request->new_dropping_point,
                'amount' => $pricing['amount'],
                'busFee' => $pricing['busFee'],
                'discount_amount' => $pricing['discount_amount'],
                'distance' => $pricing['distance'],
                'bima_amount' => $pricing['bima_amount'],
                'vat' => $pricing['vat'],
                'fee' => $pricing['fee'],
                'service' => $pricing['service'],
                'vender_fee' => $pricing['vender_fee'],
                'vender_service' => $pricing['vender_service'],
                'payment_status' => $originalPaymentStatus,
                'booking_code' => $newBookingCode,
                // Retain passenger details from original booking
                'gender' => $booking->gender,
                'age' => $booking->age,
                'infant_child' => $booking->infant_child,
                'age_group' => $booking->age_group,
                'customer_phone' => $booking->customer_phone,
                'customer_name' => $booking->customer_name,
                'customer_email' => $booking->customer_email,
                'user_id' => $booking->user_id,
                'vender_id' => $booking->vender_id,
                'bima' => $booking->bima,
                ////'bima_amount' => $booking->bima_amount,
                'insuranceDate' => $booking->insuranceDate,
                'discount' => $booking->discount,
                //'discount_amount' => $booking->discount_amount,
                'cancel_amount' => $booking->cancel_amount,
                'cancel_key' => $booking->cancel_key,
            ]);

            DB::commit();
            return redirect()->back()->with('success', __('vender/transfer.transfer_success'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking transfer failed: ' . $e->getMessage());
            return back()->with('error', __('vender/transfer.transfer_failed', ['error' => $e->getMessage()]));
        }
    }

    private function buildTransferPricing(Booking $booking, Bus $newBus, string $pickupPoint, string $droppingPoint): array
    {
        $formulaService = app(FareFormulaService::class);
        $seatCount = $formulaService->seatCountFromSeatString($booking->seat);

        $baseFare = max(0, (float) ($newBus->route->price ?? 0) * $seatCount);
        $discountAmount = max(0, (float) ($booking->discount_amount ?? 0));
        $discountAmount = min($discountAmount, $baseFare);

        $discountedFare = max(0, $baseFare - $discountAmount);
        $setting = Setting::first();
        $fee = $formulaService->calculateTravellerServiceFee($discountedFare, $setting, $seatCount);
        $distance = RouteDistanceService::resolveForBooking(
            null,
            $pickupPoint,
            $droppingPoint,
            (float) ($newBus->route->distance ?? 0)
        );

        return [
            'amount' => round($baseFare, 2),
            'busFee' => round($baseFare, 2),
            'discount_amount' => round($discountAmount, 2),
            'distance' => round($distance, 2),
            'bima_amount' => round((float) ($booking->bima_amount ?? 0), 2),
            'vat' => round($baseFare * 0.005, 2),
            'fee' => round($fee, 2),
            'service' => round((float) ($booking->service ?? 0), 2),
            'vender_fee' => round((float) ($booking->vender_fee ?? 0), 2),
            'vender_service' => round((float) ($booking->vender_service ?? 0), 2),
        ];
    }
}
