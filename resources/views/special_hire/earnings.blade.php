@extends('special_hire.app')

@section('title', 'Earnings')
@section('page_title', 'Earnings Report')
@section('page_subtitle', 'Track your revenue and performance')

@section('content')
<div class="space-y-6">
    <!-- Period Filter -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <div class="flex flex-wrap items-center gap-4">
            <span class="text-gray-600 font-medium">Period:</span>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('special_hire.earnings', ['period' => 'today']) }}" 
                   class="px-4 py-2 rounded-xl font-medium transition-colors {{ $period === 'today' ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Today
                </a>
                <a href="{{ route('special_hire.earnings', ['period' => 'week']) }}" 
                   class="px-4 py-2 rounded-xl font-medium transition-colors {{ $period === 'week' ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    This Week
                </a>
                <a href="{{ route('special_hire.earnings', ['period' => 'month']) }}" 
                   class="px-4 py-2 rounded-xl font-medium transition-colors {{ $period === 'month' ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    This Month
                </a>
                <a href="{{ route('special_hire.earnings', ['period' => 'year']) }}" 
                   class="px-4 py-2 rounded-xl font-medium transition-colors {{ $period === 'year' ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    This Year
                </a>
            </div>
            <div class="ml-auto text-sm text-gray-500">
                {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
            </div>
        </div>
    </div>

    @php
        $periodLabel = match ($period) {
            'today' => 'Today',
            'week' => 'This week',
            'year' => 'This year',
            default => 'This month',
        };
    @endphp

    <!-- Stats Cards (filtered by Period above) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-teal-100 text-sm font-medium">Paid earnings — {{ $periodLabel }} (net)</p>
                    <p class="text-teal-100/80 text-xs mt-0.5">{{ $startDate->format('M d') }} – {{ $endDate->format('M d, Y') }}</p>
                    <h3 class="text-3xl font-bold mt-2">Tsh {{ number_format($totalEarnings) }}</h3>
                    @if($totalCommission > 0)
                        <p class="text-teal-100/90 text-xs mt-1">After Tsh {{ number_format($totalCommission, 0) }} platform commission</p>
                    @endif
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1 1.05.82 1.87 2.65 1.87 1.96 0 2.4-.98 2.4-1.59 0-.83-.44-1.61-2.67-2.14-2.48-.6-4.18-1.62-4.18-3.67 0-1.72 1.39-2.84 3.11-3.21V4h2.67v1.95c1.86.45 2.79 1.86 2.85 3.39H14.3c-.05-1.11-.64-1.87-2.22-1.87-1.5 0-2.4.68-2.4 1.64 0 .84.65 1.39 2.67 1.91s4.18 1.39 4.18 3.91c-.01 1.83-1.38 2.83-3.12 3.16z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Paid orders — {{ $periodLabel }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1">{{ $totalOrders }}</h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Distance — {{ $periodLabel }}</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($totalDistance) }} km</h3>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <p class="text-sm text-gray-600 bg-teal-50/80 border border-teal-100 rounded-xl px-4 py-3">
        <strong class="text-gray-800">Why two different totals?</strong>
        The teal card is only for the <strong>period you selected</strong> (e.g. this month). Withdrawals use your
        <strong>lifetime</strong> paid hires (all dates), minus anything already paid out or held for a pending request.
        Example: <span class="text-teal-800 font-medium">Tsh {{ number_format($totalEarnings, 0) }}</span> this period vs
        <span class="text-teal-800 font-medium">Tsh {{ number_format($lifetimePaidEarnings, 0) }}</span> all-time net.
        All figures are after the platform commission has been deducted.
    </p>

    <!-- Request withdrawal (payout to your account) -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-amber-100">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50">
            <h3 class="text-lg font-bold text-gray-800">Request withdrawal from admin</h3>
            <p class="text-sm text-gray-600 mt-1">Payouts are based on <strong>all-time</strong> paid hire revenue (not the month filter above). Enter where you want to receive the money.</p>
        </div>
        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4 text-sm space-y-2">
                    <div class="flex justify-between"><span class="text-gray-600">Lifetime paid hire total (gross)</span><span class="font-semibold text-gray-900">Tsh {{ number_format($lifetimeGrossEarnings, 0) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Platform commission deducted</span><span class="font-semibold text-red-600">− Tsh {{ number_format($lifetimeCommission, 0) }}</span></div>
                    <div class="flex justify-between border-t border-gray-200 pt-2"><span class="text-gray-800 font-medium">Your net hire earnings</span><span class="font-semibold text-gray-900">Tsh {{ number_format($lifetimePaidEarnings, 0) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Already paid out to you</span><span class="font-semibold text-teal-700">Tsh {{ number_format($withdrawalPaidOut, 0) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">In review (pending / approved)</span><span class="font-semibold text-amber-700">Tsh {{ number_format($withdrawalReserved, 0) }}</span></div>
                    <div class="flex justify-between pt-2 border-t border-gray-200"><span class="text-gray-800 font-medium">You can request now</span><span class="font-bold text-teal-600 text-lg">Tsh {{ number_format($withdrawableBalance, 0) }}</span></div>
                </div>
                @if($withdrawableBalance < 1)
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3">Nothing available to withdraw right now. When customers pay for hires and amounts are marked paid, your balance will update.</p>
                @else
                    <form action="{{ route('special_hire.withdrawal.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="period" value="{{ $period }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (Tsh) *</label>
                            <input type="number" name="amount" value="{{ old('amount') }}" required min="1" step="0.01" max="{{ $withdrawableBalance }}"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="e.g. {{ number_format(min(50000, $withdrawableBalance), 0, '', '') }}">
                            @error('amount')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment method *</label>
                            <input type="text" name="payment_method" value="{{ old('payment_method') }}" required maxlength="100"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="e.g. M-Pesa, Tigo Pesa, Bank transfer">
                            @error('payment_method')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Account / phone number *</label>
                            <input type="text" name="payment_number" value="{{ old('payment_number') }}" required maxlength="255"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                   placeholder="Number or account the admin should pay to">
                            @error('payment_number')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Note to admin</label>
                            <textarea name="notes" rows="2" maxlength="2000"
                                      class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                      placeholder="Optional">{{ old('notes') }}</textarea>
                            @error('notes')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="btn-primary w-full sm:w-auto px-6 py-2.5 text-white rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                            Submit withdrawal request
                        </button>
                    </form>
                @endif
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-800 mb-2">Open requests</h4>
                <p class="text-xs text-gray-500 mb-3">Pending or approved — admin has not marked paid yet.</p>
                @if($withdrawalRequestsOpen->isEmpty())
                    <p class="text-gray-500 text-sm mb-6">None right now.</p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-amber-100 mb-6">
                        <table class="w-full text-sm">
                            <thead class="bg-amber-50 text-left text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2">Amount</th>
                                    <th class="px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($withdrawalRequestsOpen as $req)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $req->created_at->format('M d, Y') }}</td>
                                        <td class="px-3 py-2 font-medium text-gray-900">Tsh {{ number_format($req->amount, 0) }}</td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold capitalize
                                                {{ $req->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                                                {{ $req->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h4 class="text-sm font-semibold text-gray-800 mb-2">History — paid or rejected</h4>
                @if($withdrawalRequestsExecuted->isEmpty())
                    <p class="text-gray-500 text-sm">No completed requests yet.</p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-3 py-2">Date</th>
                                    <th class="px-3 py-2">Amount</th>
                                    <th class="px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($withdrawalRequestsExecuted as $req)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $req->created_at->format('M d, Y') }}</td>
                                        <td class="px-3 py-2 font-medium text-gray-900">Tsh {{ number_format($req->amount, 0) }}</td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold capitalize
                                                {{ $req->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $req->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                                {{ $req->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Admin may add a note when updating your request.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Earnings by Coaster -->
    @if($earningsByCoaster->count() > 0)
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-800">Earnings by Coaster</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($earningsByCoaster as $earning)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">{{ $earning->coaster->name ?? 'Unknown' }}</h4>
                                    <p class="text-sm text-gray-500">{{ $earning->trips }} trips</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-teal-600 text-lg">Tsh {{ number_format($earning->total) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Recent Paid Orders -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">Paid Orders</h3>
        </div>
        @if($orders->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500">No paid orders for this period</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Coaster</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Distance</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($orders as $order)
                            <tr class="table-row-hover">
                                <td class="px-6 py-4">
                                    <a href="{{ route('special_hire.orders.show', $order->id) }}" class="font-mono font-semibold text-teal-600 hover:text-teal-700">
                                        {{ $order->order_code }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-gray-800">{{ $order->customer_name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $order->coaster->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $order->distance_km }} km</td>
                                <td class="px-6 py-4 text-gray-600">{{ $order->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900">Tsh {{ number_format($order->total_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

