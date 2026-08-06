@php
    $status = $status ?? $flow->normalizeStatus($parcel);
    $showPrefix = request()->routeIs('bus_owner.*') ? 'bus_owner.parcels' : 'vender.parcels';
    $currency = $currency ?? session('currency', 'TZS');
    $canPrint = $flow->canPrintReceipt($parcel);
    $isCollection = ($parcel->parcel_instructions ?? '') === 'collection';
    $routeFrom = $parcel->bus->route->from ?? $parcel->bus->schedule->from ?? null;
    $routeTo = $parcel->bus->route->to ?? $parcel->bus->schedule->to ?? null;
    $addressLabel = $isCollection
        ? __('vender/parcels.receiver_collection_address')
        : __('vender/parcels.receiver_delivery_address');
@endphp

@if(session('success'))
    <div class="mb-4 rounded-lg border-l-4 border-green-500 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ $parcel->parcel_number }}</h1>
        <p class="text-sm text-gray-500">{{ $parcel->bus->bus_number ?? '—' }} · {{ $parcel->bus->campany->name ?? '' }}</p>
        @if($routeFrom || $routeTo)
            <p class="mt-1 text-sm font-medium text-gray-700">
                <i class="fas fa-route mr-1 text-teal-600"></i>
                {{ __('vender/parcels.origin_destination') }}:
                <span class="font-semibold">{{ $routeFrom ?: '—' }} → {{ $routeTo ?: '—' }}</span>
            </p>
        @endif
        <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $flow->statusLabel($status) }}</span>
        <span class="ml-2 text-sm text-gray-600">{{ $currency }} {{ convert_money($parcel->amount_paid) }} · {{ $parcel->payment_status ?? '—' }}</span>
    </div>
    <div class="flex flex-wrap gap-2 items-center">
        <a href="{{ route($showPrefix.'.index') }}" class="rounded-lg border px-3 py-2 text-sm">{{ __('vender/parcels.back') }}</a>
        @if($canPrint)
            <a href="{{ route($showPrefix.'.print', $parcel->id) }}" target="_blank" class="rounded-lg bg-teal-600 px-3 py-2 text-sm text-white">{{ __('vender/parcels.print_receipt') }}</a>
        @else
            <span class="inline-flex flex-col items-end">
                <button type="button" disabled class="rounded-lg bg-gray-300 px-3 py-2 text-sm text-gray-600 cursor-not-allowed" title="{{ __('vender/parcels.print_payment_required') }}">
                    {{ __('vender/parcels.print_receipt') }}
                </button>
                <span class="mt-1 text-xs text-red-600">{{ __('vender/parcels.print_payment_required') }}</span>
            </span>
        @endif
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border bg-white p-5 shadow-sm space-y-2 text-sm">
        @if($routeFrom || $routeTo)
            <p><strong>{{ __('vender/parcels.origin_destination') }}:</strong> {{ $routeFrom ?: '—' }} → {{ $routeTo ?: '—' }}</p>
        @endif
        <p><strong>{{ __('vender/parcels.sender_name') }}:</strong> {{ $parcel->sender_name }} ({{ $parcel->sender_contact }})</p>
        <p><strong>{{ __('vender/parcels.receiver_name') }}:</strong> {{ $parcel->receiver_name }} ({{ $parcel->receiver_contact_1 }})</p>
        <p><strong>{{ $addressLabel }}:</strong> {{ $parcel->receiver_delivery_address }}</p>
        <p><strong>{{ __('vender/parcels.parcel_instructions') }}:</strong>
            {{ $isCollection ? __('vender/parcels.instructions_collection') : __('vender/parcels.instructions_delivery') }}
        </p>
        <p><strong>{{ __('vender/parcels.weight_kg') }}:</strong> {{ $parcel->weight ?? '—' }}</p>
        @if(!$isCollection)
            <p><strong>{{ __('vender/parcels.receiving_agent_name') }}:</strong> {{ $parcel->receiving_agent_name ?? '—' }} {{ $parcel->receiving_agent_phone }}</p>
        @endif
    </div>

    <div class="space-y-4">
        @if(($parcel->payment_status ?? '') !== 'paid')
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">{{ __('vender/parcels.pay_clickpesa') }}</h2>
            <form method="POST" action="{{ route($showPrefix.'.pay', $parcel->id) }}" class="space-y-3">
                @csrf
                <input type="text" name="phone" value="{{ old('phone', $parcel->sender_contact) }}" class="w-full rounded-lg border-gray-300" required>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">{{ __('vender/parcels.pay_and_register') }}</button>
            </form>
        </div>
        @endif

        @if(!$isCollection)
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">{{ __('vender/parcels.assign_receiving') }}</h2>
            <form method="POST" action="{{ route($showPrefix.'.assign', $parcel->id) }}" class="space-y-2">
                @csrf
                <input type="text" name="receiving_agent_name" value="{{ old('receiving_agent_name', $parcel->receiving_agent_name) }}" placeholder="{{ __('vender/parcels.receiving_agent_name') }}" class="w-full rounded-lg border-gray-300 text-sm">
                <input type="text" name="receiving_agent_phone" value="{{ old('receiving_agent_phone', $parcel->receiving_agent_phone) }}" placeholder="{{ __('vender/parcels.receiving_agent_phone') }}" class="w-full rounded-lg border-gray-300 text-sm">
                <input type="text" name="delivery_rider_name" value="{{ old('delivery_rider_name', $parcel->delivery_rider_name) }}" placeholder="{{ __('vender/parcels.delivery_rider_name') }}" class="w-full rounded-lg border-gray-300 text-sm">
                <input type="text" name="delivery_rider_phone" value="{{ old('delivery_rider_phone', $parcel->delivery_rider_phone) }}" placeholder="{{ __('vender/parcels.delivery_rider_phone') }}" class="w-full rounded-lg border-gray-300 text-sm">
                <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">{{ __('vender/parcels.save_assignment') }}</button>
            </form>
        </div>
        @endif

        <div class="rounded-xl border bg-white p-5 shadow-sm flex flex-wrap gap-2">
            <form method="POST" action="{{ route($showPrefix.'.depart', $parcel->id) }}">@csrf
                <button class="rounded-lg bg-blue-600 px-3 py-2 text-sm text-white" @if(!in_array($status, ['registered','pending'], true)) disabled @endif>{{ __('vender/parcels.mark_departed') }}</button>
            </form>
            <form method="POST" action="{{ route($showPrefix.'.arrive', $parcel->id) }}">@csrf
                <button class="rounded-lg bg-purple-600 px-3 py-2 text-sm text-white" @if($status !== 'in_transit') disabled @endif>{{ __('vender/parcels.mark_arrived') }}</button>
            </form>
        </div>

        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">{{ __('vender/parcels.collect_verify') }}</h2>
            <p class="text-xs text-gray-500 mb-2">{{ __('vender/parcels.collect_hint') }}</p>
            <form method="POST" action="{{ route($showPrefix.'.collect', $parcel->id) }}" class="flex gap-2">
                @csrf
                <input type="text" name="tracking_number" required placeholder="{{ __('vender/parcels.parcel_number') }}" class="flex-1 rounded-lg border-gray-300 text-sm">
                <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white">{{ __('vender/parcels.mark_collected') }}</button>
            </form>
        </div>
    </div>
</div>
