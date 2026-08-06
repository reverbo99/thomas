@php $currency = $currency ?? session('currency', 'TZS'); @endphp
<form method="GET" action="{{ url()->current() }}" class="mb-4 flex flex-col gap-3 md:flex-row md:items-end">
    <div class="flex-1">
        <label class="block text-xs font-medium text-gray-600">{{ __('vender/luggage.search') }}</label>
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm" placeholder="{{ __('vender/luggage.ticket_placeholder') }}">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600">{{ __('vender/luggage.status') }}</label>
        <select name="status" class="mt-1 rounded-lg border-gray-300 shadow-sm">
            <option value="">{{ __('vender/luggage.all_statuses') }}</option>
            @foreach (['declared','awaiting_payment','ready','assigned','retrieved'] as $st)
                <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ $luggageService->statusLabel($st) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white">{{ __('vender/luggage.filter') }}</button>
</form>

<div class="overflow-x-auto rounded-xl border border-gray-100 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('vender/luggage.ticket') }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('vender/luggage.passenger') }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('vender/luggage.route') }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('vender/luggage.bus') }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('vender/luggage.fee') }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('vender/luggage.status') }}</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($bookings as $b)
                @php
                    $st = $luggageService->normalizeStatus($b);
                    $from = $b->route->from ?? $b->bus->route->from ?? null;
                    $to = $b->route->to ?? $b->bus->route->to ?? null;
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $b->booking_code }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $b->customer_name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $from ?: '—' }} → {{ $to ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $b->bus->bus_number ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-900">{{ $currency }} {{ convert_money($b->excess_luggage_fee ?? 0) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ $luggageService->statusLabel($st) }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if(!empty($ctx['show_route']))
                            <a href="{{ route($ctx['show_route'], $b->id) }}" class="text-teal-700 hover:underline">{{ __('vender/luggage.open') }}</a>
                        @else
                            <span class="text-gray-400">{{ $b->bus->campany->name ?? ($b->campany->name ?? '') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('vender/luggage.none_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $bookings->links() }}</div>
