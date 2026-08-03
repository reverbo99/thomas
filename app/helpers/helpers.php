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
    /**
     * The bus owner's excess luggage fee as charged on the booking (gross).
     * This is the bus owner's figure — see system_luggage_fee() for the system's cut.
     */
    function booking_luggage_fee($booking): float
    {
        if ((int) ($booking->has_excess_luggage ?? 0) === 1 || (float) ($booking->excess_luggage_fee ?? 0) > 0) {
            return (float) ($booking->excess_luggage_fee ?? 0);
        }

        return 0.0;
    }
}

if (!function_exists('system_luggage_percent')) {
    /**
     * Percentage of the bus owner's excess luggage fee that the system retains.
     * Single source of truth for the split — settlement, dashboard, system income
     * and the exports must all agree, so never hardcode the rate at the call site.
     */
    function system_luggage_percent(): float
    {
        return \App\Services\FareFormulaService::SYSTEM_LUGGAGE_PERCENT;
    }
}

if (!function_exists('system_luggage_fee')) {
    /**
     * The system's share of a booking's excess luggage fee (system income).
     */
    function system_luggage_fee($booking): float
    {
        return round(booking_luggage_fee($booking) * system_luggage_percent() / 100, 2);
    }
}

if (!function_exists('bus_owner_luggage_fee')) {
    /**
     * The bus owner's share of a booking's excess luggage fee, after the system's cut.
     */
    function bus_owner_luggage_fee($booking): float
    {
        return round(booking_luggage_fee($booking) - system_luggage_fee($booking), 2);
    }
}

if (!function_exists('government_levy_percent')) {
    function government_levy_percent(): float
    {
        return \App\Services\FareFormulaService::DEFAULT_GOVERNMENT_LEVY_PERCENT;
    }
}

if (!function_exists('booking_gross_service_fee')) {
    /**
     * Traveller-facing / full service fee before vendor split (bookings.system_service_fee).
     */
    function booking_gross_service_fee($booking): float
    {
        $gross = (float) ($booking->system_service_fee ?? 0);
        if ($gross > 0) {
            return $gross;
        }

        return max(0.0, (float) ($booking->service ?? 0) + (float) ($booking->vender_service ?? 0));
    }
}

if (!function_exists('booking_system_retained_service_fee')) {
    /**
     * Platform-retained service fee after vendor share and government levy on the full service fee.
     * Prefer this over raw payment_fees.amount — older vendor settlements levied 5% on the
     * after-vendor pool and overstated the system's retained fee.
     */
    function booking_system_retained_service_fee($booking): float
    {
        $gross = booking_gross_service_fee($booking);
        if ($gross <= 0) {
            return 0.0;
        }

        $vendorService = (float) ($booking->vender_service ?? 0);
        $poolAfterVendor = max(0.0, $gross - $vendorService);
        $levyOnFullService = $gross * (government_levy_percent() / 100);

        return max(0.0, round($poolAfterVendor - $levyOnFullService, 2));
    }
}

if (!function_exists('booking_government_levy_on_fare')) {
    function booking_government_levy_on_fare($booking): float
    {
        $stored = (float) ($booking->government_levy ?? 0);
        if ($stored > 0) {
            return round($stored, 2);
        }

        return round((float) ($booking->busFee ?? 0) * government_levy_percent() / 100, 2);
    }
}

if (!function_exists('booking_government_levy_on_service')) {
    /**
     * Levied on the FULL service fee before the vendor's cut. Older vendor settlements
     * stored 5% of the after-vendor pool in government_levies, so recalculate from the
     * booking columns and only fall back to the stored rows when no service fee is known.
     */
    function booking_government_levy_on_service($booking): float
    {
        $gross = booking_gross_service_fee($booking);
        if ($gross > 0) {
            return round($gross * government_levy_percent() / 100, 2);
        }

        return round((float) $booking->governmentLeviesOnService->sum('amount'), 2);
    }
}

if (!function_exists('booking_total_government_levy')) {
    function booking_total_government_levy($booking): float
    {
        return round(booking_government_levy_on_fare($booking) + booking_government_levy_on_service($booking), 2);
    }
}

if (!function_exists('government_levy_on_amount')) {
    function government_levy_on_amount(float $baseAmount): float
    {
        return round(max(0.0, $baseAmount) * government_levy_percent() / 100, 2);
    }
}

if (!function_exists('booking_gross_commission')) {
    /** Gross commission extracted from the fare (system remainder + vendor commission). */
    function booking_gross_commission($booking): float
    {
        return max(0.0, (float) ($booking->fee ?? 0) + (float) ($booking->vender_fee ?? 0));
    }
}

if (!function_exists('booking_government_levy_on_commission')) {
    function booking_government_levy_on_commission($booking): float
    {
        return government_levy_on_amount(booking_gross_commission($booking));
    }
}

if (!function_exists('booking_government_levy_on_luggage')) {
    function booking_government_levy_on_luggage($booking): float
    {
        return government_levy_on_amount(booking_luggage_fee($booking));
    }
}

if (!function_exists('booking_seat_list')) {
    function booking_seat_list($seatString): array
    {
        $seats = array_values(array_filter(array_map('trim', explode(',', (string) $seatString))));

        return !empty($seats) ? $seats : ['N/A'];
    }
}

if (!function_exists('booking_passengers_list')) {
    /**
     * @param  \App\Models\Booking|object|array  $booking
     */
    function booking_passengers_list($booking): array
    {
        $passengers = is_array($booking)
            ? ($booking['passengers'] ?? null)
            : ($booking->passengers ?? null);

        if (is_string($passengers)) {
            $passengers = json_decode($passengers, true);
        }

        return is_array($passengers) ? array_values($passengers) : [];
    }
}

if (!function_exists('booking_passengers_for_storage')) {
    function booking_passengers_for_storage(array $form): ?array
    {
        $details = $form['passenger_details'] ?? null;

        if (!is_array($details) || empty($details)) {
            return null;
        }

        return array_values($details);
    }
}

if (!function_exists('booking_passenger_name_for_seat')) {
    /**
     * @param  \App\Models\Booking|object|array  $booking
     */
    function booking_passenger_name_for_seat($booking, int $seatIndex, ?string $seatLabel = null): string
    {
        return booking_passenger_field_for_seat($booking, $seatIndex, $seatLabel, 'name', 'customer_name');
    }
}

if (!function_exists('booking_passenger_phone_for_seat')) {
    /**
     * @param  \App\Models\Booking|object|array  $booking
     */
    function booking_passenger_phone_for_seat($booking, int $seatIndex, ?string $seatLabel = null): string
    {
        return booking_passenger_field_for_seat($booking, $seatIndex, $seatLabel, 'phone', 'customer_phone');
    }
}

if (!function_exists('booking_passenger_field_for_seat')) {
    /**
     * @param  \App\Models\Booking|object|array  $booking
     */
    function booking_passenger_field_for_seat(
        $booking,
        int $seatIndex,
        ?string $seatLabel,
        string $passengerKey,
        string $bookingFallbackKey
    ): string {
        $fallback = trim((string) (is_array($booking)
            ? ($booking[$bookingFallbackKey] ?? '')
            : ($booking->{$bookingFallbackKey} ?? '')));

        if ($fallback === '' && $bookingFallbackKey === 'customer_phone') {
            $fallback = trim((string) (is_array($booking)
                ? ($booking['payment_number'] ?? '')
                : ($booking->payment_number ?? '')));
        }

        if ($fallback === '') {
            $fallback = 'N/A';
        }

        $passengers = booking_passengers_list($booking);
        if (empty($passengers)) {
            return $fallback;
        }

        $resolve = function ($passenger) use ($passengerKey) {
            if (!is_array($passenger)) {
                return '';
            }

            return trim((string) ($passenger[$passengerKey] ?? ''));
        };

        if (isset($passengers[$seatIndex])) {
            $value = $resolve($passengers[$seatIndex]);
            if ($value !== '') {
                return $value;
            }
        }

        if ($seatLabel !== null) {
            foreach ($passengers as $passenger) {
                if (!is_array($passenger)) {
                    continue;
                }

                $passengerSeat = trim((string) ($passenger['seat'] ?? ''));
                if ($passengerSeat !== '' && strcasecmp($passengerSeat, trim($seatLabel)) === 0) {
                    $value = $resolve($passenger);
                    if ($value !== '') {
                        return $value;
                    }
                }
            }
        }

        return $fallback;
    }
}

if (!function_exists('booking_ticket_pdf_filename')) {
    /**
     * @param  \App\Models\Booking|object|array  $booking
     */
    function booking_ticket_pdf_filename($booking): string
    {
        $bookingCode = trim((string) (is_array($booking)
            ? ($booking['booking_code'] ?? '')
            : ($booking->booking_code ?? '')));

        $seatCount = count(booking_seat_list(is_array($booking)
            ? ($booking['seat'] ?? '')
            : ($booking->seat ?? '')));

        if ($seatCount > 1 && $bookingCode !== '') {
            return $bookingCode . '_tickets.pdf';
        }

        $name = trim((string) (is_array($booking)
            ? ($booking['customer_name'] ?? '')
            : ($booking->customer_name ?? '')));

        if ($name === '') {
            $name = $bookingCode !== '' ? $bookingCode : 'ticket';
        }

        return preg_replace('/[^\w\-]+/u', '_', $name) . '.pdf';
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
            // Prefer residual from customer_paid_total when it yields a positive
            // service share; never wipe a known column-based service fee to 0.
            $residualService = max(0, $breakdownStoredTotal - $breakdownTicketFee - $breakdownLuggageFee - $breakdownInsurance);
            if ($residualService > 0) {
                $breakdownServiceFee = $residualService;
            } elseif ($breakdownServiceFee <= 0) {
                $breakdownServiceFee = 0.0;
            }
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
        $govLevyOnFare = booking_government_levy_on_fare($booking);
        $govLevyOnService = booking_government_levy_on_service($booking);
        $totalGovLevy = booking_total_government_levy($booking);
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
            'gov_levy' => (string) $govLevyOnFare,
            'gov_levy_service' => (string) $govLevyOnService,
            'gov_levy_total' => (string) $totalGovLevy,
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

if (!function_exists('schedule_seat_maps')) {
    /**
     * Build a booked-seat map for a set of schedules, keyed by schedule id.
     *
     * Each value is an array of `seat label => passenger name` for that
     * schedule's bus on its travel date. Matches the customer seat-selection
     * logic: seats are a comma-separated list on Paid/Reserved/resaved bookings.
     *
     * @param  iterable  $schedules  Schedules with a loaded `bus` relation.
     * @return array<int, array<string, string>>
     */
    function schedule_seat_maps($schedules): array
    {
        $schedules = collect($schedules);

        $busIds = $schedules->pluck('bus_id')->filter()->unique()->values();
        $dates = $schedules
            ->pluck('schedule_date')
            ->filter()
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        if ($busIds->isEmpty() || empty($dates)) {
            return [];
        }

        $grouped = \App\Models\Booking::whereIn('bus_id', $busIds)
            ->whereIn('travel_date', $dates)
            ->whereIn('payment_status', ['Paid', 'Reserved', 'resaved'])
            ->get(['bus_id', 'travel_date', 'seat', 'customer_name', 'schedule_id'])
            ->groupBy(fn ($booking) => $booking->bus_id . '|' . \Carbon\Carbon::parse($booking->travel_date)->format('Y-m-d'));

        $schedulesPerKey = \App\Models\Schedule::whereIn('bus_id', $busIds)
            ->whereIn('schedule_date', $dates)
            ->selectRaw('bus_id, schedule_date, count(*) as schedule_count')
            ->groupBy('bus_id', 'schedule_date')
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->bus_id . '|' . \Carbon\Carbon::parse($row->schedule_date)->format('Y-m-d') => (int) $row->schedule_count,
            ]);

        $maps = [];
        foreach ($schedules as $schedule) {
            $bus = $schedule->bus ?? null;
            if (!$bus) {
                continue;
            }

            $date = $schedule->schedule_date ? \Carbon\Carbon::parse($schedule->schedule_date)->format('Y-m-d') : null;
            $key = $bus->id . '|' . $date;
            // When one bus runs several departures on the same date, bus+date alone
            // would credit every booking to each departure, so honour schedule_id.
            $splitBySchedule = ($schedulesPerKey[$key] ?? 1) > 1;

            $seatMap = [];
            foreach (($grouped->get($key) ?? collect()) as $booking) {
                if ($splitBySchedule && (int) $booking->schedule_id > 0 && (int) $booking->schedule_id !== (int) $schedule->id) {
                    continue;
                }

                foreach (explode(',', (string) $booking->seat) as $seat) {
                    $seat = trim($seat);
                    if ($seat !== '') {
                        $seatMap[$seat] = $booking->customer_name ?? '';
                    }
                }
            }
            $maps[$schedule->id] = $seatMap;
        }

        return $maps;
    }
}

if (!function_exists('apply_booking_history_column_filters')) {
    /**
     * Filter a booking history query by bus name, plate number, departure
     * date/time, driver name and conductor name. Each is optional and applied
     * only when present, so it composes with date-range and channel filters.
     * Shared by the admin (system) and bus-owner booking history pages.
     */
    function apply_booking_history_column_filters($query, $request): void
    {
        if ($request->filled('bus_name')) {
            $busName = $request->bus_name;
            $query->whereHas('bus.busname', function ($q) use ($busName) {
                $q->where('name', 'like', "%{$busName}%");
            });
        }

        if ($request->filled('bus_number')) {
            $busNumber = $request->bus_number;
            $query->whereHas('bus', function ($q) use ($busNumber) {
                $q->where('bus_number', 'like', "%{$busNumber}%");
            });
        }

        if ($request->filled('driver')) {
            $driver = $request->driver;
            $query->whereHas('bus', function ($q) use ($driver) {
                $q->where('driver_name', 'like', "%{$driver}%")
                    ->orWhere('driver_name_2', 'like', "%{$driver}%");
            });
        }

        if ($request->filled('conductor')) {
            $conductor = $request->conductor;
            $query->whereHas('bus', function ($q) use ($conductor) {
                $q->where('conductor_name', 'like', "%{$conductor}%")
                    ->orWhere('conductor', 'like', "%{$conductor}%");
            });
        }

        if ($request->filled('departure_date')) {
            $departureDate = $request->departure_date;
            $query->whereHas('schedule', function ($q) use ($departureDate) {
                $q->whereDate('schedule_date', $departureDate);
            });
        }

        if ($request->filled('departure_time')) {
            $departureTime = $request->departure_time;
            $query->whereHas('schedule', function ($q) use ($departureTime) {
                $q->where('start', 'like', "%{$departureTime}%");
            });
        }
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

        if ($period === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();

            if ($start->gt($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            $query->whereBetween($dateColumn, [$start, $end]);
        } elseif ($period && $period !== 'custom') {
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

if (! function_exists('transaction_is_bank_payment_method')) {
    function transaction_is_bank_payment_method(?string $method): bool
    {
        $normalized = strtolower(trim((string) $method));

        return $normalized === 'bank' || str_contains($normalized, 'bank');
    }
}

if (! function_exists('transaction_vendor_user')) {
    /**
     * Resolve the vendor user for a payout transaction (vender_id takes precedence).
     */
    function transaction_vendor_user($transaction): ?\App\Models\User
    {
        if (! $transaction) {
            return null;
        }

        $vendorUserId = (int) ($transaction->vender_id ?? 0);
        if ($vendorUserId <= 0) {
            $vendorUserId = (int) ($transaction->user_id ?? 0);
        }

        if ($vendorUserId <= 0) {
            return $transaction->user ?? null;
        }

        if ($transaction->user && (int) $transaction->user->id === $vendorUserId) {
            return $transaction->user;
        }

        return \App\Models\User::with('VenderAccount')->find($vendorUserId);
    }
}

if (! function_exists('transaction_payment_detail')) {
    /**
     * Resolve the payout account shown to admins (mobile money number or bank account).
     */
    function transaction_payment_detail($transaction): string
    {
        $unknown = __('system.common.unknown');

        if ($transaction instanceof \App\Models\Transaction) {
            $model = $transaction;
        } elseif (is_object($transaction) && ! empty($transaction->id)) {
            $model = \App\Models\Transaction::with(['user.VenderAccount'])->find($transaction->id) ?? $transaction;
        } else {
            $model = $transaction;
        }

        $method = (string) ($model->payment_method ?? '');

        if (transaction_is_bank_payment_method($method)) {
            $bankName = '';
            $bankNumber = '';

            $vendorUser = transaction_vendor_user($model);
            if ($vendorUser) {
                $account = $vendorUser->relationLoaded('VenderAccount')
                    ? $vendorUser->VenderAccount
                    : $vendorUser->VenderAccount()->first();

                if ($account) {
                    $bankName = trim((string) ($account->bank_name ?? ''));
                    $bankNumber = trim((string) ($account->bank_number ?? ''));
                }
            }

            $stored = trim((string) ($model->payment_number ?? ''));

            // Prefer profile bank; fall back to stored snapshot (may already be "Bank — Account")
            if ($bankNumber === '' && $stored !== '') {
                if (str_contains($stored, '—') || str_contains($stored, '-')) {
                    return $stored;
                }
                $bankNumber = $stored;
            }

            if ($bankName !== '' && $bankNumber !== '') {
                // Avoid duplicating if stored already includes bank name
                if ($stored !== '' && str_contains($stored, $bankNumber)) {
                    return $stored;
                }

                return $bankName . ' — ' . $bankNumber;
            }

            return $bankNumber !== '' ? $bankNumber : ($stored !== '' ? $stored : $unknown);
        }

        $paymentNumber = trim((string) ($model->payment_number ?? ''));

        return $paymentNumber !== '' ? $paymentNumber : $unknown;
    }
}

if (!function_exists('manifest_passenger_is_infant')) {
    /**
     * @param  array<string, mixed>  $passenger
     */
    function manifest_passenger_is_infant(array $passenger): bool
    {
        if (! empty($passenger['is_infant'])) {
            return filter_var($passenger['is_infant'], FILTER_VALIDATE_BOOLEAN);
        }

        if (! empty($passenger['infant_child'])) {
            return (int) $passenger['infant_child'] === 1
                || filter_var($passenger['infant_child'], FILTER_VALIDATE_BOOLEAN);
        }

        $type = strtolower(trim((string) ($passenger['age_group'] ?? $passenger['passenger_type'] ?? '')));

        return in_array($type, ['infant', 'baby', 'newborn'], true);
    }
}

if (!function_exists('booking_has_lap_infant')) {
    /**
     * Lap / accompanying infant flagged on the booking (`bookings.infant_child`),
     * not a seated passenger in the passengers JSON.
     *
     * @param  \App\Models\Booking|object|array  $booking
     */
    function booking_has_lap_infant($booking): bool
    {
        $flag = is_array($booking)
            ? ($booking['infant_child'] ?? 0)
            : ($booking->infant_child ?? 0);

        return (int) $flag === 1 || $flag === true || $flag === '1';
    }
}

if (!function_exists('manifest_infant_companion_row')) {
    /**
     * Synthetic manifest row for a lap infant travelling with a paying passenger.
     *
     * @param  array<string, mixed>  $baseRow
     * @return array<string, mixed>
     */
    function manifest_infant_companion_row(array $baseRow): array
    {
        $adultName = strtoupper(trim((string) ($baseRow['customer_name'] ?? '')));
        if ($adultName === '' || $adultName === 'N/A') {
            $label = __('vender/history.infant_passenger');
        } else {
            $label = __('vender/history.infant_with_passenger', ['name' => $adultName]);
        }

        $row = $baseRow;
        $row['seat'] = '—';
        $row['customer_name'] = $label;
        $row['gender_code'] = '';
        $row['gender'] = '';
        $row['passenger_type'] = 'INFANT';
        $row['age_group'] = 'Infant';
        $row['infant_child'] = 1;
        $row['id_type'] = '';
        $row['id_number'] = '';
        $row['base_fare'] = '0';
        $row['manifest_discount'] = '0';
        $row['paid_fare'] = '0';
        $row['remarks'] = __('vender/history.infant_lap_remark');
        $row['is_staff'] = false;
        $row['is_infant_companion'] = true;

        return $row;
    }
}

if (!function_exists('expand_bookings_to_manifest_rows')) {
    /**
     * Expand a bookings collection into individual passenger rows for the
     * passenger manifest, including per-passenger detail from the JSON
     * `passengers` column.  When a booking has no per-passenger breakdown
     * (legacy data), a single row is emitted so nothing is lost.
     *
     * Lap infants (`bookings.infant_child`) are appended as their own rows when
     * they are not already present in the passengers JSON.
     *
     * @param  \Illuminate\Support\Collection  $bookings  Eloquent Booking collection (with relations loaded)
     * @param  array  $rows  Output of booking_to_report_row() — array of associative arrays.
     * @return array  Expanded rows, one entry per passenger (or one per booking as fallback).
     */
    function expand_bookings_to_manifest_rows($bookings, array $rows): array
    {
        $expanded = [];
        $indexed = collect($rows)->keyBy('booking_code');

        foreach ($bookings as $booking) {
            $bookingCode = $booking->booking_code ?? '';
            $baseRow = $indexed->get($bookingCode);
            if ($baseRow === null) {
                continue;
            }

            $passengers = booking_passengers_list($booking);
            $seatLabels = booking_seat_list($booking->seat ?? '');
            $emittedInfant = false;
            $bookingRows = [];

            if (empty($passengers)) {
                $isSeatedInfant = manifest_passenger_is_infant([
                    'age_group' => $baseRow['age_group'] ?? null,
                    'passenger_type' => $baseRow['passenger_type'] ?? null,
                    'infant_child' => 0,
                ]);
                $row = $baseRow;
                $row['infant_child'] = $isSeatedInfant ? 1 : 0;
                if ($isSeatedInfant) {
                    $row['passenger_type'] = 'INFANT';
                    $emittedInfant = true;
                }
                $bookingRows[] = $row;
            } else {
                foreach ($passengers as $idx => $passenger) {
                    if (! is_array($passenger)) {
                        continue;
                    }

                    $seatLabel = $seatLabels[$idx] ?? ($passenger['seat'] ?? '');
                    $passengerName = trim((string) ($passenger['name'] ?? ''));
                    $passengerPhone = trim((string) ($passenger['phone'] ?? ''));
                    $isInfant = manifest_passenger_is_infant($passenger);

                    $row = $baseRow;
                    $row['seat'] = $seatLabel;
                    $row['customer_name'] = $passengerName !== '' ? $passengerName : ($baseRow['customer_name'] ?? 'N/A');
                    $row['customer_phone'] = $passengerPhone !== '' ? $passengerPhone : ($baseRow['customer_phone'] ?? 'N/A');
                    $row['age_group'] = $passenger['age_group'] ?? ($isInfant ? 'Infant' : ($baseRow['age_group'] ?? 'Adult'));
                    $row['passenger_type'] = $isInfant
                        ? 'INFANT'
                        : ($passenger['age_group'] ?? $baseRow['passenger_type'] ?? 'Adult');
                    $row['infant_child'] = $isInfant ? 1 : 0;
                    $row['gender_code'] = manifest_gender_code($passenger['gender'] ?? null);
                    $row['gender'] = $passenger['gender'] ?? $baseRow['gender'] ?? '';
                    $row['id_type'] = $passenger['id_type'] ?? $baseRow['id_type'] ?? '';
                    $row['id_number'] = $passenger['id_number'] ?? $baseRow['id_number'] ?? '';
                    $row['is_staff'] = false;

                    if ($isInfant) {
                        $emittedInfant = true;
                    }

                    $bookingRows[] = $row;
                }
            }

            if (booking_has_lap_infant($booking) && ! $emittedInfant && ! empty($bookingRows)) {
                $companionBase = $bookingRows[0];
                $companionBase['infant_child'] = 0;
                $bookingRows[0] = $companionBase;
                $bookingRows[] = manifest_infant_companion_row($companionBase);
            }

            foreach ($bookingRows as $bookingRow) {
                $expanded[] = $bookingRow;
            }
        }

        return $expanded;
    }
}

if (!function_exists('booking_seat_list')) {
    /**
     * Split a comma-separated seat string into an indexed array.
     */
    function booking_seat_list(?string $seatString): array
    {
        if ($seatString === null || trim($seatString) === '') {
            return [];
        }

        return array_map('trim', explode(',', $seatString));
    }
}