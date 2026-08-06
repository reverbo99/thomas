@extends('system.app')

@section('title', $campany->name . ' - Dashboard')

@section('content')
<div class="min-h-screen bg-slate-50/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back + Header --}}
        <div class="mb-8">
            <a href="{{ route('system.campany') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-indigo-600 mb-4 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('system.pages.back_to_operators') }}
            </a>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="campany-dash-icon w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">{{ $campany->name }}</h1>
                        <p class="text-slate-500 text-sm mt-0.5">{{ $campany->user->name ?? '—' }} · {{ $campany->user->contact ?? '—' }}</p>
                        <span class="inline-flex mt-2 items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $campany->status == 1 ? 'bg-emerald-100 text-emerald-800' : ($campany->status == 2 ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700') }}">
                            {{ $campany->status == 1 ? __('system.common.active') : ($campany->status == 2 ? __('system.common.disabled') : __('system.common.pending')) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
            <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('system.pages.wallet_balance') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-800">{{ $currency }} {{ convert_money($campany->balance->amount ?? 0) }}</p>
            </div>
            <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('system.pages.booking_revenue') }}</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $currency }} {{ convert_money($totalBookingsRevenue ?? 0) }}</p>
            </div>
            <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('system.pages.parcel_revenue') }}</p>
                <p class="mt-1 text-2xl font-bold text-purple-600">{{ $currency }} {{ convert_money($totalParcelRevenue ?? 0) }}</p>
            </div>
            <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('system.pages.luggage_revenue_gross') }}</p>
                <p class="mt-1 text-2xl font-bold text-cyan-600">{{ $currency }} {{ convert_money($totalLuggageRevenue ?? 0) }}</p>
            </div>
            <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('system.pages.commission_system') }}</p>
                <p class="mt-1 text-2xl font-bold text-indigo-600">{{ $currency }} {{ convert_money($totalCommission ?? 0) }}</p>
            </div>
            <div class="kpi-card bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('system.pages.service_fees_system') }}</p>
                <p class="mt-1 text-2xl font-bold text-amber-600">{{ $currency }} {{ convert_money($totalServiceFees ?? 0) }}</p>
            </div>
        </div>

        {{-- Chart --}}
        <div class="chart-card bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">{{ __('system.pages.bookings_revenue_chart') }}</h2>
            <div class="h-64">
                <canvas id="bookingsChart" width="400" height="200"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
            {{-- Recent Bookings --}}
            <div class="table-card bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-semibold text-slate-800">{{ __('system.pages.recent_bookings') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('system.pages.paid_bookings_label') }}</p>
                </div>
                <div class="overflow-x-auto max-h-80 overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80 sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.pages.code') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.common.route') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.common.date') }}</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-600">{{ __('system.common.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($bookings->take(15) as $b)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-2.5 text-sm font-medium text-slate-800">{{ $b->booking_code }}</td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ optional($b->schedule)->from ?? '—' }} → {{ optional($b->schedule)->to ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ $b->travel_date }}</td>
                                    <td class="px-4 py-2.5 text-sm text-right font-medium text-slate-800">{{ $currency }} {{ convert_money($b->amount ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400 text-sm">{{ __('system.pages.no_bookings_short') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Schedules --}}
            <div class="table-card bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-semibold text-slate-800">{{ __('system.sidebar.bus_schedule') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('system.pages.upcoming_trips') }}</p>
                </div>
                <div class="overflow-x-auto max-h-80 overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80 sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.pages.bus_label') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.pages.from_to') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.common.date') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.pages.time_label') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($schedules->take(15) as $s)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-2.5 text-sm font-medium text-slate-800">{{ $s->bus->bus_number ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ $s->from ?? '—' }} → {{ $s->to ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ $s->schedule_date ? \Carbon\Carbon::parse($s->schedule_date)->format('d M Y') : '—' }}</td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ $s->start ?? '—' }} - {{ $s->end ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400 text-sm">{{ __('system.pages.no_schedules_short') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
            {{-- Buses --}}
            <div class="table-card bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-semibold text-slate-800">{{ __('system.sidebar.buses') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('system.pages.total_count', ['count' => $campany->bus->count()]) }}</p>
                </div>
                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80 sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.pages.bus_number') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.pages.type_label') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('all.seats') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($campany->bus as $bus)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-2.5 text-sm font-medium text-slate-800">{{ $bus->bus_number }}</td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ $bus->bus_type ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ $bus->total_seats ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400 text-sm">{{ __('system.pages.no_buses_dot') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Payment Requests (Transactions) --}}
            <div class="table-card bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-lg font-semibold text-slate-800">{{ __('system.sidebar.payment_request') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('system.pages.recent_transactions_sub') }}</p>
                </div>
                <div class="overflow-x-auto max-h-64 overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/80 sticky top-0">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.common.user') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.common.amount') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.common.status') }}</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600">{{ __('system.common.date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($transactions->take(10) as $t)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-2.5 text-sm text-slate-800">{{ $t->user->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-sm font-medium text-slate-800">{{ $currency }} {{ convert_money($t->amount ?? 0) }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $t->status === 'Completed' ? 'bg-emerald-100 text-emerald-800' : ($t->status === 'Pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $t->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm text-slate-600">{{ $t->created_at ? $t->created_at->format('d M Y H:i') : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400 text-sm">{{ __('system.pages.no_transactions_dot') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Anime.js: animate cards and chart in
    var kpiCards = document.querySelectorAll('.kpi-card');
    var chartCard = document.querySelector('.chart-card');
    var tableCards = document.querySelectorAll('.table-card');
    var dashIcon = document.querySelector('.campany-dash-icon');

    anime({
        targets: dashIcon,
        translateY: [20, 0],
        opacity: [0, 1],
        duration: 500,
        easing: 'easeOutElastic(1, .6)'
    });

    anime({
        targets: kpiCards,
        translateY: [24, 0],
        opacity: [0, 1],
        delay: anime.stagger(80),
        duration: 400,
        easing: 'easeOutExpo'
    });

    anime({
        targets: chartCard,
        translateY: [24, 0],
        opacity: [0, 1],
        delay: 350,
        duration: 450,
        easing: 'easeOutExpo'
    });

    anime({
        targets: tableCards,
        translateY: [20, 0],
        opacity: [0, 1],
        delay: anime.stagger(80, { start: 500 }),
        duration: 400,
        easing: 'easeOutExpo'
    });

    // Chart.js: bookings & revenue last 14 days
    var chartData = @json($bookingsChart);
    var labels = chartData.map(function(d) { return d.date; });
    var counts = chartData.map(function(d) { return d.count; });
    var totals = chartData.map(function(d) { return Math.round(parseFloat(d.total) || 0); });

    var ctx = document.getElementById('bookingsChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Bookings',
                        data: counts,
                        backgroundColor: 'rgba(99, 102, 241, 0.6)',
                        borderColor: 'rgb(99, 102, 241)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue ({{ $currency }})',
                        data: totals,
                        type: 'line',
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Bookings' } },
                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Revenue' } }
                }
            }
        });
    }
});
</script>
@endsection
