@extends('admin.app')

@section('title', __('vender/parcels.find_bus_for_parcel'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('vender/parcels.assign_parcel_to_bus') }}</h1>
            <p class="text-sm text-gray-500">{{ __('vender/parcels.search_bus_hint') }}</p>
        </div>
        <a href="{{ route('bus_owner.parcels.index') }}" class="rounded-lg border px-3 py-2 text-sm">{{ __('vender/parcels.back') }}</a>
    </div>

    <form method="GET" action="{{ route('bus_owner.parcels.find_bus') }}" class="mb-4 flex gap-2">
        <input type="text" name="query" value="{{ request('query') }}" class="flex-1 rounded-lg border-gray-300" placeholder="{{ __('vender/parcels.bus_search_placeholder') }}">
        <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">{{ __('vender/parcels.search') }}</button>
    </form>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($buses as $bus)
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <h3 class="font-bold">{{ $bus->bus_number }}</h3>
                <p class="text-sm text-gray-500">{{ $bus->campany->name ?? '' }}</p>
                @if($bus->max_parcel_weight_kg)
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('vender/parcels.capacity') }}:
                        {{ number_format($bus->parcel_weight_used ?? 0, 1) }} / {{ number_format($bus->max_parcel_weight_kg, 1) }} kg
                    </p>
                @endif
                <a href="{{ route('bus_owner.parcels.create', $bus->id) }}" class="mt-3 inline-block rounded-lg bg-teal-600 px-3 py-2 text-sm text-white">
                    {{ __('vender/parcels.select_this_bus') }}
                </a>
            </div>
        @empty
            <p class="text-gray-500">{{ __('vender/parcels.no_buses_found') }}</p>
        @endforelse
    </div>
</div>
@endsection
