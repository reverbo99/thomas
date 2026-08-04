@extends('system.app')
@section('title', __('vender/parcels.admin_tracking'))
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ __('vender/parcels.admin_tracking') }}</h1>
            <p class="text-sm text-gray-500">{{ __('vender/parcels.manifest_subtitle') }}</p>
        </div>
        <a href="{{ route('system.parcels.manifest') }}" class="rounded-lg bg-teal-600 px-3 py-2 text-sm text-white">{{ __('vender/parcels.print_manifest') }}</a>
    </div>
    <form method="GET" class="mb-4 flex gap-2">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="rounded-lg border-gray-300 text-sm" placeholder="{{ __('vender/parcels.search_placeholder') }}">
        <select name="status" class="rounded-lg border-gray-300 text-sm">
            <option value="">{{ __('vender/parcels.all_statuses') }}</option>
            @foreach(['awaiting_payment','registered','in_transit','arrived','completed','cancelled'] as $st)
                <option value="{{ $st }}" @selected(($filters['status'] ?? '')===$st)>{{ $flow->statusLabel($st) }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-gray-800 px-3 py-2 text-sm text-white">Filter</button>
    </form>
    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50"><tr>
                <th class="px-3 py-2 text-left">Tracking</th>
                <th class="px-3 py-2 text-left">Company</th>
                <th class="px-3 py-2 text-left">Bus</th>
                <th class="px-3 py-2 text-left">Amount</th>
                <th class="px-3 py-2 text-left">Pay</th>
                <th class="px-3 py-2 text-left">Status</th>
            </tr></thead>
            <tbody>
                @forelse($parcels as $p)
                    <tr class="border-t">
                        <td class="px-3 py-2 font-medium">{{ $p->parcel_number }}</td>
                        <td class="px-3 py-2">{{ $p->bus->campany->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $p->bus->bus_number ?? '—' }}</td>
                        <td class="px-3 py-2">{{ number_format($p->amount_paid, 0) }}</td>
                        <td class="px-3 py-2">{{ $p->payment_status ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $flow->statusLabel($flow->normalizeStatus($p)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">{{ __('vender/parcels.no_parcels_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $parcels->links() }}</div>
</div>
@endsection
