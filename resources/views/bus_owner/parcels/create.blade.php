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
    $storeRoute = 'bus_owner.parcels.store';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6 max-w-3xl">
    <div class="mb-4 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ __('vender/parcels.register_new_parcel') }}</h1>
            <p class="text-sm text-gray-500">{{ $bus->bus_number }} · {{ $bus->campany->name }}</p>
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

    <form action="{{ route('bus_owner.parcels.store') }}" method="POST" class="space-y-4 rounded-xl border bg-white p-6 shadow-sm">
        @csrf
        <input type="hidden" name="bus_id" value="{{ $bus->id }}">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.parcel_number') }}</label>
                <input type="text" name="parcel_number" value="{{ old('parcel_number', 'PCL-' . strtoupper(\Illuminate\Support\Str::random(6))) }}" required readonly class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50">
            </div>
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.parcel_type') }}</label>
                <select name="parcel_type" required class="mt-1 w-full rounded-lg border-gray-300">
                    @foreach($parcelTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('parcel_type')==$value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.weight_kg') }}</label><input type="number" step="0.01" name="weight" value="{{ old('weight') }}" class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.amount_paid', ['currency' => $currency ?? 'TZS']) }}</label><input type="number" step="0.01" name="amount_paid" value="{{ old('amount_paid') }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.sender_name') }}</label><input type="text" name="sender_name" value="{{ old('sender_name') }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.sender_contact') }}</label><input type="text" name="sender_contact" value="{{ old('sender_contact') }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div>
                <label class="text-sm font-medium">{{ __('vender/parcels.parcel_instructions') }}</label>
                <select name="parcel_instructions" required class="mt-1 w-full rounded-lg border-gray-300">
                    <option value="collection">{{ __('vender/parcels.instructions_collection') }}</option>
                    <option value="delivery">{{ __('vender/parcels.instructions_delivery') }}</option>
                </select>
            </div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.clickpesa_phone') }}</label><input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.receiver_name') }}</label><input type="text" name="receiver_name" value="{{ old('receiver_name') }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.receiver_contact_1') }}</label><input type="text" name="receiver_contact_1" value="{{ old('receiver_contact_1') }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div class="md:col-span-2"><label class="text-sm font-medium">{{ __('vender/parcels.receiver_delivery_address') }}</label><input type="text" name="receiver_delivery_address" value="{{ old('receiver_delivery_address') }}" required class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.receiving_agent_name') }}</label><input type="text" name="receiving_agent_name" value="{{ old('receiving_agent_name') }}" class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.receiving_agent_phone') }}</label><input type="text" name="receiving_agent_phone" value="{{ old('receiving_agent_phone') }}" class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.delivery_rider_name') }}</label><input type="text" name="delivery_rider_name" value="{{ old('delivery_rider_name') }}" class="mt-1 w-full rounded-lg border-gray-300"></div>
            <div><label class="text-sm font-medium">{{ __('vender/parcels.delivery_rider_phone') }}</label><input type="text" name="delivery_rider_phone" value="{{ old('delivery_rider_phone') }}" class="mt-1 w-full rounded-lg border-gray-300"></div>
        </div>
        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-white text-sm">{{ __('vender/parcels.pay_and_register') }}</button>
    </form>
</div>
@endsection
