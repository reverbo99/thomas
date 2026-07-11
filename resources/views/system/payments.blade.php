@extends('system.app')

@section('content')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- DataTables DateTime CSS -->
    <link href="https://cdn.datatables.net/datetime/1.5.1/css/dataTables.dateTime.min.css" rel="stylesheet">

    @php
        $totalCommissionBalance = (float) $balances->sum('balance');
        $totalServiceFees = (float) $pays->sum('amount');
        $totalLevies = (float) $levies->sum('amount');
        $combinedIncome = $totalCommissionBalance + $totalServiceFees + $totalLevies;
    @endphp

    <div class="max-w-7xl mx-auto px-2 sm:px-4 py-4 sm:py-6">
        <!-- Page header -->
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 mb-1">{{ __('system.pages.admin_finance') }}</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">{{ __('system.pages.payments_title') }}</h1>
                <p class="text-gray-600 mt-2 max-w-2xl text-sm sm:text-base leading-relaxed">
                    {{ __('system.pages.payments_subtitle') }}
                </p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-end gap-3 shrink-0">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('system.payments.pdf', request()->only(['period', 'start_date', 'end_date'])) }}" target="_blank"
                       class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                        {{ __('system.pages.export_pdf') }}
                    </a>
                    <a href="{{ route('system.payments.csv', request()->only(['period', 'start_date', 'end_date'])) }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm">
                        {{ __('system.pages.export_csv') }}
                    </a>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm lg:text-right">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('system.pages.combined_total') }}</p>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $currency }} {{ convert_money($combinedIncome) }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('all.highlink_isgc') }}</p>
                </div>
            </div>
        </div>

        <!-- KPI summary -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <a href="#income-commission" class="group rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white p-5 shadow-sm hover:shadow-md transition-shadow focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">{{ __('system.pages.commission') }}</p>
                <p class="text-xl sm:text-2xl font-bold text-blue-900 tabular-nums mt-1">{{ $currency }} {{ convert_money($totalCommissionBalance) }}</p>
                <p class="text-xs text-blue-600 opacity-90 mt-2 group-hover:underline">{{ __('system.pages.commission_rows', ['count' => $balances->count()]) }}</p>
            </a>
            <a href="#income-service-fees" class="group rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm hover:shadow-md transition-shadow focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">{{ __('system.pages.service_fees') }}</p>
                <p class="text-xl sm:text-2xl font-bold text-emerald-900 tabular-nums mt-1">{{ $currency }} {{ convert_money($totalServiceFees) }}</p>
                <p class="text-xs text-emerald-600 opacity-90 mt-2 group-hover:underline">{{ __('system.pages.service_fees_rows', ['count' => $pays->count()]) }}</p>
            </a>
            <a href="#income-levy" class="group rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm hover:shadow-md transition-shadow focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                <p class="text-xs font-semibold text-amber-800 uppercase tracking-wide">{{ __('system.pages.gov_levy_service') }}</p>
                <p class="text-xl sm:text-2xl font-bold text-amber-950 tabular-nums mt-1">{{ $currency }} {{ convert_money($totalLevies) }}</p>
                <p class="text-xs text-amber-700 opacity-90 mt-2 group-hover:underline">{{ __('system.pages.gov_levy_rows', ['count' => $levies->count()]) }}</p>
            </a>
        </div>

        <div class="space-y-10">
            <!-- Commission (system balances) — table id serviceTable preserved for JS -->
            <section id="income-commission" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden scroll-mt-24">
                <div class="px-5 sm:px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-500 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('system.pages.commission_section') }}</h2>
                        <p class="text-blue-100 text-xs sm:text-sm mt-0.5">{{ __('system.pages.commission_desc') }}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-xs text-blue-100 uppercase tracking-wide font-medium">{{ __('system.pages.section_total') }}</span>
                        <div class="text-xl font-bold tabular-nums">{{ $currency }} <span id="serviceTotal">{{ convert_money($totalCommissionBalance) }}</span></div>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="flex flex-col lg:flex-row gap-4 mb-4 bg-gray-50 rounded-lg border border-gray-100 p-4">
                        <div class="w-full lg:w-64">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">{{ __('system.pages.period') }}</label>
                            <select id="serviceTimeFilter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm text-gray-800">
                                <option value="all">{{ __('system.common.all_time') }}</option>
                                <option value="day">{{ __('system.sidebar.today') }}</option>
                                <option value="week">{{ __('system.common.this_week') }}</option>
                                <option value="month">{{ __('system.common.this_month') }}</option>
                                <option value="year">{{ __('system.common.this_year') }}</option>
                                <option value="custom">{{ __('system.common.custom_range') }}</option>
                            </select>
                        </div>
                        <div class="w-full lg:flex-1 hidden" id="serviceDateRangeGroup">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Custom range</label>
                            <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                                <input type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" id="serviceMinDate" placeholder="Start Date">
                                <span class="text-gray-400 text-sm hidden sm:block pb-2.5">→</span>
                                <input type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" id="serviceMaxDate" placeholder="End Date">
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
                    <div class="overflow-x-auto px-4 py-4">
                        <div class="system-payments-filters mb-3">
                            <div class="system-payments-filters__grid">
                                <input type="text" class="system-payments-filters__input search-input" data-table="serviceTable" data-column="0" placeholder="No.">
                                <input type="text" class="system-payments-filters__input search-input" data-table="serviceTable" data-column="1" placeholder="{{ __('system.pages.col_company') }}">
                                <input type="text" class="system-payments-filters__input search-input" data-table="serviceTable" data-column="2" placeholder="Booking code">
                                <input type="text" class="system-payments-filters__input search-input text-right" data-table="serviceTable" data-column="3" placeholder="{{ __('system.common.amount') }}">
                                <input type="text" class="system-payments-filters__input search-input" data-table="serviceTable" data-column="4" placeholder="{{ __('system.common.date') }}">
                            </div>
                        </div>
                        <table id="serviceTable" class="display stripe w-full table-fixed border-collapse text-sm system-payments-table">
                            <colgroup>
                                <col class="system-payments-col-no">
                                <col class="system-payments-col-company">
                                <col class="system-payments-col-booking">
                                <col class="system-payments-col-amount">
                                <col class="system-payments-col-date">
                            </colgroup>
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide border-b border-gray-200">
                                    <th class="py-2.5 px-4 text-left font-semibold">No</th>
                                    <th class="py-2.5 px-4 text-left font-semibold">{{ __('system.pages.col_company') }}</th>
                                    <th class="py-2.5 px-4 text-left font-semibold">Booking code</th>
                                    <th class="py-2.5 px-4 text-right font-semibold whitespace-nowrap">{{ __('system.common.amount') }}</th>
                                    <th class="py-2.5 px-4 text-left font-semibold whitespace-nowrap">{{ __('system.common.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm divide-y divide-gray-100 bg-white">
                                @php $i = 1; @endphp
                                @if ($balances->count() > 0)
                                    @foreach ($balances as $payment)
                                        <tr class="hover:bg-blue-50 transition-colors">
                                            <td class="py-2.5 px-4 text-gray-500 tabular-nums">{{ $i++ }}</td>
                                            <td class="py-2.5 px-4 font-medium text-gray-900">{{ $payment->campany->name }}</td>
                                            <td class="py-2.5 px-4 font-mono text-xs text-gray-800">{{ $payment->booking_id ?? 'N/A' }}</td>
                                            <td class="py-2.5 px-4 text-right font-semibold tabular-nums amount" data-amount="{{ $payment->balance }}">{{ $currency }} {{ convert_money($payment->balance) }}</td>
                                            <td class="py-2.5 px-4 whitespace-nowrap text-gray-600" data-date="{{ $payment->created_at->format('Y-m-d') }}">{{ $payment->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="payments-empty-row">
                                        @for ($col = 0; $col < 5; $col++)
                                            <td class="py-8 px-4 text-center text-gray-500 text-sm">{{ $col === 0 ? __('system.pages.no_data_found') : '' }}</td>
                                        @endfor
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </section>

            <section id="income-service-fees" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden scroll-mt-24">
                <div class="px-5 sm:px-6 py-4 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('system.pages.service_fees') }}</h2>
                        <p class="text-emerald-100 text-xs sm:text-sm mt-0.5">Platform service fee pool recorded per booking</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-xs text-emerald-100 uppercase tracking-wide font-medium">Section total</span>
                        <div class="text-xl font-bold tabular-nums">{{ $currency }} <span id="commissionTotal">{{ convert_money($totalServiceFees) }}</span></div>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="flex flex-col lg:flex-row gap-4 mb-4 bg-gray-50 rounded-lg border border-gray-100 p-4">
                        <div class="w-full lg:w-64">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Period</label>
                            <select id="commissionTimeFilter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm text-gray-800">
                                <option value="all">All Time</option>
                                <option value="day">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="year">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="w-full lg:flex-1 hidden" id="commissionDateRangeGroup">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Custom range</label>
                            <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                                <input type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm" id="commissionMinDate" placeholder="Start Date">
                                <span class="text-gray-400 text-sm hidden sm:block pb-2.5">→</span>
                                <input type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm" id="commissionMaxDate" placeholder="End Date">
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
                    <div class="overflow-x-auto px-4 py-4">
                        <div class="system-payments-filters mb-3">
                            <div class="system-payments-filters__grid">
                                <input type="text" class="system-payments-filters__input search-input" data-table="commissionTable" data-column="0" placeholder="No.">
                                <input type="text" class="system-payments-filters__input search-input" data-table="commissionTable" data-column="1" placeholder="{{ __('system.pages.col_company') }}">
                                <input type="text" class="system-payments-filters__input search-input" data-table="commissionTable" data-column="2" placeholder="Booking code">
                                <input type="text" class="system-payments-filters__input search-input text-right" data-table="commissionTable" data-column="3" placeholder="{{ __('system.common.amount') }}">
                                <input type="text" class="system-payments-filters__input search-input" data-table="commissionTable" data-column="4" placeholder="{{ __('system.common.date') }}">
                            </div>
                        </div>
                        <table id="commissionTable" class="display stripe w-full table-fixed border-collapse text-sm system-payments-table">
                            <colgroup>
                                <col class="system-payments-col-no">
                                <col class="system-payments-col-company">
                                <col class="system-payments-col-booking">
                                <col class="system-payments-col-amount">
                                <col class="system-payments-col-date">
                            </colgroup>
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide border-b border-gray-200">
                                    <th class="py-2.5 px-4 text-left font-semibold">No</th>
                                    <th class="py-2.5 px-4 text-left font-semibold">{{ __('system.pages.col_company') }}</th>
                                    <th class="py-2.5 px-4 text-left font-semibold">Booking code</th>
                                    <th class="py-2.5 px-4 text-right font-semibold whitespace-nowrap">{{ __('system.common.amount') }}</th>
                                    <th class="py-2.5 px-4 text-left font-semibold whitespace-nowrap">{{ __('system.common.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm divide-y divide-gray-100 bg-white">
                                @php $p = 1; @endphp
                                @if($pays->count() > 0)
                                    @foreach ($pays as $payment)
                                        <tr class="hover:bg-emerald-50 transition-colors">
                                            <td class="py-2.5 px-4 text-gray-500 tabular-nums">{{ $p++ }}</td>
                                            <td class="py-2.5 px-4 font-medium text-gray-900">{{ $payment->campany->name }}</td>
                                            <td class="py-2.5 px-4 font-mono text-xs text-gray-800">{{ $payment->booking_id }}</td>
                                            <td class="py-2.5 px-4 text-right font-semibold tabular-nums amount" data-amount="{{ $payment->amount }}">{{ $currency }} {{ convert_money($payment->amount) }}</td>
                                            <td class="py-2.5 px-4 whitespace-nowrap text-gray-600" data-date="{{ $payment->created_at->format('Y-m-d') }}">{{ $payment->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="payments-empty-row">
                                        @for ($col = 0; $col < 5; $col++)
                                            <td class="py-8 px-4 text-center text-gray-500 text-sm">{{ $col === 0 ? __('system.pages.no_data_found') : '' }}</td>
                                        @endfor
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </section>

            <section id="income-levy" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden scroll-mt-24">
                <div class="px-5 sm:px-6 py-4 bg-gradient-to-r from-amber-600 to-amber-500 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold">Government levy (from service)</h2>
                        <p class="text-amber-100 text-xs sm:text-sm mt-0.5">Levy portion attributed to service fees</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <span class="text-xs text-amber-100 uppercase tracking-wide font-medium">Section total</span>
                        <div class="text-xl font-bold tabular-nums">{{ $currency }} <span id="levyTotal">{{ convert_money($totalLevies) }}</span></div>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="flex flex-col lg:flex-row gap-4 mb-4 bg-gray-50 rounded-lg border border-gray-100 p-4">
                        <div class="w-full lg:w-64">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Period</label>
                            <select id="levyTimeFilter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm text-gray-800">
                                <option value="all">All Time</option>
                                <option value="day">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="year">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div class="w-full lg:flex-1 hidden" id="levyDateRangeGroup">
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Custom range</label>
                            <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                                <input type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm" id="levyMinDate" placeholder="Start Date">
                                <span class="text-gray-400 text-sm hidden sm:block pb-2.5">→</span>
                                <input type="text" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm" id="levyMaxDate" placeholder="End Date">
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
                    <div class="overflow-x-auto px-4 py-4">
                        <div class="system-payments-filters mb-3">
                            <div class="system-payments-filters__grid">
                                <input type="text" class="system-payments-filters__input search-input" data-table="levyTable" data-column="0" placeholder="No.">
                                <input type="text" class="system-payments-filters__input search-input" data-table="levyTable" data-column="1" placeholder="{{ __('system.pages.col_company') }}">
                                <input type="text" class="system-payments-filters__input search-input" data-table="levyTable" data-column="2" placeholder="Booking code">
                                <input type="text" class="system-payments-filters__input search-input text-right" data-table="levyTable" data-column="3" placeholder="{{ __('system.common.amount') }}">
                                <input type="text" class="system-payments-filters__input search-input" data-table="levyTable" data-column="4" placeholder="{{ __('system.common.date') }}">
                            </div>
                        </div>
                        <table id="levyTable" class="display stripe w-full table-fixed border-collapse text-sm system-payments-table">
                            <colgroup>
                                <col class="system-payments-col-no">
                                <col class="system-payments-col-company">
                                <col class="system-payments-col-booking">
                                <col class="system-payments-col-amount">
                                <col class="system-payments-col-date">
                            </colgroup>
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wide border-b border-gray-200">
                                    <th class="py-2.5 px-4 text-left font-semibold">No</th>
                                    <th class="py-2.5 px-4 text-left font-semibold">{{ __('system.pages.col_company') }}</th>
                                    <th class="py-2.5 px-4 text-left font-semibold">Booking code</th>
                                    <th class="py-2.5 px-4 text-right font-semibold whitespace-nowrap">{{ __('system.common.amount') }}</th>
                                    <th class="py-2.5 px-4 text-left font-semibold whitespace-nowrap">{{ __('system.common.date') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm divide-y divide-gray-100 bg-white">
                                @php $l = 1; @endphp
                                @if($levies->count() > 0)
                                    @foreach ($levies as $levy)
                                        <tr class="hover:bg-amber-50 transition-colors">
                                            <td class="py-2.5 px-4 text-gray-500 tabular-nums">{{ $l++ }}</td>
                                            <td class="py-2.5 px-4 font-medium text-gray-900">{{ $levy->campany->name }}</td>
                                            <td class="py-2.5 px-4 font-mono text-xs text-gray-800">{{ $levy->booking_id }}</td>
                                            <td class="py-2.5 px-4 text-right font-semibold tabular-nums amount" data-amount="{{ $levy->amount }}">{{ $currency }} {{ convert_money($levy->amount) }}</td>
                                            <td class="py-2.5 px-4 whitespace-nowrap text-gray-600" data-date="{{ $levy->created_at->format('Y-m-d') }}">{{ $levy->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="payments-empty-row">
                                        @for ($col = 0; $col < 5; $col++)
                                            <td class="py-8 px-4 text-center text-gray-500 text-sm">{{ $col === 0 ? __('system.pages.no_data_found') : '' }}</td>
                                        @endfor
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </section>
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
        window.paymentsCurrency = @json(session('currency', 'Tzs'));
        window.paymentsUsdToTzs = {{ app('usdToTzs') ?? 2500 }};

        function formatPaymentsTotal(total) {
            const isUsd = String(window.paymentsCurrency || '').toLowerCase() === 'usd';
            const display = isUsd ? (total / (window.paymentsUsdToTzs || 2500)) : total;
            return display.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function sumFilteredAmounts(api) {
            let total = 0;
            api.rows({ search: 'applied' }).every(function() {
                const amount = $(this.node()).find('.amount').data('amount') || 0;
                total += parseFloat(amount) || 0;
            });
            return total;
        }

        function initPaymentsTable(selector, totalSelector) {
            const $table = $(selector);
            if (!$table.length || $table.find('tbody tr.payments-empty-row').length) {
                return null;
            }

            return $table.DataTable({
                responsive: false,
                scrollX: false,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                dom: "<'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3'<'text-sm text-gray-600'l><'text-sm'f>>rtip",
                paging: true,
                searching: true,
                ordering: true,
                autoWidth: false,
                language: {
                    emptyTable: "No data found"
                },
                footerCallback: function() {
                    $(totalSelector).text(formatPaymentsTotal(sumFilteredAmounts(this.api())));
                }
            });
        }

        function wireColumnFilters(tableMap) {
            $('.system-payments-filters__input').on('keyup change', function() {
                const tableId = $(this).data('table');
                const column = $(this).data('column');
                const table = tableMap[tableId];
                if (!table) {
                    return;
                }
                table.column(column).search(this.value).draw();
            });
        }

        $(document).ready(function () {
            // Create date inputs for Service Fees Table
            var serviceMinDate = new DateTime($('#serviceMinDate'), {
                format: 'DD MMM YYYY'
            });
            var serviceMaxDate = new DateTime($('#serviceMaxDate'), {
                format: 'DD MMM YYYY'
            });

            // Create date inputs for Commission Fees Table
            var commissionMinDate = new DateTime($('#commissionMinDate'), {
                format: 'DD MMM YYYY'
            });
            var commissionMaxDate = new DateTime($('#commissionMaxDate'), {
                format: 'DD MMM YYYY'
            });

            // Create date inputs for Government Levy Table
            var levyMinDate = new DateTime($('#levyMinDate'), {
                format: 'DD MMM YYYY'
            });
            var levyMaxDate = new DateTime($('#levyMaxDate'), {
                format: 'DD MMM YYYY'
            });

            // Custom date filtering function for all tables
            $.fn.dataTableExt.afnFiltering.push(function (settings, data, dataIndex) {
                let tableId = settings.sTableId;
                let filterValue, minDate, maxDate, dateStr;

                if (tableId === 'serviceTable') {
                    filterValue = $('#serviceTimeFilter').val();
                    minDate = serviceMinDate.val();
                    maxDate = serviceMaxDate.val();
                    dateStr = data[4]; // Date column for serviceTable (now has booking code)
                } else if (tableId === 'commissionTable') {
                    filterValue = $('#commissionTimeFilter').val();
                    minDate = commissionMinDate.val();
                    maxDate = commissionMaxDate.val();
                    dateStr = data[4]; // Date column for commissionTable
                } else if (tableId === 'levyTable') {
                    filterValue = $('#levyTimeFilter').val();
                    minDate = levyMinDate.val();
                    maxDate = levyMaxDate.val();
                    dateStr = data[4]; // Date column for levyTable
                } else {
                    return true;
                }

                let date = moment(dateStr, 'DD MMM YYYY');
                if (!date.isValid()) return true; // Skip invalid dates

                let now = moment();

                // For custom date range
                if (filterValue === 'custom') {
                    if (minDate && maxDate) {
                        let minDateMoment = moment(minDate, 'DD MMM YYYY');
                        let maxDateMoment = moment(maxDate, 'DD MMM YYYY');
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

            // Initialize tables
            const serviceTable = initPaymentsTable('#serviceTable', '#serviceTotal');
            const commissionTable = initPaymentsTable('#commissionTable', '#commissionTotal');
            const levyTable = initPaymentsTable('#levyTable', '#levyTotal');
            const tableMap = {
                serviceTable: serviceTable,
                commissionTable: commissionTable,
                levyTable: levyTable,
            };
            wireColumnFilters(tableMap);

            // Apply time filter for Service Fees Table
            $('#serviceTimeFilter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#serviceDateRangeGroup').removeClass('hidden');
                } else {
                    $('#serviceDateRangeGroup').addClass('hidden');
                    serviceTable?.draw();
                }
            });

            // Apply time filter for Commission Fees Table
            $('#commissionTimeFilter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#commissionDateRangeGroup').removeClass('hidden');
                } else {
                    $('#commissionDateRangeGroup').addClass('hidden');
                    commissionTable?.draw();
                }
            });

            // Redraw the Service Fees Table when the custom date inputs change
            $('#serviceMinDate, #serviceMaxDate').on('change', function() {
                if ($('#serviceTimeFilter').val() === 'custom') {
                    serviceTable?.draw();
                }
            });

            // Redraw the Commission Fees Table when the custom date inputs change
            $('#commissionMinDate, #commissionMaxDate').on('change', function() {
                if ($('#commissionTimeFilter').val() === 'custom') {
                    commissionTable?.draw();
                }
            });

            // Apply time filter for Government Levy Table
            $('#levyTimeFilter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#levyDateRangeGroup').removeClass('hidden');
                } else {
                    $('#levyDateRangeGroup').addClass('hidden');
                    levyTable?.draw();
                }
            });

            // Redraw the Government Levy Table when the custom date inputs change
            $('#levyMinDate, #levyMaxDate').on('change', function() {
                if ($('#levyTimeFilter').val() === 'custom') {
                    levyTable?.draw();
                }
            });
        });
    </script>

    <style>
        .system-payments-filters__grid {
            display: grid;
            grid-template-columns: 4rem minmax(0, 1.6fr) minmax(0, 1.2fr) minmax(6rem, 9rem) minmax(6rem, 9rem);
            gap: 0.5rem;
            min-width: 560px;
        }

        .system-payments-filters__input,
        .search-input {
            width: 100%;
            padding: 0.375rem 0.5rem;
            font-size: 12px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background: #fff;
        }

        .system-payments-table {
            min-width: 560px;
        }

        .system-payments-col-no { width: 4rem; }
        .system-payments-col-company { width: 32%; }
        .system-payments-col-booking { width: 24%; }
        .system-payments-col-amount { width: 18%; }
        .system-payments-col-date { width: 18%; }

        @media (max-width: 768px) {
            .system-payments-filters__grid,
            .system-payments-table {
                min-width: 640px;
            }
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 0.5rem;
        }
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.375rem 0.625rem;
            font-size: 0.875rem;
        }
    </style>
@endsection