 @extends('admin.app')

@section('content')
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row justify-between items-center">
                    <h4 class="text-xl font-semibold text-gray-800 mb-2 sm:mb-0">{{ __('vender/earning.earnings_payments') }}</h4>
                    <div class="flex space-x-2">
                        <!-- Request Transaction Button -->
                        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center"
                            onclick="document.getElementById('requestTransactionModal').classList.remove('hidden')">
                            <i class="fas fa-plus-circle mr-2"></i> {{ __('vender/earning.request_transaction') }}
                        </button>
                        <form action="{{ route('export') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                                <i class="fas fa-download mr-2"></i> {{ __('vender/earning.export') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <!-- Balance Card -->
            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.balance') }}</p>
                        <h3 class="text-lg font-semibold">{{ $currency }} {{ convert_money(auth()->user()->campany->balance->amount ?? 0) }}</h3>
                    </div>
                </div>
            </div>

            <!-- Withdrawals Requested Card -->
            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-yellow-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.withdrawals_requested') }}</p>
                        <h3 class="text-lg font-semibold">{{ $currency }} {{ convert_money($data['request'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>

            <!-- Withdrawals Card -->
            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-blue-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.withdrawals') }}</p>
                        <h3 class="text-lg font-semibold">{{ $currency }} {{ convert_money($data['success'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gray-50">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                    <h5 class="text-lg font-semibold text-gray-800 mb-2 sm:mb-0">{{ __('vender/earning.payment_transactions') }}</h5>
                    <div class="text-sm font-semibold">
                        {{ __('vender/earning.total_amount') }} <span id="transactionTotal">0</span>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.filter_by') }}</label>
                        <select id="timeFilter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all">{{ __('vender/earning.all_time') }}</option>
                            <option value="day">{{ __('vender/earning.today') }}</option>
                            <option value="week">{{ __('vender/earning.this_week') }}</option>
                            <option value="month">{{ __('vender/earning.this_month') }}</option>
                            <option value="year">{{ __('vender/earning.this_year') }}</option>
                            <option value="custom">{{ __('vender/earning.custom_range') }}</option>
                        </select>
                    </div>
                    <div id="dateRangeGroup" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.date_range') }}</label>
                        <div class="flex items-center space-x-2">
                            <input type="date" id="minDate" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-gray-500">{{ __('vender/earning.to') }}</span>
                            <input type="date" id="maxDate" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="transactionsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.company') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.user') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.amount') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.reference_no') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.cancel_reason') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($data['transactions'] as $transaction)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ auth()->user()->campany->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $transaction->user ? $transaction->user->name : __('vender/earning.unknown') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 amount" data-amount="{{ $transaction->amount }}">
                                    {{ $currency }} {{ convert_money($transaction->amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-date="{{ $transaction->created_at->format('Y-m-d') }}">
                                    {{ $transaction->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $transaction->reference_number ?? '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusKey = strtolower($transaction->status ?? '');
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ in_array($statusKey, ['completed', 'complete']) ? 'bg-green-100 text-green-800' : 
                                           ($statusKey === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($statusKey === 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800')) }}">
                                        {{ in_array($statusKey, ['completed', 'complete']) ? __('vender/earning.status_completed') : 
                                           ($statusKey === 'pending' ? __('vender/earning.status_pending') : 
                                           ($statusKey === 'cancelled' ? __('vender/earning.status_cancelled') : __('vender/earning.status_failed'))) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                    @if($statusKey === 'cancelled' && !empty($transaction->cancel_reason))
                                        <span title="{{ $transaction->cancel_reason }}">{{ Str::limit($transaction->cancel_reason, 80) }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('vender/earning.cancel_reason_na') }}</span>
                                    @endif
                                </td>                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @if(in_array($statusKey, ['completed', 'complete']))
                                    <form action="{{ route('print.recipt') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="data" value="{{ $transaction }}">
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm flex items-center">
                                            <i class="fas fa-receipt mr-1"></i> {{ __('vender/earning.print') }}
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('vender/earning.no_transactions_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Request Transaction Modal -->
        <div id="requestTransactionModal" class="hidden fixed inset-0 overflow-y-auto z-50">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="document.getElementById('requestTransactionModal').classList.add('hidden')">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('vender/earning.request_transaction') }}</h3>
                                <form id="requestTransactionForm" action="{{ route('transaction.request') }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.amount_tsh') }}</label>
                                        <input type="number" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            placeholder="{{ __('vender/earning.max') }} {{ convert_money(auth()->user()->campany->balance->amount ?? 0) }}"
                                            id="amount" name="amount" step="0.01" min="1" required>
                                        @error('amount')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="mb-4">
                                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.payment_method') }}</label>
                                        <select class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            id="payment_method" name="payment_method" required>
                                            <option value="MPesa">{{ __('vender/earning.mpesa') }}</option>
                                            <option value="AirtelMoney">{{ __('vender/earning.airtel_money') }}</option>
                                            <option value="MixxBYYass">{{ __('vender/earning.mixx_by_yass') }}</option>
                                            <option value="Halopesa">{{ __('vender/earning.halopesa') }}</option>
                                            <option value="bank">{{ __('vender/earning.bank') }}</option>
                                        </select>
                                        @error('payment_method')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="mb-4">
                                        <label for="payment_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.payment_number') }}</label>
                                        <input type="number" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-100" 
                                            readonly name="payment_number" value="{{ auth()->user()->campany->payment_number ?? __('vender/earning.na') }}" required>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" form="requestTransactionForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('vender/earning.submit_request') }}
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                onclick="document.getElementById('requestTransactionModal').classList.add('hidden')">
                            {{ __('vender/earning.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
    <!-- Moment.js for date handling -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script>
        $(document).ready(function() {
            // Define translation strings for JavaScript
            const translations = {
                empty_table: "{{ __('vender/earning.no_transactions_found') }}",
                search_company: "{{ __('vender/earning.search_company') }}",
                search_user: "{{ __('vender/earning.search_user') }}",
                search_amount: "{{ __('vender/earning.search_amount') }}",
                search_date: "{{ __('vender/earning.search_date') }}",
                search_reference_no: "{{ __('vender/earning.search_reference_no') }}",
                search_status: "{{ __('vender/earning.search_status') }}"
            };

            // Initialize DataTable
            DataTable.ext.errMode = 'none'; // Prevent DataTables from throwing errors
            const transactionsTable = $('#transactionsTable').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                lengthChange: true,
                info: true,
                autoWidth: false,
                language: {
                    emptyTable: translations.empty_table,
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
                pageLength: 10,
                lengthMenu: [5, 10, 20, 50],
                columnDefs: [
                    { orderable: false, targets: 6 }, // Disable sorting on Action column
                    { searchable: false, targets: 6 } // Disable searching on Action column
                ],
                footerCallback: function(row, data, start, end, display) {
                    let api = this.api();
                    let total = api
                        .rows({
                            page: 'current'
                        })
                        .nodes()
                        .toArray()
                        .reduce((sum, row) => {
                            let amount = $(row).find('.amount').data('amount') || 0;
                            return sum + parseFloat(amount);
                        }, 0);
                    $('#transactionTotal').text(total.toLocaleString('en-US', {
                        minimumFractionDigits: 2
                    }));
                }
            });

            // Apply search to each column
            $('#transactionsTable thead th').each(function(index) {
                if (index !== 6) { // Skip Action column
                    let title = $(this).text();
                    let placeholderKey = translations[`search_${title.toLowerCase().replace(/\s+/g, '_')}`] || `Search ${title}`;
                    $(this).html(`
                        <input type="text" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" 
                               placeholder="${placeholderKey}" data-column-index="${index}"/>
                    `);
                    
                    $('input', this).on('keyup change', function() {
                        if (transactionsTable.column(index).search() !== this.value) {
                            transactionsTable.column(index).search(this.value).draw();
                        }
                    });
                }
            });

            // Apply time filter
            $('#timeFilter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#dateRangeGroup').removeClass('hidden');
                } else {
                    $('#dateRangeGroup').addClass('hidden');
                    filterByTime($(this).val());
                }
            });

            // Redraw the table when the custom date inputs change
            $('#minDate, #maxDate').on('change', function() {
                if ($('#timeFilter').val() === 'custom') {
                    filterByCustomDate();
                }
            });

            // Time filtering functions
            function filterByTime(timeRange) {
                transactionsTable.rows().every(function() {
                    let row = this.node();
                    let dateStr = $(row).find('td:eq(3)').data('date');
                    let date = moment(dateStr, 'YYYY-MM-DD');
                    let now = moment();
                    let showRow = false;

                    if (!date.isValid()) {
                        showRow = true;
                    } else {
                        switch (timeRange) {
                            case 'day':
                                showRow = date.isSame(now, 'day');
                                break;
                            case 'week':
                                showRow = date.isSame(now, 'week');
                                break;
                            case 'month':
                                showRow = date.isSame(now, 'month');
                                break;
                            case 'year':
                                showRow = date.isSame(now, 'year');
                                break;
                            case 'all':
                            default:
                                showRow = true;
                        }
                    }

                    $(row).toggle(showRow);
                });

                // Update total
                updateTotalAmount();
            }

            function filterByCustomDate() {
                let minDate = $('#minDate').val();
                let maxDate = $('#maxDate').val();

                if (!minDate || !maxDate) return;

                transactionsTable.rows().every(function() {
                    let row = this.node();
                    let dateStr = $(row).find('td:eq(3)').data('date');
                    let date = moment(dateStr, 'YYYY-MM-DD');
                    let min = moment(minDate);
                    let max = moment(maxDate);

                    let showRow = date.isBetween(min, max, 'day', '[]'); // inclusive

                    $(row).toggle(showRow);
                });

                // Update total
                updateTotalAmount();
            }

            function updateTotalAmount() {
                let total = 0;
                transactionsTable.rows({ search: 'applied' }).every(function() {
                    let row = this.node();
                    if ($(row).is(':visible')) {
                        let amount = $(row).find('.amount').data('amount') || 0;
                        total += parseFloat(amount);
                    }
                });
                $('#transactionTotal').text(total.toLocaleString('en-US', {
                    minimumFractionDigits: 2
                }));
            }
        });
    </script>
@endsection 