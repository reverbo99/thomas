@extends('admin.app')

@section('title', __('vender/parcels.find_bus_for_parcel'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('vender/parcels.assign_parcel_to_bus') }}</h1>
            <p class="text-sm text-gray-500">{{ __('vender/parcels.search_bus_hint_extended') }}</p>
        </div>
        <a href="{{ route('bus_owner.parcels.index') }}" class="rounded-lg border px-3 py-2 text-sm">{{ __('vender/parcels.back') }}</a>
    </div>

    <form method="GET" action="{{ route('bus_owner.parcels.find_bus') }}" class="mb-4 grid gap-3 md:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">{{ __('vender/parcels.search') }}</label>
            <input type="text" name="query" value="{{ request('query') }}" class="w-full rounded-lg border-gray-300" placeholder="{{ __('vender/parcels.bus_search_placeholder') }}">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('vender/parcels.filter_from') }}</label>
            <select name="from" class="w-full rounded-lg border-gray-300">
                <option value="">{{ __('vender/parcels.any_city') }}</option>
                @foreach(($cities ?? []) as $city)
                    <option value="{{ $city->id }}" @selected((string) request('from') === (string) $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('vender/parcels.filter_to') }}</label>
            <select name="to" class="w-full rounded-lg border-gray-300">
                <option value="">{{ __('vender/parcels.any_city') }}</option>
                @foreach(($cities ?? []) as $city)
                    <option value="{{ $city->id }}" @selected((string) request('to') === (string) $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('vender/parcels.filter_date') }}</label>
            <input type="date" name="departure_date" value="{{ request('departure_date') }}" class="w-full rounded-lg border-gray-300" min="{{ date('Y-m-d') }}">
        </div>
        <div class="md:col-span-2 lg:col-span-5">
            <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">{{ __('vender/parcels.search') }}</button>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse($buses as $bus)
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <h3 class="font-bold">{{ $bus->bus_number }}</h3>
                <p class="text-sm text-gray-500">{{ $bus->campany->name ?? '' }}</p>
                @if($bus->route || ($bus->schedule && ($bus->schedule->from || $bus->schedule->to)))
                    <p class="text-sm text-teal-800 mt-1 font-medium">
                        {{ $bus->schedule->from ?? $bus->route->from ?? '—' }}
                        →
                        {{ $bus->schedule->to ?? $bus->route->to ?? '—' }}
                    </p>
                @endif
                @if($bus->schedule && $bus->schedule->schedule_date)
                    <p class="text-xs text-gray-500 mt-1">
                        {{ \Carbon\Carbon::parse($bus->schedule->schedule_date)->format('d M Y') }}
                        · {{ $bus->schedule->start }}
                    </p>
                @endif
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
