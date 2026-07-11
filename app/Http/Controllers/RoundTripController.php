<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\bus;
use App\Models\Campany;
use App\Models\City;
use App\Models\Discount;
use App\Models\Roundtrip;
use App\Models\route;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\TempWallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Services\FareFormulaService;
use App\Services\RouteDistanceService;
use Illuminate\Support\Str;

class RoundTripController extends Controller
{
    const EXCESS_LUGGAGE_FEE = 2500; // TSh. 2,500 for excess luggage

    private function roundtripLegData($row): array
    {
        if (!$row) {
            return [];
        }

        $raw = $row->data ?? null;

        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function direction($view, $data = [])
    {
        $user = auth()->user();

        if ($user) {
            if ($user->isCustomer()) {
                if (request()->routeIs('customer.round.trip*')) {
                    if ($view === 'round_6') {
                        return view('customer.round_trip_checkout', $data);
                    }
                    if ($view === 'round_payment_success') {
                        return view('round_payment_success', $data);
                    }
                    if ($view === 'round_payment_failed') {
                        return view('round_payment_failed', $data);
                    }

                    return view($view, $data);
                }

                return view("customer.{$view}", $data);
            } elseif ($user->isVender()) {
                if (request()->routeIs('vender.round.trip*')) {
                    if ($view === 'round_6') {
                        return view('vender.round_trip_checkout', $data);
                    }
                    if ($view === 'round_payment_success') {
                        return view('vender.round_payment_success', $data);
                    }
                    if ($view === 'round_payment_failed') {
                        return view('vender.round_payment_failed', $data);
                    }
                }

                return view("vender.{$view}", $data);
            }
        }

        return view($view, $data);
    }

    /**
     * Build round_6 checkout view data from stored leg payloads.
     */
    private function buildRoundTripCheckoutData(array $data1, array $data2, ?Setting $setting = null): array
    {
        $setting = $setting ?? Setting::first();

        return [
            'price' => ($data1['price'] ?? 0) + ($data2['price'] ?? 0),
            'ins' => ($data1['bima_amount'] ?? 0) + ($data2['bima_amount'] ?? 0),
            'fees' => ($data1['fees'] ?? 0) + ($data2['fees'] ?? 0),
            'dis' => ($data1['discount_amount'] ?? 0) + ($data2['discount_amount'] ?? 0),
            'excess_luggage_fee' => ($data1['excess_luggage_fee'] ?? 0) + ($data2['excess_luggage_fee'] ?? 0),
            'test_mode' => (bool) ($setting->test_mode ?? false),
        ];
    }

    private function buildRoundTripLegSummary(array $legData, string $legLabel): array
    {
        $car = bus::with(['busname', 'route.via'])->find($legData['bus_id'] ?? null);
        $seatList = array_values(array_filter(array_map('trim', explode(',', (string) ($legData['seats'] ?? '')))));

        return [
            'leg_label' => $legLabel,
            'bus_name' => $car->busname->name ?? null,
            'bus_number' => $car->bus_number ?? null,
            'via' => $car->route->via->name ?? null,
            'pickup' => $legData['pickup_point'] ?? ($legData['from'] ?? null),
            'dropping' => $legData['dropping_point'] ?? ($legData['to'] ?? null),
            'travel_date' => $legData['travel_date'] ?? null,
            'seats' => $seatList,
            'passengers' => $legData['passenger_details'] ?? [],
        ];
    }

    private function buildRoundTripLegSummaryFromBooking(Booking $booking, string $legLabel): array
    {
        $car = bus::with(['busname', 'route.via'])->find($booking->bus_id);
        $seatList = array_values(array_filter(array_map('trim', explode(',', (string) ($booking->seat ?? '')))));

        return [
            'leg_label' => $legLabel,
            'bus_name' => $car->busname->name ?? null,
            'bus_number' => $car->bus_number ?? null,
            'via' => $car->route->via->name ?? null,
            'pickup' => $booking->pickup_point,
            'dropping' => $booking->dropping_point,
            'travel_date' => $booking->travel_date,
            'seats' => $seatList,
            'passengers' => [],
        ];
    }

    /**
     * Resume payment for a reserved round-trip pair using the round-trip checkout flow.
     */
    public function loadResavedRoundTripCheckout(Booking $outbound, Booking $return): void
    {
        $totalAmount = (float) $outbound->amount + (float) $return->amount;

        session()->put('booking1', $outbound);
        session()->put('booking2', $return);
        session()->put('is_round', true);
        session()->put('booking_form', [
            'customer_number' => $outbound->customer_phone,
            'customer_email' => $outbound->customer_email,
            'customer_name' => $outbound->customer_name,
            'payable_amount' => $totalAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    private function buildRoundTripCheckoutFromBookings(Booking $b1, Booking $b2, Setting $setting): array
    {
        $legs = sort_round_trip_resaved_legs([$b1, $b2]);
        $outbound = $legs[0];
        $return = $legs[1];

        $ins = (float) ($outbound->bima_amount ?? 0) + (float) ($return->bima_amount ?? 0);
        $dis = (float) ($outbound->discount_amount ?? 0) + (float) ($return->discount_amount ?? 0);
        $excess = (float) ($outbound->excess_luggage_fee ?? 0) + (float) ($return->excess_luggage_fee ?? 0);
        $totalAmount = (float) ($outbound->amount ?? 0) + (float) ($return->amount ?? 0);
        $legFees = max(0, (float) ($outbound->amount ?? 0) - (float) ($outbound->busFee ?? 0) - (float) ($outbound->bima_amount ?? 0) - (float) ($outbound->excess_luggage_fee ?? 0))
            + max(0, (float) ($return->amount ?? 0) - (float) ($return->busFee ?? 0) - (float) ($return->bima_amount ?? 0) - (float) ($return->excess_luggage_fee ?? 0));
        $price = max(0, $totalAmount - $legFees);

        return [
            'price' => $price,
            'ins' => $ins,
            'fees' => $legFees,
            'dis' => $dis,
            'excess_luggage_fee' => $excess,
            'test_mode' => (bool) ($setting->test_mode ?? false),
            'legSummaries' => [
                $this->buildRoundTripLegSummaryFromBooking($outbound, __('all.outbound')),
                $this->buildRoundTripLegSummaryFromBooking($return, __('all.return_leg')),
            ],
            'contactPhone' => $outbound->customer_phone ?? '',
            'paymentAction' => round_trip_route('get_payment'),
            'standalone' => true,
        ];
    }

    private function buildRoundTripInlinePaymentViewData(Roundtrip $firstbooking, Roundtrip $secondbooking): array
    {
        $data1 = $this->roundtripLegData($firstbooking);
        $data2 = $this->roundtripLegData($secondbooking);
        $checkout = $this->buildRoundTripCheckoutData($data1, $data2);

        return array_merge($checkout, [
            'legSummaries' => [
                $this->buildRoundTripLegSummary($data1, __('all.outbound')),
                $this->buildRoundTripLegSummary($data2, __('all.return_leg')),
            ],
            'contactPhone' => $data2['customer_number'] ?? ($data1['customer_number'] ?? ''),
            'paymentAction' => round_trip_route('get_payment'),
        ]);
    }

    private function renderRoundTripInlinePaymentHtml(Roundtrip $firstbooking, Roundtrip $secondbooking, bool $standalone = false): string
    {
        return view('test.partials.round_trip_payment_details_inline', array_merge(
            $this->buildRoundTripInlinePaymentViewData($firstbooking, $secondbooking),
            ['standalone' => $standalone]
        ))->render();
    }

    /**
     * Show final round-trip checkout (round_6) when leg data or pending bookings remain in session.
     */
    private function roundTripCheckoutResponse()
    {
        $setting = Setting::first();

        if (session()->has('firstbooking') && session()->has('secondbooking')) {
            $firstbooking = session()->get('firstbooking');
            $secondbooking = session()->get('secondbooking');
            $data = array_merge(
                $this->buildRoundTripInlinePaymentViewData($firstbooking, $secondbooking),
                ['standalone' => true]
            );

            return $this->direction('round_6', $data);
        }

        if (session()->get('is_round') && session()->has('booking1') && session()->has('booking2')) {
            $b1 = session()->get('booking1');
            $b2 = session()->get('booking2');
            if ($b1 instanceof Booking) {
                $b1 = Booking::find($b1->id) ?? $b1;
            }
            if ($b2 instanceof Booking) {
                $b2 = Booking::find($b2->id) ?? $b2;
            }

            return $this->direction('round_6', $this->buildRoundTripCheckoutFromBookings($b1, $b2, $setting));
        }

        return null;
    }

    public function checkout()
    {
        if ($this->roundTripPassengerStepPending()) {
            return redirect()->to(round_trip_route('payment'));
        }

        if ($response = $this->roundTripCheckoutResponse()) {
            return $response;
        }

        return redirect()->to(round_trip_route('index'))
            ->with('error', __('all.booking_session_lost_seats'));
    }

    private function redirectRoundTripCheckout(array $errors = [])
    {
        $redirect = redirect()->to(round_trip_route('checkout'));

        return empty($errors) ? $redirect : $redirect->withErrors($errors);
    }

    /**
     * True when the user has picked seats but not yet submitted passenger details (round_5).
     */
    private function roundTripPassengerStepPending(?array $bookingForm = null): bool
    {
        $bookingForm = $bookingForm ?? session()->get('booking_form');

        return is_array($bookingForm)
            && !empty($bookingForm['bus_id'])
            && !empty($bookingForm['seats'])
            && empty($bookingForm['customer_name']);
    }

    /**
     * Keep the same round-trip key across outbound and return legs even if session is partially lost.
     */
    private function resolveRoundTripKey(): string
    {
        $key = session()->get('key') ?: session()->get('round_trip_pending_key');

        if (empty($key)) {
            $key = uniqid('Round_');
        }

        session()->put('key', $key);
        session()->put('round_trip_pending_key', $key);

        return $key;
    }

    private function clearRoundTripLegSession(): void
    {
        session()->forget(['key', 'round_trip_pending_key']);
    }

    private function isInlineBookingRequest(Request $request): bool
    {
        return $request->ajax()
            || $request->boolean('inline')
            || $request->header('X-Inline-Booking') === '1';
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
                if (! empty($booking_form['from']) && ! empty($booking_form['to'])) {
                    $query->where('from', $booking_form['from'])->where('to', $booking_form['to']);
                }
            },
        ])->find($bus_id);

        if (! $car) {
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

    private function buildReturnLegSearchUrl(array $legData): string
    {
        $fromName = $legData['to'] ?? '';
        $toName = $legData['from'] ?? '';
        $outboundDate = $legData['travel_date'] ?? Carbon::today()->toDateString();
        $date = session('return_date') ?? Carbon::parse($outboundDate)->addDay()->toDateString();

        if (Carbon::parse($date)->lt(Carbon::parse($outboundDate))) {
            $date = Carbon::parse($outboundDate)->addDay()->toDateString();
        }

        $fromCity = City::where('name', $fromName)->first();
        $toCity = City::where('name', $toName)->first();

        if (! $fromCity || ! $toCity) {
            return round_trip_route('index');
        }

        return round_trip_route('by_routesearch', [
            'departure_city' => $fromCity->id,
            'arrival_city' => $toCity->id,
            'departure_date' => $date,
        ]);
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

        $inlineUid = 'rt_' . $car->id . '_' . substr(md5($from . $to . session('departure_date')), 0, 8);

        return view('test.partials.round_trip_booking_form_inline', compact('car', 'inlineUid'));
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
        if (! $this->isInlineBookingRequest($request)) {
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

        if (empty($selected) && ! empty($bus_info['seats'])) {
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
        if (! empty($alreadyBooked)) {
            return response()->json([
                'ok' => false,
                'message' => __('all.seats_no_longer_available'),
            ], 422);
        }

        $passengers = $request->input('passengers');
        if (is_string($passengers)) {
            $passengers = json_decode($passengers, true) ?: [];
        }
        if (! is_array($passengers) || count($passengers) !== count($selected)) {
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
        $paymentRequest = Request::create(round_trip_route('payment_pay'), 'POST', $merged);
        $paymentRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
        $paymentRequest->headers->set('X-Inline-Booking', '1');

        return $this->payment_info($paymentRequest);
    }

    /**
     * Guest/customer round-trip search uses the marketing inline checkout UI.
     */
    private function usesMarketingRoundTrip(): bool
    {
        if (! auth()->check()) {
            return true;
        }

        return request()->routeIs('customer.round.trip*', 'vender.round.trip*');
    }

    private function roundTripSearchView(): string
    {
        return match (booking_channel()) {
            'customer' => 'customer.round_trip_search',
            'vender' => 'vender.round_trip_search',
            default => 'round_trip_search',
        };
    }

    private function guestRoundTripSearchData(
        string $departureCityName = '',
        string $arrivalCityName = '',
        ?string $departure_date = null
    ): array {
        return [
            'busList' => collect(),
            'departureCityName' => $departureCityName,
            'arrivalCityName' => $arrivalCityName,
            'departure_date' => $departure_date ?? Carbon::today()->toDateString(),
        ];
    }

    /**
     * Query outbound buses for guest round-trip search (one schedule row per bus, like one-way).
     */
    private function queryGuestRoundTripBuses(
        string $departureCityName,
        string $arrivalCityName,
        string $departure_date,
        ?string $busType = null
    ) {
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
            },
        ])
            ->whereHas('busname', function ($query) {
                $query->where('status', 1);
            })
            ->whereHas('schedule', function ($query) use ($departureCityName, $arrivalCityName, $departure_date) {
                $query->where('from', $departureCityName)
                    ->where('to', $arrivalCityName)
                    ->where('schedule_date', $departure_date);
            });

        if (! empty($busType) && $busType !== 'any') {
            $busQuery->where('bus_type', $busType);
        }

        return $busQuery->get()
            ->map(function ($bus) {
                return tap($bus, function ($bus) {
                    $total_seats = $bus->total_seats ?? $bus->busname->total_seats ?? 0;
                    $booked_seats = $bus->booking
                        ->flatMap(function ($booking) {
                            return array_filter(array_map('trim', explode(',', $booking->seat)));
                        })
                        ->unique()
                        ->count();

                    $bus->booked_seats = $booked_seats;
                    $bus->remain_seats = max(0, $total_seats - $booked_seats);
                });
            })
            ->sortBy(fn ($bus) => $bus->schedule->start ?? '99:99')
            ->values();
    }

    public function index()
    {
        if (auth()->user()?->isCustomer() && request()->routeIs('round.trip*') && ! request()->routeIs('customer.round.trip*')) {
            return redirect()->route('customer.round.trip');
        }

        if (auth()->user()?->isVender() && request()->routeIs('round.trip*') && ! request()->routeIs('vender.round.trip*')) {
            return redirect()->route('vender.round.trip');
        }

        if ($this->usesMarketingRoundTrip()) {
            return view($this->roundTripSearchView(), $this->guestRoundTripSearchData());
        }

        return $this->direction('round_1');
    }

    public function search(Request $request)
    {
        session()->forget(['firstbooking', 'secondbooking', 'booking_form', 'booking1', 'booking2', 'is_round']);
        $this->clearRoundTripLegSession();

        // Validate the request
        //return $request->all();
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
        $busQuery = Bus::with([
            'busname' => function ($query) {
                $query->where('status', 1);
            },
            'route.via',
            'schedule' => function ($query) use ($departureCityName, $arrivalCityName, $departure_date) {
                $query->where('from', $departureCityName)
                    ->where('to', $arrivalCityName)
                    ->where('schedule_date', $departure_date);
                //->where('start', '>', Carbon::now()->toTimeString());
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

        // Optional filter by bus_type if provided and not 'any'
        if ($request->filled('bus_type') && $request->bus_type !== 'any') {
            $busQuery->where('bus_type', $request->bus_type);
        }

        $busList = $busQuery
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
        //return response()->json($busList);
        $data = [
            'busList' => $busList,
            'departureCityName' => $departureCityName,
            'arrivalCityName' => $arrivalCityName,
            'departure_date' => $departure_date,
        ];

        return $this->direction('round_1', $data);

        //return view('vender.route', compact('busList', 'departureCityName', 'arrivalCityName', 'departure_date'));
    }

    public function by_routesearch(Request $request)
    {
        if (auth()->user()?->isCustomer() && request()->routeIs('round.trip*') && ! request()->routeIs('customer.round.trip*')) {
            return redirect()->route('customer.round.trip.by.routesearch', $request->query());
        }

        if (auth()->user()?->isVender() && request()->routeIs('round.trip*') && ! request()->routeIs('vender.round.trip*')) {
            return redirect()->route('vender.round.trip.by.routesearch', $request->query());
        }

        $validated = $request->validate([
            'departure_city' => 'required|exists:cities,id',
            'arrival_city' => 'required|exists:cities,id|different:departure_city',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after_or_equal:departure_date',
            'bus_type' => 'sometimes|in:any,10,20,30,40',
            'bus_class' => 'sometimes|in:any,10,20,30,40',
            'passengers' => 'sometimes|integer|min:1',
        ]);

        $departureCityName = City::findOrFail($validated['departure_city'])->name;
        $arrivalCityName = City::findOrFail($validated['arrival_city'])->name;
        $departure_date = Carbon::parse($validated['departure_date'])->toDateString();
        $busType = $validated['bus_type'] ?? $validated['bus_class'] ?? 'any';

        session()->put('departure_date', $departure_date);

        if ($request->filled('return_date')) {
            session()->put('return_date', Carbon::parse($validated['return_date'])->toDateString());
        }

        if ($this->usesMarketingRoundTrip()) {
            $busList = $this->queryGuestRoundTripBuses(
                $departureCityName,
                $arrivalCityName,
                $departure_date,
                $busType
            );

            return view($this->roundTripSearchView(), compact(
                'busList',
                'departureCityName',
                'arrivalCityName',
                'departure_date'
            ));
        }

        // Authenticated customer/vendor round-trip portal
        $busQuery = Bus::with([
            'busname' => function ($query) {
                $query->where('status', 1);
            },
            'route.via',
            'schedules' => function ($query) use ($departureCityName, $arrivalCityName, $departure_date) {
                $query->where('from', $departureCityName)
                    ->where('to', $arrivalCityName)
                    ->where('schedule_date', $departure_date)
                    ->where(function ($timeQuery) use ($departure_date) {
                        // If it's today, only show schedules that haven't started yet
                        if ($departure_date === Carbon::now()->toDateString()) {
                            $timeQuery->where('start', '>', Carbon::now()->toTimeString());
                        }
                    });
            },
            'booking' => function ($query) use ($departure_date) {
                $query->where('travel_date', $departure_date)
                    ->where('payment_status', 'Paid');
            }
        ])
            ->whereHas('busname', function ($query) {
                $query->where('status', 1);
            })
            ->whereHas('schedules', function ($query) use ($departureCityName, $arrivalCityName, $departure_date) {
                $query->where('from', $departureCityName)
                    ->where('to', $arrivalCityName)
                    ->where('schedule_date', $departure_date)
                    ->where(function ($timeQuery) use ($departure_date) {
                        // If it's today, only show schedules that haven't started yet
                        if ($departure_date === Carbon::now()->toDateString()) {
                            $timeQuery->where('start', '>', Carbon::now()->toTimeString());
                        }
                    });
            });

        // Add bus class filter if specified and not "any"
        if (! empty($busType) && $busType !== 'any') {
            $busQuery->where('bus_type', $busType);
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
            });

        $data = [
            'busList' => $busList,
            'departureCityName' => $departureCityName,
            'arrivalCityName' => $arrivalCityName,
            'departure_date' => $departure_date,
        ];

        return $this->direction('round_1', $data);
    }

    public function by_bus(Request $request)
    {
        //return $request->all();
        $busList = '';
        if (!$request->query('query')) {
            $currentTime = Carbon::now()->format('H:i:s');
            $currentDate = Carbon::now()->format('Y-m-d');

            $busList = Bus::with([
                'schedules' => function ($query) use ($currentDate) {
                    $query->where('schedule_date', '>', $currentDate)
                        ->orwhere('schedule_date', '=', $currentDate);
                    //->where('start', '>=', Carbon::now()->format('H:i:s')); // Optional: future schedules
                }
            ])
                ->where('campany_id', $request->bus_id)
                ->whereHas('schedules', function ($query) use ($currentDate) {
                    $query->where('schedule_date', '>=', $currentDate);
                    //->where('start', '>=', Carbon::now()->format('H:i:s')); // Optional
                })
                ->get();
        }
        $data = [
            'busList' => $busList,
        ];
        return $this->direction('round_1', $data);
        //return view('vender.route', compact('busList'));
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

        $data = [
            'busList' => $busList,
            'departureCityName' => $departureCityName,
            'arrivalCityName' => $arrivalCityName,
            'departure_date' => $departure_date,
        ];

        return $this->direction('round_2', $data);

        //return view('vender.begin', compact('busList', 'departureCityName', 'arrivalCityName', 'departure_date'));
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
            return redirect()->to(round_trip_route('index'))->with(
                'error',
                __('all.trip_not_available')
            );
        }

        $time = [
            'start' => $car->schedule->start ?? $car->route->route_start,
            'end'   => $car->schedule->end ?? $car->route->route_end,
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

        $data = [
            'car' => $car,
        ];

        return $this->direction('round_3', $data);

        //return view('booking_form', compact('car'));
        //return $car;
    }

    public function get_form(Request $request)
    {
        $route = route::find($request->route_id);
        $schedule = Schedule::find($request->schedule_id);
        $pickupPoint = $request->pickup_point ?? ($schedule->from ?? $route->from);
        $droppingPoint = $request->dropping_point ?? ($schedule->to ?? $route->to);

        $routeDistance = RouteDistanceService::resolveForBooking(
            $request->route_distance,
            $pickupPoint,
            $droppingPoint,
            $route ? (float) ($route->distance ?? 0) : null
        );

        $bus_info = [
            'bus_id' => $request->bus_id,
            'from' => $schedule->from ?? $route->from,
            'to' => $schedule->to ?? $route->to,
            'route_id' => $request->route_id,
            'pickup_point' => $pickupPoint,
            'dropping_point' => $droppingPoint,
            'travel_date' => session()->get('departure_date') ?? now()->format('Y-m-d'),
            'dropping_point_amount' => $request->dropping_point_amount ?? ($route ? $route->price : 0),
            'route_distance' => $routeDistance,
            'schedule_id' => $request->schedule_id,
        ];

        // Store in session
        session()->put('booking_form', $bus_info);

        if ($this->isInlineBookingRequest($request)) {
            $context = $this->getSeatsContext();
            if (! $context) {
                return response()->json(['ok' => false, 'message' => __('all.session_expired_try_again')], 422);
            }

            $inlineUid = $request->input('inline_uid', 'rt_seats');

            return response()->json([
                'ok' => true,
                'step' => 2,
                'html' => view('test.partials.inline_checkout_wizard', array_merge($context, [
                    'inlineUid' => $inlineUid,
                    'inlinePrepareUrl' => round_trip_route('inline_prepare'),
                    'inlineWalletUrl' => round_trip_route('inline_wallet'),
                ]))->render(),
            ]);
        }

        return redirect()->to(round_trip_route('seats'));
        //return session()->get('booking_form');
    }

    public function seates()
    {
        $booking_form = session()->get('booking_form');
        if (is_null($booking_form) || empty($booking_form['bus_id'])) {
            return redirect()->to(round_trip_route('index'))
                ->with('error', __('all.booking_session_lost_bus'));
        }
        $bus_id = $booking_form['bus_id'];
        $travel_date = $booking_form['travel_date'] ?? null;
        $price = $booking_form['dropping_point_amount'] ?? 0;

        //return $travel_date;

        $infos = session()->get('booking_form');
        $car = Bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($travel_date) {
                $query->where('schedule_date', $travel_date)
                    ->orwhere('schedule_date', '>', $travel_date);
            },
            'route.points'
        ])->find($bus_id);

        $car = Bus::with([
            'busname',
            'route',
            'schedule' => function ($query) use ($travel_date) {
                $query->where('schedule_date', $travel_date)
                    ->orwhere('schedule_date', '>', $travel_date);
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

        $data = [
            'booked_seats' => $booked_seats,
            'car' => $car,
            'price' => $price,
            'infos' => $infos,
        ];

        return $this->direction('round_4', $data);

        //return $info;

        //return view('seates', compact('price', 'booked_seats', 'car'));

        //return  $car;
    }


    public function get_seats(Request $request)
    {
        $seats = $request->selected_seats;
        $price = $request->total_amount;

        $bus_info = session()->get('booking_form', []);
        if (empty($bus_info['bus_id']) || empty($bus_info['travel_date'])) {
            return redirect()->to(round_trip_route('index'))->with('error', __('all.session_expired_try_again'));
        }

        $selected = is_array($seats) ? $seats : (is_string($seats) ? array_map('trim', explode(',', $seats)) : []);
        $selected = array_filter($selected);

        if (empty($selected)) {
            return redirect()->to(round_trip_route('seats'))->with('error', __('all.select_at_least_one_seat'));
        }

        // Always store a valid numeric total. If the posted amount is missing/zero
        // (e.g. JS didn't update the hidden field), recompute it from the per-seat
        // price so the next step never falsely reports "session expired".
        $price = is_numeric($price) ? (float) $price : 0;
        if ($price <= 0) {
            $perSeat = (float) ($bus_info['dropping_point_amount'] ?? 0);
            $price = $perSeat * count($selected);
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
            return redirect()->to(round_trip_route('seats'))->with('error', __('all.seats_no_longer_available_named', ['seats' => implode(', ', array_slice($alreadyBooked, 0, 3))]));
        }

        $bus_info['total_amount'] = $price;
        $bus_info['total_amount_before_coupon'] = $price;
        $bus_info['seats'] = $seats;

        session()->put('booking_form', $bus_info);

        return redirect()->to(round_trip_route('payment'));
    }

    public function payment()
    {
        $bookingForm = session()->get('booking_form');

        // Seats chosen but passenger form not filled — always show round_5, not checkout.
        if ($this->roundTripPassengerStepPending($bookingForm)) {
            // Keep leg session while passenger details are still being collected.
        } elseif ($response = $this->roundTripCheckoutResponse()) {
            return $response;
        }

        $setting = Setting::first();

        // Only treat as a lost session if the core seat-selection data is gone.
        // A missing/zero total_amount alone should NOT bounce the user out — recompute it.
        if (is_null($bookingForm) || empty($bookingForm['bus_id']) || empty($bookingForm['seats'])) {
            Log::warning('Round trip payment(): booking_form missing seat data', [
                'has_booking_form' => !is_null($bookingForm),
                'session_keys' => array_keys(session()->all()),
            ]);
            return redirect()->to(round_trip_route('index'))
                ->with('error', __('all.booking_session_lost_seats'));
        }

        if (!isset($bookingForm['total_amount']) || !is_numeric($bookingForm['total_amount']) || (float) $bookingForm['total_amount'] <= 0) {
            $seatCount = count(array_filter(array_map('trim', explode(',', (string) $bookingForm['seats']))));
            $bookingForm['total_amount'] = (float) ($bookingForm['dropping_point_amount'] ?? 0) * max(1, $seatCount);
            $bookingForm['total_amount_before_coupon'] = $bookingForm['total_amount_before_coupon'] ?? $bookingForm['total_amount'];
            session()->put('booking_form', $bookingForm);
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
        $data = [
            'price' => $price,
            'seats' => $seats,
            'info' => $info,
            'car' => $car,
            'time' => $time,
            'date' => $date,
            'fees' => $fees,
            'distance' => $distance,
        ];

        return $this->direction('round_5', $data);
        //return view('vender.payment', compact('price', 'seats', 'info', 'car', 'time', 'date', 'fees', 'distance'));
    }

    public function payment_info(Request $request)
    {
        //return $bus_info = session()->get('booking_form', []);
        $bus_info = session()->get('booking_form', []);

        // Only bounce out if the core seat data is gone. Recompute a missing total.
        if (empty($bus_info['bus_id']) || empty($bus_info['seats'])) {
            Log::warning('Round trip payment_info(): booking_form missing seat data', [
                'session_keys' => array_keys(session()->all()),
            ]);
            if ($this->isInlineBookingRequest($request)) {
                return response()->json(['ok' => false, 'message' => __('all.booking_session_lost_seats')], 422);
            }

            return redirect()->to(round_trip_route('index'))
                ->with('error', __('all.booking_session_lost_seats'));
        }

        if (!isset($bus_info['total_amount']) || !is_numeric($bus_info['total_amount']) || (float) $bus_info['total_amount'] <= 0) {
            $seatCount = count(array_filter(array_map('trim', explode(',', (string) $bus_info['seats']))));
            $bus_info['total_amount'] = (float) ($bus_info['dropping_point_amount'] ?? 0) * max(1, $seatCount);
        }

        $bus_info['customer_name'] = $request->customer;
        $bus_info['gender'] = $request->gender;
        $bus_info['age'] = $request->age;
        $bus_info['infant_child'] = $request->infant_child ?? 0;
        $bus_info['age_group'] = $request->age_group;
        $bus_info['category'] = $request->category;
        $time = session()->get('time', []);
        $bus_info['start'] = $time['start'] ?? null;
        $bus_info['end'] = $time['end'] ?? null;
        $bus_info['discount'] = $request->discount ?? '';
        $bus_info['cancel_amount'] = $request->amount_cancel ?? 0;
        $bus_info['cancel_key'] = $request->key ?? '';
        $bus_info['has_excess_luggage'] = $request->excess_luggage ?? 0;
        $bus_info['excess_luggage_fee'] = 0; // Initialize to 0
        session()->put('booking_form', $bus_info);

        $insuranceError = process_booking_insurance_input($request, $bus_info);
        session()->put('booking_form', $bus_info);
        if ($insuranceError) {
            if ($this->isInlineBookingRequest($request)) {
                return response()->json(['ok' => false, 'message' => __($insuranceError)], 422);
            }

            return redirect()->to(round_trip_route('payment'))->with('error', __($insuranceError));
        }

        if (!empty($bus_info['discount'])) {
            $couponCheck = Discount::where('code', $bus_info['discount'])->first();
            if (!$couponCheck) {
                if ($this->isInlineBookingRequest($request)) {
                    return response()->json(['ok' => false, 'message' => __('all.invalid_coupon_code')], 422);
                }

                return redirect()->to(round_trip_route('payment'))->with('error', __('all.invalid_coupon_code'));
            }
            if (!$couponCheck->isValid()) {
                if ($this->isInlineBookingRequest($request)) {
                    return response()->json(['ok' => false, 'message' => __('all.coupon_expired_or_limit')], 422);
                }

                return redirect()->to(round_trip_route('payment'))->with('error', __('all.coupon_expired_or_limit'));
            }
        }

        $ins = (float) ($bus_info['bima_amount'] ?? 0);
        $dis = 0;
        $setting = Setting::first();

        $total_amount = session()->get('booking_form')['total_amount'];
        $excessLuggageFee = 0;

        if (session()->get('booking_form')['has_excess_luggage'] == 1) {
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
        $bus_info['fees'] = $fees;
        $bus_info['price'] = $price;
        $bus_info['payable_amount'] = round($price + $fees);
        session()->put('booking_form', $bus_info);



        $key = $this->resolveRoundTripKey();
        $legPayload = session()->get('booking_form');

        Roundtrip::create([
            'key' => $key,
            'data' => json_encode($legPayload),
        ]);

        session()->forget('booking_form');

        $get = Roundtrip::where('key', $key)->count();

        if ($get !== 2) {
            session()->put('round_trip_pending_key', $key);
            session()->put('key', $key);

            if ($this->isInlineBookingRequest($request)) {
                session()->flash('round_trip_outbound_saved', true);

                return response()->json([
                    'ok' => true,
                    'redirect' => $this->buildReturnLegSearchUrl(is_array($legPayload) ? $legPayload : []),
                    'message' => __('all.outbound_leg_saved_select_return'),
                ]);
            }

            return redirect()->to($this->buildReturnLegSearchUrl(is_array($legPayload) ? $legPayload : []))
                ->with('round_trip_outbound_saved', true);
        }

        $firstbooking = Roundtrip::where('key', $key)
            ->orderBy('id')
            ->first();

        $secondbooking = Roundtrip::where('key', $key)
            ->orderByDesc('id')
            ->first();

        $this->clearRoundTripLegSession();

        session()->put('firstbooking', $firstbooking);
        session()->put('secondbooking', $secondbooking);

        if ($this->isInlineBookingRequest($request)) {
            return response()->json([
                'ok' => true,
                'step' => 4,
                'html' => $this->renderRoundTripInlinePaymentHtml($firstbooking, $secondbooking),
            ]);
        }

        return redirect()->to(round_trip_route('checkout'));

        // return $this->direction('round_6', $data);

        //return view('vender.payment_details', compact('price', 'ins', 'fees', 'dis'));
    }

    public function get_payment(Request $request)
    {
        $request->validate([
            'contactNumber' => ['required', 'string'],
            'contactEmail' => ['nullable', 'email'],
        ]);
        Log::info('Round Trip Get Payment Request', [
            'request_data' => $request->all(),
            'payment_method' => $request->payment_method,
            'amount' => $request->amount
        ]);

        $contactNumber = normalize_tanzania_phone_for_booking((string) $request->contactNumber);
        $paymentRaw = trim((string) ($request->payment_contact ?? ''));
        $paymentContact = $paymentRaw !== '' ? normalize_tanzania_phone_for_booking($paymentRaw) : '';

        $bus_info = session()->get('booking_form', []);
        $bus_info['customer_number'] = $contactNumber;
        $bus_info['customer_email'] = $request->contactEmail;
        $bus_info['customer_payment_number'] = $paymentContact !== '' ? $paymentContact : $contactNumber;
        $bus_info['countrycode'] = $request->countrycode;
        $bus_info['customer_name'] = $request->customer_name ?? ($bus_info['customer_name'] ?? 'Customer');

        $user = $request->user_id ?? "";

        session()->put('booking_form', $bus_info);
        $payment_method = $request->payment_method;

        Log::info('Round Trip Payment Info Prepared', [
            'bus_info' => $bus_info,
            'user' => $user,
            'payment_method' => $payment_method
        ]);

        $canonicalAmount = session()->get('booking_form')['payable_amount'] ?? $request->amount;
        $isResave = $request->has('resave_ticket') && $request->input('resave_ticket') == '1';

        return $this->pay($canonicalAmount, $user, $payment_method, $isResave);
    }

    private function resaveSuccessRedirect()
    {
        $message = 'Tickets reserved successfully! Please pay within 24 hours. After that, the bookings will be cancelled.';
        $channel = round_trip_routes()['channel'] ?? 'guest';

        if ($channel === 'vender') {
            return redirect()->route('vender.resaved.tickets')->with('success', $message);
        }

        if ($channel === 'customer') {
            return redirect()->route('customer.mybooking')->with('success', $message);
        }

        return redirect()->to(booking_route('home'))->with('success', $message);
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

    public function pay($amount, $user, $method, $isResave = false)
    {
        Log::info('Starting Round Trip Payment Processing', [
            'amount' => $amount,
            'user' => $user,
            'method' => $method,
            'is_resave' => $isResave,
            'firstbooking_session' => session()->get('firstbooking') ? 'exists' : 'missing',
            'secondbooking_session' => session()->get('secondbooking') ? 'exists' : 'missing',
            'booking_form_session' => session()->get('booking_form') ? 'exists' : 'missing'
        ]);

        // Check if test mode is enabled
        $settings = \App\Models\Setting::first();
        if ($settings && ($settings->test_mode ?? false)) {
            // Test mode is enabled - use test payment processing
            return $this->processTestPayment($amount, $user, $method, $isResave);
        }

        // Common payment details from the request (e.g., contact number, email)
        $commonPaymentInfo = session()->get('booking_form', []);
        if ($method === 'wallet') {
            if (!auth()->check() || !auth()->user()->isCustomer()) {
                return $this->redirectRoundTripCheckout(['payment_error' => __('all.payment_not_allowed') ?? 'Wallet payment is available for customers only.']);
            }
            $walletBalance = (float) (auth()->user()->temp_wallets->amount ?? 0);
            if ($walletBalance + 0.0001 < (float) $amount) {
                return $this->redirectRoundTripCheckout(['payment_error' => __('system/messages.insufficient_balance') ?? 'Insufficient wallet balance.']);
            }
        }

        $existingBooking1 = session()->get('booking1');
        $existingBooking2 = session()->get('booking2');
        $reuseBookings = session()->get('is_round')
            && $existingBooking1
            && $existingBooking2
            && in_array($existingBooking1->payment_status ?? '', ['Unpaid', 'resaved'], true)
            && in_array($existingBooking2->payment_status ?? '', ['Unpaid', 'resaved'], true);

        if ($reuseBookings) {
            $booking1 = Booking::find($existingBooking1->id);
            $booking2 = Booking::find($existingBooking2->id);
            if (!$booking1 || !$booking2) {
                $reuseBookings = false;
            }
        }

        if (!$reuseBookings) {
            if (!session()->has('firstbooking') || !session()->has('secondbooking')) {
                return $this->redirectRoundTripCheckout()
                    ->with('error', __('all.booking_session_lost_seats'));
            }

            $firstBookingData = $this->roundtripLegData(session()->get('firstbooking'));
            $secondBookingData = $this->roundtripLegData(session()->get('secondbooking'));

            Log::info('Round Trip Payment Data', [
                'firstBookingData' => $firstBookingData,
                'secondBookingData' => $secondBookingData,
                'commonPaymentInfo' => $commonPaymentInfo
            ]);

            $roundResaveRef = $isResave
                ? (round_trip_resaved_group_prefix() . strtoupper((string) Str::uuid()))
                : null;

            // Prepare booking data for the first leg
            $bookingCode1 = $this->generateRandomCode();
            $bus1 = Bus::with(['busname', 'campany.balance'])->find($firstBookingData['bus_id']);
            $pop1 = '';
            $cust1 = 0;
            if (auth()->check()) {
                if (auth()->user()->role == 'vender') {
                    $pop1 = auth()->user()->id;
                } elseif (auth()->user()->role == 'customer') {
                    $cust1 = auth()->user()->id;
                }
            }

            $bookingData1 = [
                'booking_code' => $bookingCode1,
                'campany_id' => $bus1->campany->id,
                'bus_id' => $firstBookingData['bus_id'],
                'route_id' => $firstBookingData['route_id'],
                'pickup_point' => $firstBookingData['pickup_point'],
                'dropping_point' => $firstBookingData['dropping_point'],
                'travel_date' => $firstBookingData['travel_date'],
                'seat' => $firstBookingData['seats'],
                'amount' => round($firstBookingData['payable_amount'] ?? (($firstBookingData['price'] ?? 0) + ($firstBookingData['fees'] ?? 0))),
                'gender' => $firstBookingData['gender'],
                'age' => $firstBookingData['age'],
                'infant_child' => $firstBookingData['infant_child'],
                'age_group' => $firstBookingData['age_group'],
                'payment_status' => $isResave ? 'resaved' : 'Unpaid',
                'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
                'transaction_ref_id' => $roundResaveRef,
                'customer_phone' => $commonPaymentInfo['customer_number'] ?? '',
                'customer_name' => $firstBookingData['customer_name'],
                'passengers' => booking_passengers_for_storage($firstBookingData),
                'customer_email' => $commonPaymentInfo['customer_email'] ?? '',
                'user_id' => $cust1,
                'bima' => $firstBookingData['bima'],
                'insuranceDate' => $firstBookingData['insuranceDate'],
                'vender_id' => $pop1,
                'discount' => $firstBookingData['discount'],
                'discount_amount' => $firstBookingData['discount_amount'],
                'distance' => $firstBookingData['route_distance'],
                'busFee' => $firstBookingData['dispo'] ?? $firstBookingData['total_amount'],
                'schedule_id' => $firstBookingData['schedule_id'],
                'has_excess_luggage' => $firstBookingData['has_excess_luggage'],
                'excess_luggage_fee' => $firstBookingData['excess_luggage_fee'],
            ];
            if ($firstBookingData['bima'] == 1) {
                $bookingData1['bima_amount'] = $firstBookingData['bima_amount'];
            } else {
                $bookingData1['bima_amount'] = 0;
            }

            // Prepare booking data for the second leg
            $bookingCode2 = $this->generateRandomCode();
            $bus2 = Bus::with(['busname', 'campany.balance'])->find($secondBookingData['bus_id']);
            $pop2 = '';
            $cust2 = 0;
            if (auth()->check()) {
                if (auth()->user()->role == 'vender') {
                    $pop2 = auth()->user()->id;
                } elseif (auth()->user()->role == 'customer') {
                    $cust2 = auth()->user()->id;
                }
            }

            $bookingData2 = [
                'booking_code' => $bookingCode2,
                'campany_id' => $bus2->campany->id,
                'bus_id' => $secondBookingData['bus_id'],
                'route_id' => $secondBookingData['route_id'],
                'pickup_point' => $secondBookingData['pickup_point'],
                'dropping_point' => $secondBookingData['dropping_point'],
                'travel_date' => $secondBookingData['travel_date'],
                'seat' => $secondBookingData['seats'],
                'amount' => round($secondBookingData['payable_amount'] ?? (($secondBookingData['price'] ?? 0) + ($secondBookingData['fees'] ?? 0))),
                'gender' => $secondBookingData['gender'],
                'age' => $secondBookingData['age'],
                'infant_child' => $secondBookingData['infant_child'],
                'age_group' => $secondBookingData['age_group'],
                'payment_status' => $isResave ? 'resaved' : 'Unpaid',
                'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
                'transaction_ref_id' => $roundResaveRef,
                'customer_phone' => $commonPaymentInfo['customer_number'] ?? '',
                'customer_name' => $secondBookingData['customer_name'],
                'passengers' => booking_passengers_for_storage($secondBookingData),
                'customer_email' => $commonPaymentInfo['customer_email'] ?? '',
                'user_id' => $cust2,
                'bima' => $secondBookingData['bima'],
                'insuranceDate' => $secondBookingData['insuranceDate'],
                'vender_id' => $pop2,
                'discount' => $secondBookingData['discount'],
                'discount_amount' => $secondBookingData['discount_amount'],
                'distance' => $secondBookingData['route_distance'],
                'busFee' => $secondBookingData['dispo'] ?? $secondBookingData['total_amount'],
                'schedule_id' => $secondBookingData['schedule_id'],
                'has_excess_luggage' => $secondBookingData['has_excess_luggage'],
                'excess_luggage_fee' => $secondBookingData['excess_luggage_fee'],
            ];
            if ($secondBookingData['bima'] == 1) {
                $bookingData2['bima_amount'] = $secondBookingData['bima_amount'];
            } else {
                $bookingData2['bima_amount'] = 0;
            }

            DB::beginTransaction();
            try {
                $booking1 = Booking::create($bookingData1);
                $booking2 = Booking::create($bookingData2);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Round trip booking failed: ' . $e->getMessage());
                return redirect()->to(round_trip_route('payment_failed'))->with('error', 'Failed to create round trip bookings: ' . $e->getMessage());
            }

            session()->put('booking1', $booking1);
            session()->put('booking2', $booking2);
            session()->put('is_round', true);
        } else {
            $contactUpdate = array_filter([
                'customer_phone' => $commonPaymentInfo['customer_number'] ?? null,
                'customer_email' => $commonPaymentInfo['customer_email'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');

            if (!empty($contactUpdate)) {
                $booking1->update($contactUpdate);
                $booking2->update($contactUpdate);
            }
        }

        if ($isResave) {
            if ($reuseBookings) {
                $roundResaveRef = round_trip_resaved_group_prefix() . strtoupper((string) Str::uuid());
                $resaveUpdate = [
                    'payment_status' => 'resaved',
                    'resaved_until' => Carbon::now()->addDay(),
                    'transaction_ref_id' => $roundResaveRef,
                ];
                $booking1->update($resaveUpdate);
                $booking2->update($resaveUpdate);
            }

            session()->forget(['booking_form', 'firstbooking', 'secondbooking', 'booking1', 'booking2', 'is_round']);

            return $this->resaveSuccessRedirect();
        }

        $data = [
            'account' => $commonPaymentInfo['customer_payment_number'] ?? ($commonPaymentInfo['customer_number'] ?? ''),
            'countryCode' => '255',
            'country' => 'TZA',
            'firstName' => $commonPaymentInfo['customer_name'] ?? '',
            'lastName' => '',
            'email' => $commonPaymentInfo['customer_email'] ?? '',
            'currency' => 'TZS',
            'amount' => round($amount),
            'transactionRefId' => uniqid('Round_'),
        ];

        if ($method == 'mixx') {
            Log::info('Initiating Mixx Payment for Round Trip', [
                'amount' => $amount,
                'data' => $data,
                'commonPaymentInfo' => $commonPaymentInfo,
                'session_booking_form' => session()->get('booking_form')
            ]);

            $tigo = new TigosecureController();
            try {
                $paymentResponse = $tigo->payment($data);

                Log::info('Mixx Payment Response', [
                    'response' => $paymentResponse,
                    'redirectUrl' => $paymentResponse['redirectUrl'] ?? 'Not set'
                ]);

                $txRef = $paymentResponse['transactionRefId'] ?? $data['transactionRefId'];
                $booking1->update(['transaction_ref_id' => $txRef]);
                $booking2->update(['transaction_ref_id' => $txRef]);
                session()->put('booking1', $booking1->fresh());
                session()->put('booking2', $booking2->fresh());
                session()->put('is_round', true);
                session()->forget('booking_form');
                // Redirect to payment URL
                return redirect($paymentResponse['redirectUrl']);

            } catch (\Exception $e) {
                Log::channel('tigo')->error('Mixx Payment initiation failed', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->redirectRoundTripCheckout(['payment_error' => 'Mixx Payment initiation failed: ' . $e->getMessage()]);
            }
        } elseif ($method == 'dpo') {

            try {
                $dpo = new PDOController();

                Log::info('Initiating DPO Payment for Round Trip', [
                    'amount' => $amount,
                    'customer_name' => $commonPaymentInfo['customer_name'] ?? 'Customer',
                    'customer_number' => $commonPaymentInfo['customer_number'],
                    'customer_email' => $commonPaymentInfo['customer_email']
                ]);

                $result = $dpo->initiatePayment(
                    round($amount),
                    $commonPaymentInfo['customer_name'] ?? 'Customer',
                    $commonPaymentInfo['customer_name'] ?? 'Customer',
                    $commonPaymentInfo['customer_number'],
                    $commonPaymentInfo['customer_email'],
                    uniqid('Round_')
                );

                Log::info('DPO Payment Initiation Result', [
                    'result_type' => gettype($result),
                    'result' => $result
                ]);

                return $result;
            } catch (\Exception $e) {
                // Log the error
                Log::error('DPO Payment initiation failed: ' . $e->getMessage());
                // Redirect back with error message instead of returning string
                return $this->redirectRoundTripCheckout(['payment_error' => 'DPO Payment initiation failed: ' . $e->getMessage()]);
            }
        } elseif ($method == 'cash') {
            Log::info('Processing Cash Payment for Round Trip', [
                'amount' => $amount,
                'booking1_id' => $booking1->id ?? 'Not set',
                'booking2_id' => $booking2->id ?? 'Not set',
                'booking1_code' => $booking1->booking_code ?? 'Not set',
                'booking2_code' => $booking2->booking_code ?? 'Not set'
            ]);

            try {
                // Process cash payment for both bookings
                $cashController = new CashController();

                // Process first booking
                $result1 = $cashController->cash($booking1, uniqid('Round_Cash_'));
                // Process second booking  
                $result2 = $cashController->cash($booking2, uniqid('Round_Cash_'));

                // Clear only booking_form; keep booking1/booking2/is_round for success page (paymentSuccess() will clear them)
                session()->forget(['booking_form']);

                return redirect()->to(round_trip_route('payment_success'))->with('success', 'Round trip bookings created successfully via cash!');
            } catch (\Exception $e) {
                Log::error('Cash Payment processing failed', [
                    'error' => $e->getMessage(),
                    'booking1_id' => $booking1->id ?? 'Not set',
                    'booking2_id' => $booking2->id ?? 'Not set',
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->redirectRoundTripCheckout(['payment_error' => 'Cash Payment processing failed: ' . $e->getMessage()]);
            }
        } elseif ($method == 'clickpesa') {
            try {
                // ClickPesa charges the mobile-money number entered for payment, not the contact number.
                $clickpesaPhone = $commonPaymentInfo['customer_payment_number']
                    ?? ($commonPaymentInfo['customer_number'] ?? '');
                if ((string) $clickpesaPhone === '' && isset($booking1->customer_phone)) {
                    $clickpesaPhone = (string) $booking1->customer_phone;
                }
                $normalized = ClickPesaController::normalizeTanzaniaMsisdnForClickPesa((string) $clickpesaPhone);
                if (!$normalized['ok']) {
                    return $this->redirectRoundTripCheckout([
                        'payment_error' => 'ClickPesa Payment Failed: ' . ($normalized['error'] ?? 'Invalid mobile money number.')
                    ]);
                }
                $commonPaymentInfo['customer_number'] = $normalized['phone'];

                $clickpesa = new ClickPesaController();

                Log::info('Initiating ClickPesa Payment for Round Trip', [
                    'amount' => $amount,
                    'customer_name' => $commonPaymentInfo['customer_name'] ?? 'Customer',
                    'customer_number' => $commonPaymentInfo['customer_number'],
                    'customer_email' => $commonPaymentInfo['customer_email']
                ]);

                $result = $clickpesa->initiatePayment(
                    round($amount),
                    $commonPaymentInfo['customer_name'] ?? 'Customer',
                    $commonPaymentInfo['customer_name'] ?? 'Customer',
                    $commonPaymentInfo['customer_number'],
                    $commonPaymentInfo['customer_email'] ?? '',
                    uniqid('Round_')
                );

                return $result;
            } catch (\Exception $e) {
                Log::error('ClickPesa Payment initiation failed: ' . $e->getMessage());
                return $this->redirectRoundTripCheckout(['payment_error' => 'ClickPesa Payment initiation failed: ' . $e->getMessage()]);
            }
        } elseif ($method == 'wallet') {
            return $this->processRoundTripWalletPayment($booking1, $booking2, (float) $amount);
        }
    }

    private function processRoundTripWalletPayment(Booking $booking1, Booking $booking2, float $amount)
    {
        if (!auth()->check()) {
            return $this->redirectRoundTripCheckout(['payment_error' => __('all.session_expired_try_again')]);
        }

        $payableAmount = max(0, round($amount, 2));

        DB::beginTransaction();
        try {
            $booking1 = Booking::lockForUpdate()->find($booking1->id);
            $booking2 = Booking::lockForUpdate()->find($booking2->id);
            if (
                !$booking1 || !$booking2
                || !in_array($booking1->payment_status, ['Unpaid', 'resaved'], true)
                || !in_array($booking2->payment_status, ['Unpaid', 'resaved'], true)
            ) {
                DB::rollBack();
                return $this->redirectRoundTripCheckout(['payment_error' => __('customer/busroot.payment_error') ?? 'Payment cannot be processed.']);
            }

            $wallet = TempWallet::where('user_id', auth()->id())->lockForUpdate()->first();
            $walletBalance = (float) ($wallet->amount ?? 0);
            if ($walletBalance + 0.0001 < $payableAmount) {
                DB::rollBack();
                return $this->redirectRoundTripCheckout(['payment_error' => __('system/messages.insufficient_balance') ?? 'Insufficient wallet balance.']);
            }

            $settlementService = app(\App\Services\BookingSettlementService::class);
            $settled1 = $settlementService->settlePaidBooking($booking1, [
                'trans_status' => 'success',
                'trans_token' => 'RWALLET1-' . strtoupper(uniqid()),
                'payment_method' => 'wallet',
                'cancel_amount' => Session::get('cancel', 0),
                'skip_cancel_wallet_consumption' => true,
            ]);
            $settled2 = $settlementService->settlePaidBooking($booking2, [
                'trans_status' => 'success',
                'trans_token' => 'RWALLET2-' . strtoupper(uniqid()),
                'payment_method' => 'wallet',
                'cancel_amount' => 0,
                'skip_cancel_wallet_consumption' => true,
            ]);

            $booking1 = $settled1['booking'];
            $booking2 = $settled2['booking'];

            $wallet->update([
                'amount' => max(0, $walletBalance - $payableAmount),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Round trip wallet payment failed', [
                'booking1_id' => $booking1->id ?? null,
                'booking2_id' => $booking2->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->redirectRoundTripCheckout(['payment_error' => __('all.payment_initiation_failed')]);
        }

        try {
            $tra = new \App\Services\TraVfdService();
            $tra->fiscalize($booking1->refresh());
            $tra->fiscalize($booking2->refresh());
        } catch (\Throwable $e) {
            Log::error('Round trip wallet TRA fiscalization failed: ' . $e->getMessage(), [
                'booking1_id' => $booking1->id ?? null,
                'booking2_id' => $booking2->id ?? null,
            ]);
        }

        Session::forget(['booking_form', 'firstbooking', 'secondbooking', 'booking1', 'booking2', 'is_round', 'cancel']);
        $keyController = new FunctionsController();
        $keyController->delete_key($booking1);
        $keyController->delete_key($booking2);

        $redirectController = new RedirectController();
        return $redirectController->showRoundTripBookingStatus($booking1, $booking2);
    }

    /**
     * Process round-trip payment in test mode - bypasses real payment gateways
     *
     * @param float $amount
     * @param string $user
     * @param string $method
     * @return \Illuminate\Http\RedirectResponse
     */
    private function processTestPayment($amount, $user, $method, $isResave = false)
    {
        if (!session()->has('firstbooking') || !session()->has('secondbooking')) {
            return $this->redirectRoundTripCheckout()
                ->with('error', __('all.booking_session_lost_seats'));
        }

        $firstBookingData = $this->roundtripLegData(session()->get('firstbooking'));
        $secondBookingData = $this->roundtripLegData(session()->get('secondbooking'));
        $commonPaymentInfo = session()->get('booking_form');

        $bus1 = Bus::with(['busname', 'campany.balance'])->find($firstBookingData['bus_id']);
        $bus2 = Bus::with(['busname', 'campany.balance'])->find($secondBookingData['bus_id']);

        $pop1 = '';
        $cust1 = 0;
        if (auth()->check()) {
            if (auth()->user()->role == 'vender') {
                $pop1 = auth()->user()->id;
            } elseif (auth()->user()->role == 'customer') {
                $cust1 = auth()->user()->id;
            }
        }

        $pop2 = '';
        $cust2 = 0;
        if (auth()->check()) {
            if (auth()->user()->role == 'vender') {
                $pop2 = auth()->user()->id;
            } elseif (auth()->user()->role == 'customer') {
                $cust2 = auth()->user()->id;
            }
        }

        // Generate test transaction references
        $xcode1 = 'TEST-RT1-' . strtoupper(uniqid() . rand(1000, 9999));
        $xcode2 = 'TEST-RT2-' . strtoupper(uniqid() . rand(1000, 9999));
        $roundResaveRef = $isResave
            ? (round_trip_resaved_group_prefix() . strtoupper((string) Str::uuid()))
            : null;

        // Create first booking (mirror live pay() field sources)
        $bookingData1 = [
            'booking_code' => $this->generateRandomCode(),
            'campany_id' => $bus1->campany->id,
            'bus_id' => $firstBookingData['bus_id'],
            'route_id' => $firstBookingData['route_id'],
            'pickup_point' => $firstBookingData['pickup_point'],
            'dropping_point' => $firstBookingData['dropping_point'],
            'travel_date' => $firstBookingData['travel_date'],
            'seat' => $firstBookingData['seats'],
            'amount' => round($firstBookingData['payable_amount'] ?? (($firstBookingData['price'] ?? $firstBookingData['total_amount'] ?? 0) + ($firstBookingData['fees'] ?? 0))),
            'gender' => $firstBookingData['gender'],
            'age' => $firstBookingData['age'],
            'infant_child' => $firstBookingData['infant_child'],
            'age_group' => $firstBookingData['age_group'],
            'payment_status' => $isResave ? 'resaved' : 'Unpaid',
            'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
            'customer_phone' => $commonPaymentInfo['customer_number'],
            'customer_name' => $firstBookingData['customer_name'],
            'passengers' => booking_passengers_for_storage($firstBookingData),
            'customer_email' => $commonPaymentInfo['customer_email'],
            'user_id' => $cust1,
            'bima' => $firstBookingData['bima'],
            'insuranceDate' => $firstBookingData['insuranceDate'],
            'vender_id' => $pop1,
            'discount' => $firstBookingData['discount'],
            'discount_amount' => $firstBookingData['discount_amount'],
            'distance' => $firstBookingData['route_distance'],
            'busFee' => $firstBookingData['dispo'] ?? $firstBookingData['total_amount'],
            'schedule_id' => $firstBookingData['schedule_id'],
            'has_excess_luggage' => $firstBookingData['has_excess_luggage'],
            'excess_luggage_fee' => $firstBookingData['excess_luggage_fee'],
            'transaction_ref_id' => $isResave ? $roundResaveRef : $xcode1,
            'payment_method' => 'test_mode',
        ];

        if ($firstBookingData['bima'] == 1) {
            $bookingData1['bima_amount'] = $firstBookingData['bima_amount'];
        } else {
            $bookingData1['bima_amount'] = 0;
        }

        // Create second booking (mirror live pay() field sources)
        $bookingData2 = [
            'booking_code' => $this->generateRandomCode(),
            'campany_id' => $bus2->campany->id,
            'bus_id' => $secondBookingData['bus_id'],
            'route_id' => $secondBookingData['route_id'],
            'pickup_point' => $secondBookingData['pickup_point'],
            'dropping_point' => $secondBookingData['dropping_point'],
            'travel_date' => $secondBookingData['travel_date'],
            'seat' => $secondBookingData['seats'],
            'amount' => round($secondBookingData['payable_amount'] ?? (($secondBookingData['price'] ?? $secondBookingData['total_amount'] ?? 0) + ($secondBookingData['fees'] ?? 0))),
            'gender' => $secondBookingData['gender'],
            'age' => $secondBookingData['age'],
            'infant_child' => $secondBookingData['infant_child'],
            'age_group' => $secondBookingData['age_group'],
            'payment_status' => $isResave ? 'resaved' : 'Unpaid',
            'resaved_until' => $isResave ? Carbon::now()->addDay() : null,
            'customer_phone' => $commonPaymentInfo['customer_number'],
            'customer_name' => $secondBookingData['customer_name'],
            'passengers' => booking_passengers_for_storage($secondBookingData),
            'customer_email' => $commonPaymentInfo['customer_email'],
            'user_id' => $cust2,
            'bima' => $secondBookingData['bima'],
            'insuranceDate' => $secondBookingData['insuranceDate'],
            'vender_id' => $pop2,
            'discount' => $secondBookingData['discount'],
            'discount_amount' => $secondBookingData['discount_amount'],
            'distance' => $secondBookingData['route_distance'],
            'busFee' => $secondBookingData['dispo'] ?? $secondBookingData['total_amount'],
            'schedule_id' => $secondBookingData['schedule_id'],
            'has_excess_luggage' => $secondBookingData['has_excess_luggage'],
            'excess_luggage_fee' => $secondBookingData['excess_luggage_fee'],
            'transaction_ref_id' => $isResave ? $roundResaveRef : $xcode2,
            'payment_method' => 'test_mode',
        ];

        if ($secondBookingData['bima'] == 1) {
            $bookingData2['bima_amount'] = $secondBookingData['bima_amount'];
        } else {
            $bookingData2['bima_amount'] = 0;
        }

        try {
            $booking1 = Booking::create($bookingData1);
            $booking2 = Booking::create($bookingData2);
        } catch (\Exception $e) {
            Log::error('Test mode round-trip: Failed to create bookings', [
                'error' => $e->getMessage(),
            ]);
            return redirect()->route('home')->with('error', 'Failed to create bookings in test mode');
        }

        // Store bookings in session
        Session::put('booking1', $booking1);
        Session::put('booking2', $booking2);
        Session::put('is_round', true);

        if ($isResave) {
            session()->forget(['firstbooking', 'secondbooking', 'booking_form', 'booking1', 'booking2', 'is_round']);

            return $this->resaveSuccessRedirect();
        }

        // Clear roundtrip session data
        session()->forget(['firstbooking', 'secondbooking', 'booking_form']);

        // Redirect to test payment processing
        return redirect()->route('test.payment.process');
    }

    public function paymentSuccess()
    {
        $booking1 = session()->get('booking1');
        $booking2 = session()->get('booking2');
        $isRound = session()->get('is_round');

        // Clear session data after retrieval
        session()->forget(['booking1', 'booking2', 'is_round']);

        // Reload bookings with bus, company and schedule (schedule used for second leg from/to)
        if ($booking1 && $booking1->id) {
            $booking1 = Booking::with(['bus.route', 'campany', 'schedule'])->find($booking1->id);
        }
        if ($booking2 && $booking2->id) {
            $booking2 = Booking::with(['bus.route', 'campany', 'schedule'])->find($booking2->id);
        }

        return $this->direction('round_payment_success', compact('booking1', 'booking2', 'isRound'));
    }

    public function paymentFailed($error)
    {
        // Clear any lingering session data related to the failed booking attempt
        session()->forget(['booking1', 'booking2', 'is_round', 'firstbooking', 'secondbooking', 'booking_form']);
        $this->clearRoundTripLegSession();
        $data = [
            'error' => $error
        ];
        return $this->direction('round_payment_failed', $data);
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
