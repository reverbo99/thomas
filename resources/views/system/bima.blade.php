@extends('system.app')

@section('content')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- DataTables DateTime CSS -->
    <link href="https://cdn.datatables.net/datetime/1.5.1/css/dataTables.dateTime.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <div class="container mx-auto px-4 py-6 max-w-full">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-blue-500 to-blue-400 text-white flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-semibold">{{ __('system.pages.bima_title') }}</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('bima.pdf', request()->only(['period', 'start_date', 'end_date'])) }}" target="_blank"
                       class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                        {{ __('system.pages.export_pdf') }}
                    </a>
                    <a href="{{ route('bima.csv', request()->only(['period', 'start_date', 'end_date'])) }}"
                       class="inline-flex items-center px-3 py-2 bg-white text-blue-700 rounded-lg hover:bg-blue-50 transition text-sm">
                        {{ __('system.pages.export_csv') }}
                    </a>
                    <span class="text-sm font-medium">Total: {{ $currency }} <span id="bimaTotal">0</span></span>
                </div>
            </div>

            <div class="p-4">
                <div class="flex flex-col sm:flex-row gap-4 mb-4">
                    <div class="w-full sm:w-1/3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('system.common.filter_by') }}</label>
                        <select id="bimaTimeFilter" class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="all">{{ __('system.common.all_time') }}</option>
                            <option value="day">{{ __('system.sidebar.today') }}</option>
                            <option value="week">{{ __('system.common.this_week') }}</option>
                            <option value="month">{{ __('system.common.this_month') }}</option>
                            <option value="year">{{ __('system.common.this_year') }}</option>
                            <option value="custom">{{ __('system.common.custom_range') }}</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-2/3 hidden" id="dateRangeGroup">
                        <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('system.common.date_range') }}</label>
                        <div class="flex flex-col sm:flex-row items-center gap-2">
                            <input type="text" class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" id="minDate" placeholder="Start Date">
                            <span class="text-gray-500 text-sm">to</span>
                            <input type="text" class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" id="maxDate" placeholder="End Date">
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="bimaTable" class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search #"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Booking Code"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Booking Date"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Username"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Phone Number"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Bus Info"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Start Date"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search End Date"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Valid Days"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search Amount"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="Search VAT"></th>
                            </tr>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium">#</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.pages.col_booking_code') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.pages.col_booking_date') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.pages.col_username') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.pages.col_phone_number') }}</th>
                                <th class="py-2 px-4 text-left font-medium">Bus Info</th>
                                <th class="py-2 px-4 text-left font-medium">Start Date</th>
                                <th class="py-2 px-4 text-left font-medium">End Date</th>
                                <th class="py-2 px-4 text-left font-medium">Valid Days</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.amount') }}</th>
                                <th class="py-2 px-4 text-left font-medium">VAT</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs">
                            @foreach($bimas as $index => $bima)
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                    <td class="py-2 px-4">{{ $index + 1 }}</td>
                                    <td class="py-2 px-4">{{ $bima->booking->booking_code ?? 'N/A' }}</td>
                                    <td class="py-2 px-4">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ $bima->booking->created_at->format('d M Y') ?? 'N/A' }}</span>
                                            <span class="text-gray-500 text-xs">{{ $bima->booking->created_at->format('H:i A') ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2 px-4">{{ $bima->booking->customer_name ?? 'N/A' }}</td>
                                    <td class="py-2 px-4">{{ $bima->booking->customer_phone ?? 'N/A' }}</td>
                                    <td class="py-2 px-4">
                                        <ul class="list-disc pl-4 mb-0">
                                            <li>{{ $bima->booking->campany->name ?? 'N/A' }} | {{ $bima->booking->bus->bus_number ?? 'N/A' }}</li>
                                            <li>From {{ $bima->booking->route->from ?? 'N/A' }} To {{ $bima->booking->route->to ?? 'N/A' }}</li>
                                        </ul>
                                    </td>
                                    <td class="py-2 px-4" data-date="{{ \Carbon\Carbon::parse($bima->start_date)->format('Y-m-d') }}">{{ \Carbon\Carbon::parse($bima->start_date)->format('d M Y') }}</td>
                                    <td class="py-2 px-4" data-date="{{ \Carbon\Carbon::parse($bima->end_date)->format('Y-m-d') }}">{{ \Carbon\Carbon::parse($bima->end_date)->format('d M Y') }}</td>
                                    <td class="py-2 px-4">{{ $bima->valid_days }} days</td>
                                    <td class="py-2 px-4 amount" data-amount="{{ $bima->amount }}">{{ $currency }} {{ convert_money($bima->amount) }}</td>
                                    <td class="py-2 px-4 amount" data-amount="{{ $bima->bima_vat }}">{{ $currency }} {{ convert_money($bima->bima_vat) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Moment.js for date handling -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <!-- DataTables DateTime plugin -->
    <script src="https://cdn.datatables.net/datetime/1.5.1/js/dataTables.dateTime.min.js"></script>

    <script>
        window.bimaCurrency = @json(session('currency', 'Tzs'));
        window.bimaUsdToTzs = {{ app('usdToTzs') ?? 2500 }};
        $(document).ready(function() {
            // Create date inputs
            var minDate = new DateTime($('#minDate'), {
                format: 'DD MMM YYYY'
            });
            var maxDate = new DateTime($('#maxDate'), {
                format: 'DD MMM YYYY'
            });

            // Custom date filtering function
            $.fn.dataTableExt.afnFiltering.push(function (settings, data, dataIndex) {
                let filterValue = $('#bimaTimeFilter').val();
                let dateStr = data[6]; // Start Date column (index 6, moved due to new Booking Date column)
                let date = moment(dateStr, 'DD MMM YYYY');

                if (!date.isValid()) return true; // Skip invalid dates

                let now = moment();
                
                // For custom date range
                if (filterValue === 'custom') {
                    let min = minDate.val();
                    let max = maxDate.val();
                    
                    if (min && max) {
                        let minDateMoment = moment(min, 'DD MMM YYYY');
                        let maxDateMoment = moment(max, 'DD MMM YYYY');
                        return date.isBetween(minDateMoment, maxDateMoment, null, '[]'); // inclusive
                    }
                    return true;
                }

                switch (filterValue) {
                    case 'day':
                        return date.isSame(now, 'day');
                    case 'week':
                        return date.isSame(now, 'week');
                    case 'month':
                        return date.isSame(now, 'month');
                    case 'year':
                        return date.isSame(now, 'year');
                    case 'all':
                    default:
                        return true;
                }
            });

            const bimaTable = $('#bimaTable').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                lengthChange: true,
                info: true,
                autoWidth: false,
                language: {
                    emptyTable: "No insurance data found."
                },
                footerCallback: function(row, data, start, end, display) {
                    let api = this.api();
                    let total = api
                        .rows({ page: 'current' })
                        .nodes()
                        .toArray()
                        .reduce((sum, row) => {
                            let amount = $(row).find('.amount').data('amount') || 0;
                            return sum + parseFloat(amount);
                        }, 0);
                    let displayTotal = window.bimaCurrency === 'Usd' ? (total / (window.bimaUsdToTzs || 2500)).toLocaleString('en-US', { minimumFractionDigits: 2 }) : total.toLocaleString('en-US', { minimumFractionDigits: 2 });
                    $('#bimaTotal').text(displayTotal);
                }
            });

            // Apply search to each column
            $('#bimaTable thead tr:first-child th').each(function(index) {
                $(this).find('input').on('keyup change', function() {
                    bimaTable.column(index).search(this.value).draw();
                });
            });

            // Apply time filter
            $('#bimaTimeFilter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#dateRangeGroup').removeClass('hidden');
                } else {
                    $('#dateRangeGroup').addClass('hidden');
                    bimaTable.draw();
                }
            });

            // Redraw the table when the custom date inputs change
            $('#minDate, #maxDate').on('change', function() {
                if ($('#bimaTimeFilter').val() === 'custom') {
                    bimaTable.draw();
                }
            });
        });
    </script>

    <style>
        .search-input {
            width: 100%;
            padding: 4px;
            font-size: 12px;
        }
        .table ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
    </style>
@endsection