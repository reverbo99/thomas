@php
    $activeSearchTab = $activeSearchTab ?? 'one-way';
    $selectedCompanyId = request('bus_id');
    $selectedDepartureDate = request('departure_date', now()->toDateString());
    $br = booking_routes();
    $rtr = round_trip_routes();
@endphp

<div id="search" class="home-search fade-in">
    <div class="home-search__tabs">
        <button type="button" class="home-search__tab search-tab {{ $activeSearchTab === 'one-way' ? 'home-search__tab--active' : '' }}" data-tab="one-way" aria-selected="{{ $activeSearchTab === 'one-way' ? 'true' : 'false' }}">{{ __('all.one_way') }}</button>
        <button type="button" class="home-search__tab search-tab {{ $activeSearchTab === 'round-trip' ? 'home-search__tab--active' : '' }}" data-tab="round-trip" aria-selected="{{ $activeSearchTab === 'round-trip' ? 'true' : 'false' }}">{{ __('all.round_trip') }}</button>
        <button type="button" class="home-search__tab search-tab {{ $activeSearchTab === 'bus-name' ? 'home-search__tab--active' : '' }}" data-tab="bus-name" aria-selected="{{ $activeSearchTab === 'bus-name' ? 'true' : 'false' }}">{{ __('all.bus_name') }}</button>
    </div>

    <!-- One Way -->
    <form action="{{ $br['search_form'] }}" method="{{ $br['search_method'] }}" class="search-form home-search__form {{ $activeSearchTab === 'one-way' ? '' : 'hidden' }}" id="one-way-form">
        @csrf
        <div class="home-search__fields">
            <div class="home-search__field home-search__field--from">
                <label class="home-search__label" for="departure_city">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                    <span class="home-search__label-text">{{ __('all.from') }}</span>
                </label>
                <div class="home-search__control">
                    <select name="departure_city" id="departure_city" class="home-search__input" required>
                        <option value="" disabled {{ old('departure_city', request('departure_city')) ? '' : 'selected' }}>{{ __('all.from') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" {{ (string) old('departure_city', request('departure_city')) === (string) $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="home-search__field home-search__field--to">
                <label class="home-search__label" for="arrival_city">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                    <span class="home-search__label-text">{{ __('all.to') }}</span>
                </label>
                <div class="home-search__control">
                    <select name="arrival_city" id="arrival_city" class="home-search__input" required>
                        <option value="" disabled {{ old('arrival_city', request('arrival_city')) ? '' : 'selected' }}>{{ __('all.to') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" {{ (string) old('arrival_city', request('arrival_city')) === (string) $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="home-search__field home-search__field--date">
                <label class="home-search__label" for="departure_date">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-calendar"></i></span>
                    <span class="home-search__label-text">{{ __('all.date') }}</span>
                </label>
                <div class="home-search__control">
                    <input type="date" name="departure_date" id="departure_date" value="{{ old('departure_date', request('departure_date')) }}" class="home-search__input">
                </div>
            </div>

            <div class="home-search__field home-search__field--class">
                <label class="home-search__label" for="bus_class">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-bus"></i></span>
                    <span class="home-search__label-text">{{ __('customer/busroot.bus_class') }}</span>
                </label>
                <div class="home-search__control">
                    <select name="bus_class" id="bus_class" class="home-search__input">
                        <option value="any">{{ __('customer/busroot.any') }}</option>
                        <option value="10" {{ old('bus_class', request('bus_class')) == '10' ? 'selected' : '' }}>{{ __('all.luxury') }}</option>
                        <option value="20" {{ old('bus_class', request('bus_class')) == '20' ? 'selected' : '' }}>{{ __('all.upper_semiluxury') }}</option>
                        <option value="30" {{ old('bus_class', request('bus_class')) == '30' ? 'selected' : '' }}>{{ __('all.lower_semiluxury') }}</option>
                        <option value="40" {{ old('bus_class', request('bus_class')) == '40' ? 'selected' : '' }}>{{ __('all.ordinary') }}</option>
                    </select>
                </div>
            </div>

            <div class="home-search__field home-search__field--submit">
                <button type="submit" class="home-search__cta">{{ __('all.find_buses') }}</button>
            </div>
        </div>
    </form>

    <!-- Round Trip -->
    <form action="{{ route($rtr['by_routesearch']) }}" method="GET" class="search-form home-search__form {{ $activeSearchTab === 'round-trip' ? '' : 'hidden' }}" id="round-trip-form">
        <div class="home-search__fields">
            <div class="home-search__field home-search__field--from">
                <label class="home-search__label" for="rt_departure_city">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                    <span class="home-search__label-text">{{ __('all.from') }}</span>
                </label>
                <div class="home-search__control">
                    <select name="departure_city" id="rt_departure_city" class="home-search__input" required>
                        <option value="" disabled {{ (request('departure_city') ?? old('departure_city')) ? '' : 'selected' }}>{{ __('all.from') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" {{ (string) (request('departure_city') ?? old('departure_city')) === (string) $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="home-search__field home-search__field--to">
                <label class="home-search__label" for="rt_arrival_city">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                    <span class="home-search__label-text">{{ __('all.to') }}</span>
                </label>
                <div class="home-search__control">
                    <select name="arrival_city" id="rt_arrival_city" class="home-search__input" required>
                        <option value="" disabled {{ (request('arrival_city') ?? old('arrival_city')) ? '' : 'selected' }}>{{ __('all.to') }}</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" {{ (string) (request('arrival_city') ?? old('arrival_city')) === (string) $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="home-search__field home-search__field--date">
                <label class="home-search__label" for="rt_departure_date">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-calendar"></i></span>
                    <span class="home-search__label-text">{{ __('all.date') }}</span>
                </label>
                <div class="home-search__control">
                    <input type="date" name="departure_date" id="rt_departure_date" value="{{ request('departure_date', old('departure_date')) }}" class="home-search__input">
                </div>
            </div>

            <div class="home-search__field home-search__field--class">
                <label class="home-search__label" for="rt_bus_class">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-bus"></i></span>
                    <span class="home-search__label-text">{{ __('customer/busroot.bus_class') }}</span>
                </label>
                <div class="home-search__control">
                    <select name="bus_class" id="rt_bus_class" class="home-search__input">
                        <option value="any">{{ __('customer/busroot.any') }}</option>
                        <option value="10" {{ (request('bus_class') ?? request('bus_type')) == '10' ? 'selected' : '' }}>{{ __('all.luxury') }}</option>
                        <option value="20" {{ (request('bus_class') ?? request('bus_type')) == '20' ? 'selected' : '' }}>{{ __('all.upper_semiluxury') }}</option>
                        <option value="30" {{ (request('bus_class') ?? request('bus_type')) == '30' ? 'selected' : '' }}>{{ __('all.lower_semiluxury') }}</option>
                        <option value="40" {{ (request('bus_class') ?? request('bus_type')) == '40' ? 'selected' : '' }}>{{ __('all.ordinary') }}</option>
                    </select>
                </div>
            </div>

            <div class="home-search__field home-search__field--submit">
                <button type="submit" class="home-search__cta">{{ __('all.find_buses') }}</button>
            </div>
        </div>
    </form>

    <!-- Bus Name -->
    <form action="{{ route($br['busname']) }}" method="get" class="search-form home-search__form {{ $activeSearchTab === 'bus-name' ? '' : 'hidden' }}" id="bus-name-form">
        <div class="home-search__fields home-search__fields--bus-name">
            <div class="home-search__field home-search__field--bus">
                <label class="home-search__label" for="bus_departure_date">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-bus"></i></span>
                    <span class="home-search__label-text">{{ __('all.bus_name') }}</span>
                </label>
                <div class="home-search__control">
                    <select name="bus_id" id="bus_departure_date" class="home-search__input" required>
                        <option value="" disabled {{ $selectedCompanyId ? '' : 'selected' }}>{{ __('all.bus_name') }}</option>
                        @forelse (App\Models\Campany::all() as $bus)
                            <option value="{{ $bus->id }}" {{ (string) $selectedCompanyId === (string) $bus->id ? 'selected' : '' }}>{{ $bus->name ?? 'N/A' }}</option>
                        @empty
                            <option value="">{{ __('all.no_companies_found') }}</option>
                        @endforelse
                    </select>
                </div>
            </div>
            <div class="home-search__field home-search__field--bus-date">
                <label class="home-search__label" for="bus_company_departure_date">
                    <span class="home-search__label-icon" aria-hidden="true"><i class="fas fa-calendar"></i></span>
                    <span class="home-search__label-text">{{ __('all.date') }}</span>
                </label>
                <div class="home-search__control">
                    <input type="date" name="departure_date" id="bus_company_departure_date" value="{{ $selectedDepartureDate }}" class="home-search__input">
                </div>
            </div>
            <div class="home-search__field home-search__field--bus-submit">
                <button type="submit" class="home-search__cta">{{ __('all.find_buses') }}</button>
            </div>
        </div>
    </form>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/home-search.js') }}?v=3"></script>
    @endpush
@endonce
