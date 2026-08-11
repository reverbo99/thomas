@php
    $inlineUid = $inlineUid ?? 'ib_seats';
    $br = booking_routes();
    $inlinePrepareUrl = $inlinePrepareUrl ?? route($br['inline_prepare']);
    $inlineWalletUrl = $inlineWalletUrl ?? route($br['inline_wallet']);
    $distance = $info['route_distance'] ?? 0;
    $mode = [
        '10' => 'LUXURY',
        '20' => 'UPPER-SEMILUXURY',
        '30' => 'LOWER-SEMILUXURY',
        '40' => 'ORDINARY',
    ];
    $busType = isset($car->bus_type) && array_key_exists($car->bus_type, $mode)
        ? $mode[$car->bus_type]
        : __('all.normal_seat');
    $displayPrice = session('currency') == 'Usd'
        ? number_format(($price ?? 0) / ($usdToTzs ?? 2500), 2)
        : ($price ?? 0);
    $insuranceForm = array_merge(session('booking_form', []), [
        'route_distance' => $distance,
        'travel_date' => $info['travel_date'] ?? session('booking_form.travel_date'),
    ]);
    $insuranceEligible = booking_insurance_eligible($insuranceForm);
    $luggageFeePerKg = (float) (\App\Models\Setting::first()->excess_luggage_fee_per_kg ?? 0);
    if ($luggageFeePerKg <= 0) {
        $luggageFeePerKg = 2500;
    }
@endphp

<div class="inline-wizard" data-inline-wizard data-inline-uid="{{ $inlineUid }}">
    @include('test.partials.booking_steps', [
        'currentStep' => 2,
        'interactive' => true,
        'steps' => [
            1 => ['label' => __('all.step_pickup_drop'), 'icon' => 'fa-map-marker-alt', 'key' => 'pickup'],
            2 => ['label' => __('all.step_select_seats'), 'icon' => 'fa-chair', 'key' => 'seats'],
            3 => ['label' => __('all.step_details'), 'icon' => 'fa-user', 'key' => 'extras'],
            4 => ['label' => __('all.step_payment'), 'icon' => 'fa-credit-card', 'key' => 'payment'],
        ],
    ])

    <div class="inline-booking-panel__error booking-alert booking-alert--error hidden" role="alert"></div>

    {{-- Step 2: Compact seats + per-seat passengers --}}
    <div class="inline-wizard__pane" data-wizard-pane="seats">
        @if ($distance > 0)
            <p class="inline-wizard__km">
                <i class="fas fa-road" aria-hidden="true"></i>
                {{ __('all.km_total_distance', ['distance' => number_format((float) $distance, 1)]) }}
            </p>
        @endif

        <div class="inline-wizard__seats-layout">
            <div class="inline-wizard__seats-col">
                <div class="inline-seats-wrap">
                    <div id="seatMapGrid_{{ $inlineUid }}" class="seat-map-grid seat-map-grid--compact"></div>
                    <div id="seatMapFallback_{{ $inlineUid }}" class="seat-map seat-map--compact" style="display:none;"></div>
                </div>

                <div class="inline-wizard__legend">
                    <span><span class="legend-swatch seat-available-legend"></span> {{ __('customer/busroot.available') }}</span>
                    <span><span class="legend-swatch seat-selected-legend"></span> {{ __('customer/busroot.selected') }}</span>
                    <span><span class="legend-swatch seat-booked-legend"></span> {{ __('customer/busroot.booked') }}</span>
                </div>
            </div>

            <div id="passengersWrap_{{ $inlineUid }}" class="inline-passengers">
                <h4 class="inline-passengers__title">
                    <i class="fas fa-users" aria-hidden="true"></i> {{ __('customer/busroot.passenger_details') }}
                </h4>
                <p class="inline-passengers__empty" id="passengersEmpty_{{ $inlineUid }}">
                    {{ __('all.select_seat_passenger_hint') }}
                </p>
                <div id="passengersList_{{ $inlineUid }}" class="inline-passengers__list"></div>
            </div>
        </div>

        <div class="inline-wizard__totals">
            <span>{{ __('customer/busroot.selected_seats') }}: <strong id="selectedSeatsList_{{ $inlineUid }}">{{ __('customer/busroot.none') }}</strong></span>
            <span>{{ __('customer/busroot.total_amount') }}: <strong id="totalAmount_{{ $inlineUid }}">0</strong> {{ $currency }}</span>
        </div>

        <div class="inline-booking-actions">
            <button type="button" class="page-btn page-btn--outline" data-inline-nav-back="pickup">
                {{ __('all.back_button') }}
            </button>
            <button type="button"
                class="page-btn disabled:opacity-50 disabled:cursor-not-allowed"
                id="wizardNextExtras_{{ $inlineUid }}"
                data-wizard-next="extras"
                disabled>
                {{ __('all.next') }}
            </button>
        </div>
    </div>

    {{-- Step 3: Extras (image fields) --}}
    <div class="inline-wizard__pane hidden" data-wizard-pane="extras">
        <form class="inline-extras-form" data-inline-extras-form data-inline-uid="{{ $inlineUid }}">
            @csrf
            <div class="inline-extras-form__fields">
                <div class="inline-extras-form__row inline-extras-form__row--full">
                    <label class="inline-extras-form__check">
                        <input type="checkbox" name="infant_child" value="1" id="infantChild_{{ $inlineUid }}">
                        <span>{{ __('customer/busroot.user_has_infant_child') }}</span>
                    </label>
                </div>

                <div class="inline-extras-form__row inline-extras-form__row--full">
                    <label class="inline-extras-form__check">
                        <input type="checkbox" name="excess_luggage" value="1" id="excessLuggage_{{ $inlineUid }}" data-inline-luggage-toggle>
                        <span>{{ __('customer/busroot.excess_luggage', [
                            'dimensions' => '60X45X50',
                            'weight' => '20kg',
                            'fee' => ($currency . ' ' . convert_money($luggageFeePerKg) . '/kg'),
                        ]) }}</span>
                    </label>
                    <div class="inline-extras-form__luggage-fields hidden mt-2" data-inline-luggage-fields>
                        <div class="booking-field mb-2">
                            <label class="booking-field__label" for="excessLuggageDesc_{{ $inlineUid }}">{{ __('customer/busroot.excess_luggage_description') }}</label>
                            <input type="text" class="page-input" name="excess_luggage_description" id="excessLuggageDesc_{{ $inlineUid }}"
                                placeholder="{{ __('customer/busroot.e_g_1_extra_bag_large_box') }}" disabled>
                        </div>
                        <div class="booking-field">
                            <label class="booking-field__label" for="estimatedWeight_{{ $inlineUid }}">{{ __('customer/busroot.estimated_weight_kg') }}</label>
                            <input type="number" step="0.1" min="0" class="page-input" name="estimated_weight" id="estimatedWeight_{{ $inlineUid }}"
                                placeholder="20" disabled>
                        </div>
                    </div>
                </div>

                @if ($insuranceEligible)
                <div class="inline-extras-form__row inline-extras-form__row--full">
                    <label class="inline-extras-form__check">
                        <input type="checkbox" name="Insurance" value="1" id="insurance_{{ $inlineUid }}" data-inline-insurance-toggle>
                        <span>{{ __('all.add_insurance') }} ({{ insurance_local_rate_display() }}/{{ __('all.day') ?? 'day' }})</span>
                    </label>
                </div>

                <div class="inline-extras-form__row inline-extras-form__row--full hidden" data-inline-insurance-fields>
                    <div class="inline-extras-form__row">
                        <div class="booking-field">
                            <label class="booking-field__label" for="insuranceType_{{ $inlineUid }}">{{ __('all.insurance_type') }}</label>
                            <select class="page-input" name="type" id="insuranceType_{{ $inlineUid }}" disabled>
                                <option value="local" selected>{{ __('customer/busroot.local') }}</option>
                                <option value="foreign">{{ __('customer/busroot.foreign') }}</option>
                            </select>
                        </div>
                        <div class="booking-field">
                            <label class="booking-field__label" for="insuranceDate_{{ $inlineUid }}">{{ __('all.insurance_date') }}</label>
                            <input type="date" class="page-input" name="insuranceDate" id="insuranceDate_{{ $inlineUid }}"
                                value="{{ session('booking_form.travel_date') ?? now()->format('Y-m-d') }}" disabled>
                        </div>
                    </div>
                </div>
                @endif

                <div class="inline-extras-form__row inline-extras-form__row--full">
                    <div class="booking-field">
                        <label class="booking-field__label" for="discount_{{ $inlineUid }}">{{ __('customer/busroot.discount_coupon') }}</label>
                        <input type="text" class="page-input" name="discount" id="discount_{{ $inlineUid }}" placeholder="">
                    </div>
                </div>

                <div class="inline-extras-form__row">
                    <div class="booking-field">
                        <label class="booking-field__label">{{ __('customer/busroot.seat_class') }}</label>
                        <input type="text" class="page-input page-input--readonly" value="{{ $busType }}" readonly>
                    </div>
                    <div class="booking-field">
                        <label class="booking-field__label">{{ __('customer/busroot.fare') }}</label>
                        <input type="text" class="page-input page-input--readonly" id="fareDisplay_{{ $inlineUid }}" value="{{ $displayPrice }}" readonly>
                    </div>
                </div>

                <div class="inline-extras-form__row inline-extras-form__row--full">
                    <div class="booking-field">
                        <label class="booking-field__label" for="walletKey_{{ $inlineUid }}">{{ __('all.temp_wallet_key') }}</label>
                        <div class="inline-wallet-field">
                            <input type="text" class="page-input" name="key" id="walletKey_{{ $inlineUid }}" placeholder="">
                            <span class="inline-wallet-field__amount" id="walletAmount_{{ $inlineUid }}">
                                {{ __('all.amount_label', ['amount' => '0.00', 'currency' => $currency]) }}
                            </span>
                            <input type="hidden" name="amount_cancel" id="walletAmountHidden_{{ $inlineUid }}" value="0">
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="selected_seats" id="hiddenSelectedSeats_{{ $inlineUid }}" value="">
            <input type="hidden" name="total_amount" id="hiddenTotalAmount_{{ $inlineUid }}" value="0">
            <input type="hidden" name="passengers" id="hiddenPassengers_{{ $inlineUid }}" value="">

            <div class="inline-booking-actions">
                <button type="button" class="page-btn page-btn--outline" data-inline-nav-back="seats">
                    {{ __('all.back_button') }}
                </button>
                <button type="submit" class="page-btn">
                    {{ __('all.next') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Step 4: Payment (loaded via AJAX) --}}
    <div class="inline-wizard__pane hidden" data-wizard-pane="payment"></div>

    <script type="application/json" class="inline-seat-config">
        {!! json_encode([
            'inlineUid' => $inlineUid,
            'seatPrice' => $price,
            'bookedSeats' => $booked_seats ?? [],
            'totalSeats' => $car->total_seats,
            'layoutRaw' => $car->seate_json ?? null,
            'maxSeats' => 5,
            'currency' => $currency,
            'usdToTzs' => $usdToTzs ?? 2500,
            'noneLabel' => __('customer/busroot.none'),
            'maxSeatsMsg' => __('customer/busroot.max_seats_limit'),
            'useUsd' => session('currency') == 'Usd',
            'ageGroupLabel' => __('customer/busroot.age_group'),
            'adultLabel' => __('customer/busroot.adult'),
            'childLabel' => __('customer/busroot.child'),
            'seniorLabel' => __('customer/busroot.senior'),
            'infantLabel' => __('customer/busroot.infant'),
            'fullNameLabel' => __('customer/busroot.full_name') ?? __('all.full_name') ?? 'Full name',
            'phoneLabel' => __('customer/busroot.phone') ?? __('all.phone') ?? 'Phone',
            'prepareUrl' => $inlinePrepareUrl,
            'walletLookupUrl' => $inlineWalletUrl,
            'distance' => $distance,
            'fees' => $fees ?? 0,
            'excessLuggageFeePerKg' => $luggageFeePerKg,
        ]) !!}
    </script>
</div>
