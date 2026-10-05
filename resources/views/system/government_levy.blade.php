@extends('system.app')

@section('content')
    @php
        $levyPercentLabel = rtrim(rtrim(number_format($levyPercent ?? government_levy_percent(), 2, '.', ''), '0'), '.');
    @endphp
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800 dark:text-slate-100">{{ __('system.pages.levy_title') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ __('system.pages.levy_subtitle_six', ['percent' => $levyPercentLabel]) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('system.government_levy.pdf', request()->only(['period', 'start_date', 'end_date'])) }}" target="_blank"
                   class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                    {{ __('system.pages.export_pdf') }}
                </a>
                <a href="{{ route('system.government_levy.csv', request()->only(['period', 'start_date', 'end_date'])) }}"
                   class="inline-flex items-center px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition text-sm">
                    {{ __('system.pages.export_csv') }}
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('system.government_levy') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-600 shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label for="period" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.pages.period') }}</label>
                    <select id="period" name="period" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="all" {{ ($period ?? '') === 'all' ? 'selected' : '' }}>{{ __('system.common.all_time') }}</option>
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>{{ __('system.sidebar.today') }}</option>
                        <option value="week" {{ $period === 'week' ? 'selected' : '' }}>{{ __('system.common.this_week') }}</option>
                        <option value="month" {{ $period === 'month' ? 'selected' : '' }}>{{ __('system.common.this_month') }}</option>
                        <option value="year" {{ $period === 'year' ? 'selected' : '' }}>{{ __('system.common.this_year') }}</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>{{ __('system.common.custom_range') }}</option>
                    </select>
                </div>
                <div>
                    <label for="start_date" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.common.start_date') }}</label>
                    <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.common.end_date') }}</label>
                    <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full md:w-auto px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
                        {{ __('system.pages.apply_filter') }}
                    </button>
                    <a href="{{ route('system.government_levy', ['period' => 'all']) }}" class="w-full md:w-auto text-center px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition">
                        {{ __('system.pages.reset') }}
                    </a>
                </div>
            </div>
        </form>

        @if(!$hasGovernmentLevyColumn || !$hasSystemServiceFeeColumn)
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
                Some columns are missing in your database schema.
                @if(!$hasGovernmentLevyColumn)
                    <span class="font-semibold">`government_levy`</span>
                @endif
                @if(!$hasGovernmentLevyColumn && !$hasSystemServiceFeeColumn)
                    and
                @endif
                @if(!$hasSystemServiceFeeColumn)
                    <span class="font-semibold">`system_service_fee`</span>
                @endif
                values will fall back to recalculated figures until migration is applied.
            </div>
        @endif

        {{-- Grand total government levy (all categories + fare) --}}
        <div class="mb-6 rounded-xl border-2 border-emerald-400 bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950 dark:to-emerald-900 dark:border-emerald-600 p-6 shadow-sm">
            <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-200 uppercase tracking-wide">{{ __('system.pages.grand_total_government_levy') }}</p>
            <p class="text-3xl font-bold text-emerald-900 dark:text-emerald-50 mt-2">{{ $currency }} {{ convert_money($grandTotalGovernmentLevy) }}</p>
            <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-1">{{ __('system.pages.grand_total_government_levy_hint', ['percent' => $levyPercentLabel]) }}</p>
        </div>

        {{-- Levy category cards (fare first; no commission overlay / duplicate service card) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-600 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-600 dark:text-slate-300 uppercase tracking-wide">{{ __('system.pages.gov_levy_fare') }}</p>
                <p class="text-xl font-semibold text-slate-900 dark:text-slate-50 mt-2">{{ $currency }} {{ convert_money($totalGovLevyOnFare) }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $levyPercentLabel }}% × busFee</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-emerald-200 dark:border-emerald-800 shadow-sm p-4">
                <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">{{ __('system.pages.levy_cat_service') }}</p>
                <p class="text-xl font-semibold text-emerald-800 dark:text-emerald-100 mt-2">{{ $currency }} {{ convert_money($levyService) }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $levyPercentLabel }}% × full service fee</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-cyan-200 dark:border-cyan-800 shadow-sm p-4">
                <p class="text-xs font-medium text-cyan-600 dark:text-cyan-400 uppercase tracking-wide">{{ __('system.pages.levy_cat_luggage') }}</p>
                <p class="text-xl font-semibold text-cyan-800 dark:text-cyan-100 mt-2">{{ $currency }} {{ convert_money($levyLuggage) }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $levyPercentLabel }}% × excess luggage · {{ $luggageBookingCount }} {{ __('system.common.entries') }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-rose-200 dark:border-rose-800 shadow-sm p-4">
                <p class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wide">{{ __('system.pages.levy_cat_cancellation') }}</p>
                <p class="text-xl font-semibold text-rose-800 dark:text-rose-100 mt-2">{{ $currency }} {{ convert_money($levyCancellation) }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $levyPercentLabel }}% × cancellation fee · {{ $cancellationCount }} {{ __('system.common.entries') }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-purple-200 dark:border-purple-800 shadow-sm p-4">
                <p class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wide">{{ __('system.pages.levy_cat_parcel') }}</p>
                <p class="text-xl font-semibold text-purple-800 dark:text-purple-100 mt-2">{{ $currency }} {{ convert_money($levyParcel) }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $levyPercentLabel }}% × {{ __('system.pages.levy_parcel_fee_base') }} · {{ $parcelCount }} {{ __('system.common.entries') }}</p>
                @if(($parcelFeesBase ?? 0) > 0)
                    <p class="text-xs text-purple-600 dark:text-purple-300 mt-0.5">{{ __('system.pages.levy_parcel_fees_sum') }}: {{ $currency }} {{ convert_money($parcelFeesBase) }}</p>
                @endif
                <p class="text-xs text-purple-500 dark:text-purple-300 mt-0.5">{{ __('system.pages.levy_cat_parcel_included_hint', ['percent' => government_levy_percent()]) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-teal-200 dark:border-teal-800 shadow-sm p-4">
                <p class="text-xs font-medium text-teal-600 dark:text-teal-400 uppercase tracking-wide">{{ __('system.pages.levy_cat_special_hire') }}</p>
                <p class="text-xl font-semibold text-teal-800 dark:text-teal-100 mt-2">{{ $currency }} {{ convert_money($levySpecialHire) }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $levyPercentLabel }}% × platform commission</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-emerald-300 dark:border-emerald-700 shadow-sm p-4 sm:col-span-2 lg:col-span-3">
                <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300 uppercase tracking-wide">{{ __('system.pages.total_gov_levy') }}</p>
                <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-50 mt-2">{{ $currency }} {{ convert_money($totalGovernmentLevy) }}</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ __('system.pages.levy_six_total_hint') }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-600 shadow-sm overflow-hidden mb-6">
            <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 bg-emerald-50 dark:bg-emerald-950">
                <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">{{ __('system.pages.levy_bookings_section') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-600">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Booking</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">{{ __('system.common.date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Route</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Vendor</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">{{ __('system.pages.paid_amount') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">{{ __('system.pages.bus_fee') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">{{ __('system.pages.gov_levy_fare') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">{{ __('system.pages.gov_levy_service') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">{{ __('system.pages.levy_cat_luggage') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">{{ __('system.pages.total_gov_levy') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse($bookings as $booking)
                            @php
                                $govLevyOnFare = booking_government_levy_on_fare($booking);
                                $govLevyOnService = booking_government_levy_on_service($booking);
                                $luggageLevy = booking_government_levy_on_luggage($booking);
                                $rowTotalLevy = booking_row_total_government_levy($booking);
                                // Reconciled total (fare + actual weighed luggage + service + insurance),
                                // never customer_paid_total which keeps the estimated deposit.
                                $paidAmount = booking_reconciled_customer_total($booking);
                                $busFee = (float) ($booking->busFee ?? 0);
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 font-medium">{{ $booking->booking_code ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ optional($booking->created_at)->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $booking->route->from ?? 'N/A' }} - {{ $booking->route->to ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                    @if (($booking->vender_id ?? 0) > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100">Involved</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-200">Not Involved</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">{{ $currency }} {{ convert_money($paidAmount) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">{{ $currency }} {{ convert_money($busFee) }}</td>
                                <td class="px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300 text-right font-medium">{{ $currency }} {{ convert_money($govLevyOnFare) }}</td>
                                <td class="px-4 py-3 text-sm text-emerald-600 dark:text-emerald-400 text-right font-medium">{{ $currency }} {{ convert_money($govLevyOnService) }}</td>
                                <td class="px-4 py-3 text-sm text-cyan-700 dark:text-cyan-300 text-right font-medium">{{ $currency }} {{ convert_money($luggageLevy) }}</td>
                                <td class="px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200 text-right font-semibold">{{ $currency }} {{ convert_money($rowTotalLevy) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('system.pages.no_paid_bookings_filter') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-600">
                {{ $bookings->links() }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-teal-200 dark:border-teal-800 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-950">
                <h3 class="text-sm font-semibold text-teal-800 dark:text-teal-200">{{ __('system.pages.levy_special_hire_section', ['percent' => $levyPercentLabel]) }}</h3>
                <p class="text-xs text-teal-600 dark:text-teal-400 mt-0.5">{{ __('system.pages.levy_special_hire_hint', ['percent' => $levyPercentLabel]) }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-teal-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.col_order_code') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.common.date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.col_pickup_dropoff') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.operator') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.platform_commission') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-teal-700 uppercase">{{ __('system.pages.levy_cat_special_hire') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($specialHireOrders as $order)
                            @php
                                $commission = (float) ($order->platform_commission_amount ?? 0);
                                $shLevy = government_levy_on_amount($commission);
                            @endphp
                            <tr class="hover:bg-teal-50 transition">
                                <td class="px-4 py-3 text-sm text-slate-700 font-medium">{{ $order->order_code ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ optional($order->created_at)->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $order->pickup_location ?? 'N/A' }} — {{ $order->dropoff_location ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $order->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 text-right font-medium">{{ $currency }} {{ convert_money($commission) }}</td>
                                <td class="px-4 py-3 text-sm text-teal-700 text-right font-semibold">{{ $currency }} {{ convert_money($shLevy) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('system.pages.levy_no_special_hire') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $specialHireOrders->links() }}
            </div>
        </div>
    </div>
@endsection
