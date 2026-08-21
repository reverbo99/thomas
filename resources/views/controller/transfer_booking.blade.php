@extends('admin.app')

@section('content')
@php
    $fieldClass = 'mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md shadow-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 sm:text-sm disabled:bg-gray-100 dark:disabled:bg-slate-900 disabled:text-gray-500';
    $readonlyClass = 'mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400 shadow-sm cursor-not-allowed sm:text-sm';
    $labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
    $btnPrimary = 'inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 dark:focus:ring-offset-slate-800 disabled:opacity-50';
    $btnSecondary = 'inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-slate-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600';
    $company = $company ?? auth()->user()->campany;
    $routes = $routes ?? collect();
    $otherCompanies = $otherCompanies ?? collect();
    $prefill = $prefill ?? null;
    $steps = [
        __('vender/transfer.step_source_trip'),
        __('vender/transfer.step_source_bus'),
        __('vender/transfer.step_source_seats'),
        __('vender/transfer.step_dest_trip'),
        __('vender/transfer.step_dest_bus'),
        __('vender/transfer.step_dest_seats'),
        __('vender/transfer.step_passengers'),
    ];
@endphp
<div class="container mx-auto px-4 py-6 max-w-5xl">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden border border-gray-100 dark:border-slate-700">
        <div class="p-4 bg-gradient-to-r from-teal-500 to-teal-400 dark:from-teal-700 dark:to-teal-600 text-white">
            <div class="text-lg font-semibold">{{ __('vender/transfer.transfer_booking') }}</div>
            <p class="text-sm text-teal-50 mt-1" id="stepLabel">{{ __('vender/transfer.step_of', ['current' => 1, 'total' => 7]) }} — {{ $steps[0] }}</p>
        </div>

        <div class="px-4 pt-4">
            <div class="flex flex-wrap gap-1 mb-4" id="stepIndicators">
                @foreach ($steps as $i => $title)
                    <button type="button" data-step-indicator="{{ $i + 1 }}"
                        class="step-indicator text-xs px-2 py-1 rounded-md border {{ $i === 0 ? 'bg-teal-600 text-white border-teal-600' : 'bg-gray-50 dark:bg-slate-900 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-slate-600' }}">
                        {{ $i + 1 }}. {{ $title }}
                    </button>
                @endforeach
            </div>

            @if (session('success'))
                <div class="bg-green-100 dark:bg-green-900/40 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded relative mb-4" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 dark:bg-red-900/40 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded relative mb-4" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 dark:bg-red-900/40 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded relative mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div id="wizardAlert" class="hidden bg-amber-100 dark:bg-amber-900/40 border border-amber-400 dark:border-amber-700 text-amber-800 dark:text-amber-100 px-4 py-3 rounded relative mb-4"></div>
        </div>

        <form action="{{ route('booking.transfer') }}" method="POST" id="transferWizardForm"
            onsubmit="return confirm(@json(__('vender/transfer.confirm_transfer_warning')))"
            class="p-4 pt-0">
            @csrf
            <input type="hidden" name="transfer_mode" id="transfer_mode" value="scheduled">
            <input type="hidden" name="booking_id" id="booking_id" value="">
            <div id="bookingIdsContainer"></div>
            <div id="seatMapContainer"></div>
            <div id="passengersContainer"></div>

            {{-- Step 1: source date / route / company --}}
            <div class="wizard-step" data-step="1">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('vender/transfer.step_source_trip') }}</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="source_travel_date" class="{{ $labelClass }}">{{ __('vender/transfer.travel_date') }}</label>
                        <input type="date" id="source_travel_date" class="{{ $fieldClass }}" value="{{ $prefill['travel_date'] ?? '' }}">
                    </div>
                    <div>
                        <label for="source_route_id" class="{{ $labelClass }}">{{ __('vender/transfer.route') }}</label>
                        <select id="source_route_id" class="{{ $fieldClass }}">
                            <option value="">{{ __('vender/transfer.select_route') }}</option>
                            @foreach ($routes as $route)
                                <option value="{{ $route->id }}" @selected(($prefill['route_id'] ?? null) == $route->id)>
                                    {{ $route->from }} → {{ $route->to }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="source_company" class="{{ $labelClass }}">{{ __('vender/transfer.bus_company') }}</label>
                        <input type="text" id="source_company" class="{{ $readonlyClass }}" readonly value="{{ $company->name ?? '' }}">
                    </div>
                </div>
            </div>

            {{-- Step 2: source bus --}}
            <div class="wizard-step hidden" data-step="2">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('vender/transfer.step_source_bus') }}</h3>
                <label for="source_bus_id" class="{{ $labelClass }}">{{ __('vender/transfer.select_bus') }}</label>
                <select id="source_bus_id" class="{{ $fieldClass }}">
                    <option value="">{{ __('vender/transfer.select_bus') }}</option>
                </select>
            </div>

            {{-- Step 3: source seats --}}
            <div class="wizard-step hidden" data-step="3">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('vender/transfer.step_source_seats') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ __('vender/transfer.select_source_seats_hint') }}</p>
                <div class="flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-400 mb-3">
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-rose-500"></span> {{ __('vender/transfer.seat_occupied') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-teal-600"></span> {{ __('vender/transfer.seat_selected') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-200 dark:bg-slate-600"></span> {{ __('vender/transfer.seat_available') }}</span>
                </div>
                <div id="sourceSeatGrid" class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2 mb-4"></div>
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-1">{{ __('vender/transfer.selected_passengers') }}</p>
                    <ul id="selectedPassengersList" class="text-sm text-gray-600 dark:text-gray-400 space-y-1"></ul>
                </div>
            </div>

            {{-- Step 4: destination date / route / company --}}
            <div class="wizard-step hidden" data-step="4">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('vender/transfer.step_dest_trip') }}</h3>
                <div class="mb-4">
                    <p class="{{ $labelClass }} mb-2">{{ __('vender/transfer.transfer_mode') }}</p>
                    <div class="inline-flex rounded-lg border border-gray-200 dark:border-slate-600 p-1 bg-gray-50 dark:bg-slate-900" role="group">
                        <button type="button" id="modeScheduledBtn" data-mode="scheduled"
                            class="transfer-mode-btn px-4 py-2 text-sm font-medium rounded-md bg-teal-600 text-white shadow-sm">
                            {{ __('vender/transfer.mode_scheduled') }}
                        </button>
                        <button type="button" id="modeEmergencyBtn" data-mode="emergency"
                            class="transfer-mode-btn px-4 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                            {{ __('vender/transfer.mode_emergency') }}
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="dest_travel_date" class="{{ $labelClass }}">{{ __('vender/transfer.travel_date') }}</label>
                        <input type="date" id="dest_travel_date" name="new_travel_date" class="{{ $fieldClass }}" required>
                    </div>
                    <div>
                        <label for="dest_route_id" class="{{ $labelClass }}">{{ __('vender/transfer.route') }}</label>
                        <select id="dest_route_id" class="{{ $fieldClass }}">
                            <option value="">{{ __('vender/transfer.select_route') }}</option>
                            @foreach ($routes as $route)
                                <option value="{{ $route->id }}">{{ $route->from }} → {{ $route->to }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="destCompanyWrap" class="hidden">
                        <label for="dest_company_id" class="{{ $labelClass }}">{{ __('vender/transfer.dest_company') }}</label>
                        <select id="dest_company_id" class="{{ $fieldClass }}">
                            <option value="">{{ __('vender/transfer.dest_company_any') }}</option>
                            @foreach ($otherCompanies as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="destCompanyLocked">
                        <label class="{{ $labelClass }}">{{ __('vender/transfer.bus_company') }}</label>
                        <input type="text" class="{{ $readonlyClass }}" readonly value="{{ $company->name ?? '' }}">
                    </div>
                </div>
            </div>

            {{-- Step 5: destination bus / schedule / pickup --}}
            <div class="wizard-step hidden" data-step="5">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('vender/transfer.step_dest_bus') }}</h3>
                <div class="mb-4">
                    <label for="new_schedule_id" class="{{ $labelClass }}">{{ __('vender/transfer.new_schedule') }}</label>
                    <select class="{{ $fieldClass }}" id="new_schedule_id" name="new_schedule_id">
                        <option value="">{{ __('vender/transfer.select_new_schedule') }}</option>
                    </select>
                    <p id="unscheduledHint" class="text-xs text-amber-700 dark:text-amber-300 mt-1 hidden">{{ __('vender/transfer.unscheduled_hint') }}</p>
                </div>
                <div class="mb-4">
                    <label for="new_bus_id" class="{{ $labelClass }}">{{ __('vender/transfer.new_bus') }}</label>
                    <select class="{{ $fieldClass }}" id="new_bus_id" name="new_bus_id" required>
                        <option value="">{{ __('vender/transfer.select_new_bus') }}</option>
                    </select>
                </div>
                <div id="receivingCompanyCard" class="mb-4 hidden">
                    <div id="receivingCompanyInner" class="flex items-center gap-3 rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/30 p-4">
                        <div class="h-12 w-12 rounded-full bg-teal-600 dark:bg-teal-500 text-white flex items-center justify-center text-lg font-semibold shrink-0" id="receivingCompanyInitials">—</div>
                        <div class="min-w-0">
                            <p class="text-xs uppercase tracking-wide text-teal-700 dark:text-teal-300">{{ __('vender/transfer.receiving_company') }}</p>
                            <p class="text-base font-semibold text-teal-900 dark:text-teal-100 truncate" id="receivingCompanyName"></p>
                            <p class="text-sm text-teal-700/80 dark:text-teal-300/80" id="receivingCompanyHint">{{ __('vender/transfer.brand_same_company') }}</p>
                        </div>
                    </div>
                </div>
                <div id="emergencyTimesWrap" class="mb-4 hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="emergency_start" class="{{ $labelClass }}">{{ __('vender/transfer.emergency_start') }}</label>
                        <input type="time" class="{{ $fieldClass }}" id="emergency_start" name="emergency_start">
                    </div>
                    <div>
                        <label for="emergency_end" class="{{ $labelClass }}">{{ __('vender/transfer.emergency_end') }}</label>
                        <input type="time" class="{{ $fieldClass }}" id="emergency_end" name="emergency_end">
                    </div>
                </div>
                <div id="emergencyAgreement" class="mb-4 hidden rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="emergency_agreement" id="emergency_agreement" value="1"
                            class="mt-0.5 h-4 w-4 rounded border-amber-400 dark:border-amber-600 text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-700 focus:ring-amber-500">
                        <span class="text-sm text-amber-900 dark:text-amber-100">{{ __('vender/transfer.emergency_agreement') }}</span>
                    </label>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="new_pickup_point" class="{{ $labelClass }}">{{ __('vender/transfer.new_pickup_point') }}</label>
                        <input type="text" class="{{ $fieldClass }}" id="new_pickup_point" name="new_pickup_point" required value="{{ $prefill['pickup_point'] ?? '' }}">
                    </div>
                    <div>
                        <label for="new_dropping_point" class="{{ $labelClass }}">{{ __('vender/transfer.new_dropping_point') }}</label>
                        <input type="text" class="{{ $fieldClass }}" id="new_dropping_point" name="new_dropping_point" required value="{{ $prefill['dropping_point'] ?? '' }}">
                    </div>
                </div>
                <div class="rounded-md border border-gray-200 dark:border-slate-600 p-3">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">{{ __('vender/transfer.fare_preview') }}</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">{{ __('vender/transfer.new_amount') }}</label>
                            <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_amount" name="new_amount" readonly>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">{{ __('vender/transfer.new_bus_fee') }}</label>
                            <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_busFee" name="new_busFee" readonly>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">{{ __('vender/transfer.new_fee') }}</label>
                            <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_fee" name="new_fee" readonly>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 dark:text-gray-400">{{ __('vender/transfer.new_distance') }}</label>
                            <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_distance" name="new_distance" readonly>
                        </div>
                    </div>
                    <input type="hidden" id="new_discount_amount" name="new_discount_amount">
                    <input type="hidden" id="new_bima_amount" name="new_bima_amount">
                    <input type="hidden" id="new_vat" name="new_vat">
                    <input type="hidden" id="new_service" name="new_service">
                    <input type="hidden" id="new_vender_fee" name="new_vender_fee">
                    <input type="hidden" id="new_vender_service" name="new_vender_service">
                    <input type="hidden" id="new_campany_id" name="new_campany_id">
                    <input type="hidden" id="new_route_id" name="new_route_id">
                </div>
            </div>

            {{-- Step 6: destination seats --}}
            <div class="wizard-step hidden" data-step="6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('vender/transfer.step_dest_seats') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3" id="destSeatsHint"></p>
                <div class="flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-400 mb-3">
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-rose-500"></span> {{ __('vender/transfer.seat_occupied') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-teal-600"></span> {{ __('vender/transfer.seat_selected') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-200 dark:bg-emerald-900 border border-emerald-400"></span> {{ __('vender/transfer.seat_available') }}</span>
                </div>
                <div id="destSeatGrid" class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2 mb-4"></div>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('vender/transfer.selected_dest_seats') }}: <span id="selectedDestSeatsLabel" class="font-medium text-gray-900 dark:text-gray-100">—</span></p>
            </div>

            {{-- Step 7: optional passenger edits --}}
            <div class="wizard-step hidden" data-step="7">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('vender/transfer.step_passengers') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('vender/transfer.passenger_edit_hint') }}</p>
                <div id="passengerEditList" class="space-y-4"></div>
                <div class="mt-4 rounded-md border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/30 p-3 text-sm text-yellow-800 dark:text-yellow-200">
                    {{ __('vender/transfer.transfer_warning_note') }}
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 dark:border-slate-700 pt-4">
                <button type="button" id="btnBack" class="{{ $btnSecondary }} hidden">{{ __('vender/transfer.back') }}</button>
                <div class="flex gap-2 ml-auto">
                    <button type="button" id="btnNext" class="{{ $btnPrimary }}">{{ __('vender/transfer.next') }}</button>
                    <button type="submit" id="btnSubmit" class="{{ $btnPrimary }} hidden">{{ __('vender/transfer.confirm_transfer') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
(function () {
    const i18n = {
        stepOf: @json(__('vender/transfer.step_of')),
        steps: @json($steps),
        selectBus: @json(__('vender/transfer.select_bus')),
        selectSchedule: @json(__('vender/transfer.select_new_schedule')),
        selectNewBus: @json(__('vender/transfer.select_new_bus')),
        noBuses: @json(__('vender/transfer.no_buses_found')),
        noSchedules: @json(__('vender/transfer.no_schedules_found')),
        noOccupied: @json(__('vender/transfer.no_occupied_seats')),
        noAvailable: @json(__('vender/transfer.no_available_seats')),
        errorBuses: @json(__('vender/transfer.error_loading_buses')),
        errorSchedules: @json(__('vender/transfer.error_loading_schedules')),
        errorSeats: @json(__('vender/transfer.error_loading_seats')),
        needSourceTrip: @json(__('vender/transfer.need_source_trip')),
        needSourceBus: @json(__('vender/transfer.need_source_bus')),
        needSelectSource: @json(__('vender/transfer.need_select_source')),
        needDestTrip: @json(__('vender/transfer.need_dest_trip')),
        needDestBus: @json(__('vender/transfer.need_dest_bus')),
        needDestExact: @json(__('vender/transfer.need_select_dest_exact')),
        destSeatsHint: @json(__('vender/transfer.select_dest_seats_hint')),
        brandSame: @json(__('vender/transfer.brand_same_company')),
        brandEmergency: @json(__('vender/transfer.brand_emergency')),
        routeTo: @json(__('vender/history.route')),
        onDate: @json(__('vender/dashboard.date')),
        fromSeat: @json(__('vender/transfer.from_seat')),
        toSeat: @json(__('vender/transfer.to_seat')),
        passengerName: @json(__('vender/transfer.passenger_name')),
        passengerPhone: @json(__('vender/transfer.passenger_phone')),
        passengerEmail: @json(__('vender/transfer.passenger_email')),
        passengerGender: @json(__('vender/transfer.passenger_gender')),
        bookingCode: @json(__('vender/transfer.booking_code')),
    };

    const routes = {
        sourceBuses: @json(route('get.transfer.source.buses')),
        sourceSeats: @json(route('get.transfer.source.seats')),
        destSeats: @json(route('get.transfer.destination.seats')),
        schedules: @json(route('get.filtered.schedules')),
        buses: @json(route('get.transfer.buses')),
        amounts: @json(route('calculate.transfer.amounts')),
    };

    const authCompanyId = @json(auth()->user()->campany->id ?? null);
    const prefill = @json($prefill);

    let currentStep = 1;
    const totalSteps = 7;
    let transferMode = 'scheduled';
    let sourceBookings = {};
    let selectedBookingIds = [];
    let selectedDestSeats = [];
    let requiredSeatCount = 0;
    let scheduleMeta = {};
    let busMeta = {};

    function showAlert(msg) {
        const el = $('#wizardAlert');
        if (!msg) {
            el.addClass('hidden').text('');
            return;
        }
        el.removeClass('hidden').text(msg);
    }

    function isEmergency() {
        return transferMode === 'emergency';
    }

    function seatCountForBookings() {
        return selectedBookingIds.reduce(function (sum, id) {
            const b = sourceBookings[id];
            return sum + (b ? (b.seat_count || (b.seats || []).length || 1) : 0);
        }, 0);
    }

    function syncHiddenFields() {
        const idsWrap = $('#bookingIdsContainer').empty();
        const seatWrap = $('#seatMapContainer').empty();
        selectedBookingIds.forEach(function (id) {
            idsWrap.append($('<input>', { type: 'hidden', name: 'booking_ids[]', value: id }));
        });
        $('#booking_id').val(selectedBookingIds[0] || '');

        // Distribute destination seats across bookings in selection order.
        let cursor = 0;
        selectedBookingIds.forEach(function (id) {
            const b = sourceBookings[id];
            const count = b ? (b.seat_count || (b.seats || []).length || 1) : 1;
            const seats = selectedDestSeats.slice(cursor, cursor + count);
            cursor += count;
            if (seats.length) {
                seatWrap.append($('<input>', {
                    type: 'hidden',
                    name: 'seat_map[' + id + ']',
                    value: seats.join(',')
                }));
            }
        });
    }

    function updateStepUi() {
        $('.wizard-step').addClass('hidden');
        $('.wizard-step[data-step="' + currentStep + '"]').removeClass('hidden');
        $('#stepLabel').text(
            i18n.stepOf.replace(':current', currentStep).replace(':total', totalSteps)
            + ' — ' + (i18n.steps[currentStep - 1] || '')
        );
        $('[data-step-indicator]').each(function () {
            const n = Number($(this).data('step-indicator'));
            const active = n === currentStep;
            const done = n < currentStep;
            $(this).attr('class',
                'step-indicator text-xs px-2 py-1 rounded-md border ' +
                (active
                    ? 'bg-teal-600 text-white border-teal-600'
                    : (done
                        ? 'bg-teal-50 dark:bg-teal-900/40 text-teal-800 dark:text-teal-200 border-teal-300 dark:border-teal-700'
                        : 'bg-gray-50 dark:bg-slate-900 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-slate-600'))
            );
        });
        $('#btnBack').toggleClass('hidden', currentStep === 1);
        $('#btnNext').toggleClass('hidden', currentStep === totalSteps);
        $('#btnSubmit').toggleClass('hidden', currentStep !== totalSteps);
        showAlert('');
    }

    function setMode(mode) {
        transferMode = mode;
        $('#transfer_mode').val(mode);
        const scheduledActive = 'px-4 py-2 text-sm font-medium rounded-md bg-teal-600 text-white shadow-sm';
        const emergencyActive = 'px-4 py-2 text-sm font-medium rounded-md bg-amber-600 text-white shadow-sm';
        const inactive = 'px-4 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
        $('#modeScheduledBtn').attr('class', 'transfer-mode-btn ' + (mode === 'scheduled' ? scheduledActive : inactive));
        $('#modeEmergencyBtn').attr('class', 'transfer-mode-btn ' + (mode === 'emergency' ? emergencyActive : inactive));
        $('#destCompanyWrap').toggleClass('hidden', mode !== 'emergency');
        $('#destCompanyLocked').toggleClass('hidden', mode === 'emergency');
        $('#unscheduledHint').toggleClass('hidden', mode !== 'emergency');
        $('#emergencyTimesWrap').toggleClass('hidden', mode !== 'emergency');
        $('#emergencyAgreement').toggleClass('hidden', mode !== 'emergency');
        $('#emergency_agreement').prop('required', mode === 'emergency').prop('disabled', mode !== 'emergency');
        $('#new_schedule_id').prop('required', mode !== 'emergency');
    }

    function companyInitials(name) {
        return (name || '').split(/\s+/).filter(Boolean).slice(0, 2)
            .map(function (p) { return p.charAt(0).toUpperCase(); }).join('') || '—';
    }

    function showBrand(companyName, companyId) {
        if (!companyName) {
            $('#receivingCompanyCard').addClass('hidden');
            return;
        }
        $('#receivingCompanyCard').removeClass('hidden');
        $('#receivingCompanyName').text(companyName);
        $('#receivingCompanyInitials').text(companyInitials(companyName));
        const cross = companyId && authCompanyId && Number(companyId) !== Number(authCompanyId);
        $('#receivingCompanyHint').text(cross || isEmergency() ? i18n.brandEmergency : i18n.brandSame);
        const inner = $('#receivingCompanyInner');
        if (cross || isEmergency()) {
            inner.attr('class', 'flex items-center gap-3 rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 p-4');
        } else {
            inner.attr('class', 'flex items-center gap-3 rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/30 p-4');
        }
    }

    function loadSourceBuses(thenSelectBusId) {
        const date = $('#source_travel_date').val();
        const routeId = $('#source_route_id').val();
        const select = $('#source_bus_id').empty().append($('<option>', { value: '', text: i18n.selectBus }));
        if (!date || !routeId) {
            return $.Deferred().resolve().promise();
        }
        return $.get(routes.sourceBuses, { travel_date: date, route_id: routeId })
            .done(function (list) {
                if (!list.length) {
                    select.append($('<option>', { value: '', text: i18n.noBuses }));
                    return;
                }
                list.forEach(function (bus) {
                    select.append($('<option>', {
                        value: bus.id,
                        text: (bus.bus_number || '') + ' (' + (bus.name || bus.company_name || '') + ')'
                    }));
                });
                if (thenSelectBusId) {
                    select.val(String(thenSelectBusId));
                }
            })
            .fail(function () {
                select.append($('<option>', { value: '', text: i18n.errorBuses }));
            });
    }

    function renderSourceSeats(payload) {
        sourceBookings = {};
        (payload.bookings || []).forEach(function (b) {
            sourceBookings[b.id] = b;
        });
        const occupiedMap = {};
        (payload.seats || []).forEach(function (s) {
            occupiedMap[String(s.seat)] = s;
        });
        const total = payload.total_seats || 0;
        const grid = $('#sourceSeatGrid').empty();
        if (!total && !(payload.seats || []).length) {
            grid.append($('<p>', { class: 'text-sm text-gray-500 col-span-full', text: i18n.noOccupied }));
            return;
        }
        const seats = total > 0
            ? Array.from({ length: total }, function (_, i) { return String(i + 1); })
            : Object.keys(occupiedMap);
        seats.forEach(function (seat) {
            const info = occupiedMap[seat];
            const btn = $('<button>', {
                type: 'button',
                text: seat,
                'data-seat': seat,
                'data-booking-id': info ? info.booking_id : '',
                class: 'seat-btn h-10 rounded-md text-sm font-medium border transition ' +
                    (info
                        ? 'bg-rose-500 text-white border-rose-600 hover:bg-rose-600 cursor-pointer'
                        : 'bg-gray-100 dark:bg-slate-700 text-gray-400 border-gray-200 dark:border-slate-600 cursor-not-allowed')
            });
            if (!info) {
                btn.prop('disabled', true);
            }
            grid.append(btn);
        });
        refreshSourceSelectionUi();
    }

    function refreshSourceSelectionUi() {
        $('#sourceSeatGrid .seat-btn').each(function () {
            const bid = Number($(this).data('booking-id'));
            if (!bid) return;
            const selected = selectedBookingIds.indexOf(bid) !== -1;
            $(this).toggleClass('bg-teal-600 border-teal-700 hover:bg-teal-700', selected);
            $(this).toggleClass('bg-rose-500 border-rose-600 hover:bg-rose-600', !selected);
        });
        const list = $('#selectedPassengersList').empty();
        if (!selectedBookingIds.length) {
            list.append($('<li>', { text: '—' }));
            return;
        }
        selectedBookingIds.forEach(function (id) {
            const b = sourceBookings[id];
            if (!b) return;
            list.append($('<li>', {
                text: (b.customer_name || '') + ' · ' + i18n.fromSeat + ' ' + (b.seat || '') + ' · ' + (b.booking_code || '')
            }));
        });
    }

    function loadSourceSeats(preselectBookingId) {
        const busId = $('#source_bus_id').val();
        const date = $('#source_travel_date').val();
        if (!busId || !date) {
            return $.Deferred().resolve().promise();
        }
        return $.get(routes.sourceSeats, { bus_id: busId, travel_date: date })
            .done(function (payload) {
                selectedBookingIds = [];
                renderSourceSeats(payload);
                if (preselectBookingId && sourceBookings[preselectBookingId]) {
                    selectedBookingIds = [Number(preselectBookingId)];
                    refreshSourceSelectionUi();
                }
            })
            .fail(function () {
                $('#sourceSeatGrid').html('<p class="text-sm text-red-600">' + i18n.errorSeats + '</p>');
            });
    }

    function loadSchedules() {
        const select = $('#new_schedule_id').empty().append($('<option>', { value: '', text: i18n.selectSchedule }));
        scheduleMeta = {};
        return $.get(routes.schedules, {
            travel_date: $('#dest_travel_date').val() || '',
            route_id: $('#dest_route_id').val() || '',
            emergency: isEmergency() ? 1 : 0,
            dest_company_id: $('#dest_company_id').val() || ''
        }).done(function (list) {
            if (!list.length) {
                select.append($('<option>', { value: '', text: i18n.noSchedules }));
            } else {
                list.forEach(function (s) {
                    scheduleMeta[s.id] = s;
                    const busLabel = s.bus_number ? ' · ' + s.bus_number : '';
                    const companyLabel = s.company_name ? ' · ' + s.company_name : '';
                    select.append($('<option>', {
                        value: s.id,
                        text: s.from + ' ' + i18n.routeTo + ' ' + s.to + ' ' + i18n.onDate + ' ' + s.schedule_date
                            + ' (' + s.start + ' - ' + s.end + ')' + busLabel + companyLabel
                    }));
                });
            }
            if (isEmergency()) {
                loadDestBuses();
            } else {
                resetDestBuses();
            }
        }).fail(function () {
            select.append($('<option>', { value: '', text: i18n.errorSchedules }));
        });
    }

    function resetDestBuses() {
        busMeta = {};
        $('#new_bus_id').empty().append($('<option>', { value: '', text: i18n.selectNewBus }));
        $('#receivingCompanyCard').addClass('hidden');
    }

    function loadDestBuses() {
        const scheduleId = $('#new_schedule_id').val();
        if (!scheduleId && !isEmergency()) {
            resetDestBuses();
            return;
        }
        const select = $('#new_bus_id').empty().append($('<option>', { value: '', text: i18n.selectNewBus }));
        busMeta = {};
        $.get(routes.buses, {
            schedule_id: scheduleId || '',
            travel_date: $('#dest_travel_date').val() || '',
            route_id: $('#dest_route_id').val() || '',
            emergency: isEmergency() ? 1 : 0,
            dest_company_id: $('#dest_company_id').val() || ''
        }).done(function (list) {
            if (!list.length) {
                select.append($('<option>', { value: '', text: i18n.noBuses }));
                return;
            }
            list.forEach(function (bus) {
                busMeta[bus.id] = bus;
                select.append($('<option>', {
                    value: bus.id,
                    text: (bus.bus_number || '') + ' (' + (bus.name || bus.company_name || '') + ')',
                    selected: !!bus.is_schedule_bus
                }));
            });
            if (select.val()) {
                onDestBusChange();
            }
        }).fail(function () {
            select.append($('<option>', { value: '', text: i18n.errorBuses }));
        });
    }

    function calculateAmounts() {
        const busId = $('#new_bus_id').val();
        const scheduleId = $('#new_schedule_id').val();
        const travelDate = $('#dest_travel_date').val();
        const pickup = $('#new_pickup_point').val();
        const drop = $('#new_dropping_point').val();
        const originalId = selectedBookingIds[0];
        if (!busId || !travelDate || !pickup || !drop || !originalId) return;
        if (!isEmergency() && !scheduleId) return;
        $.get(routes.amounts, {
            bus_id: busId,
            schedule_id: scheduleId || '',
            travel_date: travelDate,
            pickup_point: pickup,
            dropping_point: drop,
            original_booking_id: originalId,
            emergency: isEmergency() ? 1 : 0
        }).done(function (r) {
            $('#new_amount').val(r.new_amount);
            $('#new_busFee').val(r.new_busFee);
            $('#new_discount_amount').val(r.new_discount_amount);
            $('#new_distance').val(r.new_distance);
            $('#new_bima_amount').val(r.new_bima_amount);
            $('#new_vat').val(r.new_vat);
            $('#new_fee').val(r.new_fee);
            $('#new_service').val(r.new_service);
            $('#new_vender_fee').val(r.new_vender_fee);
            $('#new_vender_service').val(r.new_vender_service);
            $('#new_campany_id').val(r.new_campany_id);
            $('#new_route_id').val(r.new_route_id);
            if (r.company_name) showBrand(r.company_name, r.company_id);
        });
    }

    function onDestBusChange() {
        const meta = busMeta[$('#new_bus_id').val()];
        if (meta) showBrand(meta.company_name, meta.company_id);
        calculateAmounts();
    }

    function loadDestSeats() {
        requiredSeatCount = seatCountForBookings();
        $('#destSeatsHint').text(i18n.destSeatsHint.replace(':count', requiredSeatCount));
        selectedDestSeats = [];
        const busId = $('#new_bus_id').val();
        const date = $('#dest_travel_date').val();
        if (!busId || !date) return;
        $.get(routes.destSeats, {
            bus_id: busId,
            travel_date: date,
            schedule_id: $('#new_schedule_id').val() || '',
            'exclude_booking_ids[]': selectedBookingIds
        }).done(function (payload) {
            const occupied = {};
            (payload.occupied || []).forEach(function (s) { occupied[String(s)] = true; });
            const all = payload.all_seats && payload.all_seats.length
                ? payload.all_seats
                : Array.from({ length: payload.total_seats || 0 }, function (_, i) { return String(i + 1); });
            const grid = $('#destSeatGrid').empty();
            if (!all.length) {
                grid.append($('<p>', { class: 'text-sm text-gray-500 col-span-full', text: i18n.noAvailable }));
                return;
            }
            all.forEach(function (seat) {
                const isOcc = !!occupied[String(seat)];
                const btn = $('<button>', {
                    type: 'button',
                    text: seat,
                    'data-seat': seat,
                    disabled: isOcc,
                    class: 'dest-seat-btn h-10 rounded-md text-sm font-medium border transition ' +
                        (isOcc
                            ? 'bg-rose-500 text-white border-rose-600 cursor-not-allowed'
                            : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-900 dark:text-emerald-100 border-emerald-400 hover:bg-emerald-200 cursor-pointer')
                });
                grid.append(btn);
            });
            updateDestSelectionLabel();
        }).fail(function () {
            $('#destSeatGrid').html('<p class="text-sm text-red-600">' + i18n.errorSeats + '</p>');
        });
    }

    function updateDestSelectionLabel() {
        $('#selectedDestSeatsLabel').text(selectedDestSeats.length ? selectedDestSeats.join(', ') : '—');
        $('#destSeatGrid .dest-seat-btn:not(:disabled)').each(function () {
            const seat = String($(this).data('seat'));
            const selected = selectedDestSeats.indexOf(seat) !== -1;
            $(this).toggleClass('bg-teal-600 text-white border-teal-700', selected);
            $(this).toggleClass('bg-emerald-100 dark:bg-emerald-900/40 text-emerald-900 dark:text-emerald-100 border-emerald-400', !selected);
        });
    }

    function buildPassengerEditors() {
        const wrap = $('#passengerEditList').empty();
        const passWrap = $('#passengersContainer').empty();
        selectedBookingIds.forEach(function (id) {
            const b = sourceBookings[id];
            if (!b) return;
            const destSeats = [];
            // preview mapping for display
            let cursor = 0;
            selectedBookingIds.forEach(function (bid) {
                const bb = sourceBookings[bid];
                const count = bb ? (bb.seat_count || (bb.seats || []).length || 1) : 1;
                if (bid === id) {
                    destSeats.push.apply(destSeats, selectedDestSeats.slice(cursor, cursor + count));
                }
                cursor += count;
            });
            const card = $('<div>', { class: 'rounded-lg border border-gray-200 dark:border-slate-600 p-4 bg-gray-50 dark:bg-slate-900/50' });
            card.append($('<p>', {
                class: 'text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2',
                text: (b.customer_name || '') + ' · ' + (b.booking_code || '')
            }));
            card.append($('<p>', {
                class: 'text-xs text-gray-500 dark:text-gray-400 mb-3',
                text: i18n.fromSeat + ' ' + (b.seat || '') + ' → ' + i18n.toSeat + ' ' + (destSeats.join(',') || '—')
            }));
            const grid = $('<div>', { class: 'grid grid-cols-1 sm:grid-cols-2 gap-3' });
            [
                ['customer_name', i18n.passengerName, b.customer_name || ''],
                ['customer_phone', i18n.passengerPhone, b.customer_phone || ''],
                ['customer_email', i18n.passengerEmail, b.customer_email || ''],
                ['gender', i18n.passengerGender, b.gender || ''],
            ].forEach(function (field) {
                const key = field[0], label = field[1], val = field[2];
                const block = $('<div>');
                block.append($('<label>', { class: 'block text-xs font-medium text-gray-600 dark:text-gray-400', text: label }));
                const input = $('<input>', {
                    type: 'text',
                    name: 'passengers[' + id + '][' + key + ']',
                    value: val,
                    class: 'mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md shadow-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 sm:text-sm'
                });
                block.append(input);
                grid.append(block);
            });
            card.append(grid);
            wrap.append(card);
        });
        syncHiddenFields();
    }

    function validateStep(step) {
        if (step === 1) {
            if (!$('#source_travel_date').val() || !$('#source_route_id').val()) {
                showAlert(i18n.needSourceTrip);
                return false;
            }
        }
        if (step === 2) {
            if (!$('#source_bus_id').val()) {
                showAlert(i18n.needSourceBus);
                return false;
            }
        }
        if (step === 3) {
            if (!selectedBookingIds.length) {
                showAlert(i18n.needSelectSource);
                return false;
            }
        }
        if (step === 4) {
            if (!$('#dest_travel_date').val() || (!$('#dest_route_id').val() && !isEmergency())) {
                showAlert(i18n.needDestTrip);
                return false;
            }
        }
        if (step === 5) {
            if (!$('#new_bus_id').val() || (!isEmergency() && !$('#new_schedule_id').val())) {
                showAlert(i18n.needDestBus);
                return false;
            }
            if (!$('#new_pickup_point').val() || !$('#new_dropping_point').val()) {
                showAlert(i18n.needDestBus);
                return false;
            }
            if (isEmergency() && !$('#emergency_agreement').is(':checked')) {
                showAlert(i18n.needDestBus);
                return false;
            }
        }
        if (step === 6) {
            requiredSeatCount = seatCountForBookings();
            if (selectedDestSeats.length !== requiredSeatCount) {
                showAlert(i18n.needDestExact.replace(':count', requiredSeatCount));
                return false;
            }
        }
        return true;
    }

    async function enterStep(step) {
        if (step === 2) {
            await loadSourceBuses(prefill && currentStep <= 2 ? prefill.bus_id : $('#source_bus_id').val());
        }
        if (step === 3) {
            await loadSourceSeats(prefill && selectedBookingIds.length === 0 ? prefill.booking_id : null);
        }
        if (step === 5) {
            await loadSchedules();
        }
        if (step === 6) {
            loadDestSeats();
        }
        if (step === 7) {
            buildPassengerEditors();
        }
    }

    $('#btnNext').on('click', async function () {
        if (!validateStep(currentStep)) return;
        if (currentStep < totalSteps) {
            currentStep += 1;
            updateStepUi();
            await enterStep(currentStep);
        }
    });

    $('#btnBack').on('click', function () {
        if (currentStep > 1) {
            currentStep -= 1;
            updateStepUi();
        }
    });

    $('#sourceSeatGrid').on('click', '.seat-btn', function () {
        const bid = Number($(this).data('booking-id'));
        if (!bid) return;
        const idx = selectedBookingIds.indexOf(bid);
        if (idx === -1) selectedBookingIds.push(bid);
        else selectedBookingIds.splice(idx, 1);
        refreshSourceSelectionUi();
    });

    $('#destSeatGrid').on('click', '.dest-seat-btn', function () {
        if ($(this).prop('disabled')) return;
        const seat = String($(this).data('seat'));
        const idx = selectedDestSeats.indexOf(seat);
        if (idx !== -1) {
            selectedDestSeats.splice(idx, 1);
        } else {
            if (selectedDestSeats.length >= requiredSeatCount) {
                selectedDestSeats.shift();
            }
            selectedDestSeats.push(seat);
        }
        updateDestSelectionLabel();
        syncHiddenFields();
    });

    $('#modeScheduledBtn').on('click', function () { setMode('scheduled'); loadSchedules(); });
    $('#modeEmergencyBtn').on('click', function () { setMode('emergency'); loadSchedules(); });
    $('#dest_company_id').on('change', loadSchedules);
    $('#dest_travel_date, #dest_route_id').on('change', function () {
        if (currentStep >= 5) loadSchedules();
    });
    $('#new_schedule_id').on('change', function () {
        const meta = scheduleMeta[$(this).val()];
        if (meta) {
            if (meta.schedule_date) $('#dest_travel_date').val(meta.schedule_date);
            if (meta.start) $('#emergency_start').val(String(meta.start).substring(0, 5));
            if (meta.end) $('#emergency_end').val(String(meta.end).substring(0, 5));
            showBrand(meta.company_name, meta.company_id);
        }
        loadDestBuses();
    });
    $('#new_bus_id').on('change', onDestBusChange);
    $('#new_pickup_point, #new_dropping_point, #dest_travel_date').on('change', calculateAmounts);

    $('#transferWizardForm').on('submit', function () {
        syncHiddenFields();
        if (!selectedBookingIds.length || selectedDestSeats.length !== seatCountForBookings()) {
            showAlert(i18n.needDestExact.replace(':count', seatCountForBookings()));
            return false;
        }
        return true;
    });

    // Prefill from history deep-link
    (async function init() {
        setMode('scheduled');
        updateStepUi();
        if (prefill) {
            if (prefill.travel_date) $('#source_travel_date').val(prefill.travel_date);
            if (prefill.route_id) $('#source_route_id').val(String(prefill.route_id));
            if (prefill.pickup_point) $('#new_pickup_point').val(prefill.pickup_point);
            if (prefill.dropping_point) $('#new_dropping_point').val(prefill.dropping_point);
            if (prefill.travel_date && prefill.route_id) {
                currentStep = 2;
                updateStepUi();
                await loadSourceBuses(prefill.bus_id);
                if (prefill.bus_id) {
                    currentStep = 3;
                    updateStepUi();
                    await loadSourceSeats(prefill.booking_id);
                }
            }
        }
    })();
})();
</script>
@endsection
