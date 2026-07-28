@extends('system.app')

@section('content')
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800">{{ __('system.pages.levy_title') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('system.pages.levy_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('system.government_levy.pdf', request()->only(['period', 'start_date', 'end_date'])) }}" target="_blank"
                   class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                    {{ __('system.pages.export_pdf') }}
                </a>
                <a href="{{ route('system.government_levy.csv', request()->only(['period', 'start_date', 'end_date'])) }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm">
                    {{ __('system.pages.export_csv') }}
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('system.government_levy') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label for="period" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.pages.period') }}</label>
                    <select id="period" name="period" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
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
                    <a href="{{ route('system.government_levy') }}" class="w-full md:w-auto text-center px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition">
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
                values will show as 0 until migration is applied.
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-500">Paid Amount</p>
                <p class="text-xl font-semibold text-slate-800 mt-2">{{ $currency }} {{ convert_money($totalPaidAmount) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-500">VAT</p>
                <p class="text-xl font-semibold text-slate-800 mt-2">{{ $currency }} {{ convert_money($totalVat) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <p class="text-xs font-medium text-slate-500">System Service Fee</p>
                <p class="text-xl font-semibold text-indigo-700 mt-2">{{ $currency }} {{ convert_money($totalSystemServiceFee) }}</p>
            </div>
            <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-4">
                <p class="text-xs font-medium text-amber-600">Est. Special Hire Revenue</p>
                <p class="text-xl font-semibold text-amber-800 mt-2">{{ $currency }} {{ convert_money($specialHireTotalAmount) }}</p>
            </div>
        </div>

        {{-- Government levy breakdown cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-4">
                <p class="text-xs font-medium text-emerald-600">Gov Levy (Fare)</p>
                <p class="text-xl font-semibold text-emerald-700 mt-2">{{ $currency }} {{ convert_money($totalGovLevyOnFare) }}</p>
                <p class="text-xs text-slate-400 mt-1">5% of bus fare</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-4">
                <p class="text-xs font-medium text-emerald-600">Gov Levy (Service)</p>
                <p class="text-xl font-semibold text-emerald-600 mt-2">{{ $currency }} {{ convert_money($totalGovLevyOnService) }}</p>
                <p class="text-xs text-slate-400 mt-1">5% of service fee</p>
            </div>
            <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-4">
                <p class="text-xs font-medium text-amber-600">Gov Levy (Special Hire)</p>
                <p class="text-xl font-semibold text-amber-700 mt-2">{{ $currency }} {{ convert_money($specialHireLevyTotal) }}</p>
                <p class="text-xs text-slate-400 mt-1">5% of SH revenue (est.)</p>
            </div>
            <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-4 lg:col-span-2">
                <p class="text-xs font-medium text-emerald-800">{{ __('system.pages.total_gov_levy') }}</p>
                <p class="text-2xl font-bold text-emerald-900 mt-2">{{ $currency }} {{ convert_money($totalGovernmentLevy) }}</p>
                <p class="text-xs text-slate-400 mt-1">Fare + Service + Special Hire</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 bg-emerald-50">
                <h3 class="text-sm font-semibold text-emerald-800">Paid Bookings — Levy on Fare + Levy on Service Fee</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Booking</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.common.date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Route</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Vendor</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Paid Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">VAT</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Gov Levy (Fare)</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Gov Levy (Service)</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.total_gov_levy') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.service_fee') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bookings as $booking)
                            @php
                                $govLevyOnFare = (float) ($booking->government_levy ?? 0);
                                $govLevyOnService = (float) $booking->governmentLeviesOnService->sum('amount');
                                $totalGovLevy = $govLevyOnFare + $govLevyOnService;
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 text-sm text-slate-700 font-medium">{{ $booking->booking_code ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ optional($booking->created_at)->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $booking->route->from ?? 'N/A' }} - {{ $booking->route->to ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if (($booking->vender_id ?? 0) > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Involved</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Not Involved</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 text-right">{{ $currency }} {{ convert_money($booking->amount ?? 0) }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 text-right">{{ $currency }} {{ convert_money($booking->vat ?? 0) }}</td>
                                <td class="px-4 py-3 text-sm text-emerald-700 text-right font-medium">{{ $currency }} {{ convert_money($govLevyOnFare) }}</td>
                                <td class="px-4 py-3 text-sm text-emerald-600 text-right font-medium">{{ $currency }} {{ convert_money($govLevyOnService) }}</td>
                                <td class="px-4 py-3 text-sm text-emerald-800 text-right font-semibold">{{ $currency }} {{ convert_money($totalGovLevy) }}</td>
                                <td class="px-4 py-3 text-sm text-indigo-700 text-right font-medium">{{ $currency }} {{ convert_money($booking->system_service_fee ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('system.pages.no_paid_bookings_filter') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $bookings->links() }}
            </div>
        </div>

        {{-- Special Hire Orders breakdown --}}
        <div class="bg-white rounded-xl border border-amber-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-amber-200 bg-amber-50">
                <h3 class="text-sm font-semibold text-amber-800">Special Hire Orders — Estimated Government Levy (5%)</h3>
                <p class="text-xs text-amber-600 mt-0.5">This levy is estimated at 5% of the total order amount. It is not deducted from operator payouts.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-amber-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Order Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.common.date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Pickup → Dropoff</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Operator</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">Total Amount</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-amber-700 uppercase">Est. Gov Levy (5%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($specialHireOrders as $order)
                            @php
                                $shLevy = round((float) $order->total_amount * 5 / 100, 2);
                            @endphp
                            <tr class="hover:bg-amber-50 transition">
                                <td class="px-4 py-3 text-sm text-slate-700 font-medium">{{ $order->order_code ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ optional($order->created_at)->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $order->pickup_location ?? 'N/A' }} — {{ $order->dropoff_location ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $order->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 text-right font-medium">{{ $currency }} {{ convert_money($order->total_amount ?? 0) }}</td>
                                <td class="px-4 py-3 text-sm text-amber-700 text-right font-semibold">{{ $currency }} {{ convert_money($shLevy) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No paid special hire orders for this period.</td>
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
