@extends('admin.app')

@section('content')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <div class="container mx-auto px-4 py-6 max-w-full">
        <h4 class="text-blue-600 text-center text-lg font-semibold mb-4">{{ __('vender/history.highlink_isgc') }}</h4>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Card Header -->
            <div
                class="p-4 bg-gradient-to-r from-teal-500 to-teal-400 text-white flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex flex-col">
                    <h2 class="text-lg font-semibold mb-2">{{ __('vender/history.booking_history') }}</h2>
                    <div class="flex flex-wrap gap-3 text-sm font-medium">
                        <span>{{ __('vender/history.total_payment') }} {{ $currency ?? 'TSH' }} <span id="totalPayment">{{ convert_money($totalPayment ?? 0) }}</span></span>
                        <span>{{ __('vender/history.total_discount') }} {{ $currency ?? 'TSH' }} <span id="totalDiscount">{{ convert_money($totalDiscount ?? 0) }}</span></span>
                        <span>{{ __('vender/history.total_vat') }} {{ $currency ?? 'TSH' }} <span id="totalVAT">{{ convert_money($totalVAT ?? 0) }}</span></span>
                        <span>{{ __('vender/history.grand_total') }} {{ $currency ?? 'TSH' }} <span id="grandTotal">{{ convert_money($grandTotal ?? 0) }}</span></span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-2 flex-wrap">
                    @include('partials.booking_history_period_filter', [
                        'formAction' => route('history'),
                        'resetUrl' => route('history'),
                        'period' => $period ?? request('period'),
                        'startDate' => $startDate ?? request('start_date'),
                        'endDate' => $endDate ?? request('end_date'),
                        'labelClass' => 'text-white',
                        'columnFilters' => [
                            ['name' => 'bus_name', 'type' => 'text', 'label' => __('system.pages.filter_bus_name'), 'value' => request('bus_name')],
                            ['name' => 'bus_number', 'type' => 'text', 'label' => __('system.pages.filter_plate_number'), 'value' => request('bus_number')],
                            ['name' => 'departure_date', 'type' => 'date', 'label' => __('system.pages.filter_departure_date'), 'value' => request('departure_date')],
                            ['name' => 'departure_time', 'type' => 'time', 'label' => __('system.pages.filter_departure_time'), 'value' => request('departure_time')],
                            ['name' => 'driver', 'type' => 'text', 'label' => __('system.pages.filter_driver'), 'value' => request('driver')],
                            ['name' => 'conductor', 'type' => 'text', 'label' => __('system.pages.filter_conductor'), 'value' => request('conductor')],
                        ],
                    ])
                    <div class="relative w-full sm:w-auto">
                        <button type="button"
                            class="px-3 py-2 bg-white text-blue-500 rounded-lg hover:bg-blue-50 transition flex items-center gap-1 text-sm w-full sm:w-auto"
                            onclick="toggleDropdown(this)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                            {{ __('vender/history.actions') }}
                        </button>
                        <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10">
                            <form action="{{ route('admin.print.manifest') }}" method="POST" id="manifestForm">
                                @csrf
                                <input type="hidden" name="booking_ids" id="manifestBookingIds" value="">
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full">{{ __('vender/history.print_manifest') }}</button>
                            </form>
                            <form action="{{ route('admin.print') }}" method="POST" id="incomeForm">
                                @csrf
                                <input type="hidden" name="booking_ids" id="incomeBookingIds" value="">
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full">{{ __('vender/history.print_income') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table id="busTable" class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium">{{ __('vender/history.sn') }}</th>
                                @foreach (['booking_id', 'bus_route', 'travel_details', 'passenger', 'seats_payment', 'commission', 'total', 'action'] as $key)
                                    <th class="py-2 px-4 text-left font-medium">{{ __('vender/history.' . $key) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs">
                            @if (isset($bookings) && $bookings->count())
                                @foreach ($bookings as $index => $booking)
                                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition" data-booking-id="{{ $booking->id }}" data-created-at="{{ $booking->created_at->format('Y-m-d') }}"
                                        data-has-excess-luggage="{{ (int) ($booking->has_excess_luggage ?? 0) }}"
                                        data-excess-luggage-fee="{{ (float) ($booking->excess_luggage_fee ?? 0) }}"
                                        data-excess-luggage-description="{{ e($booking->excess_luggage_description ?? '') }}"
                                        data-estimated-weight="{{ $booking->estimated_weight !== null ? (float) $booking->estimated_weight : '' }}"
                                        data-actual-weight="{{ $booking->actual_weight !== null ? (float) $booking->actual_weight : '' }}"
                                        data-actual-length="{{ $booking->actual_length !== null ? (float) $booking->actual_length : '' }}"
                                        data-actual-height="{{ $booking->actual_height !== null ? (float) $booking->actual_height : '' }}"
                                        data-actual-width="{{ $booking->actual_width !== null ? (float) $booking->actual_width : '' }}"
                                        data-luggage-refund-amount="{{ $booking->luggage_refund_amount !== null ? (float) $booking->luggage_refund_amount : '' }}"
                                        data-luggage-weight-verdict="{{ e($booking->luggage_weight_verdict ?? '') }}"
                                        data-luggage-payment-status="{{ e($booking->luggage_payment_status ?? '') }}"
                                        data-luggage-status="{{ e($booking->luggage_status ?? '') }}"
                                        data-payment-status="{{ e($booking->payment_status ?? '') }}"
                                        data-booking-code="{{ e($booking->booking_code ?? '') }}"
                                        data-infant-child="{{ (int) ($booking->infant_child ?? 0) }}">
                                        <td class="py-2 px-4 text-center">{{ $index + 1 }}</td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="font-medium mb-0">
                                                    {{ $booking->booking_code ?? __('vender/history.na') }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('vender/history.confirmed') }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('vender/history.paid_time') }}:
                                                    {{ $booking->created_at->format('d M Y H:i A') }}</p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <h6 class="font-medium mb-0">
                                                    {{ $booking->campany->name ?? __('vender/history.na') }}</h6>
                                                <p class="text-gray-500 mb-0">
                                                    {{ $booking->schedule->from ?? __('vender/history.na') }}
                                                    {{ __('vender/history.to') }}
                                                    {{ $booking->schedule->to ?? __('vender/history.na') }}</p>
                                                <p class="text-gray-500 mb-0">
                                                    {{ $booking->bus->bus_number ?? __('vender/history.na') }}</p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="font-medium mb-0 view-booking" data-id="{{ $booking->id }}" data-created-at="{{ $booking->created_at->format('Y-m-d') }}">
                                                    {{ $booking->travel_date ?? __('vender/history.na') }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('vender/history.seat') }}
                                                    {{ $booking->seat ?? __('vender/history.na') }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('vender/history.pickup') }}
                                                    {{ $booking->pickup_point ?? __('vender/history.na') }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('vender/history.drop_point') }}
                                                    {{ $booking->dropping_point ?? __('vender/history.na') }}</p>
                                                @if ($booking->has_excess_luggage ?? false)
                                                    <p class="mb-0">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 text-[10px] font-medium">
                                                            {{ __('vender/luggage.excess_luggage') }}: {{ $currency ?? 'TSH' }} {{ convert_money($booking->excess_luggage_fee ?? 0) }}
                                                        </span>
                                                    </p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="font-medium mb-0">
                                                    {{ $booking->customer_name ?? __('vender/history.na') }}</p>
                                                <p class="text-gray-500 mb-0">
                                                    {{ $booking->customer_phone ?? __('vender/history.na') }}</p>
                                                @if (!empty($booking->infant_child))
                                                    <p class="mb-0 mt-1">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-medium">
                                                            {{ __('vender/history.infant_badge') }}
                                                        </span>
                                                    </p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="text-gray-500 mb-0 payment-amount"
                                                    data-amount="{{ $booking->amount ?? '0' }}"
                                                    data-vat="{{ $booking->vat ?? '0' }}"
                                                    data-discount="{{ $booking->discount_amount ?? '0' }}"
                                                    data-fee="{{ $booking->fee ?? '0' }}"
                                                    data-vender_fee="{{ $booking->vender_fee ?? '0' }}"
                                                    data-fee_vat="{{ $booking->fee_vat ?? '0' }}">
                                                    {{ $currency ?? 'TSH' }} {{ convert_money(($booking->amount ?? 0) + ($booking->vat ?? 0)) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            @php
                                                $govLevyOnFare = booking_government_levy_on_fare($booking);
                                                $govLevyOnService = booking_government_levy_on_service($booking);
                                                $totalCommission = ($booking->fee ?? 0) + ($booking->vender_fee ?? 0);
                                                $rowServiceFee = booking_service_fee($booking);
                                            @endphp
                                            <div class="flex flex-col commission-breakdown"
                                                data-commission-total="{{ $totalCommission }}"
                                                data-service-fee="{{ $rowServiceFee }}"
                                                data-discount="{{ $booking->discount_amount ?? 0 }}"
                                                data-gov-levy="{{ $govLevyOnFare + $govLevyOnService }}"
                                                data-vat="{{ $booking->vat ?? 0 }}">
                                                <p class="text-gray-500 font-medium mb-0">
                                                    {{ __('vender/history.commission_total') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($totalCommission) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">
                                                    {{ __('vender/history.service_fee') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($rowServiceFee) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">
                                                    {{ __('vender/history.discount') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($booking->discount_amount ?? 0) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">{{ __('vender/history.government_levy') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($govLevyOnFare) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">{{ __('vender/history.government_levy_service') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($govLevyOnService) }}</p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                @php
                                                    // Show the gross bus fee (fare before commission/service/levy extraction)
                                                    // straight from the bookings.busFee column.
                                                    $rowTotal = round((float) ($booking->busFee ?? 0));
                                                @endphp
                                                <p class="text-gray-500 font-medium mb-0 total-amount"
                                                    data-total="{{ $rowTotal }}">
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($rowTotal) }}
                                                </p>
                                                <p class="hidden text-gray-500 font-medium mb-0">
                                                    {{ $currency ?? 'TSH' }} {{ convert_money(round(($booking->fee ?? 0) + ($booking->vender_fee ?? 0) + ($booking->amount ?? 0) + ($booking->vat ?? 0) + ($booking->service ?? 0) + ($booking->vender_service ?? 0) + ($booking->fee_vat ?? 0))) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="relative">
                                                <button
                                                    class="px-3 py-1 bg-white text-blue-500 rounded-lg hover:bg-blue-50 transition flex items-center gap-1 text-sm"
                                                    onclick="toggleDropdown(this)">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                    </svg>
                                                    {{ __('vender/history.print') }}
                                                </button>
                                                <div
                                                    class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10">
                                                    <form action="{{ route('ticket.print') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="data"
                                                            value='{{ json_encode(["id" => $booking->id, "booking_code" => $booking->booking_code]) }}'>
                                                        <button type="submit"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('vender/history.print_ticket') }}</button>
                                                    </form>
                                                    <form action="{{ route('print.service') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="data"
                                                            value='{{ json_encode(["id" => $booking->id, "booking_code" => $booking->booking_code]) }}'>
                                                        <button type="submit"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('vender/history.print_service') }}</button>
                                                    </form>
                                                    <form
                                                        action="{{ route('booking.transfer.form', ['booking_id' => $booking->id]) }}"
                                                        method="GET">
                                                        <button type="submit"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transfer
                                                            Booking</button>
                                                    </form>
                                                    <button type="button"
                                                        class="excess-luggage-btn block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        data-booking-id="{{ $booking->id }}">{{ __('vender/luggage.add_edit_excess_luggage') }}</button>
                                                    <a href="{{ route('bus_owner.excess_luggage.show', $booking->id) }}"
                                                        class="block w-full text-left px-4 py-2 text-sm text-teal-700 hover:bg-gray-100">{{ __('vender/luggage.process_title') }} ({{ __('vender/luggage.tracking') }})</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="py-2 px-4 text-center text-gray-500">{{ __('vender/history.no_bookings_found') }}</td>
                                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View Booking Modal -->
        <div id="viewBookingModal"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 transform transition-all">
                <div class="p-4 flex justify-between items-center border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">{{ __('vender/history.booking_details') }}</h2>
                    <button type="button" class="text-gray-500 hover:text-gray-700"
                        onclick="document.getElementById('viewBookingModal').classList.add('hidden')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4" id="modalContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-gray-200">
                    <button type="button"
                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm"
                        onclick="document.getElementById('viewBookingModal').classList.add('hidden')">{{ __('vender/history.close') }}</button>
                    <button type="button"
                        class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm print-ticket">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16">
                            </path>
                        </svg>
                        {{ __('vender/history.print_ticket') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Excess Luggage Modal -->
        <div id="excessLuggageModal"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4 transform transition-all">
                <div class="p-4 flex justify-between items-center border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">{{ __('vender/luggage.excess_luggage') }}</h2>
                    <button type="button" class="text-gray-500 hover:text-gray-700" id="excessLuggageCloseBtn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="excessLuggageForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="luggage_action" id="excessLuggageActionInput" value="set">
                    <div class="p-4 space-y-4">
                        <div>
                            <label for="excessLuggageFeeInput" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.excess_luggage_fee') }}</label>
                            <input type="number" step="0.01" min="0" name="excess_luggage_fee" id="excessLuggageFeeInput"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="excessLuggageDescInput" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.excess_luggage_description') }}</label>
                            <textarea name="excess_luggage_description" id="excessLuggageDescInput" rows="2"
                                placeholder="{{ __('vender/luggage.excess_luggage_description_placeholder') }}"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>
                        <div class="border-t border-gray-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">{{ __('vender/luggage.weigh_in_section') }}</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.estimated_weight') }}</label>
                                    <p id="excessLuggageEstimatedWeightDisplay" class="mt-1 px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-sm text-gray-600">—</p>
                                </div>
                                <div>
                                    <label for="excessLuggageActualWeightInput" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_weight') }}</label>
                                    <input type="number" step="0.1" min="0" name="actual_weight" id="excessLuggageActualWeightInput"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-3 mt-3">
                                <div>
                                    <label for="excessLuggageActualLengthInput" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_length') }}</label>
                                    <input type="number" step="0.1" min="0" name="actual_length" id="excessLuggageActualLengthInput"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="excessLuggageActualHeightInput" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_height') }}</label>
                                    <input type="number" step="0.1" min="0" name="actual_height" id="excessLuggageActualHeightInput"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="excessLuggageActualWidthInput" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_width') }}</label>
                                    <input type="number" step="0.1" min="0" name="actual_width" id="excessLuggageActualWidthInput"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="mt-3 rounded-md border border-teal-100 bg-teal-50/60 p-3 space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-teal-800">{{ __('vender/luggage.auto_reconciliation') }}</p>
                                <p class="text-xs text-gray-500">{{ __('vender/luggage.fee_per_kg_label') }}:
                                    {{ session('currency', 'TZS') }} {{ convert_money((float) (\App\Models\Setting::first()->excess_luggage_fee_per_kg ?? 0)) }}
                                </p>
                                <div>
                                    <label for="excessLuggageVerdictDisplay" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.weight_verdict') }}</label>
                                    <input type="text" id="excessLuggageVerdictDisplay" readonly
                                        class="mt-1 block w-full px-3 py-2 border border-gray-200 bg-white rounded-md shadow-sm sm:text-sm text-gray-800">
                                    <input type="hidden" name="luggage_weight_verdict" id="excessLuggageVerdictInput" value="">
                                    <p class="text-xs text-gray-500 mt-1">{{ __('vender/luggage.weight_verdict_hint') }}</p>
                                </div>
                                <div>
                                    <label for="excessLuggageRefundInput" class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.refund_payment_amount') }}</label>
                                    <input type="number" step="0.01" name="luggage_refund_amount" id="excessLuggageRefundInput" readonly
                                        class="mt-1 block w-full px-3 py-2 border border-gray-200 bg-white rounded-md shadow-sm sm:text-sm text-gray-800">
                                    <p class="text-xs text-gray-500 mt-1" id="excessLuggageRefundHint">{{ __('vender/luggage.refund_payment_hint') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 flex items-center justify-between border-t border-gray-200 gap-2">
                        <button type="button" id="excessLuggageRemoveBtn"
                            class="px-3 py-1.5 text-sm text-red-600 hover:text-red-800 hidden">{{ __('vender/luggage.remove') }}</button>
                        <a href="#" target="_blank" id="excessLuggageReceiptBtn"
                            class="px-3 py-1.5 text-sm text-blue-600 hover:text-blue-800 hidden">{{ __('vender/luggage.print_receipt') }}</a>
                        <div class="flex gap-2 ml-auto">
                            <button type="button" id="excessLuggageCancelBtn"
                                class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">{{ __('vender/luggage.cancel') }}</button>
                            <button type="submit"
                                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">{{ __('vender/luggage.save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        (function() {
            var $ = window.jQuery;
            if (!$ || !$.fn.DataTable) return;

            window.historyCurrency = @json(session('currency', 'Tsh'));
            window.historyUsdToTzs = @json(app('usdToTzs') ?? 2500);

            function formatHistoryAmount(tzsAmount) {
                var isUsd = (window.historyCurrency || '').toLowerCase() === 'usd';
                var rate = window.historyUsdToTzs || 2500;
                var value = isUsd ? (tzsAmount / rate) : tzsAmount;
                return parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            $(function() {
                var $table = $('#busTable');
                if (!$table.length || $table.hasClass('dataTable')) return;
                $.fn.dataTable.ext.errMode = 'none';
                var table = $table.DataTable({
                    paging: true,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('all.dt_all') }}"]],
                    searching: true,
                    ordering: true,
                    order: [[1, 'desc']],
                    language: {
                        emptyTable: "{{ __('vender/history.no_bookings_found') }}",
                        search: "{{ __('all.dt_search') }}",
                        lengthMenu: "{{ __('all.dt_show_entries') }}",
                        info: "{{ __('all.dt_info') }}",
                        paginate: {
                            first: "{{ __('all.dt_first') }}",
                            last: "{{ __('all.dt_last') }}",
                            next: "{{ __('all.dt_next') }}",
                            previous: "{{ __('all.dt_previous') }}"
                        }
                    },
                    footerCallback: function() {
                        var totalPayment = 0;
                        var totalDiscount = 0;
                        var totalVAT = 0;
                        var grandTotal = 0;

                        this.api()
                            .rows({ search: 'applied' })
                            .every(function() {
                                var rowNode = this.node();
                                var paymentEl = $(rowNode).find('.payment-amount');
                                var totalEl = $(rowNode).find('.total-amount');
                                var amount = parseFloat(paymentEl.data('amount')) || 0;
                                var vat = parseFloat(paymentEl.data('vat')) || 0;
                                var discount = parseFloat(paymentEl.data('discount')) || 0;
                                var total = parseFloat(totalEl.data('total')) || 0;

                                totalPayment += amount + vat;
                                totalDiscount += discount;
                                totalVAT += vat;
                                grandTotal += total;
                            });

                        $('#totalPayment').text(formatHistoryAmount(totalPayment));
                        $('#totalDiscount').text(formatHistoryAmount(totalDiscount));
                        $('#totalVAT').text(formatHistoryAmount(totalVAT));
                        $('#grandTotal').text(formatHistoryAmount(grandTotal));
                    }
                });

                function getVisibleBookingIds() {
                    var ids = [];
                    table.rows({ filter: 'applied', search: 'applied' }).every(function() {
                        var rowNode = this.node();
                        var id = $(rowNode).attr('data-booking-id') || $(rowNode).find('[data-booking-id]').first().attr('data-booking-id');
                        if (id) ids.push(parseInt(id, 10));
                    });
                    return ids;
                }
                $('#manifestForm, #incomeForm').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var ids = getVisibleBookingIds();
                    if (ids.length === 0) {
                        alert('{{ __('vender/history.no_bookings_found') }}');
                        return false;
                    }
                    form.find('input[name="booking_ids"]').val(JSON.stringify(ids));
                    form.off('submit').submit();
                });

            // View booking details
            $(document).on('click', '.view-booking', function() {
                const bookingId = $(this).data('id');
                $.ajax({
                    url: '{{ route('history.show', ':id') }}'.replace(':id', bookingId),
                    method: 'GET',
                    success: function(response) {
                        $('#modalContent').html(response.html);
                        document.getElementById('viewBookingModal').classList.remove('hidden');
                    },
                    error: function(xhr) {
                        console.error('Error fetching booking details:', xhr);
                    }
                });
            });

            // Close modal when clicking outside
            document.getElementById('viewBookingModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });

            // Excess luggage modal
            const excessLuggageModal = document.getElementById('excessLuggageModal');
            const excessLuggageForm = document.getElementById('excessLuggageForm');
            const excessLuggageFeeInput = document.getElementById('excessLuggageFeeInput');
            const excessLuggageDescInput = document.getElementById('excessLuggageDescInput');
            const excessLuggageActionInput = document.getElementById('excessLuggageActionInput');
            const excessLuggageRemoveBtn = document.getElementById('excessLuggageRemoveBtn');
            const excessLuggageEstimatedWeightDisplay = document.getElementById('excessLuggageEstimatedWeightDisplay');
            const excessLuggageActualWeightInput = document.getElementById('excessLuggageActualWeightInput');
            const excessLuggageActualLengthInput = document.getElementById('excessLuggageActualLengthInput');
            const excessLuggageActualHeightInput = document.getElementById('excessLuggageActualHeightInput');
            const excessLuggageActualWidthInput = document.getElementById('excessLuggageActualWidthInput');
            const excessLuggageVerdictInput = document.getElementById('excessLuggageVerdictInput');
            const excessLuggageVerdictDisplay = document.getElementById('excessLuggageVerdictDisplay');
            const excessLuggageRefundInput = document.getElementById('excessLuggageRefundInput');
            const excessLuggageRefundHint = document.getElementById('excessLuggageRefundHint');
            const excessLuggageReceiptBtn = document.getElementById('excessLuggageReceiptBtn');
            const excessLuggageUrlTemplate = '{{ route('booking.excess_luggage.update', ':id') }}';
            const excessLuggageReceiptUrlTemplate = '{{ route('excess_luggage.receipt.print', ':id') }}';
            const luggageFeePerKg = {{ json_encode((float) (\App\Models\Setting::first()->excess_luggage_fee_per_kg ?? 0)) }};
            const luggageVerdictLabels = {
                underestimated: @json(__('vender/luggage.weight_verdict_underestimated')),
                overestimated: @json(__('vender/luggage.weight_verdict_overestimated')),
                correct: @json(__('vender/luggage.weight_verdict_correct')),
            };
            const luggageDeltaHints = {
                positive: @json(__('vender/luggage.auto_delta_additional')),
                negative: @json(__('vender/luggage.auto_delta_refund')),
                zero: @json(__('vender/luggage.auto_delta_none')),
            };
            let currentEstimatedWeight = null;

            function roundLuggageDelta(n) { return Math.round(n * 100) / 100; }

            function recomputeLuggageDelta() {
                const actualRaw = excessLuggageActualWeightInput.value !== ''
                    ? parseFloat(excessLuggageActualWeightInput.value) : NaN;
                const paid = excessLuggageFeeInput.value !== ''
                    ? parseFloat(excessLuggageFeeInput.value) : 0;
                let delta = 0;

                if (!isNaN(actualRaw)) {
                    if (luggageFeePerKg > 0) {
                        if (currentEstimatedWeight !== null && currentEstimatedWeight !== undefined) {
                            delta = roundLuggageDelta((actualRaw - currentEstimatedWeight) * luggageFeePerKg);
                        } else {
                            delta = roundLuggageDelta((actualRaw * luggageFeePerKg) - (isNaN(paid) ? 0 : paid));
                        }
                    } else if (currentEstimatedWeight && currentEstimatedWeight > 0 && !isNaN(paid) && paid > 0) {
                        delta = roundLuggageDelta(paid * ((actualRaw - currentEstimatedWeight) / currentEstimatedWeight));
                    }
                }

                if (Math.abs(delta) < 0.005) delta = 0;

                let verdict = 'correct';
                if (delta > 0) verdict = 'underestimated';
                else if (delta < 0) verdict = 'overestimated';

                if (excessLuggageVerdictInput) excessLuggageVerdictInput.value = verdict;
                if (excessLuggageVerdictDisplay) {
                    excessLuggageVerdictDisplay.value = luggageVerdictLabels[verdict] || verdict;
                }
                if (excessLuggageRefundInput) excessLuggageRefundInput.value = delta.toFixed(2);
                if (excessLuggageRefundHint) {
                    if (delta > 0) excessLuggageRefundHint.textContent = luggageDeltaHints.positive;
                    else if (delta < 0) excessLuggageRefundHint.textContent = luggageDeltaHints.negative;
                    else excessLuggageRefundHint.textContent = luggageDeltaHints.zero;
                }
            }

            function closeExcessLuggageModal() {
                excessLuggageModal.classList.add('hidden');
            }

            $(document).on('click', '.excess-luggage-btn', function() {
                const bookingId = $(this).data('booking-id');
                const row = $(this).closest('tr').length ? $(this).closest('tr') : $('tr[data-booking-id="' + bookingId + '"]');
                const currentFee = parseFloat(row.attr('data-excess-luggage-fee')) || 0;
                const currentDesc = row.attr('data-excess-luggage-description') || '';
                const hasLuggage = row.attr('data-has-excess-luggage') === '1';
                const estimatedWeight = row.attr('data-estimated-weight') || '';
                const actualWeight = row.attr('data-actual-weight') || '';
                const actualLength = row.attr('data-actual-length') || '';
                const actualHeight = row.attr('data-actual-height') || '';
                const actualWidth = row.attr('data-actual-width') || '';
                const luggagePayStatus = (row.attr('data-luggage-payment-status') || '').toLowerCase();
                const luggageStatus = (row.attr('data-luggage-status') || '').toLowerCase();
                const bookingPayStatus = row.attr('data-payment-status') || '';
                const refundAmount = row.attr('data-luggage-refund-amount') || '';
                const refundNum = parseFloat(refundAmount);
                const amountDue = (!isNaN(refundNum) && refundNum > 0 && luggagePayStatus !== 'paid') ? refundNum : 0;
                const canPrintReceipt = hasLuggage
                    && bookingPayStatus === 'Paid'
                    && amountDue <= 0
                    && luggagePayStatus !== 'pending'
                    && luggageStatus !== 'awaiting_payment';

                currentEstimatedWeight = estimatedWeight !== '' ? parseFloat(estimatedWeight) : null;
                if (currentEstimatedWeight !== null && isNaN(currentEstimatedWeight)) {
                    currentEstimatedWeight = null;
                }

                excessLuggageForm.setAttribute('action', excessLuggageUrlTemplate.replace(':id', bookingId));
                excessLuggageActionInput.value = 'set';
                excessLuggageFeeInput.value = hasLuggage ? currentFee : 2500;
                excessLuggageDescInput.value = currentDesc;
                excessLuggageEstimatedWeightDisplay.textContent = estimatedWeight !== '' ? (estimatedWeight + ' kg') : '{{ __('vender/luggage.not_declared') }}';
                excessLuggageActualWeightInput.value = actualWeight;
                excessLuggageActualLengthInput.value = actualLength;
                excessLuggageActualHeightInput.value = actualHeight;
                excessLuggageActualWidthInput.value = actualWidth;
                excessLuggageRemoveBtn.classList.toggle('hidden', !hasLuggage);
                excessLuggageReceiptBtn.classList.toggle('hidden', !canPrintReceipt);
                excessLuggageReceiptBtn.setAttribute('href', excessLuggageReceiptUrlTemplate.replace(':id', bookingId));
                recomputeLuggageDelta();
                excessLuggageModal.classList.remove('hidden');
            });

            ['input', 'change'].forEach(function (evt) {
                if (excessLuggageActualWeightInput) {
                    excessLuggageActualWeightInput.addEventListener(evt, recomputeLuggageDelta);
                }
                if (excessLuggageFeeInput) {
                    excessLuggageFeeInput.addEventListener(evt, recomputeLuggageDelta);
                }
            });

            excessLuggageRemoveBtn.addEventListener('click', function() {
                if (!confirm(@json(__('vender/luggage.confirm_remove')))) return;
                excessLuggageActionInput.value = 'remove';
                excessLuggageForm.submit();
            });

            document.getElementById('excessLuggageCloseBtn').addEventListener('click', closeExcessLuggageModal);
            document.getElementById('excessLuggageCancelBtn').addEventListener('click', closeExcessLuggageModal);
            excessLuggageModal.addEventListener('click', function(e) {
                if (e.target === this) closeExcessLuggageModal();
            });
            });
        })();
    </script>
    @endpush

    <script>
        // Toggle dropdown
        function toggleDropdown(button) {
            const dropdown = button.nextElementSibling;
            dropdown.classList.toggle('hidden');
            document.addEventListener('click', function closeDropdown(e) {
                if (!button.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }
    </script>

    <style>
        .dataTables_wrapper .dataTables_filter input {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
        .dataTables_wrapper .dataTables_length select {
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
    </style>
@endsection
