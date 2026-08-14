@php
    $br = booking_routes();
    $rtr = round_trip_routes();
@endphp
<section class="home-search-results home-search-results--page {{ !empty($resultsCentered) ? 'home-search-results--centered' : '' }}">
    <div class="home-search-results__inner">
        @if ($busList->isEmpty())
            <div class="home-schedules__empty fade-in">
                <div class="home-schedules__empty-icon" aria-hidden="true"><i class="fas fa-bus"></i></div>
                <h3>{{ __('all.no_buses_available') }}</h3>
                <p>{{ __('all.try_different_search_criteria') }}</p>
                @if (!empty($departureCityName) && !empty($arrivalCityName))
                    <p class="text-sm" style="color:var(--home-muted)">
                        {{ $departureCityName }} ➔ {{ $arrivalCityName }} ·
                        {{ \Carbon\Carbon::parse($departure_date)->format('D, M d, Y') }}
                    </p>
                @elseif (($resultsContext ?? '') === 'company' && !empty($departureCityName))
                    <p class="text-sm" style="color:var(--home-muted)">
                        {{ $departureCityName }} · {{ __('all.no_upcoming_departures') }}
                    </p>
                @endif
                <a href="{{ ($bookingMode ?? '') === 'round_trip' ? round_trip_route('index') : $br['back_search'] }}" class="home-schedules__empty-link">
                    {{ __('all.back_button') }} <i class="fas fa-route" aria-hidden="true"></i>
                </a>
            </div>
        @else
            <div class="home-search-results__toolbar fade-in">
                <p class="home-search-results__count">
                    <span>{{ $busList->count() }}</span>
                    @if (($resultsContext ?? '') === 'company')
                        {{ $busList->count() === 1 ? __('all.departure_for', ['city' => $departureCityName]) : __('all.departures_for', ['city' => $departureCityName]) }}
                    @else
                        {{ $busList->count() === 1 ? __('all.buses_found_one') : __('all.buses_found_many') }}
                    @endif
                </p>
                <p class="home-search-results__sort">
                    <i class="fas fa-sort-amount-down-alt mr-1" aria-hidden="true"></i>
                    @if (($resultsContext ?? '') === 'company')
                        {{ __('all.sorted_by_date_time') }}
                    @else
                        {{ __('all.sorted_by_departure_time') }}
                    @endif
                </p>
            </div>

            <div class="home-search-results__list">
                @foreach ($busList as $bus)
                    @if (($bookingMode ?? 'one_way') === 'round_trip')
                        @include('test.partials.round_trip_bus_search_row', ['bus' => $bus])
                    @else
                        @include('test.partials.bus_search_row', ['bus' => $bus])
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</section>

@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        @include('test.partials.guest_i18n')
        <script src="{{ asset('js/inline-booking.js') }}?v={{ filemtime(public_path('js/inline-booking.js')) }}"></script>
    @endpush
@endonce
