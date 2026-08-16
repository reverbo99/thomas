@extends('admin.app')

@section('content')
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .earnings-tab {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            color: #6b7280;
            background: transparent;
            cursor: pointer;
        }
        .earnings-tab:hover {
            color: #374151;
            border-bottom-color: #d1d5db;
        }
        .earnings-tab.is-active {
            border-bottom-color: #0d9488;
            color: #0f766e;
            background: #fff;
        }
        .earnings-tab-panel { display: block; }
        .earnings-tab-panel.is-hidden { display: none !important; }

        /* DataTables — light controls (no dark blocks) */
        .earnings-tab-panel .dataTables_wrapper {
            color: #374151;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_length,
        .earnings-tab-panel .dataTables_wrapper .dataTables_filter,
        .earnings-tab-panel .dataTables_wrapper .dataTables_info {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_length select,
        .earnings-tab-panel .dataTables_wrapper .dataTables_filter input {
            background: #fff !important;
            color: #374151 !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            margin: 0 0.25rem;
            min-height: 2.25rem;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_filter input {
            min-width: 12rem;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_paginate {
            margin-top: 0.75rem;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #fff !important;
            color: #374151 !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.375rem 0.75rem !important;
            margin: 0 0.125rem !important;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .earnings-tab-panel .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: #0d9488 !important;
            color: #fff !important;
            border-color: #0d9488 !important;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f3f4f6 !important;
            color: #111827 !important;
            border-color: #9ca3af !important;
        }
        .earnings-tab-panel .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .earnings-tab-panel .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            background: #f9fafb !important;
            color: #9ca3af !important;
            border-color: #e5e7eb !important;
        }
        .earnings-tab-panel table.dataTable thead th {
            background: #f9fafb;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        .earnings-tab-panel table.dataTable tbody td {
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }
        .earnings-tab-panel table.dataTable.no-footer {
            border-bottom: 1px solid #e5e7eb;
        }
    </style>

    @php
        $currentPeriod = $period ?? 'month';
        $currentStart = $start_date ?? ($data['period_start'] ?? '');
        $currentEnd = $end_date ?? ($data['period_end'] ?? '');
    @endphp

    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100">
                <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-3">
                    <h4 class="text-xl font-semibold text-gray-800">{{ __('vender/earning.earnings_payments') }}</h4>
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center"
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

        <!-- Period Filter -->
        <div class="mb-6 bg-white rounded-lg shadow-md p-4 border border-gray-100">
            <form action="{{ route('earnings.filter') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end" id="earningsPeriodForm">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.earnings_period') }}</label>
                    <select name="period" id="earningsPeriodSelect"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                        <option value="today" @selected($currentPeriod === 'today')>{{ __('vender/earning.today') }}</option>
                        <option value="week" @selected($currentPeriod === 'week')>{{ __('vender/earning.this_week') }}</option>
                        <option value="month" @selected($currentPeriod === 'month')>{{ __('vender/earning.this_month') }}</option>
                        <option value="year" @selected($currentPeriod === 'year')>{{ __('vender/earning.this_year') }}</option>
                        <option value="custom" @selected($currentPeriod === 'custom')>{{ __('vender/earning.custom_range') }}</option>
                    </select>
                </div>
                <div id="earningsCustomStart" class="{{ $currentPeriod === 'custom' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.date_range') }}</label>
                    <input type="date" name="start_date" value="{{ $currentStart }}"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div id="earningsCustomEnd" class="{{ $currentPeriod === 'custom' ? '' : 'hidden' }}">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.to') }}</label>
                    <input type="date" name="end_date" value="{{ $currentEnd }}"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                </div>
                <div>
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        {{ __('vender/earning.filter_by') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3 shrink-0">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.balance') }}</p>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $currency }} {{ convert_money(auth()->user()->campany->balance->amount ?? 0) }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-teal-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3 shrink-0">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.ticket_earnings') }}</p>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $currency }} {{ convert_money($data['ticket_earnings'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-amber-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3 shrink-0">
                        <i class="fas fa-suitcase"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.luggage_earnings') }}</p>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $currency }} {{ convert_money($data['luggage_earnings'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-yellow-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3 shrink-0">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.withdrawals_requested') }}</p>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $currency }} {{ convert_money($data['request'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                <div class="flex items-center">
                    <div class="bg-blue-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3 shrink-0">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('vender/earning.withdrawals') }}</p>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $currency }} {{ convert_money($data['success'] ?? 0) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Tabs -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border border-gray-100">
            <div class="px-6 pt-4 bg-gray-50 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-800 mb-4">{{ __('vender/earning.income_breakdown') }}</h5>
                <nav class="flex flex-wrap gap-0 -mb-px" role="tablist" aria-label="{{ __('vender/earning.income_breakdown') }}">
                    <button type="button" id="tabPaidTickets" role="tab" aria-selected="true" aria-controls="panelPaidTickets"
                        class="earnings-tab is-active" data-tab="paidTickets">
                        <i class="fas fa-ticket-alt mr-2"></i>{{ __('vender/earning.tab_paid_tickets') }}
                    </button>
                    <button type="button" id="tabExcessLuggage" role="tab" aria-selected="false" aria-controls="panelExcessLuggage"
                        class="earnings-tab" data-tab="excessLuggage">
                        <i class="fas fa-suitcase mr-2"></i>{{ __('vender/earning.tab_excess_luggage') }}
                    </button>
                    <button type="button" id="tabPaymentTransactions" role="tab" aria-selected="false" aria-controls="panelPaymentTransactions"
                        class="earnings-tab" data-tab="paymentTransactions">
                        <i class="fas fa-exchange-alt mr-2"></i>{{ __('vender/earning.payment_transactions') }}
                    </button>
                </nav>
            </div>

            <div id="panelPaidTickets" role="tabpanel" aria-labelledby="tabPaidTickets" class="earnings-tab-panel p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 w-full" id="paidTicketsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.booking_code') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.travel_date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.route') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.customer') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.ticket_amount') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.paid_date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>

            <div id="panelExcessLuggage" role="tabpanel" aria-labelledby="tabExcessLuggage" class="earnings-tab-panel is-hidden p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 w-full" id="excessLuggageTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.booking_code') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.released_fee') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.owner_share') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.released_at') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/earning.route') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>

            <div id="panelPaymentTransactions" role="tabpanel" aria-labelledby="tabPaymentTransactions" class="earnings-tab-panel is-hidden p-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
                    <p class="text-sm text-gray-600">{{ __('vender/earning.payment_transactions') }}</p>
                    <div class="text-sm font-semibold text-gray-700">
                        {{ __('vender/earning.total_amount') }} <span id="transactionTotal">0</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.filter_by') }}</label>
                        <select id="timeFilter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                            <input type="date" id="minDate" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <span class="text-gray-500">{{ __('vender/earning.to') }}</span>
                            <input type="date" id="maxDate" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500">
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
                                <tr class="hover:bg-gray-50">
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
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
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
                                <tr class="transactions-empty-row">
                                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('vender/earning.no_transactions_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Request Transaction Modal -->
        <div id="requestTransactionModal" class="hidden fixed inset-0 overflow-y-auto z-50">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="document.getElementById('requestTransactionModal').classList.add('hidden')">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('vender/earning.request_transaction') }}</h3>
                                <form id="requestTransactionForm" action="{{ route('transaction.request') }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.amount_tsh') }}</label>
                                        <input type="number" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            placeholder="{{ __('vender/earning.max') }} {{ convert_money(auth()->user()->campany->balance->amount ?? 0) }}"
                                            id="amount" name="amount" step="0.01" min="1" required>
                                        @error('amount')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="mb-4">
                                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.payment_method') }}</label>
                                        <select class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                            id="payment_method" name="payment_method" required>
                                            <option value="MPesa">{{ __('vender/earning.mpesa') }}</option>
                                            <option value="AirtelMoney">{{ __('vender/earning.airtel_money') }}</option>
                                            <option value="MixxBYYass">{{ __('vender/earning.mixx_by_yass') }}</option>
                                            <option value="Halopesa">{{ __('vender/earning.halopesa') }}</option>
                                            <option value="bank">{{ __('vender/earning.bank') }}</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label for="payment_number" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/earning.payment_number') }}</label>
                                        <input type="number" class="w-full border-gray-300 rounded-md shadow-sm bg-gray-100"
                                            readonly name="payment_number" value="{{ auth()->user()->campany->payment_number ?? __('vender/earning.na') }}" required>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200">
                        <button type="submit" form="requestTransactionForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('vender/earning.submit_request') }}
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                                onclick="document.getElementById('requestTransactionModal').classList.add('hidden')">
                            {{ __('vender/earning.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script>
        window.earningsPeriod = @json($currentPeriod);
        window.earningsStartDate = @json($currentStart);
        window.earningsEndDate = @json($currentEnd);

        (function() {
            const earningsPanels = {
                paidTickets: document.getElementById('panelPaidTickets'),
                excessLuggage: document.getElementById('panelExcessLuggage'),
                paymentTransactions: document.getElementById('panelPaymentTransactions')
            };

            function activateEarningsTab(tab) {
                document.querySelectorAll('.earnings-tab').forEach(function(btn) {
                    const isActive = btn.getAttribute('data-tab') === tab;
                    btn.classList.toggle('is-active', isActive);
                    btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                Object.keys(earningsPanels).forEach(function(key) {
                    const panel = earningsPanels[key];
                    if (!panel) return;
                    panel.classList.toggle('is-hidden', key !== tab);
                });

                if (window.earningsOnTabShown) {
                    window.earningsOnTabShown(tab);
                }
            }

            document.querySelectorAll('.earnings-tab').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    activateEarningsTab(this.getAttribute('data-tab'));
                });
            });

            window.activateEarningsTab = activateEarningsTab;
        })();

        jQuery(function($) {
            const translations = {
                empty_table: "{{ __('vender/earning.no_transactions_found') }}",
                no_tickets: "{{ __('vender/earning.no_tickets_found') }}",
                no_luggage: "{{ __('vender/earning.no_luggage_found') }}",
                processing: "{{ __('vender/earning.processing') }}",
                search_company: "{{ __('vender/earning.search_company') }}",
                search_user: "{{ __('vender/earning.search_user') }}",
                search_amount: "{{ __('vender/earning.search_amount') }}",
                search_date: "{{ __('vender/earning.search_date') }}",
                search_reference_no: "{{ __('vender/earning.search_reference_no') }}",
                search_status: "{{ __('vender/earning.search_status') }}"
            };

            const dtLanguage = {
                emptyTable: translations.no_tickets,
                processing: translations.processing,
                search: "{{ __('all.dt_search') }}",
                lengthMenu: "{{ __('all.dt_show_entries') }}",
                info: "{{ __('all.dt_info') }}",
                paginate: {
                    first: "{{ __('all.dt_first') }}",
                    last: "{{ __('all.dt_last') }}",
                    next: "{{ __('all.dt_next') }}",
                    previous: "{{ __('all.dt_previous') }}"
                }
            };

            const periodPayload = function(d) {
                d.period = window.earningsPeriod;
                d.start_date = window.earningsStartDate;
                d.end_date = window.earningsEndDate;
            };

            DataTable.ext.errMode = 'none';

            let paidTicketsTable = null;
            let excessLuggageTable = null;
            let transactionsTable = null;
            let transactionsTableInitialized = false;

            window.earningsOnTabShown = function(tab) {
                if (tab === 'paidTickets') {
                    if (paidTicketsTable) {
                        paidTicketsTable.columns.adjust().draw(false);
                    }
                } else if (tab === 'excessLuggage') {
                    initLuggageTable();
                    if (excessLuggageTable) {
                        excessLuggageTable.columns.adjust().draw(false);
                    }
                } else if (tab === 'paymentTransactions') {
                    initTransactionsTable();
                    if (transactionsTable) {
                        transactionsTable.columns.adjust().draw(false);
                    }
                }
            };

            paidTicketsTable = $('#paidTicketsTable').DataTable({
                serverSide: true,
                processing: true,
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[5, 'desc']],
                dom: "<'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3'<'text-sm text-gray-600'l><'text-sm'f>>rtip",
                language: dtLanguage,
                ajax: {
                    url: '{{ route('earnings.tickets.data') }}',
                    data: periodPayload,
                    error: function() {
                        alert('{{ __('vender/earning.no_tickets_found') }}');
                    }
                },
                columns: [
                    { data: 'booking_code', name: 'booking_code' },
                    { data: 'travel_date', name: 'travel_date' },
                    { data: 'route', name: 'route', orderable: false },
                    { data: 'customer_name', name: 'customer_name' },
                    { data: 'amount_display', name: 'amount' },
                    { data: 'paid_at', name: 'created_at' }
                ]
            });

            function initLuggageTable() {
                if (excessLuggageTable) {
                    return;
                }
                excessLuggageTable = $('#excessLuggageTable').DataTable({
                    serverSide: true,
                    processing: true,
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[4, 'desc']],
                    dom: "<'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3'<'text-sm text-gray-600'l><'text-sm'f>>rtip",
                    language: Object.assign({}, dtLanguage, { emptyTable: translations.no_luggage }),
                    ajax: {
                        url: '{{ route('earnings.luggage.data') }}',
                        data: periodPayload,
                        error: function() {
                            alert('{{ __('vender/earning.no_luggage_found') }}');
                        }
                    },
                    columns: [
                        { data: 'booking_code', name: 'booking_code' },
                        { data: 'released_fee_display', name: 'released_fee' },
                        { data: 'owner_share_display', name: 'owner_share' },
                        { data: 'status_html', name: 'status', orderable: false, searchable: false },
                        { data: 'released_at', name: 'released_at' },
                        { data: 'route', name: 'route', orderable: false }
                    ],
                    columnDefs: [
                        { targets: 3, render: function(data) { return data; } }
                    ]
                });
            }

            function initTransactionsTable() {
                if (transactionsTableInitialized) {
                    return;
                }

                if ($('#transactionsTable tbody tr.transactions-empty-row').length) {
                    transactionsTableInitialized = true;
                    return;
                }

                transactionsTable = $('#transactionsTable').DataTable({
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    lengthChange: true,
                    info: true,
                    autoWidth: false,
                    dom: "<'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3'<'text-sm text-gray-600'l><'text-sm'f>>rtip",
                    language: Object.assign({}, dtLanguage, { emptyTable: translations.empty_table }),
                    pageLength: 10,
                    lengthMenu: [5, 10, 20, 50],
                    columnDefs: [
                        { orderable: false, targets: 7 },
                        { searchable: false, targets: 7 }
                    ],
                    drawCallback: function() {
                        updateTotalAmount();
                    }
                });

                $('#transactionsTable thead th').each(function(index) {
                    if (index !== 7) {
                        const title = $(this).text();
                        const placeholderKey = translations[`search_${title.toLowerCase().replace(/\s+/g, '_')}`] || `Search ${title}`;
                        $(this).html(`
                            <input type="text" class="w-full border-gray-300 rounded-md shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm"
                                   placeholder="${placeholderKey}" data-column-index="${index}"/>
                        `);

                        $('input', this).on('keyup change', function() {
                            if (transactionsTable.column(index).search() !== this.value) {
                                transactionsTable.column(index).search(this.value).draw();
                            }
                        });
                    }
                });

                $('#timeFilter').on('change', function() {
                    if ($(this).val() === 'custom') {
                        $('#dateRangeGroup').removeClass('hidden');
                    } else {
                        $('#dateRangeGroup').addClass('hidden');
                        filterByTime($(this).val());
                    }
                });

                $('#minDate, #maxDate').on('change', function() {
                    if ($('#timeFilter').val() === 'custom') {
                        filterByCustomDate();
                    }
                });

                transactionsTableInitialized = true;
                updateTotalAmount();
            }

            function filterByTime(timeRange) {
                if (!transactionsTable) return;

                transactionsTable.rows().every(function() {
                    const row = this.node();
                    const dateStr = $(row).find('td:eq(3)').data('date');
                    const date = moment(dateStr, 'YYYY-MM-DD');
                    const now = moment();
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

                updateTotalAmount();
            }

            function filterByCustomDate() {
                if (!transactionsTable) return;

                const minDate = $('#minDate').val();
                const maxDate = $('#maxDate').val();
                if (!minDate || !maxDate) return;

                transactionsTable.rows().every(function() {
                    const row = this.node();
                    const dateStr = $(row).find('td:eq(3)').data('date');
                    const date = moment(dateStr, 'YYYY-MM-DD');
                    const min = moment(minDate);
                    const max = moment(maxDate);
                    const showRow = date.isBetween(min, max, 'day', '[]');
                    $(row).toggle(showRow);
                });

                updateTotalAmount();
            }

            function updateTotalAmount() {
                let total = 0;

                if (transactionsTable) {
                    transactionsTable.rows({ search: 'applied' }).every(function() {
                        const row = this.node();
                        if ($(row).is(':visible')) {
                            const amount = $(row).find('.amount').data('amount') || 0;
                            total += parseFloat(amount);
                        }
                    });
                } else {
                    $('#transactionsTable tbody tr:visible .amount').each(function() {
                        total += parseFloat($(this).data('amount') || 0);
                    });
                }

                $('#transactionTotal').text(total.toLocaleString('en-US', {
                    minimumFractionDigits: 2
                }));
            }

            $('#earningsPeriodSelect').on('change', function() {
                const isCustom = $(this).val() === 'custom';
                $('#earningsCustomStart, #earningsCustomEnd').toggleClass('hidden', !isCustom);
            });
        });
    </script>
@endpush
