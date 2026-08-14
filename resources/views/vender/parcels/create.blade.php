@extends('vender.app')

@section('title', __('vender/parcels.add_new_parcel'))

@php
    $parcelTypes = [
        'Box' => __('vender/parcels.type_box'),
        'Bag' => __('vender/parcels.type_bag'),
        'Envelope' => __('vender/parcels.type_envelope'),
        'Electronic' => __('vender/parcels.type_electronic'),
        'Other' => __('vender/parcels.type_other'),
    ];
    $test_mode = \App\Models\Setting::isTestMode();
@endphp

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('vender/parcels.register_new_parcel') }}</h1>
            <p class="vendor-dash__subtitle">{{ $bus->bus_number }} · {{ $bus->campany->name }}</p>
            @if(($bus->route->from ?? null) || ($bus->route->to ?? null) || ($bus->schedule->from ?? null))
                <p class="text-sm font-medium text-gray-600 mt-1">
                    <i class="fas fa-route mr-1 text-teal-600"></i>
                    {{ __('vender/parcels.origin_destination') }}:
                    <span class="font-semibold">
                        {{ $bus->schedule->from ?? $bus->route->from ?? '—' }}
                        →
                        {{ $bus->schedule->to ?? $bus->route->to ?? '—' }}
                    </span>
                </p>
            @endif
        </div>
        <div class="vendor-dash__actions">
            <a href="{{ route('vender.parcels.find_bus') }}" class="page-btn page-btn--outline">
                <i class="fas fa-arrow-left"></i> {{ __('vender/parcels.back') }}
            </a>
        </div>
    </header>

    <div class="vendor-parcel-form-card">
        @if(session('error'))
            <div class="booking-alert booking-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="booking-alert booking-alert--error mb-6" role="alert">
                <ul class="list-disc list-inside text-sm mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route($storeRoute ?? 'vender.parcels.store') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="bus_id" value="{{ $bus->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="vendor-form-field">
                    <label for="parcel_number">{{ __('vender/parcels.parcel_number') }}</label>
                    <input type="text" name="parcel_number" id="parcel_number"
                        value="{{ old('parcel_number', 'PCL-' . strtoupper(Str::random(6))) }}" required readonly
                        class="page-input page-input--readonly">
                </div>
                <div class="vendor-form-field">
                    <label for="parcel_type">{{ __('vender/parcels.parcel_type') }}</label>
                    <select name="parcel_type" id="parcel_type" required class="page-input">
                        <option value="" disabled {{ old('parcel_type') ? '' : 'selected' }}>{{ __('vender/parcels.select_type') }}</option>
                        @foreach ($parcelTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('parcel_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="vendor-parcel-form-card__section">
                <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('vender/parcels.dimensions_weight') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="vendor-form-field">
                        <label for="weight">{{ __('vender/parcels.weight_kg') }}</label>
                        <input type="number" name="weight" id="weight" step="0.01" value="{{ old('weight') }}" class="page-input" placeholder="0.00">
                    </div>
                    <div class="vendor-form-field">
                        <label for="length">{{ __('vender/parcels.length_cm') }}</label>
                        <input type="number" name="length" id="length" step="0.01" value="{{ old('length') }}" class="page-input" placeholder="0.00">
                    </div>
                    <div class="vendor-form-field">
                        <label for="height">{{ __('vender/parcels.height_cm') }}</label>
                        <input type="number" name="height" id="height" step="0.01" value="{{ old('height') }}" class="page-input" placeholder="0.00">
                    </div>
                    <div class="vendor-form-field">
                        <label for="width">{{ __('vender/parcels.width_cm') }}</label>
                        <input type="number" name="width" id="width" step="0.01" value="{{ old('width') }}" class="page-input" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="vendor-parcel-form-card__section">
                <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('vender/parcels.sender_details') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="vendor-form-field">
                        <label for="sender_name">{{ __('vender/parcels.sender_name') }}</label>
                        <input type="text" name="sender_name" id="sender_name" value="{{ old('sender_name') }}" required class="page-input" autocomplete="name">
                    </div>
                    <div class="vendor-form-field">
                        <label for="sender_contact">{{ __('vender/parcels.sender_contact') }}</label>
                        <input type="tel" name="sender_contact" id="sender_contact" value="{{ old('sender_contact') }}" required class="page-input @error('sender_contact') border-red-500 @enderror"
                            inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.contact_phone_placeholder') }}">
                        @error('sender_contact')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="vendor-form-field md:col-span-2">
                        <label for="parcel_instructions">{{ __('vender/parcels.parcel_instructions') }}</label>
                        <select name="parcel_instructions" id="parcel_instructions" required class="page-input">
                            <option value="" disabled {{ old('parcel_instructions') ? '' : 'selected' }}>{{ __('vender/parcels.select_instructions') }}</option>
                            <option value="collection" {{ old('parcel_instructions') == 'collection' ? 'selected' : '' }}>{{ __('vender/parcels.instructions_collection') }}</option>
                            <option value="delivery" {{ old('parcel_instructions') == 'delivery' ? 'selected' : '' }}>{{ __('vender/parcels.instructions_delivery') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="vendor-parcel-form-card__section">
                <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('vender/parcels.receiver_details') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="vendor-form-field">
                        <label for="receiver_name">{{ __('vender/parcels.receiver_name') }}</label>
                        <input type="text" name="receiver_name" id="receiver_name" value="{{ old('receiver_name') }}" required class="page-input" autocomplete="name">
                    </div>
                    <div class="vendor-form-field">
                        <label for="receiver_contact_1">{{ __('vender/parcels.receiver_contact_1') }}</label>
                        <input type="tel" name="receiver_contact_1" id="receiver_contact_1" value="{{ old('receiver_contact_1') }}" required class="page-input @error('receiver_contact_1') border-red-500 @enderror"
                            inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.contact_phone_placeholder') }}">
                        @error('receiver_contact_1')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="vendor-form-field">
                        <label for="receiver_contact_2">{{ __('vender/parcels.receiver_contact_2') }}</label>
                        <input type="tel" name="receiver_contact_2" id="receiver_contact_2" value="{{ old('receiver_contact_2') }}" class="page-input @error('receiver_contact_2') border-red-500 @enderror"
                            inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.contact_phone_placeholder') }}">
                        @error('receiver_contact_2')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="vendor-form-field">
                        <label for="receiver_delivery_address" id="receiver_address_label">{{ __('vender/parcels.receiver_delivery_address') }}</label>
                        <input type="text" name="receiver_delivery_address" id="receiver_delivery_address" value="{{ old('receiver_delivery_address') }}" required class="page-input" autocomplete="street-address">
                    </div>
                </div>
            </div>

            <div class="vendor-parcel-form-card__section" id="receiving-agent-section">
                <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('vender/parcels.destination_agent') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="vendor-form-field">
                        <label for="receiving_agent_name">{{ __('vender/parcels.receiving_agent_name') }}</label>
                        <input type="text" name="receiving_agent_name" id="receiving_agent_name" value="{{ old('receiving_agent_name') }}" class="page-input" autocomplete="name">
                    </div>
                    <div class="vendor-form-field">
                        <label for="receiving_agent_phone">{{ __('vender/parcels.receiving_agent_phone') }}</label>
                        <input type="tel" name="receiving_agent_phone" id="receiving_agent_phone" value="{{ old('receiving_agent_phone') }}" class="page-input"
                            inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.contact_phone_placeholder') }}">
                    </div>
                    <div class="vendor-form-field">
                        <label for="delivery_rider_name">{{ __('vender/parcels.delivery_rider_name') }}</label>
                        <input type="text" name="delivery_rider_name" id="delivery_rider_name" value="{{ old('delivery_rider_name') }}" class="page-input" autocomplete="name">
                    </div>
                    <div class="vendor-form-field">
                        <label for="delivery_rider_phone">{{ __('vender/parcels.delivery_rider_phone') }}</label>
                        <input type="tel" name="delivery_rider_phone" id="delivery_rider_phone" value="{{ old('delivery_rider_phone') }}" class="page-input"
                            inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.contact_phone_placeholder') }}">
                    </div>
                </div>
            </div>

            <div class="vendor-parcel-form-card__section">
                <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('vender/parcels.payment_details') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="vendor-form-field">
                        <label for="amount_paid">{{ __('vender/parcels.amount_paid', ['currency' => $currency]) }}</label>
                        <input type="number" name="amount_paid" id="amount_paid" step="0.01" value="{{ old('amount_paid') }}" required class="page-input" placeholder="0.00">
                    </div>
                    <div class="vendor-form-field">
                        <label for="discount_code">{{ __('vender/parcels.discount_coupon') }}</label>
                        <input type="text" name="discount_code" id="discount_code" value="{{ old('discount_code') }}" class="page-input" placeholder="CODE">
                    </div>
                    @if($test_mode ?? false)
                    <div class="vendor-form-field md:col-span-2">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-100" role="status">
                            <p class="font-semibold">{{ __('vender/parcels.test_mode_notice') }}</p>
                            <p class="mt-1 text-xs">{{ __('vender/parcels.phone_not_required_test_mode') }}</p>
                        </div>
                    </div>
                    @else
                    <div class="vendor-form-field md:col-span-2">
                        <label for="phone">{{ __('vender/parcels.clickpesa_phone') }}</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" class="page-input @error('phone') border-red-500 @enderror"
                            inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.clickpesa_phone_placeholder') }}">
                        <p class="text-xs text-gray-500 mt-1">{{ __('vender/parcels.clickpesa_hint') }}</p>
                        @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    @endif
                </div>
            </div>

            <div class="vendor-form-field">
                <label for="description">{{ __('vender/parcels.description') }}</label>
                <textarea name="description" id="description" rows="3" class="page-input resize-none" placeholder="{{ __('vender/parcels.description_placeholder') }}">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <a href="{{ route(($storeRoute ?? '') === 'bus_owner.parcels.store' ? 'bus_owner.parcels.find_bus' : 'vender.parcels.find_bus') }}" class="page-btn page-btn--outline">
                    <i class="fas fa-arrow-left"></i> {{ __('vender/parcels.back') }}
                </a>
                <button type="submit" class="page-btn">
                    @if($test_mode ?? false)
                        {{ __('vender/parcels.pay_and_register_test_mode') }}
                    @else
                        {{ __('vender/parcels.pay_and_register') }} <i class="fas fa-mobile-alt"></i>
                    @endif
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var select = document.getElementById('parcel_instructions');
    var agentSection = document.getElementById('receiving-agent-section');
    var addressLabel = document.getElementById('receiver_address_label');
    var deliveryLabel = @json(__('vender/parcels.receiver_delivery_address'));
    var collectionLabel = @json(__('vender/parcels.receiver_collection_address'));
    var agentFields = ['receiving_agent_name', 'receiving_agent_phone', 'delivery_rider_name', 'delivery_rider_phone'];

    function syncInstructionsUi() {
        if (!select) return;
        var isCollection = select.value === 'collection';
        if (addressLabel) {
            addressLabel.textContent = isCollection ? collectionLabel : deliveryLabel;
        }
        if (agentSection) {
            agentSection.style.display = isCollection ? 'none' : '';
        }
        agentFields.forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.disabled = isCollection;
            if (isCollection) {
                el.value = '';
            }
        });
    }

    if (select) {
        select.addEventListener('change', syncInstructionsUi);
        syncInstructionsUi();
    }
})();
</script>
@endsection
