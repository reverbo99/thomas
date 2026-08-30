@php
    $br = booking_routes();
    $travelDate = session('departure_date')
        ?? ($car->schedule->schedule_date ?? null)
        ?? now('Africa/Nairobi')->format('Y-m-d');
    $travelDate = \Carbon\Carbon::parse($travelDate)->timezone('Africa/Nairobi')->toDateString();
    $routeDefaultDistance = (float) ($car->route->distance ?? 0);
@endphp
<div class="inline-booking-panel" data-inline-panel="pickup"
    data-route-default-distance="{{ $routeDefaultDistance > 1 ? $routeDefaultDistance : '' }}"
    data-route-city-from="{{ $car->schedule->from ?? $car->route->from ?? '' }}"
    data-route-city-to="{{ $car->schedule->to ?? $car->route->to ?? '' }}">
    @include('test.partials.booking_steps', [
        'currentStep' => 1,
        'interactive' => true,
        'steps' => [
            1 => ['label' => __('all.step_pickup_drop'), 'icon' => 'fa-map-marker-alt', 'key' => 'pickup'],
            2 => ['label' => __('all.step_select_seats'), 'icon' => 'fa-chair', 'key' => 'seats'],
            3 => ['label' => __('all.step_details'), 'icon' => 'fa-user', 'key' => 'extras'],
            4 => ['label' => __('all.step_payment'), 'icon' => 'fa-credit-card', 'key' => 'payment'],
        ],
    ])

    <div class="inline-booking-panel__error booking-alert booking-alert--error hidden" role="alert"></div>

    <form method="POST"
        action="{{ route($br['store']) }}"
        class="inline-booking-form booking-form"
        data-inline-form="pickup"
        data-inline-uid="{{ $inlineUid }}">
        @csrf
        <input type="hidden" name="inline" value="1">
        <input type="hidden" name="inline_uid" value="{{ $inlineUid }}">
        <input type="hidden" name="bus_id" value="{{ $car->id }}">
        <input type="hidden" name="route_id" value="{{ $car->route->id }}">
        <input type="hidden" name="schedule_id" value="{{ $car->schedule->id }}">
        <input type="hidden" name="departure_date" value="{{ $travelDate }}">
        <input type="hidden" name="travel_date" value="{{ $travelDate }}">
        <input type="hidden" name="route_distance" id="routeDistance_{{ $inlineUid }}"
            value="{{ $routeDefaultDistance > 1 ? number_format($routeDefaultDistance, 2, '.', '') : '' }}">
        <input type="hidden" name="dropping_point_amount" id="droppingPointAmount_{{ $inlineUid }}" value="{{ $car->route->price ?? 0 }}">

        <div class="booking-grid booking-grid--2">
            <div class="booking-field">
                <label class="booking-field__label" for="pickupPoint_{{ $inlineUid }}">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    {{ __('all.pickup_point_label') }}
                </label>
                <select class="page-input inline-select2" id="pickupPoint_{{ $inlineUid }}" name="pickup_point" required>
                    <option value="">{{ __('all.select_pickup_point_placeholder') }}</option>
                    @foreach ($car->filtered_points ?? [] as $value)
                        @if ($value->point_mode == 1)
                            <option value="{{ $value->point }}">{{ $value->point }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="booking-field">
                <label class="booking-field__label" for="dropoffPoint_{{ $inlineUid }}">
                    <i class="fas fa-flag-checkered" aria-hidden="true"></i>
                    {{ __('all.dropoff_point_label') }}
                </label>
                <select class="page-input inline-select2" id="dropoffPoint_{{ $inlineUid }}" name="dropping_point" required>
                    <option value="">{{ __('all.select_dropping_point_placeholder') }}</option>
                    @foreach ($car->filtered_points ?? [] as $value)
                        @if ($value->point_mode == 2)
                            <option value="{{ $value->point }}" data-amount="{{ $value->amount }}">{{ $value->point }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>

        <p class="inline-booking-distance-hint" id="routeDistanceHint_{{ $inlineUid }}" data-inline-distance-hint
            @if ($routeDefaultDistance <= 1) hidden @endif>
            @if ($routeDefaultDistance > 1)
                <i class="fas fa-road" aria-hidden="true"></i> {{ number_format($routeDefaultDistance, 1) }} km total distance
            @endif
        </p>

        <div class="inline-booking-actions">
            <button type="button" class="page-btn page-btn--outline" data-inline-nav-back="collapse">
                {{ __('all.back_button') }}
            </button>
            <button type="submit" class="page-btn">
                {{ __('all.next') }}
            </button>
        </div>
    </form>
</div>
