@extends('system.app')

@section('title', 'Special Hire — ' . $selectedOwner->name)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('system.special_hire') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← Accounts</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('system.special_hire', ['tab' => 'withdrawals']) }}" class="text-sm text-amber-800 hover:text-amber-900 font-medium">All payout requests</a>
            </div>
            <h1 class="text-2xl font-semibold text-gray-800 mt-2">{{ $selectedOwner->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $selectedOwner->email }} · {{ $selectedOwner->contact ?? $selectedOwner->phone ?? '—' }}</p>
            <p class="text-sm text-gray-500 mt-1">Special hire operator · User ID {{ $selectedOwner->id }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-8 max-w-xl">
        <h2 class="text-sm font-semibold text-gray-800 mb-3">Platform commission (each paid trip)</h2>
        <p class="text-xs text-gray-500 mb-4">Percentage of the hire total retained by the system when the customer completes the final ClickPesa payment. This rate is stored on the operator account and applied to every new quote and order for this operator.</p>
        <form action="{{ route('system.special_hire.platform_percent', $selectedOwner->id) }}" method="post" class="space-y-4">
            @csrf
            <div>
                <label for="special_hire_platform_percent" class="block text-xs font-medium text-gray-600 mb-1">Percent (0–100)</label>
                <input type="number" step="0.01" min="0" max="100" name="special_hire_platform_percent" id="special_hire_platform_percent"
                    value="{{ old('special_hire_platform_percent', $selectedOwner->special_hire_platform_percent ?? 0) }}"
                    class="w-full max-w-xs rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 @error('special_hire_platform_percent') border-red-500 @enderror" required>
                @error('special_hire_platform_percent')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="inline-flex items-center justify-center w-full max-w-xs px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save platform commission
            </button>
        </form>
    </div>

    <nav class="flex flex-wrap gap-2 mb-6 text-sm" aria-label="Page sections">
        <a href="#stats" class="px-3 py-1.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">Summary</a>
        <a href="#coasters" class="px-3 py-1.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">Coasters</a>
        <a href="#drivers" class="px-3 py-1.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">Drivers</a>
        <a href="#transactions" class="px-3 py-1.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">Orders</a>
        <a href="#withdrawals" class="px-3 py-1.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300">{{ __('system.pages.withdrawals') }}</a>
    </nav>

    <div id="stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-teal-600 uppercase tracking-wide">Coasters</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['coasters']) }}</p>
            <p class="text-xs text-gray-500 mt-1">This account</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-teal-600 uppercase tracking-wide">Drivers (assigned)</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['drivers']) }}</p>
            <p class="text-xs text-gray-500 mt-1">On this fleet</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-teal-600 uppercase tracking-wide">Hire orders</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['orders']) }}</p>
            <p class="text-xs text-gray-500 mt-1">All-time for this account</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-teal-600 uppercase tracking-wide">Paid revenue</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $currency }} {{ convert_money($stats['revenue_paid']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Pending: {{ $currency }} {{ convert_money($stats['revenue_pending']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">Order status (this account)</h2>
            <ul class="space-y-2 text-sm">
                @forelse($ordersByStatus as $status => $cnt)
                    <li class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $status) }}</span>
                        <span class="font-medium text-gray-900">{{ number_format($cnt) }}</span>
                    </li>
                @empty
                    <li class="text-gray-500">{{ __('system.pages.no_orders_yet') }}</li>
                @endforelse
            </ul>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-sm font-semibold text-gray-800 mb-3">Payment status (this account)</h2>
            <ul class="space-y-2 text-sm">
                @forelse($paymentsByStatus as $status => $cnt)
                    <li class="flex justify-between border-b border-gray-100 pb-2">
                        <span class="text-gray-600 capitalize">{{ $status }}</span>
                        <span class="font-medium text-gray-900">{{ number_format($cnt) }}</span>
                    </li>
                @empty
                    <li class="text-gray-500">{{ __('system.pages.no_orders_yet') }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div id="coasters" class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">Coasters (this account)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Vehicle</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Plate</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Driver</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.status') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">{{ __('all.seats') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($coasters as $c)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $c->name }}</div>
                                @if($c->model || $c->color)
                                    <div class="text-xs text-gray-500">{{ $c->model }} @if($c->color) · {{ $c->color }} @endif</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-mono">{{ $c->plate_number }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($c->driver)
                                    {{ $c->driver->name }}<br><span class="text-xs text-gray-400">{{ $c->driver->email }}</span>
                                @else
                                    {{ $c->driver_name ?: '—' }}
                                    @if($c->driver_contact)<br><span class="text-xs text-gray-400">{{ $c->driver_contact }}</span>@endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    {{ $c->status === 'available' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $c->status === 'on_hire' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $c->status === 'maintenance' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $c->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900">{{ number_format($c->capacity) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('system.pages.no_coasters_account') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="drivers" class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">Drivers</h2>
            <p class="text-sm text-gray-500">Assigned to this account’s coasters</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Driver</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Email / contact</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Assigned coaster(s)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($drivers as $d)
                        @php $assigned = $coastersByDriverId->get($d->id, collect()); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $d->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $d->email }}<br><span class="text-xs text-gray-400">{{ $d->contact ?? '—' }}</span></td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($assigned->isEmpty())
                                    <span class="text-amber-600">—</span>
                                @else
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($assigned as $ac)
                                            <li>{{ $ac->name }} <span class="font-mono text-xs">({{ $ac->plate_number }})</span></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-gray-500">{{ __('system.pages.no_drivers_fleet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="transactions" class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Hire orders</h2>
                <p class="text-sm text-gray-500">Latest {{ $orders->count() }} (max 150). New hires are accepted or declined by the assigned driver in the driver app; this list is read-only for monitoring.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('system.special_hire.show.report.pdf', $selectedOwner->id) }}" target="_blank"
                   class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                    {{ __('system.pages.export_pdf') }}
                </a>
                <a href="{{ route('system.special_hire.show.report.csv', $selectedOwner->id) }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
                    {{ __('system.pages.export_csv') }}
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Code</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Created</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Coaster</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Customer</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Hire date</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Total</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Payment</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Order</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.pages.manifest') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($orders as $o)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-gray-900">{{ $o->order_code }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $o->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $o->coaster->name ?? '—' }}<br><span class="text-xs font-mono text-gray-400">{{ $o->coaster->plate_number ?? '' }}</span></td>
                            <td class="px-4 py-3 text-gray-600">{{ $o->customer_name }}<br><span class="text-xs text-gray-400">{{ $o->customer_phone }}</span></td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $o->hire_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">{{ $currency }} {{ convert_money($o->total_amount) }}</td>
                            <td class="px-4 py-3">
                                <span class="capitalize text-xs font-semibold
                                    {{ $o->payment_status === 'paid' ? 'text-green-700' : '' }}
                                    {{ $o->payment_status === 'pending' ? 'text-amber-700' : '' }}
                                    {{ $o->payment_status === 'refunded' ? 'text-red-700' : '' }}">
                                    {{ $o->payment_status }}
                                </span>
                                @if($o->payment_method)
                                    <div class="text-xs text-gray-400">{{ $o->payment_method }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 capitalize text-xs text-gray-700">{{ str_replace('_', ' ', $o->order_status) }}</td>
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <a href="{{ route('system.special_hire.order.passengers.edit', $o->id) }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ __('system.pages.manage_passengers') }}</a>
                                    <a href="{{ route('system.special_hire.order.manifest.pdf', $o->id) }}" target="_blank" class="text-gray-600 hover:text-gray-900">{{ __('system.pages.manifest') }}</a>
                                    <a href="{{ route('system.special_hire.order.receipt.customer', $o->id) }}" target="_blank" class="text-gray-600 hover:text-gray-900">{{ __('system.pages.customer_receipt') }}</a>
                                    <a href="{{ route('system.special_hire.order.receipt.commission', $o->id) }}" target="_blank" class="text-gray-600 hover:text-gray-900">{{ __('system.pages.commission_receipt') }}</a>
                                    @unless(in_array($o->order_status, ['completed', 'cancelled'], true))
                                        <a href="{{ route('system.special_hire.order.transfer.edit', $o->id) }}" class="text-amber-600 hover:text-amber-800">{{ __('system.pages.transfer_order') }}</a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">{{ __('system.pages.no_hire_orders_account') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="withdrawals" class="space-y-8 mb-8">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-amber-100">
            <div class="px-6 py-4 border-b border-gray-200 bg-amber-50">
                <h2 class="text-lg font-semibold text-gray-800">Open withdrawal requests</h2>
                <p class="text-sm text-gray-600">For <strong>{{ $selectedOwner->name }}</strong> — pending or approved (not yet paid).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.date') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">{{ __('system.common.amount') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Pay to</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.status') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Admin action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($withdrawalRequestsOpen as $wr)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $wr->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ $currency }} {{ convert_money($wr->amount) }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    <span class="font-medium text-gray-800">{{ $wr->payment_method }}</span>
                                    <div class="text-xs font-mono mt-1">{{ $wr->payment_number }}</div>
                                    @if($wr->notes)
                                        <div class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($wr->notes, 120) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="capitalize font-medium
                                        {{ $wr->status === 'pending' ? 'text-amber-700' : '' }}
                                        {{ $wr->status === 'approved' ? 'text-blue-700' : '' }}">{{ $wr->status }}</span>
                                    @if($wr->admin_note)
                                        <div class="text-xs text-gray-500 mt-1">Note: {{ $wr->admin_note }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <form action="{{ route('system.special_hire.withdrawal', $wr->id) }}" method="post" class="space-y-2">
                                        @csrf
                                        <div class="flex flex-wrap gap-2 items-center">
                                            <select name="status" required class="text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-1 focus:ring-blue-500">
                                                @if($wr->status === 'pending')
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                @else
                                                    <option value="paid">Mark paid</option>
                                                    <option value="rejected">Reject</option>
                                                @endif
                                            </select>
                                            <button type="submit" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md">Update</button>
                                        </div>
                                        <input type="text" name="admin_note" value="{{ old('admin_note') }}" maxlength="2000" placeholder="Message to operator (optional)"
                                               class="w-full max-w-xs text-sm border border-gray-300 rounded-md px-2 py-1.5">
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('system.pages.no_open_requests') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">Executed — paid or rejected</h2>
                <p class="text-sm text-gray-600">History for <strong>{{ $selectedOwner->name }}</strong>.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.date') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">{{ __('system.common.amount') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Pay to</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.status') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Processed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($withdrawalRequestsExecuted as $wr)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $wr->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ $currency }} {{ convert_money($wr->amount) }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    <span class="font-medium text-gray-800">{{ $wr->payment_method }}</span>
                                    <div class="text-xs font-mono mt-1">{{ $wr->payment_number }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="capitalize font-medium
                                        {{ $wr->status === 'paid' ? 'text-green-700' : '' }}
                                        {{ $wr->status === 'rejected' ? 'text-red-700' : '' }}">{{ $wr->status }}</span>
                                    @if($wr->admin_note)
                                        <div class="text-xs text-gray-500 mt-1">Note: {{ $wr->admin_note }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">{{ $wr->processed_at ? $wr->processed_at->format('Y-m-d H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('system.pages.no_completed_requests') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
