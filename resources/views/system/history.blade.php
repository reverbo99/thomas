@extends('system.app')

@section('content')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Date Range Picker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <h4 class="text-blue-600 text-center text-lg font-semibold mb-4">{{ __('all.highlink_isgc') }}</h4>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Card Header -->
            <div class="p-4 bg-gradient-to-r from-blue-500 to-blue-400 text-white flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex flex-col">
                    <h2 class="text-lg font-semibold mb-2">{{ __('system.pages.history_title') }}</h2>
                    <div class="flex flex-wrap gap-3 text-sm font-medium">
                        <span>{{ __('system.pages.total_payment') }}: {{ $currency }} <span id="totalPayment">{{ convert_money($totalPayment ?? 0) }}</span></span>
                        <span>{{ __('system.pages.total_discount') }}: {{ $currency }} <span id="totalDiscount">{{ convert_money($totalDiscount ?? 0) }}</span></span>
                        <span>{{ __('system.pages.total_vat') }}: {{ $currency }} <span id="totalVAT">{{ convert_money($totalVAT ?? 0) }}</span></span>
                        <span>{{ __('system.pages.grand_total') }}: {{ $currency }} <span id="grandTotal">{{ convert_money($grandTotal ?? 0) }}</span></span>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-2">
                    <form method="GET" action="{{ route('system.history') }}" class="flex items-center gap-2">
                        <select name="channel" class="px-3 py-2 border rounded-lg text-sm" onchange="this.form.submit()">
                            <option value="">{{ __('all.sales_channel_all') }}</option>
                            <option value="online" @selected(($channelFilter ?? '') === 'online')>{{ __('all.sales_channel_online') }}</option>
                            <option value="in_person" @selected(($channelFilter ?? '') === 'in_person')>{{ __('all.sales_channel_in_person') }}</option>
                            <option value="phone" @selected(($channelFilter ?? '') === 'phone')>{{ __('all.sales_channel_phone') }}</option>
                        </select>
                    </form>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="text" class="px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm w-full sm:w-48" id="dateRangeFilter" placeholder="{{ __('system.pages.select_date_range') }}">
                        <button class="p-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" id="clearDateFilter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <button type="submit" form="manifestForm" class="px-3 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition text-sm">
                        {{ __('vender/history.print_manifest') }}
                    </button>
                    <button type="submit" form="incomeForm" class="px-3 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition text-sm">
                        {{ __('system.pages.print_service') }}
                    </button>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table id="busTable" class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium"></th>
                                @foreach ([
                                    __('system.pages.col_booking_id'),
                                    __('system.pages.col_bus_route'),
                                    __('system.pages.col_travel_details'),
                                    __('system.pages.col_passenger'),
                                    __('system.pages.col_seats_payment'),
                                    __('system.pages.col_commission'),
                                    __('system.pages.col_total'),
                                    __('system.common.actions'),
                                ] as $placeholder)
                                    <th class="py-2 px-4 text-left font-medium">
                                        <input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.pages.search_prefix', ['label' => $placeholder]) }}">
                                    </th>
                                @endforeach
                            </tr>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.pages.sn') }}</th>
                                @foreach ([
                                    __('system.pages.col_booking_id'),
                                    __('system.pages.col_bus_route'),
                                    __('system.pages.col_travel_details'),
                                    __('system.pages.col_passenger'),
                                    __('system.pages.col_seats_payment'),
                                    __('system.pages.col_commission'),
                                    __('system.pages.col_total'),
                                    __('system.common.actions'),
                                ] as $header)
                                    <th class="py-2 px-4 text-left font-medium">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs">
                            @if (isset($bookings) && $bookings->count())
                                @foreach ($bookings as $index => $booking)
                                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                        <td class="py-2 px-4 text-center">{{ $index + 1 }}</td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="font-medium mb-0">{{ $booking->booking_code ?? 'N/A' }}</p>
                                                <p class="text-gray-500 mb-0">{{ sales_channel_label($booking->sales_channel) }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('system.pages.confirmed') }}</p>
                                                <p class="text-gray-500 mb-0">Pay Time: {{ $booking->created_at->format('d M Y H:i') }}</p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <h6 class="font-medium mb-0">{{ $booking->campany->name ?? 'N/A' }}</h6>
                                                <p class="text-gray-500 mb-0">{{ $booking->schedule->from ?? $booking->route_name->from ?? 'N/A' }} to {{ $booking->schedule->to ?? $booking->route_name->to ?? 'N/A' }}</p>
                                                <p class="text-gray-500 mb-0">{{ $booking->bus->bus_number ?? 'N/A' }}</p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="font-medium mb-0 view-booking" data-id="{{ $booking->id }}" data-created-at="{{ $booking->created_at->format('Y-m-d') }}">{{ $booking->travel_date ?? 'N/A' }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('system.pages.seat') }}: {{ $booking->seat ?? __('system.common.na') }}</p>
                                                <p class="text-gray-500 mb-0">{{ __('system.pages.pickup') }}: {{ $booking->pickup_point ?? __('system.common.na') }}</p>
                                                <p class="text-gray-500 mb-0">Drop-point: {{ $booking->dropping_point ?? 'N/A' }}</p>
                                               
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="font-medium mb-0">{{ $booking->customer_name ?? 'N/A' }}</p>
                                                <p class="text-gray-500 mb-0">{{ $booking->customer_phone ?? 'N/A' }}</p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                <p class="text-gray-500 mb-0 payment-amount" data-amount="{{ $booking->amount ?? '0' }}" data-vat="{{ $booking->vat ?? '0' }}" data-discount="{{ $booking->discount_amount ?? '0' }}" data-fee="{{ $booking->fee ?? '0' }}" data-vender_fee="{{ $booking->vender_fee ?? '0' }}" data-fee_vat="{{ $booking->fee_vat ?? '0' }}">
                                                    {{ $currency }} {{ convert_money(($booking->amount ?? 0) + ($booking->vat ?? 0)) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            @php
                                                $govLevyOnFare = (float) ($booking->government_levy ?? 0);
                                                $govLevyOnService = (float) $booking->governmentLeviesOnService->sum('amount');
                                                $totalGovLevy = $govLevyOnFare + $govLevyOnService;
                                                $totalCommission = ($booking->fee ?? 0) + ($booking->vender_fee ?? 0);
                                            @endphp
                                            <div class="flex flex-col commission-breakdown"
                                                data-commission-total="{{ $totalCommission }}"
                                                data-discount="{{ $booking->discount_amount ?? 0 }}"
                                                data-gov-levy="{{ $totalGovLevy }}"
                                                data-vat="{{ $booking->vat ?? 0 }}">
                                                <p class="text-gray-500 font-medium mb-0">Commission: {{ $currency }} {{ convert_money($totalCommission) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">Discount: {{ $currency }} {{ convert_money($booking->discount_amount ?? 0) }}</p>
                                                <p class="text-gray-500 font-medium mb-0">Gov. levy: {{ $currency }} {{ convert_money($totalGovLevy) }}</p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="flex flex-col">
                                                @php
                                                    // Gross bus fee (fare before commission/service/levy extraction)
                                                    // straight from the bookings.busFee column.
                                                    $rowTotal = round((float) ($booking->busFee ?? 0));
                                                @endphp
                                                <p class="text-gray-500 font-medium mb-0 total-amount" data-total="{{ $rowTotal }}">
                                                    {{ $currency }} {{ convert_money($rowTotal) }}
                                                </p>
                                                <p class="hidden text-gray-500 font-medium mb-0">
                                                    {{ $currency }} {{ convert_money(round(($booking->fee ?? 0) + ($booking->vender_fee ?? 0) + ($booking->amount ?? 0) + ($booking->vat ?? 0) + ($booking->service ?? 0) + ($booking->vender_service ?? 0) + ($booking->fee_vat ?? 0))) }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="py-2 px-4">
                                            <div class="relative">
                                                <button class="px-3 py-1 bg-white text-blue-500 rounded-lg hover:bg-blue-50 transition flex items-center gap-1 text-sm" onclick="toggleDropdown(this)">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                    </svg>
                                                    Print
                                                </button>
                                                <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10">
                                                    <form action="{{ route('ticket.print') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="data" value="{{ $booking }}">
                                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('system.pages.print_ticket') }}</button>
                                                    </form>
                                                    <form action="{{ route('print.service') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="data" value="{{ $booking }}">
                                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('system.pages.print_service') }}</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="history-empty-row">
                                    @for ($col = 0; $col < 9; $col++)
                                        <td class="py-2 px-4 text-center text-gray-500">{{ $col === 0 ? __('system.pages.no_bookings_history') : '' }}</td>
                                    @endfor
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <form id="manifestForm" action="{{ route('system.print.manifest') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="data" value="">
    </form>
    <form id="incomeForm" action="{{ route('system.print') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="data" value="">
    </form>

    <!-- View Booking Modal -->
    <div id="viewBookingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 transform transition-all">
            <div class="p-4 flex justify-between items-center border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Booking Details</h2>
                <button type="button" class="text-gray-500 hover:text-gray-700" onclick="document.getElementById('viewBookingModal').classList.add('hidden')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4" id="modalContent">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="p-4 flex justify-end gap-2 border-t border-gray-200">
                <button type="button" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm" onclick="document.getElementById('viewBookingModal').classList.add('hidden')">{{ __('system.common.close') }}</button>
                <button type="button" class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm print-ticket">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                    </svg>
                    Print Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        window.historyCurrency = @json(session('currency', 'Tsh'));
        window.historyUsdToTzs = @json(app('usdToTzs') ?? 2500);

        function formatAmount(tzsAmount) {
            const isUsd = (window.historyCurrency || '').toLowerCase() === 'usd';
            const rate = window.historyUsdToTzs || 2500;
            const value = isUsd ? (tzsAmount / rate) : tzsAmount;
            return parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        $(document).ready(function() {
            const $table = $('#busTable');
            const hasBookingRows = $table.find('tbody tr').not('.history-empty-row').length > 0;
            if (!hasBookingRows) {
                return;
            }

            // Initialize DataTable
            DataTable.ext.errMode = 'none';
            let currentDateRange = null;
            const table = $table.DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                orderCellsTop: false,
                language: {
                    emptyTable: "No bookings found."
                },
                footerCallback: function() {
                    let totalPayment = 0;
                    let totalDiscount = 0;
                    let totalVAT = 0;
                    let grandTotal = 0;

                    this.api()
                        .rows({ search: 'applied' })
                        .every(function() {
                            const rowNode = this.node();
                            const paymentEl = $(rowNode).find('.payment-amount');
                            const totalEl = $(rowNode).find('.total-amount');
                            const amount = parseFloat(paymentEl.data('amount')) || 0;
                            const vat = parseFloat(paymentEl.data('vat')) || 0;
                            const discount = parseFloat(paymentEl.data('discount')) || 0;
                            const total = parseFloat(totalEl.data('total')) || 0;

                            totalPayment += amount + vat;
                            totalDiscount += discount;
                            totalVAT += vat;
                            grandTotal += total;
                        });

                    $('#totalPayment').text(formatAmount(totalPayment));
                    $('#totalDiscount').text(formatAmount(totalDiscount));
                    $('#totalVAT').text(formatAmount(totalVAT));
                    $('#grandTotal').text(formatAmount(grandTotal));
                }
            });

            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable?.id !== 'busTable') {
                    return true;
                }
                if (!currentDateRange) {
                    return true;
                }

                const api = $(settings.nTable).DataTable();
                const rowNode = api.row(dataIndex).node();
                if (!rowNode) {
                    return false;
                }

                const createdDateStr = $(rowNode).find('[data-created-at]').data('created-at');
                if (!createdDateStr) {
                    return false;
                }

                const createdDate = moment(createdDateStr, 'YYYY-MM-DD');
                return createdDate.isValid()
                    && !createdDate.isBefore(currentDateRange.start, 'day')
                    && !createdDate.isAfter(currentDateRange.end, 'day');
            });

            // Initialize date range picker
            $('#dateRangeFilter').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    applyLabel: 'Apply',
                    cancelLabel: 'Cancel',
                    fromLabel: 'From',
                    toLabel: 'To',
                    customRangeLabel: 'Custom',
                    daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                    monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    firstDay: 1
                }
            });

            // Apply date range filter
            $('#dateRangeFilter').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                currentDateRange = {
                    start: picker.startDate,
                    end: picker.endDate,
                };
                table.draw();
            });

            // Clear date filter
            $('#dateRangeFilter').on('cancel.daterangepicker', function() {
                $(this).val('');
                currentDateRange = null;
                table.draw();
            });

            $('#clearDateFilter').on('click', function() {
                $('#dateRangeFilter').val('');
                currentDateRange = null;
                table.draw();
            });

            // Column-specific search - properly map inputs to columns
            // The first row contains search inputs, second row contains headers
            // DataTable columns: 0=SN, 1=booking_id, 2=bus_route, 3=travel_details, 4=passenger, 5=seats_payment, 6=commission, 7=total, 8=action
            $('#busTable thead tr:first-child').find('th').each(function(index) {
                const input = $(this).find('input.search-input');
                if (input.length > 0) {
                    // Skip the first column (SN at index 0) and last column (Action at index 8)
                    if (index === 0 || index === 8) {
                        return;
                    }
                    
                    // Map input index to DataTable column index (they match: index 1-7)
                    let columnIndex = index;
                    
                    // Add debounce for better performance
                    let searchTimeout;
                    
                    // Add event listeners for search
                    input.on('keyup change paste', function() {
                        clearTimeout(searchTimeout);
                        const $input = $(this);
                        searchTimeout = setTimeout(function() {
                            const searchValue = $input.val().trim();
                            // Use column search with regex for better matching
                            table.column(columnIndex).search(searchValue, false, false).draw();
                        }, 300); // 300ms delay
                    });
                    
                    // Clear search when input is cleared
                    input.on('input', function() {
                        if ($(this).val() === '') {
                            clearTimeout(searchTimeout);
                            table.column(columnIndex).search('').draw();
                        }
                    });
                }
            });

            // Handle form submissions for filtered data
            $('#manifestForm, #incomeForm').on('submit', function(e) {
                e.preventDefault();
                let form = $(this);
                let filteredData = [];

                table.rows({ filter: 'applied' }).every(function() {
                    let row = this.data();
                    filteredData.push({
                        booking_code: ($(row[1]).find('p').first().text().trim() || 'N/A'),
                        company_name: ($(row[2]).find('h6').text().trim() || 'N/A'),
                        route_from: ($(row[2]).find('p').eq(0).text().split(' to ')[0]?.trim() || 'N/A'),
                        route_to: ($(row[2]).find('p').eq(0).text().split(' to ')[1]?.trim() || 'N/A'),
                        bus_number: ($(row[2]).find('p').eq(1).text().trim() || 'N/A'),
                        travel_date: ($(row[3]).find('[data-created-at]').data('created-at') || 'N/A'),
                        seat: ($(row[3]).find('p').eq(1).text().replace('Seat: ', '').trim() || 'N/A'),
                        pickup_point: ($(row[3]).find('p').eq(2).text().replace('Pickup: ', '').trim() || 'N/A'),
                        customer_name: ($(row[4]).find('p').first().text().trim() || 'N/A'),
                        customer_phone: ($(row[4]).find('p').eq(1).text().trim() || 'N/A'),
                        amount: ($(row[5]).find('p').first().text().trim() || 'N/A'),
                        commision: (function() {
                            const c = $(row[6]).find('.commission-breakdown');
                            return c.length ? ('{{ $currency }} ' + formatAmount(parseFloat(c.attr('data-commission-total')) || 0)) : (($(row[6]).find('p').first().text().replace('Commission: ', '').trim()) || 'N/A');
                        })(),
                        discount: (function() {
                            const c = $(row[6]).find('.commission-breakdown');
                            return c.length ? ('{{ $currency }} ' + formatAmount(parseFloat(c.attr('data-discount')) || 0)) : (($(row[6]).find('p').eq(1).text().replace('Discount: ', '').trim()) || 'N/A');
                        })(),
                        gov_levy: (function() {
                            const c = $(row[6]).find('.commission-breakdown');
                            return c.length ? ('{{ $currency }} ' + formatAmount(parseFloat(c.attr('data-gov-levy')) || 0)) : (($(row[6]).find('p').eq(2).text().replace('Gov. levy: ', '').trim()) || 'N/A');
                        })(),
                        vat: (function() {
                            const c = $(row[6]).find('.commission-breakdown');
                            return c.length ? ('{{ $currency }} ' + formatAmount(parseFloat(c.attr('data-vat')) || 0)) : (($(row[6]).find('p').eq(3).text().replace('VAT: ', '').trim()) || 'N/A');
                        })(),
                        total: (function() {
                            // Total = gross bus fee (bookings.busFee), excluding commission/service/levy
                            let totalEl = $(row[7]).find('.total-amount');
                            let calculatedTotal = parseFloat(totalEl.data('total')) || 0;
                            return calculatedTotal.toFixed(2);
                        })()
                    });
                });

                form.find('input[name="data"]').val(JSON.stringify(filteredData));
                form.off('submit').submit(); // Prevent infinite loop and submit
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
        .search-input {
            width: 100%;
            padding: 4px;
            font-size: 12px;
            border-radius: 4px;
        }
        .daterangepicker {
            z-index: 9999 !important;
        }
        #dateRangeFilter {
            min-width: 150px;
            text-align: center;
        }
        @media (max-width: 640px) {
            #dateRangeFilter {
                min-width: 100%;
            }
        }
    </style>
@endsection