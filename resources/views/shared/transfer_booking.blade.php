@extends($layout)

@section('content')
@php
    $fieldClass = 'mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md shadow-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 sm:text-sm disabled:bg-gray-100 dark:disabled:bg-slate-900 disabled:text-gray-500';
    $readonlyClass = 'mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-md bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400 shadow-sm cursor-not-allowed sm:text-sm';
    $labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
    $companionBookings = $companionBookings ?? collect();
    $otherCompanies = $otherCompanies ?? collect();
    $allowEmergency = $allowEmergency ?? false;
    $actor = $actor ?? 'guest';
    $showCompanions = !in_array($actor, ['customer', 'guest'], true);
@endphp
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <div class="flex justify-center">
        <div class="w-full max-w-3xl">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden border border-gray-100 dark:border-slate-700">
                <div class="p-4 bg-gradient-to-r from-teal-500 to-teal-400 dark:from-teal-700 dark:to-teal-600 text-white text-lg font-semibold">{{ __('vender/transfer.transfer_booking') }}</div>

                <div class="p-4">
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

                    <form action="{{ $formAction }}" method="POST"
                        onsubmit="return confirm(@json(__('vender/transfer.confirm_transfer_warning')))">
                        @csrf
                        <input type="hidden" name="transfer_mode" id="transfer_mode" value="scheduled">
                        <input type="hidden" name="actor" value="{{ $actor }}">

                        <div class="mb-4">
                            <label for="booking_id" class="{{ $labelClass }}">{{ __('vender/transfer.select_booking') }}</label>
                            <select class="{{ $fieldClass }}" id="booking_id" name="booking_id" required onchange="
                                const selectedBookingId = this.value;
                                if (selectedBookingId) {
                                    window.location.href = @json($formSelectBase) + '/' + selectedBookingId;
                                }
                            ">
                                <option value="">{{ __('vender/transfer.select_booking_option') }}</option>
                                @foreach ($bookings as $booking)
                                    <option value="{{ $booking->id }}" {{ $selectedBooking && $selectedBooking->id == $booking->id ? 'selected' : '' }}>
                                        {{ $booking->booking_code }} - {{ $booking->customer_name }} ({{ $booking->bus->busname->name ?? __('vender/history.na') }} - {{ $booking->route_name->from ?? __('vender/history.na') }} {{ __('vender/history.route') }} {{ $booking->route_name->to ?? __('vender/history.na') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($selectedBooking)
                            <hr class="my-4 border-gray-300 dark:border-slate-600">
                            <h5 class="text-lg font-semibold mb-2 text-gray-900 dark:text-gray-100">{{ __('vender/transfer.current_booking_details') }}</h5>
                            <p class="text-gray-700 dark:text-gray-300 mb-1"><strong>{{ __('vender/transfer.booking_code') }}</strong> {{ $selectedBooking->booking_code }}</p>
                            <p class="text-gray-700 dark:text-gray-300 mb-1"><strong>{{ __('vender/transfer.passenger_name') }}</strong> {{ $selectedBooking->customer_name }}</p>
                            <p class="text-gray-700 dark:text-gray-300 mb-1"><strong>{{ __('vender/transfer.current_bus') }}</strong> {{ $selectedBooking->bus->bus_number ?? __('vender/history.na') }} ({{ $selectedBooking->bus->busname->name ?? __('vender/history.na') }})</p>
                            <p class="text-gray-700 dark:text-gray-300 mb-1"><strong>{{ __('vender/transfer.current_route') }}</strong> {{ $selectedBooking->route_name->from ?? __('vender/history.na') }} {{ __('vender/history.route') }} {{ $selectedBooking->route_name->to ?? __('vender/history.na') }}</p>
                            <p class="text-gray-700 dark:text-gray-300 mb-1"><strong>{{ __('vender/transfer.current_travel_date') }}</strong> {{ $selectedBooking->travel_date }}</p>
                            <p class="text-gray-700 dark:text-gray-300 mb-1"><strong>{{ __('vender/transfer.current_seat') }}</strong> {{ $selectedBooking->seat }}</p>
                            <p class="text-gray-700 dark:text-gray-300 mb-4"><strong>{{ __('vender/transfer.current_amount') }}</strong> {{ $selectedBooking->amount }}</p>
                            <hr class="my-4 border-gray-300 dark:border-slate-600">

                            @if ($showCompanions && $companionBookings->isNotEmpty())
                                <div class="mb-4 rounded-lg border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-900/60 p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ __('vender/transfer.companion_passengers') }}</p>
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                            <input type="checkbox" id="selectAllCompanions"
                                                class="h-4 w-4 rounded border-gray-300 dark:border-slate-500 text-teal-600 dark:text-teal-400 bg-white dark:bg-slate-700 focus:ring-teal-500">
                                            {{ __('vender/transfer.select_all_companions') }}
                                        </label>
                                    </div>
                                    <ul class="space-y-2 max-h-40 overflow-y-auto">
                                        @foreach ($companionBookings as $companion)
                                            <li>
                                                <label class="flex items-start gap-3 rounded-md px-2 py-1.5 hover:bg-white dark:hover:bg-slate-800 cursor-pointer">
                                                    <input type="checkbox" name="companion_ids[]" value="{{ $companion->id }}"
                                                        class="companion-checkbox mt-0.5 h-4 w-4 rounded border-gray-300 dark:border-slate-500 text-teal-600 dark:text-teal-400 bg-white dark:bg-slate-700 focus:ring-teal-500">
                                                    <span>
                                                        <span class="block text-sm text-gray-900 dark:text-gray-100">{{ $companion->customer_name }} · {{ __('vender/transfer.current_seat') }} {{ $companion->seat }}</span>
                                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $companion->booking_code }}</span>
                                                    </span>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($allowEmergency)
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

                                <div id="destCompanyWrap" class="mb-4 hidden">
                                    <label for="dest_company_id" class="{{ $labelClass }}">{{ __('vender/transfer.dest_company') }}</label>
                                    <select class="{{ $fieldClass }}" id="dest_company_id">
                                        <option value="">{{ __('vender/transfer.dest_company_any') }}</option>
                                        @foreach ($otherCompanies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="mb-4">
                                <label for="new_schedule_id" class="{{ $labelClass }}">{{ __('vender/transfer.new_schedule') }}</label>
                                <select class="{{ $fieldClass }}" id="new_schedule_id" name="new_schedule_id" required>
                                    <option value="">{{ __('vender/transfer.select_new_schedule') }}</option>
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('vender/transfer.select_schedule_first_hint') }}</p>
                                @if ($allowEmergency)
                                    <p id="unscheduledHint" class="text-xs text-amber-700 dark:text-amber-300 mt-1 hidden">{{ __('vender/transfer.unscheduled_hint') }}</p>
                                @endif
                            </div>

                            <div class="mb-4">
                                <label for="new_bus_id" class="{{ $labelClass }}">{{ __('vender/transfer.new_bus') }}</label>
                                <select class="{{ $fieldClass }}" id="new_bus_id" name="new_bus_id" required disabled>
                                    <option value="">{{ __('vender/transfer.select_new_bus_first') }}</option>
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('vender/transfer.select_bus_first_hint') }}</p>
                            </div>

                            <div id="receivingCompanyCard" class="mb-4 hidden">
                                <div id="receivingCompanyInner" class="flex items-center gap-3 rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/30 p-4">
                                    <div class="h-12 w-12 rounded-full bg-teal-600 dark:bg-teal-500 text-white flex items-center justify-center text-lg font-semibold shrink-0"
                                         id="receivingCompanyInitials">—</div>
                                    <div class="min-w-0">
                                        <p class="text-xs uppercase tracking-wide text-teal-700 dark:text-teal-300">{{ __('vender/transfer.receiving_company') }}</p>
                                        <p class="text-base font-semibold text-teal-900 dark:text-teal-100 truncate" id="receivingCompanyName"></p>
                                        <p class="text-sm text-teal-700/80 dark:text-teal-300/80" id="receivingCompanyHint">{{ __('vender/transfer.brand_same_company') }}</p>
                                    </div>
                                </div>
                            </div>

                            @if ($allowEmergency)
                                <div id="emergencyTimesWrap" class="mb-4 hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="emergency_start" class="{{ $labelClass }}">{{ __('vender/transfer.emergency_start') }}</label>
                                        <input type="time" class="{{ $fieldClass }}" id="emergency_start" name="emergency_start" value="{{ optional($selectedBooking->schedule)->start }}">
                                    </div>
                                    <div>
                                        <label for="emergency_end" class="{{ $labelClass }}">{{ __('vender/transfer.emergency_end') }}</label>
                                        <input type="time" class="{{ $fieldClass }}" id="emergency_end" name="emergency_end" value="{{ optional($selectedBooking->schedule)->end }}">
                                    </div>
                                </div>
                            @endif

                            <div class="mb-4">
                                <label for="new_travel_date" class="{{ $labelClass }}">{{ __('vender/transfer.new_travel_date') }}</label>
                                <input type="date" class="{{ $readonlyClass }}" id="new_travel_date" name="new_travel_date" required readonly value="{{ $selectedBooking->travel_date }}">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('vender/transfer.travel_date_derived_hint') }}</p>
                            </div>

                            @if ($allowEmergency)
                                <div id="emergencyAgreement" class="mb-4 hidden rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" name="emergency_agreement" id="emergency_agreement" value="1"
                                            class="mt-0.5 h-4 w-4 rounded border-amber-400 dark:border-amber-600 text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-700 focus:ring-amber-500">
                                        <span class="text-sm text-amber-900 dark:text-amber-100">{{ __('vender/transfer.emergency_agreement') }}</span>
                                    </label>
                                </div>
                            @endif

                            <div class="mb-4">
                                <label for="new_pickup_point" class="{{ $labelClass }}">{{ __('vender/transfer.new_pickup_point') }}</label>
                                <input type="text" class="{{ $fieldClass }}" id="new_pickup_point" name="new_pickup_point" required value="{{ $selectedBooking->pickup_point }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_dropping_point" class="{{ $labelClass }}">{{ __('vender/transfer.new_dropping_point') }}</label>
                                <input type="text" class="{{ $fieldClass }}" id="new_dropping_point" name="new_dropping_point" required value="{{ $selectedBooking->dropping_point }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_amount" class="{{ $labelClass }}">{{ __('vender/transfer.new_amount') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_amount" name="new_amount" required readonly value="{{ $selectedBooking->amount }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_busFee" class="{{ $labelClass }}">{{ __('vender/transfer.new_bus_fee') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_busFee" name="new_busFee" required readonly value="{{ $selectedBooking->busFee }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_discount_amount" class="{{ $labelClass }}">{{ __('vender/transfer.new_discount_amount') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_discount_amount" name="new_discount_amount" required readonly value="{{ $selectedBooking->discount_amount }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_distance" class="{{ $labelClass }}">{{ __('vender/transfer.new_distance') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_distance" name="new_distance" required readonly value="{{ $selectedBooking->distance }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_bima_amount" class="{{ $labelClass }}">{{ __('vender/transfer.new_bima_amount') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_bima_amount" name="new_bima_amount" required readonly value="{{ $selectedBooking->bima_amount }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_vat" class="{{ $labelClass }}">{{ __('vender/transfer.new_vat') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_vat" name="new_vat" required readonly value="{{ $selectedBooking->vat }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_fee" class="{{ $labelClass }}">{{ __('vender/transfer.new_fee') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_fee" name="new_fee" required readonly value="{{ $selectedBooking->fee }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_service" class="{{ $labelClass }}">{{ __('vender/transfer.new_service') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_service" name="new_service" required readonly value="{{ $selectedBooking->service }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_vender_fee" class="{{ $labelClass }}">{{ __('vender/transfer.new_vender_fee') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_vender_fee" name="new_vender_fee" required readonly value="{{ $selectedBooking->vender_fee }}">
                            </div>

                            <div class="mb-4">
                                <label for="new_vender_service" class="{{ $labelClass }}">{{ __('vender/transfer.new_vender_service') }}</label>
                                <input type="number" step="0.01" class="{{ $readonlyClass }}" id="new_vender_service" name="new_vender_service" required readonly value="{{ $selectedBooking->vender_service }}">
                            </div>

                            <input type="hidden" id="new_campany_id" name="new_campany_id" value="{{ $selectedBooking->campany_id }}">
                            <input type="hidden" id="new_route_id" name="new_route_id" value="{{ $selectedBooking->route_id }}">
                            <div class="mb-4 rounded-md border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/30 p-3 text-sm text-yellow-800 dark:text-yellow-200">
                                {{ __('vender/transfer.transfer_warning_note') }}
                            </div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 dark:focus:ring-offset-slate-800">{{ __('vender/transfer.confirm_transfer') }}</button>
                        @else
                            <p class="text-gray-700 dark:text-gray-300">{{ __('vender/transfer.select_booking_hint') }}</p>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($selectedBooking)
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    const transferI18n = {
        selectSchedule: @json(__('vender/transfer.select_new_schedule')),
        selectBus: @json(__('vender/transfer.select_new_bus')),
        selectBusFirst: @json(__('vender/transfer.select_new_bus_first')),
        noSchedules: @json(__('vender/transfer.no_schedules_found')),
        noBuses: @json(__('vender/transfer.no_buses_found')),
        errorLoading: @json(__('vender/transfer.error_loading_schedules')),
        errorLoadingBuses: @json(__('vender/transfer.error_loading_buses')),
        routeTo: @json(__('vender/history.route')),
        onDate: @json(__('vender/dashboard.date')),
        brandSame: @json(__('vender/transfer.brand_same_company')),
        brandEmergency: @json(__('vender/transfer.brand_emergency')),
    };
    const authCompanyId = @json(auth()->user()?->campany?->id);
    const allowEmergency = @json((bool) $allowEmergency);
    const ajaxSchedulesRoute = @json($ajaxSchedulesRoute);
    const ajaxBusesRoute = @json($ajaxBusesRoute);
    const ajaxAmountsRoute = @json($ajaxAmountsRoute);
    const sourceBookingId = @json($selectedBooking->id);

    $(document).ready(function() {
        var scheduleMeta = {};
        var busMeta = {};
        var transferMode = 'scheduled';

        function isEmergency() {
            return allowEmergency && transferMode === 'emergency';
        }

        function setMode(mode) {
            if (!allowEmergency) {
                return;
            }
            transferMode = mode;
            $('#transfer_mode').val(mode);
            const scheduledActive = 'px-4 py-2 text-sm font-medium rounded-md bg-teal-600 text-white shadow-sm';
            const emergencyActive = 'px-4 py-2 text-sm font-medium rounded-md bg-amber-600 text-white shadow-sm';
            const inactive = 'px-4 py-2 text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700';
            $('#modeScheduledBtn').attr('class', 'transfer-mode-btn ' + (mode === 'scheduled' ? scheduledActive : inactive));
            $('#modeEmergencyBtn').attr('class', 'transfer-mode-btn ' + (mode === 'emergency' ? emergencyActive : inactive));

            $('#destCompanyWrap').toggleClass('hidden', mode !== 'emergency');
            $('#unscheduledHint').toggleClass('hidden', mode !== 'emergency');
            $('#emergencyTimesWrap').toggleClass('hidden', mode !== 'emergency');
            $('#emergencyAgreement').toggleClass('hidden', mode !== 'emergency');
            $('#emergency_agreement').prop('required', mode === 'emergency').prop('disabled', mode !== 'emergency');
            $('#new_schedule_id').prop('required', mode !== 'emergency');
            $('#new_travel_date').prop('readonly', mode !== 'emergency')
                .toggleClass('cursor-not-allowed bg-gray-100 dark:bg-slate-900', mode !== 'emergency');

            if (mode === 'emergency') {
                $('#new_bus_id').prop('disabled', false);
            }
            loadSchedules();
        }

        function companyInitials(name) {
            return (name || '')
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map(function(part) { return part.charAt(0).toUpperCase(); })
                .join('') || '—';
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
            $('#receivingCompanyHint').text(cross || isEmergency() ? transferI18n.brandEmergency : transferI18n.brandSame);
            const inner = $('#receivingCompanyInner');
            if (cross || isEmergency()) {
                inner.attr('class', 'flex items-center gap-3 rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 p-4');
            } else {
                inner.attr('class', 'flex items-center gap-3 rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/30 p-4');
            }
        }

        function loadSchedules() {
            const newScheduleSelect = $('#new_schedule_id');
            scheduleMeta = {};
            newScheduleSelect.empty().append(`<option value="">${transferI18n.selectSchedule}</option>`);

            $.ajax({
                url: ajaxSchedulesRoute,
                method: 'GET',
                data: {
                    booking_id: sourceBookingId,
                    emergency: isEmergency() ? 1 : 0,
                    dest_company_id: $('#dest_company_id').val() || ''
                },
                success: function(response) {
                    if (response.length > 0) {
                        response.forEach(function(schedule) {
                            scheduleMeta[schedule.id] = schedule;
                            const busLabel = schedule.bus_number ? ` · ${schedule.bus_number}` : '';
                            const companyLabel = schedule.company_name ? ` · ${schedule.company_name}` : '';
                            newScheduleSelect.append(
                                `<option value="${schedule.id}">${schedule.from} ${transferI18n.routeTo} ${schedule.to} ${transferI18n.onDate} ${schedule.schedule_date} (${schedule.start} - ${schedule.end})${busLabel}${companyLabel}</option>`
                            );
                        });
                    } else {
                        newScheduleSelect.append(`<option value="">${transferI18n.noSchedules}</option>`);
                    }
                    if (isEmergency()) {
                        loadBuses();
                    } else {
                        resetBuses();
                    }
                },
                error: function() {
                    newScheduleSelect.append(`<option value="">${transferI18n.errorLoading}</option>`);
                }
            });
        }

        function resetBuses() {
            busMeta = {};
            $('#new_bus_id').prop('disabled', true)
                .empty().append(`<option value="">${transferI18n.selectBusFirst}</option>`);
            $('#receivingCompanyCard').addClass('hidden');
        }

        function loadBuses() {
            const scheduleId = $('#new_schedule_id').val();
            const newBusSelect = $('#new_bus_id');
            busMeta = {};

            if (!scheduleId && !isEmergency()) {
                resetBuses();
                return;
            }

            newBusSelect.prop('disabled', false).empty().append(`<option value="">${transferI18n.selectBus}</option>`);

            $.ajax({
                url: ajaxBusesRoute,
                method: 'GET',
                data: {
                    booking_id: sourceBookingId,
                    schedule_id: scheduleId || '',
                    emergency: isEmergency() ? 1 : 0,
                    dest_company_id: $('#dest_company_id').val() || ''
                },
                success: function(response) {
                    if (response.length > 0) {
                        response.forEach(function(bus) {
                            busMeta[bus.id] = bus;
                            const preferred = bus.is_schedule_bus ? ' selected' : '';
                            newBusSelect.append(
                                `<option value="${bus.id}"${preferred}>${bus.bus_number} (${bus.name || bus.company_name || ''})</option>`
                            );
                        });
                        if (newBusSelect.val()) {
                            onBusChange();
                        }
                    } else {
                        newBusSelect.append(`<option value="">${transferI18n.noBuses}</option>`);
                    }
                },
                error: function() {
                    newBusSelect.append(`<option value="">${transferI18n.errorLoadingBuses}</option>`);
                }
            });
        }

        function onScheduleChange() {
            const scheduleId = $('#new_schedule_id').val();
            const meta = scheduleMeta[scheduleId];
            if (meta) {
                $('#new_travel_date').val(meta.schedule_date || '');
                if (allowEmergency) {
                    if (meta.start) $('#emergency_start').val(String(meta.start).substring(0, 5));
                    if (meta.end) $('#emergency_end').val(String(meta.end).substring(0, 5));
                }
                showBrand(meta.company_name, meta.company_id);
            }
            loadBuses();
        }

        function onBusChange() {
            const busId = $('#new_bus_id').val();
            const meta = busMeta[busId];
            if (meta) {
                showBrand(meta.company_name, meta.company_id);
            }
            calculateAmounts();
        }

        function calculateAmounts() {
            const busId = $('#new_bus_id').val();
            const scheduleId = $('#new_schedule_id').val();
            const travelDate = $('#new_travel_date').val();
            const pickupPoint = $('#new_pickup_point').val();
            const droppingPoint = $('#new_dropping_point').val();

            if (!busId || !travelDate || !pickupPoint || !droppingPoint || !sourceBookingId) {
                return;
            }
            if (!isEmergency() && !scheduleId) {
                return;
            }

            $.ajax({
                url: ajaxAmountsRoute,
                method: 'GET',
                data: {
                    booking_id: sourceBookingId,
                    new_bus_id: busId,
                    new_schedule_id: scheduleId || '',
                    new_travel_date: travelDate,
                    new_pickup_point: pickupPoint,
                    new_dropping_point: droppingPoint,
                    emergency: isEmergency() ? 1 : 0
                },
                success: function(response) {
                    $('#new_amount').val(response.new_amount);
                    $('#new_busFee').val(response.new_busFee);
                    $('#new_discount_amount').val(response.new_discount_amount);
                    $('#new_distance').val(response.new_distance);
                    $('#new_bima_amount').val(response.new_bima_amount);
                    $('#new_vat').val(response.new_vat);
                    $('#new_fee').val(response.new_fee);
                    $('#new_service').val(response.new_service);
                    $('#new_vender_fee').val(response.new_vender_fee);
                    $('#new_vender_service').val(response.new_vender_service);
                    $('#new_campany_id').val(response.new_campany_id);
                    $('#new_route_id').val(response.new_route_id);
                    if (response.company_name) {
                        showBrand(response.company_name, response.company_id);
                    }
                },
                error: function(xhr) {
                    console.error('Error calculating amounts:', xhr);
                }
            });
        }

        if (allowEmergency) {
            $('#modeScheduledBtn').on('click', function() { setMode('scheduled'); });
            $('#modeEmergencyBtn').on('click', function() { setMode('emergency'); });
            $('#dest_company_id').on('change', function() {
                loadSchedules();
            });
        }
        $('#new_schedule_id').on('change', onScheduleChange);
        $('#new_bus_id').on('change', onBusChange);
        $('#new_pickup_point, #new_dropping_point, #new_travel_date').on('change', calculateAmounts);

        @if ($showCompanions)
        $('#selectAllCompanions').on('change', function() {
            $('.companion-checkbox').prop('checked', this.checked);
        });
        @endif

        loadSchedules();
    });
</script>
@endif
@endsection
