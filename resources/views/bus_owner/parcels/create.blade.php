@extends('admin.app')

@section('title', __('vender/parcels.add_new_parcel'))

@php
    $parcelTypes = [
        'Box' => __('vender/parcels.type_box'),
        'Bag' => __('vender/parcels.type_bag'),
        'Envelope' => __('vender/parcels.type_envelope'),
        'Electronic' => __('vender/parcels.type_electronic'),
        'Other' => __('vender/parcels.type_other'),
    ];
    $routeFrom = $bus->schedule->from ?? $bus->route->from ?? null;
    $routeTo = $bus->schedule->to ?? $bus->route->to ?? null;
    $oldInstr = old('parcel_instructions', 'collection');
    // Preflight zeros border-width — need `border` + color; match vendor teal chrome
    $fieldClass = 'mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/30 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500';
    $readonlyFieldClass = 'mt-1 w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 shadow-sm';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    <div class="mb-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ __('vender/parcels.register_new_parcel') }}</h1>
            <p class="text-sm text-gray-500">{{ $bus->bus_number }} · {{ $bus->campany->name }}</p>
            @if($routeFrom || $routeTo)
                <p class="mt-1 text-sm font-medium text-teal-800">
                    {{ __('vender/parcels.origin_destination') }}:
                    <span class="font-semibold">{{ $routeFrom ?: '—' }} → {{ $routeTo ?: '—' }}</span>
                </p>
            @endif
        </div>
        <a href="{{ route('bus_owner.parcels.find_bus') }}" class="text-sm text-teal-700">{{ __('vender/parcels.back') }}</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border-l-4 border-red-500 bg-red-50 p-3 text-sm">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded border-l-4 border-red-500 bg-red-50 p-3 text-sm">{{ session('error') }}</div>
    @endif

    <form action="{{ route('bus_owner.parcels.store') }}" method="POST" class="space-y-4 rounded-xl border bg-white p-6 shadow-sm" id="busOwnerParcelForm">
        @csrf
        <input type="hidden" name="bus_id" value="{{ $bus->id }}">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.parcel_number') }}</label>
                <input type="text" name="parcel_number" value="{{ old('parcel_number', 'PCL-' . strtoupper(\Illuminate\Support\Str::random(6))) }}" required readonly class="{{ $readonlyFieldClass }}">
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.parcel_type') }}</label>
                <select name="parcel_type" required class="{{ $fieldClass }}">
                    @foreach($parcelTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('parcel_type')==$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.weight_kg') }}</label><input type="number" step="0.01" name="weight" value="{{ old('weight') }}" class="{{ $fieldClass }}"></div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('vender/parcels.sender_details') }}</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium">{{ __('vender/parcels.sender_name') }}</label><input type="text" name="sender_name" value="{{ old('sender_name') }}" required class="{{ $fieldClass }}" autocomplete="name"></div>
                <div><label class="text-sm font-medium">{{ __('vender/parcels.sender_contact') }}</label><input type="tel" name="sender_contact" value="{{ old('sender_contact') }}" required class="{{ $fieldClass }} @error('sender_contact') border-red-500 @enderror" inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.contact_phone_placeholder') }}">@error('sender_contact')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium">{{ __('vender/parcels.parcel_instructions') }}</label>
                    <select name="parcel_instructions" id="parcel_instructions" required class="{{ $fieldClass }}">
                        <option value="collection" @selected($oldInstr === 'collection')>{{ __('vender/parcels.instructions_collection') }}</option>
                        <option value="delivery" @selected($oldInstr === 'delivery')>{{ __('vender/parcels.instructions_delivery') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('vender/parcels.receiver_details') }}</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div><label class="text-sm font-medium">{{ __('vender/parcels.receiver_name') }}</label><input type="text" name="receiver_name" value="{{ old('receiver_name') }}" required class="{{ $fieldClass }}" autocomplete="name"></div>
                <div><label class="text-sm font-medium">{{ __('vender/parcels.receiver_contact_1') }}</label><input type="tel" name="receiver_contact_1" value="{{ old('receiver_contact_1') }}" required class="{{ $fieldClass }} @error('receiver_contact_1') border-red-500 @enderror" inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.contact_phone_placeholder') }}">@error('receiver_contact_1')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium" id="address_label" data-label-collection="{{ __('vender/parcels.receiver_collection_address') }}" data-label-delivery="{{ __('vender/parcels.receiver_delivery_address') }}">
                        {{ $oldInstr === 'collection' ? __('vender/parcels.receiver_collection_address') : __('vender/parcels.receiver_delivery_address') }}
                    </label>
                    <input type="text" name="receiver_delivery_address" value="{{ old('receiver_delivery_address') }}" required class="{{ $fieldClass }}">
                </div>
            </div>
        </div>

        <div id="delivery_agent_fields" class="grid md:grid-cols-2 gap-4 {{ $oldInstr === 'collection' ? 'hidden' : '' }}">
            <div class="md:col-span-2">
                <p class="text-sm font-semibold text-gray-700">{{ __('vender/parcels.destination_agent') }}</p>
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.receiving_agent_name') }}</label>
                <input type="text" name="receiving_agent_name" id="receiving_agent_name" value="{{ old('receiving_agent_name') }}" class="{{ $fieldClass }}" @disabled($oldInstr === 'collection')>
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.receiving_agent_phone') }}</label>
                <input type="text" name="receiving_agent_phone" id="receiving_agent_phone" value="{{ old('receiving_agent_phone') }}" class="{{ $fieldClass }}" @disabled($oldInstr === 'collection')>
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.delivery_rider_name') }}</label>
                <input type="text" name="delivery_rider_name" id="delivery_rider_name" value="{{ old('delivery_rider_name') }}" class="{{ $fieldClass }}" @disabled($oldInstr === 'collection')>
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.delivery_rider_phone') }}</label>
                <input type="text" name="delivery_rider_phone" id="delivery_rider_phone" value="{{ old('delivery_rider_phone') }}" class="{{ $fieldClass }}" @disabled($oldInstr === 'collection')>
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('vender/parcels.payment_details') }}</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium">{{ __('vender/parcels.amount_paid', ['currency' => $currency ?? 'TZS']) }}</label>
                    <input type="number" step="0.01" name="amount_paid" value="{{ old('amount_paid') }}" required class="{{ $fieldClass }}">
                </div>
                <div>
                    <label class="text-sm font-medium">{{ __('vender/parcels.discount_coupon') }}</label>
                    <input type="text" name="discount_code" value="{{ old('discount_code') }}" class="{{ $fieldClass }}" placeholder="CODE">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium" for="phone">{{ __('vender/parcels.clickpesa_phone') }}</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="{{ $fieldClass }} @error('phone') border-red-500 @enderror"
                        inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.clickpesa_phone_placeholder') }}">
                    <p class="mt-1 text-xs text-gray-500">{{ __('vender/parcels.clickpesa_hint') }}</p>
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-white text-sm">{{ __('vender/parcels.pay_and_register') }}</button>
    </form>
</div>

<script>
(function () {
    const select = document.getElementById('parcel_instructions');
    const agentWrap = document.getElementById('delivery_agent_fields');
    const addressLabel = document.getElementById('address_label');
    const agentInputs = [
        document.getElementById('receiving_agent_name'),
        document.getElementById('receiving_agent_phone'),
        document.getElementById('delivery_rider_name'),
        document.getElementById('delivery_rider_phone'),
    ];

    function syncParcelInstructionUi() {
        const isDelivery = select && select.value === 'delivery';
        if (agentWrap) {
            agentWrap.classList.toggle('hidden', !isDelivery);
        }
        agentInputs.forEach(function (el) {
            if (!el) return;
            el.disabled = !isDelivery;
            if (!isDelivery) {
                el.value = '';
            }
        });
        if (addressLabel) {
            addressLabel.textContent = isDelivery
                ? (addressLabel.dataset.labelDelivery || '')
                : (addressLabel.dataset.labelCollection || '');
        }
    }

    if (select) {
        select.addEventListener('change', syncParcelInstructionUi);
        syncParcelInstructionUi();
    }
})();
</script>
@endsection
