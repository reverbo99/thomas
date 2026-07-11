<?php

if (!function_exists('convert_money')) {
    function convert_money($tzs)
    {
        $currency = session('currency');
        $usd = app('usdToTzs') ?? 2500;

        if (strtolower((string) $currency) === 'usd') {
            return number_format($tzs / $usd, 2);
        }

        return number_format($tzs, 2);
    }
}

if (!function_exists('convert_to_usd')) {
    function convert_to_usd($tzs)
    {
        $usd = app('usdToTzs') ?? 2500;

        return number_format($tzs / $usd, 2);
    }
}

if (!function_exists('convert_to_tzs')) {
    function convert_to_tzs($money)
    {
        $usd = app('usdToTzs') ?? 2500;

        return number_format($money * $usd, 2);
    }
}

if (!function_exists('is_cancel_allowed')) {
    function is_cancel_allowed($booking): bool
    {
        if (!$booking instanceof \App\Models\Booking) {
            return false;
        }

        return app(\App\Http\Controllers\ConstData::class)->isCancelAllowed($booking);
    }
}

if (!function_exists('normalize_booking_point_name')) {
    function normalize_booking_point_name(?string $name): string
    {
        return strtoupper(trim((string) $name));
    }
}

if (!function_exists('with_route_endpoint_points')) {
    /**
     * Add schedule/route from & to cities as pickup/dropoff options when missing.
     */
    function with_route_endpoint_points($points, ?string $from, ?string $to, float $basePrice = 0)
    {
        $points = collect($points ?? []);
        $from = trim((string) $from);
        $to = trim((string) $to);

        $hasPoint = function (int $mode, string $name) use ($points) {
            $needle = normalize_booking_point_name($name);
            if ($needle === '') {
                return true;
            }

            return $points->contains(function ($point) use ($mode, $needle) {
                return (int) ($point->point_mode ?? 0) === $mode
                    && normalize_booking_point_name($point->point ?? '') === $needle;
            });
        };

        if ($from !== '' && ! $hasPoint(1, $from)) {
            $points->prepend((object) [
                'point' => $from,
                'point_mode' => 1,
                'amount' => $basePrice,
                'state' => 'yes',
            ]);
        }

        if ($to !== '' && ! $hasPoint(2, $to)) {
            $points->prepend((object) [
                'point' => $to,
                'point_mode' => 2,
                'amount' => $basePrice,
                'state' => 'yes',
            ]);
        }

        return $points->values();
    }
}

if (!function_exists('apply_booking_filtered_points')) {
    function apply_booking_filtered_points($car)
    {
        if ($car === null) {
            return null;
        }

        $from = $car->schedule->from ?? $car->route->from ?? null;
        $to = $car->schedule->to ?? $car->route->to ?? null;
        $basePrice = (float) ($car->route->price ?? 0);

        $car->filtered_points = with_route_endpoint_points(
            $car->filtered_points ?? collect(),
            $from,
            $to,
            $basePrice
        );

        return $car;
    }
}

if (!function_exists('normalize_tanzania_phone_to_canonical')) {
    /**
     * Strip formatting and return Tanzania mobile as 255 + 9 digits, or null if not a recognizable TZ mobile.
     * Treats 225… (typo for 255), 0…, 6/7…, +255…, and 255… as equivalent.
     */
    function normalize_tanzania_phone_to_canonical(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $input);
        if ($digits === '') {
            return null;
        }
        while (str_starts_with($digits, '00') && strlen($digits) > 2) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '225')) {
            $rest = substr($digits, 3);
            if (str_starts_with($rest, '6') || str_starts_with($rest, '7')) {
                $digits = '255' . $rest;
            }
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '255')) {
            return $digits;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '255' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && (str_starts_with($digits, '6') || str_starts_with($digits, '7'))) {
            return '255' . $digits;
        }

        return null;
    }
}

if (!function_exists('tanzania_phone_booking_lookup_variants')) {
    /**
     * Possible customer_phone values in DB for the same canonical 255XXXXXXXXX number.
     */
    function tanzania_phone_booking_lookup_variants(string $canonical): array
    {
        if (strlen($canonical) !== 12 || !str_starts_with($canonical, '255')) {
            return array_values(array_unique(array_filter([$canonical])));
        }
        $subscriber = substr($canonical, 3);

        return array_values(array_unique(array_filter([
            $canonical,
            '+' . $canonical,
            '0' . $subscriber,
            $subscriber,
        ])));
    }
}

if (!function_exists('normalize_tanzania_phone_for_booking')) {
    /**
     * Normalize contact / payment phone for booking flow: digits only, 255XXXXXXXXX when possible.
     * Uses strict TZ rules first (+255, 225 typo, 0…, 6/7…), then legacy prepend-255 fallback.
     */
    function normalize_tanzania_phone_for_booking(?string $input): string
    {
        if ($input === null) {
            return '';
        }
        $trimmed = trim($input);
        if ($trimmed === '') {
            return '';
        }
        $canonical = normalize_tanzania_phone_to_canonical($trimmed);
        if ($canonical !== null) {
            return $canonical;
        }
        $digits = preg_replace('/\D/', '', $trimmed);
        while (str_starts_with($digits, '00') && strlen($digits) > 2) {
            $digits = substr($digits, 2);
        }
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '255' . substr($digits, 1);
        }
        if (substr($digits, 0, 3) !== '255') {
            return '255' . $digits;
        }

        return $digits;
    }
}

if (!function_exists('booking_channel')) {
    function booking_channel(): string
    {
        if (request()->routeIs('customer.*')) {
            return 'customer';
        }

        if (request()->routeIs('vender.*')) {
            return 'vender';
        }

        return 'guest';
    }
}

if (!function_exists('sales_channel_for_booking')) {
    function sales_channel_for_booking(?int $venderId = null, ?string $paymentMethod = null): string
    {
        if ($venderId) {
            return 'in_person';
        }

        if ($paymentMethod && in_array(strtolower($paymentMethod), ['phone', 'call'], true)) {
            return 'phone';
        }

        return 'online';
    }
}

if (!function_exists('sales_channel_label')) {
    function sales_channel_label(?string $channel): string
    {
        return match ($channel) {
            'in_person' => __('all.sales_channel_in_person'),
            'phone' => __('all.sales_channel_phone'),
            default => __('all.sales_channel_online'),
        };
    }
}

if (!function_exists('booking_routes')) {
    /**
     * Named routes and URLs for one-way booking (guest vs customer portal).
     *
     * @return array<string, string>
     */
    function booking_routes(?string $channel = null): array
    {
        $channel ??= booking_channel();

        if ($channel === 'customer') {
            return [
                'channel' => 'customer',
                'search_form' => route('customer.mybooking.search.form'),
                'search_method' => 'POST',
                'inline_form' => 'customer.booking.inline.form',
                'store' => 'customer.get_form',
                'get_seats' => 'customer.get_seats',
                'inline_prepare' => 'customer.booking.inline.prepare',
                'inline_wallet' => 'customer.booking.inline.wallet',
                'verify' => 'customer.verify',
                'pay' => 'customer.pay',
                'seats' => 'customer.seats',
                'booking_form' => 'customer.booking_form',
                'back_search' => route('customer.mybooking.search'),
                'home' => 'customer.index',
                'busname' => 'customer.busname',
            ];
        }

        if ($channel === 'vender') {
            return [
                'channel' => 'vender',
                'search_form' => route('vender.route.by_route_search'),
                'search_method' => 'GET',
                'inline_form' => 'vender.booking.inline.form',
                'store' => 'vender.get_form',
                'get_seats' => 'vender.get_seats',
                'inline_prepare' => 'vender.booking.inline.prepare',
                'inline_wallet' => 'vender.booking.inline.wallet',
                'verify' => 'vender.verify',
                'pay' => 'vender.pay',
                'seats' => 'seates.vender',
                'booking_form' => 'vender.booking_form',
                'back_search' => route('vender.route'),
                'home' => 'vender.index',
                'busname' => 'vender.busname',
            ];
        }

        return [
            'channel' => 'guest',
            'search_form' => route('by_route_search'),
            'search_method' => 'POST',
            'inline_form' => 'booking.inline.form',
            'store' => 'store',
            'get_seats' => 'get_seats',
            'inline_prepare' => 'booking.inline.prepare',
            'inline_wallet' => 'booking.inline.wallet',
            'verify' => 'verify',
            'pay' => 'pay',
            'seats' => 'seates',
            'booking_form' => 'booking_form',
            'back_search' => route('routes'),
            'home' => 'home',
            'busname' => 'busname',
        ];
    }
}

if (!function_exists('booking_route')) {
    function booking_route(string $key, array $params = []): string
    {
        $routes = booking_routes();
        $value = $routes[$key] ?? $routes['home'];

        if (str_contains($value, '://') || str_starts_with($value, '/')) {
            return $value;
        }

        return route($value, $params);
    }
}

if (!function_exists('round_trip_routes')) {
    /**
     * Named routes and URLs for round-trip booking (guest vs customer portal).
     *
     * @return array<string, string>
     */
    function round_trip_routes(?string $channel = null): array
    {
        $channel ??= booking_channel();

        if ($channel === 'vender') {
            return [
                'channel' => 'vender',
                'index' => 'vender.round.trip',
                'by_routesearch' => 'vender.round.trip.by.routesearch',
                'inline_form' => 'vender.round.trip.inline.form',
                'store' => 'vender.round.trip.booking_form.store',
                'inline_prepare' => 'vender.round.trip.inline.prepare',
                'inline_wallet' => 'vender.round.trip.inline.wallet',
                'seats' => 'vender.round.trip.seats',
                'seats_post' => 'vender.round.trip.seats.post',
                'payment' => 'vender.round.trip.payment',
                'payment_pay' => 'vender.round.trip.payment.pay',
                'checkout' => 'vender.round.trip.checkout',
                'get_payment' => 'vender.round.trip.get_payment',
                'payment_success' => 'vender.round.trip.payment.success',
                'payment_failed' => 'vender.round.trip.payment.failed',
                'busname' => 'vender.busname',
            ];
        }

        if ($channel === 'customer') {
            return [
                'channel' => 'customer',
                'index' => 'customer.round.trip',
                'by_routesearch' => 'customer.round.trip.by.routesearch',
                'inline_form' => 'customer.round.trip.inline.form',
                'store' => 'customer.round.trip.booking_form.store',
                'inline_prepare' => 'customer.round.trip.inline.prepare',
                'inline_wallet' => 'customer.round.trip.inline.wallet',
                'seats' => 'customer.round.trip.seats',
                'seats_post' => 'customer.round.trip.seats.post',
                'payment' => 'customer.round.trip.payment',
                'payment_pay' => 'customer.round.trip.payment.pay',
                'checkout' => 'customer.round.trip.checkout',
                'get_payment' => 'customer.round.trip.get_payment',
                'payment_success' => 'customer.round.trip.payment.success',
                'payment_failed' => 'customer.round.trip.payment.failed',
                'busname' => 'customer.busname',
            ];
        }

        return [
            'channel' => 'guest',
            'index' => 'round.trip',
            'by_routesearch' => 'round.trip.by.routesearch',
            'inline_form' => 'round.trip.inline.form',
            'store' => 'round.trip.booking_form.store',
            'inline_prepare' => 'round.trip.inline.prepare',
            'inline_wallet' => 'round.trip.inline.wallet',
            'seats' => 'round.trip.seats',
            'seats_post' => 'round.trip.seats.post',
            'payment' => 'round.trip.payment',
            'payment_pay' => 'round.trip.payment.pay',
            'checkout' => 'round.trip.checkout',
            'get_payment' => 'round.trip.get_payment',
            'payment_success' => 'round.trip.payment.success',
            'payment_failed' => 'round.trip.payment.failed',
            'busname' => 'busname',
        ];
    }
}

if (!function_exists('round_trip_route')) {
    function round_trip_route(string $key, array $params = []): string
    {
        $routes = round_trip_routes();
        $value = $routes[$key] ?? $routes['index'];

        if (str_contains($value, '://') || str_starts_with($value, '/')) {
            return $value;
        }

        return route($value, $params);
    }
}

if (!function_exists('round_trip_resaved_group_prefix')) {
    function round_trip_resaved_group_prefix(): string
    {
        return 'RoundResave_';
    }
}

if (!function_exists('is_round_trip_resaved_group')) {
    function is_round_trip_resaved_group(?string $transactionRefId): bool
    {
        return $transactionRefId !== null
            && str_starts_with($transactionRefId, round_trip_resaved_group_prefix());
    }
}

if (!function_exists('round_trip_resaved_pair_matches')) {
    function round_trip_resaved_pair_matches(\App\Models\Booking $a, \App\Models\Booking $b): bool
    {
        if (($a->payment_status ?? '') !== 'resaved' || ($b->payment_status ?? '') !== 'resaved') {
            return false;
        }

        if (($a->resaved_until ?? null) !== ($b->resaved_until ?? null)) {
            return false;
        }

        if (($a->customer_phone ?? '') !== ($b->customer_phone ?? '')) {
            return false;
        }

        if ((string) ($a->vender_id ?? '') !== (string) ($b->vender_id ?? '')) {
            return false;
        }

        if ((string) ($a->user_id ?? '') !== (string) ($b->user_id ?? '')) {
            return false;
        }

        $createdDiff = abs(strtotime((string) $a->created_at) - strtotime((string) $b->created_at));
        if ($createdDiff > 120) {
            return false;
        }

        $aFrom = trim((string) ($a->pickup_point ?? ''));
        $aTo = trim((string) ($a->dropping_point ?? ''));
        $bFrom = trim((string) ($b->pickup_point ?? ''));
        $bTo = trim((string) ($b->dropping_point ?? ''));

        return $aFrom !== '' && $aTo !== '' && $aFrom === $bTo && $aTo === $bFrom;
    }
}

if (!function_exists('round_trip_resaved_partner')) {
    function round_trip_resaved_partner(\App\Models\Booking $booking, ?iterable $pool = null): ?\App\Models\Booking
    {
        if (($booking->payment_status ?? '') !== 'resaved') {
            return null;
        }

        $ref = $booking->transaction_ref_id ?? '';
        if (is_round_trip_resaved_group($ref)) {
            return \App\Models\Booking::query()
                ->where('transaction_ref_id', $ref)
                ->where('payment_status', 'resaved')
                ->where('id', '!=', $booking->id)
                ->first();
        }

        if ($pool !== null) {
            foreach ($pool as $candidate) {
                if ((int) $candidate->id === (int) $booking->id) {
                    continue;
                }

                if (round_trip_resaved_pair_matches($booking, $candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}

if (!function_exists('sort_round_trip_resaved_legs')) {
    /**
     * @param array<int, \App\Models\Booking> $bookings
     * @return array<int, \App\Models\Booking>
     */
    function sort_round_trip_resaved_legs(array $bookings): array
    {
        usort($bookings, function ($a, $b) {
            return strcmp((string) ($a->travel_date ?? ''), (string) ($b->travel_date ?? ''));
        });

        return $bookings;
    }
}

if (!function_exists('group_ticket_list_rows')) {
    /**
     * @param iterable<\App\Models\Booking> $bookings
     * @return array<int, array{type: string, booking?: \App\Models\Booking, bookings?: array, primary: \App\Models\Booking}>
     */
    function group_ticket_list_rows(iterable $bookings): array
    {
        $list = $bookings instanceof \Illuminate\Support\Collection
            ? $bookings->values()->all()
            : array_values(is_array($bookings) ? $bookings : iterator_to_array($bookings));

        $consumed = [];
        $rows = [];

        foreach ($list as $booking) {
            if (in_array($booking->id, $consumed, true)) {
                continue;
            }

            if (($booking->payment_status ?? '') === 'resaved') {
                $partner = round_trip_resaved_partner($booking, $list);
                if ($partner !== null) {
                    $legs = sort_round_trip_resaved_legs([$booking, $partner]);
                    $rows[] = [
                        'type' => 'round_trip_resaved',
                        'bookings' => $legs,
                        'primary' => $legs[0],
                    ];
                    $consumed[] = $booking->id;
                    $consumed[] = $partner->id;
                    continue;
                }
            }

            $rows[] = [
                'type' => 'single',
                'booking' => $booking,
                'primary' => $booking,
            ];
        }

        return $rows;
    }
}

if (!function_exists('booking_luggage_fee')) {
    function booking_luggage_fee($booking): float
    {
        if ((int) ($booking->has_excess_luggage ?? 0) === 1 || (float) ($booking->excess_luggage_fee ?? 0) > 0) {
            return (float) ($booking->excess_luggage_fee ?? 0);
        }

        return 0.0;
    }
}

if (!function_exists('booking_seat_list')) {
    function booking_seat_list($seatString): array
    {
        $seats = array_values(array_filter(array_map('trim', explode(',', (string) $seatString))));

        return !empty($seats) ? $seats : ['N/A'];
    }
}

if (!function_exists('split_amount_across_seats')) {
    function split_amount_across_seats(float $total, int $seatCount, int $seatIndex): float
    {
        $seatCount = max(1, $seatCount);
        if ($seatCount === 1) {
            return round($total, 2);
        }

        $evenShare = round($total / $seatCount, 2);

        if ($seatIndex === 0) {
            return round($total - ($evenShare * ($seatCount - 1)), 2);
        }

        return $evenShare;
    }
}

if (!function_exists('booking_insurance_eligible')) {
    function booking_insurance_eligible(array $form): bool
    {
        $distance = (float) ($form['route_distance'] ?? 0);
        if ($distance <= 99) {
            return false;
        }

        $travelDate = $form['travel_date'] ?? null;
        if (empty($travelDate)) {
            return false;
        }

        try {
            $travelDay = \Carbon\Carbon::parse($travelDate)->timezone('Africa/Nairobi')->format('Y-m-d');
            $today = now('Africa/Nairobi')->format('Y-m-d');

            return $travelDay !== $today;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('insurance_local_rate_display')) {
    function insurance_local_rate_display(): string
    {
        $setting = \App\Models\Setting::first();
        $currency = app()->bound('currency') ? app('currency') : (session('currency', 'TSH'));
        $rate = (float) ($setting->local ?? 0);

        return trim($currency . ' ' . convert_money($rate));
    }
}

if (!function_exists('validate_booking_insurance_selection')) {
    /**
     * @return string|null Translation key for error message
     */
    function validate_booking_insurance_selection($request, array $form): ?string
    {
        if ((int) ($request->Insurance ?? 0) !== 1) {
            return null;
        }

        if (!booking_insurance_eligible($form)) {
            return 'all.insurance_not_available';
        }

        $type = $request->type ?? null;
        $insuranceDate = $request->insuranceDate ?? ($form['insuranceDate'] ?? null);
        if (empty($insuranceDate) || !in_array($type, ['local', 'foreign'], true)) {
            return 'all.insurance_details_required';
        }

        return null;
    }
}

if (!function_exists('calculate_booking_bima_amount')) {
    function calculate_booking_bima_amount($request, array $form, $setting): float
    {
        if ((int) ($request->Insurance ?? 0) !== 1) {
            return 0.0;
        }

        $rate = ($request->type === 'local')
            ? (float) ($setting->local ?? 0)
            : (float) ($setting->international ?? 0);

        $insuranceDate = $form['insuranceDate'] ?? $request->insuranceDate;
        $travelDate = $form['travel_date'] ?? null;

        if (empty($insuranceDate) || empty($travelDate)) {
            return 0.0;
        }

        $days = max(1, abs(\Carbon\Carbon::parse($travelDate)->diffInDays($insuranceDate, false)) + 1);

        return $rate * $days;
    }
}

if (!function_exists('process_booking_insurance_input')) {
    /**
     * Validates insurance selection and updates $busInfo bima fields.
     *
     * @return string|null Translation key for error message
     */
    function process_booking_insurance_input($request, array &$busInfo): ?string
    {
        if ((int) ($request->Insurance ?? 0) !== 1) {
            $busInfo['bima'] = 0;
            $busInfo['bima_amount'] = 0;
            $busInfo['insuranceDate'] = null;

            return null;
        }

        $busInfo['bima'] = 1;
        $busInfo['insuranceDate'] = $request->insuranceDate;

        $errorKey = validate_booking_insurance_selection($request, $busInfo);
        if ($errorKey) {
            return $errorKey;
        }

        $setting = \App\Models\Setting::first();
        $busInfo['bima_amount'] = calculate_booking_bima_amount($request, $busInfo, $setting);

        return null;
    }
}

if (!function_exists('booking_per_seat_payment_amounts')) {
    /**
     * @param  \App\Models\Booking|object  $booking
     */
    function booking_per_seat_payment_amounts($booking, int $seatIndex, int $seatCount): array
    {
        $seatCount = max(1, $seatCount);
        $bookingBusFee = (float) ($booking->busFee ?? 0);
        $bookingLuggage = booking_luggage_fee($booking);
        $bookingInsurance = (float) ($booking->bima_amount ?? 0);
        $storedTotal = (float) ($booking->customer_paid_total ?? 0);

        $ticketFee = split_amount_across_seats($bookingBusFee, $seatCount, $seatIndex);
        $luggageFee = split_amount_across_seats($bookingLuggage, $seatCount, $seatIndex);
        $insurance = split_amount_across_seats($bookingInsurance, $seatCount, $seatIndex);

        if ($storedTotal > 0) {
            $bookingService = max(0, $storedTotal - $bookingBusFee - $bookingLuggage - $bookingInsurance);
            $serviceFee = split_amount_across_seats($bookingService, $seatCount, $seatIndex);
        } else {
            $fareService = app(\App\Services\FareFormulaService::class);
            $serviceFee = $fareService->calculateTravellerServiceFee(
                $ticketFee,
                \App\Models\Setting::first(),
                1
            );
        }

        $amountPaid = $ticketFee + $luggageFee + $insurance + $serviceFee;

        return [
            'breakdownTicketFee' => $ticketFee,
            'breakdownLuggageFee' => $luggageFee,
            'breakdownInsurance' => $insurance,
            'breakdownServiceFee' => $serviceFee,
            'breakdownAmountPaid' => $amountPaid,
        ];
    }
}

if (!function_exists('booking_service_fee')) {
    function booking_service_fee($booking): float
    {
        $serviceFee = (float) ($booking->system_service_fee ?? 0);
        if ($serviceFee <= 0) {
            $serviceFee = (float) ($booking->service ?? 0)
                + (float) ($booking->vender_service ?? 0)
                + (float) ($booking->service_vat ?? 0);
        }

        return $serviceFee;
    }
}

if (!function_exists('booking_payment_amounts')) {
    /**
     * Booking-level fee breakdown for success / status / print views.
     * Use extract(booking_payment_amounts($booking)) in the same Blade scope
     * (included partials do not leak @php variables to the parent).
     *
     * @param  \App\Models\Booking|object|null  $booking
     * @return array{
     *     breakdownTicketFee: float,
     *     breakdownLuggageFee: float,
     *     breakdownInsurance: float,
     *     breakdownServiceFee: float,
     *     breakdownAmountPaid: float,
     *     breakdownUseStoredTotal: bool,
     *     breakdownStoredTotal: float
     * }
     */
    function booking_payment_amounts($booking): array
    {
        if ($booking === null) {
            return [
                'breakdownTicketFee' => 0.0,
                'breakdownLuggageFee' => 0.0,
                'breakdownInsurance' => 0.0,
                'breakdownServiceFee' => 0.0,
                'breakdownAmountPaid' => 0.0,
                'breakdownUseStoredTotal' => false,
                'breakdownStoredTotal' => 0.0,
            ];
        }

        $breakdownTicketFee = (float) ($booking->busFee ?? 0);
        $breakdownLuggageFee = booking_luggage_fee($booking);
        $breakdownInsurance = (float) ($booking->bima_amount ?? 0);
        $breakdownStoredTotal = (float) ($booking->customer_paid_total ?? 0);
        $breakdownServiceFee = booking_service_fee($booking);

        if ($breakdownStoredTotal > 0) {
            $breakdownAmountPaid = $breakdownStoredTotal;
            $breakdownServiceFee = max(0, $breakdownStoredTotal - $breakdownTicketFee - $breakdownLuggageFee - $breakdownInsurance);
            $breakdownUseStoredTotal = true;
        } else {
            if ($breakdownServiceFee <= 0 && $breakdownTicketFee > 0) {
                $fareService = app(\App\Services\FareFormulaService::class);
                $seatCountForFee = $fareService->seatCountFromSeatString($booking->seat ?? null);
                $breakdownServiceFee = $fareService->calculateTravellerServiceFee(
                    $breakdownTicketFee,
                    \App\Models\Setting::first(),
                    $seatCountForFee
                );
            }

            $breakdownAmountPaid = $breakdownTicketFee + $breakdownLuggageFee + $breakdownInsurance + $breakdownServiceFee;
            $breakdownUseStoredTotal = false;
        }

        return [
            'breakdownTicketFee' => $breakdownTicketFee,
            'breakdownLuggageFee' => $breakdownLuggageFee,
            'breakdownInsurance' => $breakdownInsurance,
            'breakdownServiceFee' => $breakdownServiceFee,
            'breakdownAmountPaid' => $breakdownAmountPaid,
            'breakdownUseStoredTotal' => $breakdownUseStoredTotal,
            'breakdownStoredTotal' => $breakdownStoredTotal,
        ];
    }
}

if (!function_exists('manifest_gender_code')) {
    function manifest_gender_code(?string $gender): string
    {
        if ($gender === null || $gender === '' || $gender === 'N/A') {
            return '';
        }

        $normalized = strtolower(trim($gender));

        if (str_starts_with($normalized, 'm')) {
            return 'M';
        }

        if (str_starts_with($normalized, 'f')) {
            return 'F';
        }

        return strtoupper(substr($gender, 0, 1));
    }
}

if (!function_exists('manifest_issue_by')) {
    /**
     * @param  \App\Models\Booking  $booking
     */
    function manifest_issue_by($booking): string
    {
        if ($booking->vender_id && optional($booking->vender)->name) {
            return $booking->vender->name;
        }

        return sales_channel_label($booking->booking_channel);
    }
}

if (!function_exists('booking_to_report_row')) {
    /**
     * @param  \App\Models\Booking  $booking
     */
    function booking_to_report_row($booking): array
    {
        $luggageFee = booking_luggage_fee($booking);
        $serviceFee = booking_service_fee($booking);
        $govLevyOnFare = (float) ($booking->government_levy ?? 0);
        $govLevyOnService = (float) $booking->governmentLeviesOnService->sum('amount');
        $totalGovLevy = $govLevyOnFare + $govLevyOnService;
        $customerTotal = (float) ($booking->customer_paid_total ?? 0);
        $busFee = (float) ($booking->busFee ?? 0);
        $insurance = (float) ($booking->bima_amount ?? 0);

        $rowTotal = $customerTotal > 0
            ? round($customerTotal)
            : round($busFee + $luggageFee + $serviceFee + $insurance);

        $routeFrom = optional($booking->schedule)->from ?? optional(optional($booking->bus)->route)->from ?? 'N/A';
        $routeTo = optional($booking->schedule)->to ?? optional(optional($booking->bus)->route)->to ?? 'N/A';
        $routeLabel = strtoupper(trim($routeFrom . '-' . $routeTo, '-'));
        $discountAmount = round((float) ($booking->discount_amount ?? 0));

        return [
            'booking_code' => $booking->booking_code ?? 'N/A',
            'company_name' => optional($booking->campany)->name ?? 'N/A',
            'route_from' => $routeFrom,
            'route_to' => $routeTo,
            'route_label' => $routeLabel !== '' ? $routeLabel : 'N/A',
            'bus_number' => optional($booking->bus)->bus_number ?? 'N/A',
            'travel_date' => $booking->travel_date ? \Carbon\Carbon::parse($booking->travel_date)->format('Y-m-d') : 'N/A',
            'seat' => $booking->seat ?? 'N/A',
            'pickup_point' => $booking->pickup_point ?? 'N/A',
            'dropping_point' => $booking->dropping_point ?? '',
            'customer_name' => $booking->customer_name ?? 'N/A',
            'customer_phone' => $booking->customer_phone ?? 'N/A',
            'bus_fee' => (string) round($busFee),
            'base_fare' => (string) round($busFee),
            'amount' => $booking->amount ?? '0',
            'luggage_fee' => (string) round($luggageFee),
            'service_fee' => (string) round($serviceFee),
            'commision' => (string) round(($booking->fee ?? 0) + ($booking->vender_fee ?? 0)),
            'service' => $booking->vender_fee ?? 'N/A',
            'vendor_service' => $booking->vender_service ?? 'N/A',
            'discount' => $booking->discount_amount ?? 'N/A',
            'manifest_discount' => (string) $discountAmount,
            'gov_levy' => (string) $totalGovLevy,
            'gov_levy_service' => (string) $govLevyOnService,
            'vat' => $booking->vat ?? 'N/A',
            'total' => (string) $rowTotal,
            'paid_fare' => (string) $rowTotal,
            'gender' => $booking->gender ?? 'N/A',
            'gender_code' => manifest_gender_code($booking->gender ?? null),
            'age' => $booking->age ?? 'N/A',
            'age_group' => $booking->age_group ?? 'N/A',
            'passenger_type' => $booking->age_group ?: 'Adult',
            'infant_child' => $booking->infant_child ?? 0,
            'issue_date' => $booking->created_at ? $booking->created_at->format('d-m-y H:i') : '',
            'issue_by' => manifest_issue_by($booking),
            'id_type' => '',
            'id_number' => '',
            'remarks' => $booking->excess_luggage_description ?? '',
            'excess_luggage' => (int) ($booking->has_excess_luggage ?? 0),
            'excess_luggage_description' => $booking->excess_luggage_description ?? null,
            'excess_luggage_fee' => $luggageFee > 0 ? (string) round($luggageFee) : null,
        ];
    }
}

if (!function_exists('apply_booking_history_date_filter')) {
    /**
     * Apply booking history period or custom date range to a query.
     *
     * @return array{period: ?string, startDate: ?string, endDate: ?string}
     */
    function apply_booking_history_date_filter($query, $request, string $dateColumn = 'created_at'): array
    {
        $period = $request->query('period');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween($dateColumn, [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay(),
            ]);
        } elseif ($period) {
            switch ($period) {
                case 'today':
                    $query->whereDate($dateColumn, today());
                    break;
                case 'week':
                    $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year);
                    break;
                case 'year':
                    $query->whereYear($dateColumn, now()->year);
                    break;
            }
        }

        return [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }
}

if (! function_exists('transaction_payment_detail')) {
    /**
     * Resolve the payout account shown to admins (mobile money number or bank account).
     */
    function transaction_payment_detail($transaction): string
    {
        $unknown = __('system.common.unknown');
        $method = strtolower(trim((string) ($transaction->payment_method ?? '')));

        if ($method === 'bank') {
            $bankName = '';
            $bankNumber = '';

            if ((int) ($transaction->vender_id ?? 0) > 0 && $transaction->user?->VenderAccount) {
                $bankName = trim((string) ($transaction->user->VenderAccount->bank_name ?? ''));
                $bankNumber = trim((string) ($transaction->user->VenderAccount->bank_number ?? ''));
            }

            if ($bankNumber === '') {
                $bankNumber = trim((string) ($transaction->payment_number ?? ''));
            }

            if ($bankName !== '' && $bankNumber !== '') {
                return $bankName . ' — ' . $bankNumber;
            }

            return $bankNumber !== '' ? $bankNumber : $unknown;
        }

        $paymentNumber = trim((string) ($transaction->payment_number ?? ''));

        return $paymentNumber !== '' ? $paymentNumber : $unknown;
    }
}