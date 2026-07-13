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
                                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition" data-booking-id="{{ $booking->id }}" data-created-at="{{ $booking->created_at->format('Y-m-d') }}">
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
                                                
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="font-medium mb-0">
                                                    {{ $booking->customer_name ?? __('vender/history.na') }}</p>
                                                <p class="text-gray-500 mb-0">
                                                    {{ $booking->customer_phone ?? __('vender/history.na') }}</p>
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
                                                $govLevyOnFare = (float) ($booking->government_levy ?? 0);
                                                $totalCommission = ($booking->fee ?? 0) + ($booking->vender_fee ?? 0);
                                            @endphp
                                            <div class="flex flex-col commission-breakdown"
                                                data-commission-total="{{ $totalCommission }}"
                                                data-discount="{{ $booking->discount_amount ?? 0 }}"
                                                data-gov-levy="{{ $govLevyOnFare }}"
                                                data-vat="{{ $booking->vat ?? 0 }}">
                                                <p class="text-gray-500 font-medium mb-0">
                                                    {{ __('vender/history.commission_total') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($totalCommission) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">
                                                    {{ __('vender/history.discount') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($booking->discount_amount ?? 0) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">{{ __('vender/history.government_levy') }}
                                                    {{ $currency ?? 'TSH' }} {{ convert_money($govLevyOnFare) }}</p>
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
                                                            value="{{ $booking }}">
                                                        <button type="submit"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('vender/history.print_ticket') }}</button>
                                                    </form>
                                                    <form action="{{ route('print.service') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="data"
                                                            value="{{ $booking }}">
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
    </div>


    @push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        (function() {
            var $ = window.jQuery;
            if (!$ || !$.fn.DataTable) return;
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
