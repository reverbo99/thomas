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
@endphp

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('vender/parcels.register_new_parcel') }}</h1>
            <p class="vendor-dash__subtitle">{{ $bus->bus_number }} · {{ $bus->campany->name }}</p>
        </div>
        <div class="vendor-dash__actions">
            <a href="{{ route('vender.parcels.find_bus') }}" class="page-btn page-btn--outline">
                <i class="fas fa-arrow-left"></i> {{ __('vender/parcels.back') }}
            </a>
        </div>
    </header>

    <div class="vendor-parcel-form-card">
        @if ($errors->any())
            <div class="booking-alert booking-alert--error mb-6" role="alert">
                <ul class="list-disc list-inside text-sm mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('vender.parcels.store') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="bus_id" value="{{ $bus->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="vendor-form-field">
                    <label for="parcel_number">{{ __('vender/parcels.parcel_number') }}</label>
                    <input type="text" name="parcel_number" id="parcel_number"
                        value="{{ old('parcel_number', 'PCL-' . strtoupper(Str::random(6))) }}" required readonly
                        class="page-input bg-gray-50">
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
                        <input type="text" name="sender_name" id="sender_name" value="{{ old('sender_name') }}" required class="page-input">
                    </div>
                    <div class="vendor-form-field">
                        <label for="sender_contact">{{ __('vender/parcels.sender_contact') }}</label>
                        <input type="text" name="sender_contact" id="sender_contact" value="{{ old('sender_contact') }}" required class="page-input">
                    </div>
                    <div class="vendor-form-field">
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
                        <input type="text" name="receiver_name" id="receiver_name" value="{{ old('receiver_name') }}" required class="page-input">
                    </div>
                    <div class="vendor-form-field">
                        <label for="receiver_contact_1">{{ __('vender/parcels.receiver_contact_1') }}</label>
                        <input type="text" name="receiver_contact_1" id="receiver_contact_1" value="{{ old('receiver_contact_1') }}" required class="page-input">
                    </div>
                    <div class="vendor-form-field">
                        <label for="receiver_contact_2">{{ __('vender/parcels.receiver_contact_2') }}</label>
                        <input type="text" name="receiver_contact_2" id="receiver_contact_2" value="{{ old('receiver_contact_2') }}" class="page-input">
                    </div>
                    <div class="vendor-form-field">
                        <label for="receiver_delivery_address">{{ __('vender/parcels.receiver_delivery_address') }}</label>
                        <input type="text" name="receiver_delivery_address" id="receiver_delivery_address" value="{{ old('receiver_delivery_address') }}" required class="page-input">
                    </div>
                </div>
            </div>

            <div class="vendor-form-field">
                <label for="amount_paid">{{ __('vender/parcels.amount_paid', ['currency' => $currency]) }}</label>
                <input type="number" name="amount_paid" id="amount_paid" step="0.01" value="{{ old('amount_paid') }}" required class="page-input" placeholder="0.00">
            </div>

            <div class="vendor-form-field">
                <label for="description">{{ __('vender/parcels.description') }}</label>
                <textarea name="description" id="description" rows="3" class="page-input resize-none" placeholder="{{ __('vender/parcels.description_placeholder') }}">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <a href="{{ route('vender.parcels.find_bus') }}" class="page-btn page-btn--outline">
                    <i class="fas fa-arrow-left"></i> {{ __('vender/parcels.back') }}
                </a>
                <button type="submit" class="page-btn">
                    {{ __('vender/parcels.save_parcel') }} <i class="fas fa-check"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
