@extends('system.app')

@section('title', __('system.pages.special_hire_title'))

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
            <h1 class="text-2xl font-semibold text-gray-800">{{ __('system.pages.special_hire_title') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('system.pages.special_hire_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('system.special_hire.report.pdf', ['tab' => $tab]) }}" target="_blank"
               class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v2h12V3z"/></svg>
                {{ __('system.pages.export_pdf') }}
            </a>
            <a href="{{ route('system.special_hire.report.csv', ['tab' => $tab]) }}"
               class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
                {{ __('system.pages.export_csv') }}
            </a>
            @if($tab !== 'orders')
                <a href="{{ route('system.special_hire.report.pdf', ['tab' => 'orders']) }}" target="_blank"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
                    {{ __('system.pages.export_pdf') }} — {{ __('system.pages.orders') }}
                </a>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-gray-200 mb-6" role="tablist">
        <a href="{{ route('system.special_hire', ['tab' => 'accounts']) }}"
           class="px-4 py-2.5 text-sm font-medium rounded-t-lg border border-b-0 transition-colors {{ $tab === 'accounts' ? 'bg-white border-gray-200 text-blue-700 border-b-white -mb-px z-10 relative' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
            Accounts
        </a>
        <a href="{{ route('system.special_hire', ['tab' => 'withdrawals']) }}"
           class="px-4 py-2.5 text-sm font-medium rounded-t-lg border border-b-0 transition-colors inline-flex items-center gap-2 {{ $tab === 'withdrawals' ? 'bg-white border-gray-200 text-blue-700 border-b-white -mb-px z-10 relative' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
            Requested payouts
            @if($withdrawalActionNeededCount > 0)
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-100 text-amber-800 text-xs font-bold">{{ $withdrawalActionNeededCount }}</span>
            @endif
        </a>
    </div>

    @if($tab === 'accounts')
        <div class="max-w-5xl">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
                <label for="special-hire-search" class="block text-sm font-medium text-gray-700 mb-2">{{ __('system.pages.search_name_email') }}</label>
                <input type="search" id="special-hire-search" placeholder="Type to filter…"
                       class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">{{ __('system.pages.special_hire_accounts') }}</h2>
                    <p class="text-sm text-gray-500">Role <code class="text-xs bg-gray-200 px-1 rounded">special_hire</code></p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm" id="special-hire-accounts-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Account</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Email</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Contact</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Coasters</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Orders</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">{{ __('system.pages.total_amount_paid') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">{{ __('system.pages.commission_fees') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Gov. Levy (5%)</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">Platform commission %</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase text-xs">Status</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($owners as $owner)
                                <tr class="hover:bg-gray-50 special-hire-account-row" data-search="{{ strtolower($owner->name.' '.$owner->email.' '.($owner->contact ?? '').' '.($owner->phone ?? '')) }}">
                                    <td class="px-4 py-3 text-gray-900 font-medium">{{ $owner->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $owner->email }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $owner->contact ?? $owner->phone ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ number_format($owner->coasters_count) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ number_format($owner->special_hire_orders_count) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ $currency }} {{ convert_money($owner->revenue_paid_sum ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ $currency }} {{ convert_money($owner->commission_paid_sum ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ $currency }} {{ convert_money($owner->gov_levy_sum ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form action="{{ route('system.special_hire.platform_percent', $owner->id) }}" method="post" class="inline-flex flex-wrap items-center justify-end gap-2">
                                            @csrf
                                            <input type="number" step="0.01" min="0" max="100" name="special_hire_platform_percent" required
                                                aria-label="Platform commission percent for {{ $owner->name }}"
                                                value="{{ old('special_hire_platform_percent', $owner->special_hire_platform_percent ?? 0) }}"
                                                class="w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 text-right">
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-teal-600 text-white text-xs font-semibold hover:bg-teal-700 whitespace-nowrap">
                                                Save %
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <form action="{{ route('system.special_hire.toggle_status', $owner->id) }}" method="post" class="inline-flex items-center gap-2">
                                            @csrf
                                            <select name="status"
                                                    onchange="this.form.submit()"
                                                    class="rounded-md border-gray-300 text-xs shadow-sm focus:border-teal-500 focus:ring-teal-500 {{ ($owner->status ?? '') === 'accept' || $owner->status === '' ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50' }}">
                                                <option value="accept" @selected(($owner->status ?? '') === 'accept' || $owner->status === '')>Active</option>
                                                <option value="disabled" @selected(($owner->status ?? '') !== 'accept' && $owner->status !== '')>Disabled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('system.special_hire.show', $owner->id) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700">
                                            View details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="px-4 py-10 text-center text-gray-500">{{ __('system.pages.no_special_hire_accounts') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script>
        (function () {
            var input = document.getElementById('special-hire-search');
            if (!input) return;
            input.addEventListener('input', function () {
                var q = (input.value || '').toLowerCase().trim();
                document.querySelectorAll('tr.special-hire-account-row').forEach(function (row) {
                    var hay = row.getAttribute('data-search') || '';
                    row.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        })();
        </script>
    @else
        <div class="space-y-8">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-amber-100">
                <div class="px-6 py-4 border-b border-gray-200 bg-amber-50">
                    <h2 class="text-lg font-semibold text-gray-800">Open — needs action</h2>
                    <p class="text-sm text-gray-600">Pending or approved (not yet marked paid). Approve, mark paid after transfer, or reject.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.date') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Operator</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">{{ __('system.common.amount') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Pay to</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.status') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($withdrawalRequestsOpen as $wr)
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $wr->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">
                                        @if($wr->user)
                                            <a href="{{ route('system.special_hire.show', $wr->user_id) }}" class="font-medium text-blue-700 hover:text-blue-900">{{ $wr->user->name }}</a>
                                            <div class="text-xs text-gray-500">{{ $wr->user->email }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ $currency }} {{ convert_money($wr->amount) }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <span class="font-medium text-gray-800">{{ $wr->payment_method }}</span>
                                        <div class="text-xs font-mono mt-1">{{ $wr->payment_number }}</div>
                                        @if($wr->notes)
                                            <div class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($wr->notes, 80) }}</div>
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
                                            <input type="text" name="admin_note" value="{{ old('admin_note') }}" maxlength="2000" placeholder="Note (optional)"
                                                   class="w-full max-w-[14rem] text-sm border border-gray-300 rounded-md px-2 py-1.5">
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">{{ __('system.pages.no_open_payout_requests') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800">Executed — paid or rejected</h2>
                    <p class="text-sm text-gray-600">Finished requests (most recent first).</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.date') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">Operator</th>
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
                                    <td class="px-4 py-3">
                                        @if($wr->user)
                                            <a href="{{ route('system.special_hire.show', $wr->user_id) }}" class="font-medium text-blue-700 hover:text-blue-900">{{ $wr->user->name }}</a>
                                            <div class="text-xs text-gray-500">{{ $wr->user->email }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
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
                                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">{{ __('system.pages.no_executed_payouts') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
