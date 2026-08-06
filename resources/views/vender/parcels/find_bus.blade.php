@extends('vender.app')

@section('title', __('vender/parcels.find_bus_for_parcel'))

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('vender/parcels.assign_parcel_to_bus') }}</h1>
            <p class="vendor-dash__subtitle">{{ __('vender/parcels.search_bus_hint_extended') }}</p>
        </div>
        <div class="vendor-dash__actions">
            <a href="{{ route('vender.parcels.index') }}" class="page-btn page-btn--outline">
                <i class="fas fa-arrow-left"></i> {{ __('all.parcels') }}
            </a>
        </div>
    </header>

    <section class="vendor-table-card">
        <div class="vendor-table-card__head">
            <div class="vendor-table-card__title-wrap">
                <h3>{{ __('vender/parcels.find_bus') }}</h3>
                <p>{{ trans_choice('vender/parcels.buses_found', $buses->count(), ['count' => $buses->count()]) }}</p>
            </div>
        </div>

        <div class="vendor-table-toolbar">
            <form action="{{ route('vender.parcels.find_bus') }}" method="GET" class="flex flex-1 flex-wrap items-end gap-2 w-full">
                <div class="vendor-search-wrap flex-1 min-w-[12rem]">
                    <i class="fas fa-search"></i>
                    <input type="text" name="query" value="{{ request('query') }}" class="page-input text-sm border-0 shadow-none bg-transparent w-full" placeholder="{{ __('vender/parcels.bus_search_placeholder') }}">
                </div>
                <div class="min-w-[10rem]">
                    <label class="block text-xs text-gray-500 mb-1">{{ __('vender/parcels.filter_from') }}</label>
                    <select name="from" class="page-input text-sm w-full">
                        <option value="">{{ __('vender/parcels.any_city') }}</option>
                        @foreach(($cities ?? []) as $city)
                            <option value="{{ $city->id }}" @selected((string) request('from') === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[10rem]">
                    <label class="block text-xs text-gray-500 mb-1">{{ __('vender/parcels.filter_to') }}</label>
                    <select name="to" class="page-input text-sm w-full">
                        <option value="">{{ __('vender/parcels.any_city') }}</option>
                        @foreach(($cities ?? []) as $city)
                            <option value="{{ $city->id }}" @selected((string) request('to') === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[10rem]">
                    <label class="block text-xs text-gray-500 mb-1">{{ __('vender/parcels.filter_date') }}</label>
                    <input type="date" name="departure_date" value="{{ request('departure_date') }}" class="page-input text-sm w-full" min="{{ date('Y-m-d') }}">
                </div>
                <button type="submit" class="page-btn">{{ __('vender/parcels.search') }}</button>
            </form>
        </div>

        @if($buses->isEmpty())
            <div class="vendor-table-empty">
                <i class="fas fa-bus"></i>
                <h4>{{ __('vender/parcels.no_buses_found') }}</h4>
                <p>{{ __('vender/parcels.try_different_bus') }}</p>
            </div>
        @else
            <div class="vendor-parcel-grid">
                @foreach ($buses as $bus)
                    <article class="vendor-parcel-bus-card">
                        <div class="vendor-parcel-bus-card__head">
                            <div>
                                <h3 class="vendor-parcel-bus-card__title">{{ $bus->bus_number }}</h3>
                                <p class="vendor-parcel-bus-card__company">{{ $bus->campany->name }}</p>
                            </div>
                            <span class="vendor-status vendor-status--other">{{ $bus->bus_type ?: __('vender/parcels.standard') }}</span>
                        </div>

                        <div class="vendor-parcel-bus-card__meta">
                            @if($bus->route || ($bus->schedule && ($bus->schedule->from || $bus->schedule->to)))
                                <span>
                                    <i class="fas fa-route"></i>
                                    {{ $bus->schedule->from ?? $bus->route->from ?? '—' }}
                                    →
                                    {{ $bus->schedule->to ?? $bus->route->to ?? '—' }}
                                </span>
                            @endif
                            @if($bus->schedule && $bus->schedule->schedule_date)
                                <span>
                                    <i class="fas fa-clock"></i>
                                    {{ \Carbon\Carbon::parse($bus->schedule->schedule_date)->format('d M Y') }}
                                    · {{ $bus->schedule->start }}
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('vender.parcels.create', $bus->id) }}" class="page-btn page-btn--outline w-full text-center">
                            {{ __('vender/parcels.select_this_bus') }} <i class="fas fa-arrow-right"></i>
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
