@extends('customer.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<section class="bg-gray-100 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl shadow-md p-6">
                <h5 class="text-xl font-bold text-gray-800 text-center mb-6">{{ __('customer/busroot.select_your_journey_points') }}</h5>

                <form id="busSearchForm" method="POST" action="{{ route(round_trip_routes()['store']) }}">
                    @csrf

                    <!-- Bus Operator -->
                    <div class="mb-4">
                        <label for="busOperator" class="block text-sm text-gray-500 mb-1">
                            <i class="fas fa-building mr-1"></i> {{ __('customer/busroot.bus_operator') }}
                        </label>
                        <input type="text" name="bus_name" value="{{ $car->busname->name }}" readonly
                               class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <input type="hidden" name="bus_id" value="{{ $car->id }}">
                    <input type="hidden" name="route_id" value="{{ $car->schedule->route_id }}">

                    <!-- Route Selection -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- From -->
                        <div>
                            <label for="routeFrom" class="block text-sm text-gray-500 mb-1">
                                <i class="fas fa-map-marker-alt mr-1"></i> {{ __('customer/busroot.from') }}
                            </label>
                            <input type="text" id="routeFrom" name="from" value="{{ $car->schedule->from }}" readonly
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <!-- To -->
                        <div>
                            <label for="routeTo" class="block text-sm text-gray-500 mb-1">
                                <i class="fas fa-map-marker-check mr-1"></i> {{ __('customer/busroot.to') }}
                            </label>
                            <input type="text" id="routeTo" name="to" value="{{ $car->schedule->to }}" readonly
                                   class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Points -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- Pickup Point -->
                        <div>
                            <label for="pickupPoint" class="block text-sm text-gray-500 mb-1">
                                <i class="fas fa-signpost mr-1"></i> {{ __('customer/busroot.pickup_point') }}
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    id="pickupPoint" name="pickup_point">
                                <option value="">{{ __('customer/busroot.select_pickup_point') }}</option>
                                @if(isset($car->filtered_points))
                                    @foreach($car->filtered_points as $value)
                                        @if($value->point_mode == 1)
                                            <option value="{{ $value->point }}" {{ request('pickup_point_id') == $value->point ? 'selected' : '' }}>
                                                {{ $value->point }}
                                            </option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Dropoff Point -->
                        <div>
                            <label for="dropoffPoint" class="block text-sm text-gray-500 mb-1">
                                <i class="fas fa-signpost mr-1"></i> {{ __('customer/busroot.dropoff_point') }}
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    id="dropoffPoint" name="dropping_point">
                                <option value="">{{ __('customer/busroot.select_dropping_point') }}</option>
                                @if(isset($car->filtered_points))
                                    @foreach($car->filtered_points as $value)
                                        @if($value->point_mode == 2)
                                            <option value="{{ $value->point }}" data-amount="{{ $value->amount }}"
                                                    {{ request('dropping_point_id') == $value->point ? 'selected' : '' }}>
                                                {{ $value->point }}
                                            </option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <!-- Distance Display -->
                    <div class="mb-4">
                        <label for="routeDistanceDisplay" class="block text-sm text-gray-500 mb-1">
                            <i class="fas fa-ruler mr-1"></i> {{ __('customer/busroot.route_distance') }} (km)
                        </label>
                        <input type="text" id="routeDistanceDisplay" readonly
                               class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-800 focus:outline-none"
                               placeholder="{{ __('customer/busroot.distance_will_be_calculated') }}">
                    </div>

                    <!-- Map Section -->
                    <div class="mt-4">
                        <div class="mb-3">
                            <label for="start" class="block text-sm text-gray-500 mb-1">
                                <i class="fas fa-map-marker-alt mr-1"></i> {{ __('customer/busroot.pickup_location') }}
                            </label>
                            <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-yellow-50"
                                   id="start" placeholder="{{ __('customer/busroot.search_pickup_location') }}" value="{{ $car->schedule->from }}">
                        </div>
                        <div class="mb-3">
                            <label for="end" class="block text-sm text-gray-500 mb-1">
                                <i class="fas fa-map-marker-check mr-1"></i> {{ __('customer/busroot.dropping_location') }}
                            </label>
                            <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-yellow-50"
                                   id="end" placeholder="{{ __('customer/busroot.search_dropping_location') }}" value="{{ $car->schedule->to }}">
                        </div>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="font-medium text-gray-600">{{ __('customer/busroot.quick_locations') }}</span>
                            <span class="point-btn px-3 py-1 bg-indigo-500 text-white rounded-md text-xs hover:bg-indigo-600 cursor-pointer transition"
                                  data-point="Nairobi, Kenya">{{ __('customer/busroot.nairobi') }}</span>
                            <span class="point-btn px-3 py-1 bg-indigo-500 text-white rounded-md text-xs hover:bg-indigo-600 cursor-pointer transition"
                                  data-point="Mombasa, Kenya">{{ __('customer/busroot.mombasa') }}</span>
                            <span class="point-btn px-3 py-1 bg-indigo-500 text-white rounded-md text-xs hover:bg-indigo-600 cursor-pointer transition"
                                  data-point="Kisumu, Kenya">{{ __('customer/busroot.kisumu') }}</span>
                            <span class="point-btn px-3 py-1 bg-indigo-500 text-white rounded-md text-xs hover:bg-indigo-600 cursor-pointer transition"
                                  data-point="Nakuru, Kenya">{{ __('customer/busroot.nakuru') }}</span>
                            <span class="point-btn px-3 py-1 bg-indigo-500 text-white rounded-md text-xs hover:bg-indigo-600 cursor-pointer transition"
                                  data-point="Eldoret, Kenya">{{ __('customer/busroot.eldoret') }}</span>
                        </div>
                        <div class="flex gap-3 mb-3">
                            <button type="button" class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-md text-sm hover:opacity-90 transition"
                                    id="calculate">{{ __('customer/busroot.calculate_distance') }}</button>
                            <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600 transition"
                                    id="clear">{{ __('customer/busroot.clear_points') }}</button>
                        </div>
                        <div id="result" class="hidden p-3 bg-green-50 rounded-md text-sm text-gray-800"></div>
                        <div id="map" class="h-72 w-full rounded-md mt-3"></div>
                    </div>

                    <input type="hidden" name="dropping_point_amount" id="droppingPointAmount">
                    <input type="hidden" name="route_distance" id="routeDistance">

                    <button type="submit"
                            class="w-full py-2 mt-4 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-md font-medium hover:opacity-90 transition">
                        <i class="fas fa-search mr-2"></i> {{ __('customer/busroot.search_available_buses') }}
                    </button>
                    <input type="hidden" value="{{ $car->schedule->id }}" name="schedule_id">
                </form>
            </div>
        </div>
    </div>
</section>

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/jquery-migrate-3.0.1.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script defer src="{{ asset('js/bootstrap-datepicker.min.js@key=1') }}"></script>
<script defer src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('js/jquery.easing.1.3.js') }}"></script>
<script src="{{ asset('js/jquery.waypoints.min.js') }}"></script>
<script src="{{ asset('js/jquery.stellar.min.js') }}"></script>
<script src="{{ asset('js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('js/aos.js') }}"></script>
<script src="{{ asset('js/jquery.animateNumber.min.js') }}"></script>
<script src="{{ asset('js/scrollax.min.js') }}"></script>
<script src="{{ asset('js/main.js@key=1') }}"></script>
<script src="{{ asset('js/hashes.min.js') }}"></script>
<script defer src="{{ asset('js/common.js@3') }}"></script>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    // Dropoff point handling
    document.getElementById('dropoffPoint').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const amount = selectedOption.getAttribute('data-amount');
        document.getElementById('droppingPointAmount').value = amount;

        let hiddenInput = document.getElementById('hiddenDropoffPoint');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.id = 'hiddenDropoffPoint';
            hiddenInput.name = 'hidden_dropping_point';
            document.getElementById('busSearchForm').appendChild(hiddenInput);
        }
        hiddenInput.value = this.value;

        const endInput = document.getElementById('end');
        if (this.value) {
            endInput.value = this.value;
            geocodePlace(this.value, 'end');
        }
    });

    document.getElementById('pickupPoint').addEventListener('change', function () {
        const startInput = document.getElementById('start');
        if (this.value) {
            startInput.value = this.value;
            geocodePlace(this.value, 'start');
        }
    });

    // Map initialization
    let map, startMarker, endMarker, routingControl, activeInput;
    map = L.map('map').setView([-1.286389, 36.817223], 6); // Centered on Nairobi
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '{{ __('customer/busroot.map_attribution') }}'
    }).addTo(map);

    function createMarkerIcon(color) {
        return L.divIcon({
            className: 'custom-icon',
            html: `<div style="background-color:${color}; width:20px; height:20px; border-radius:50%; border:2px solid white;"></div>`,
            iconSize: [24, 24]
        });
    }

    function updateMarker(marker, latlng, inputId) {
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            const color = inputId === 'start' ? 'green' : 'red';
            marker = L.marker(latlng, {
                icon: createMarkerIcon(color),
                draggable: true
            }).addTo(map).on('dragend', function (e) {
                const position = marker.getLatLng();
                document.getElementById(inputId).value = `${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;
                if ((inputId === 'start' && endMarker) || (inputId === 'end' && startMarker)) {
                    calculateDistance();
                }
            });
            if (inputId === 'start') startMarker = marker;
            else endMarker = marker;
        }
        return marker;
    }

    function haversineKm(lat1, lon1, lat2, lon2) {
        var R = 6371, dLat = (lat2 - lat1) * Math.PI / 180, dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) * Math.sin(dLon/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function setDistanceResult(calculatedDistanceKm, distanceMeters, durationSec, startLatLng, endLatLng) {
        const resultDiv = document.getElementById('result');
        if (resultDiv) {
            resultDiv.classList.remove('hidden');
            resultDiv.className = 'p-3 rounded-md text-sm bg-green-50 text-gray-800';
            var durationStr = durationSec != null ? Math.floor(durationSec / 60) + ' min ' + (durationSec % 60) + ' sec' : '–';
            resultDiv.innerHTML = '<strong>{{ __('customer/busroot.distance') }}</strong> ' + calculatedDistanceKm + ' km (' + (distanceMeters || (calculatedDistanceKm * 1000).toFixed(0)) + ' meters)<br><strong>{{ __('customer/busroot.duration') }}</strong> ' + durationStr + '<br><strong>{{ __('customer/busroot.start') }}</strong> ' + startLatLng.lat.toFixed(6) + ', ' + startLatLng.lng.toFixed(6) + '<br><strong>{{ __('customer/busroot.end') }}</strong> ' + endLatLng.lat.toFixed(6) + ', ' + endLatLng.lng.toFixed(6);
        }
        document.getElementById('routeDistance').value = calculatedDistanceKm;
        document.getElementById('routeDistanceDisplay').value = calculatedDistanceKm;
    }

    function calculateDistance() {
        if (!startMarker || !endMarker) return;
        const startLatLng = startMarker.getLatLng();
        const endLatLng = endMarker.getLatLng();

        if (routingControl) { map.removeControl(routingControl); routingControl = null; }

        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(startLatLng.lat, startLatLng.lng),
                L.latLng(endLatLng.lat, endLatLng.lng)
            ],
            routeWhileDragging: true,
            showAlternatives: false,
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            lineOptions: {
                styles: [{ color: 'blue', opacity: 0.7, weight: 5 }]
            },
            createMarker: function () { return null; }
        }).addTo(map);

        routingControl.on('routesfound', function (e) {
            const routes = e.routes;
            const distance = routes[0].summary.totalDistance;
            const duration = routes[0].summary.totalTime;
            const calculatedDistance = (distance / 1000).toFixed(2);
            setDistanceResult(calculatedDistance, distance, duration, startLatLng, endLatLng);
        });

        routingControl.on('routingerror', function () {
            map.removeControl(routingControl);
            routingControl = null;
            var fallbackKm = haversineKm(startLatLng.lat, startLatLng.lng, endLatLng.lat, endLatLng.lng).toFixed(2);
            setDistanceResult(fallbackKm, null, null, startLatLng, endLatLng);
            var resultDiv = document.getElementById('result');
            if (resultDiv) {
                resultDiv.className = 'p-3 rounded-md text-sm bg-amber-50 text-amber-800';
                resultDiv.innerHTML = '{{ __("customer/busroot.routing_fallback") ?? "Route service unavailable; showing straight-line distance." }}<br>' + resultDiv.innerHTML;
            }
        });

        map.fitBounds(L.latLngBounds(startLatLng, endLatLng));
    }

    function geocodePlace(place, inputId, retryCount) {
        retryCount = retryCount || 0;
        if (!place) return Promise.resolve();
        const resultDiv = document.getElementById('result');
        const showResult = function (html, isError) {
            if (resultDiv) {
                resultDiv.classList.remove('hidden');
                resultDiv.className = 'p-3 rounded-md text-sm ' + (isError ? 'bg-amber-50 text-amber-800' : 'bg-green-50 text-gray-800');
                resultDiv.innerHTML = html;
            }
        };
        function doFetch() {
            return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(place) + '&limit=1', {
                headers: { 'Accept': 'application/json', 'User-Agent': 'HighlinkRoundTrip/1.0' }
            }).then(function (response) { return response.json(); });
        }
        return doFetch()
            .then(function (data) {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    const latlng = L.latLng(lat, lon);
                    document.getElementById(inputId).value = lat.toFixed(6) + ', ' + lon.toFixed(6);
                    if (inputId === 'start') {
                        startMarker = updateMarker(startMarker, latlng, 'start');
                    } else {
                        endMarker = updateMarker(endMarker, latlng, 'end');
                    }
                    if (!startMarker || !endMarker) {
                        map.setView(latlng, 12);
                    }
                } else {
                    showResult('{{ __("customer/busroot.no_results_found") }}'.replace('[place]', place), true);
                    document.getElementById(inputId).value = place;
                }
            })
            .catch(function (error) {
                if (retryCount < 1) {
                    return new Promise(function (resolve) { setTimeout(resolve, 1100); }).then(function () {
                        return geocodePlace(place, inputId, 1);
                    });
                }
                console.error('Geocoding error:', error);
                showResult('{{ __("customer/busroot.geocoding_error") }}', true);
                document.getElementById(inputId).value = place;
            });
    }

    function handleInputChange(inputId) {
        const input = document.getElementById(inputId);
        input.addEventListener('change', function () {
            const value = this.value.trim();
            if (!value.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) {
                geocodePlace(value, inputId);
            }
        });
        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                const value = this.value.trim();
                if (!value.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) {
                    geocodePlace(value, inputId);
                }
            }
        });
    }

    // Handle input focus
    document.getElementById('start').addEventListener('focus', function () {
        activeInput = 'start';
        this.classList.add('bg-yellow-50');
        document.getElementById('end').classList.remove('bg-yellow-50');
    });

    document.getElementById('end').addEventListener('focus', function () {
        activeInput = 'end';
        this.classList.add('bg-yellow-50');
        document.getElementById('start').classList.remove('bg-yellow-50');
    });

    // Handle map clicks
    map.on('click', function (e) {
        if (activeInput) {
            const latlng = e.latlng;
            document.getElementById(activeInput).value = `${latlng.lat.toFixed(6)}, ${latlng.lng.toFixed(6)}`;
            if (activeInput === 'start') {
                startMarker = updateMarker(startMarker, latlng, 'start');
            } else {
                endMarker = updateMarker(endMarker, latlng, 'end');
            }
            if (startMarker && endMarker) {
                calculateDistance();
            }
        } else {
            alert('{{ __('customer/busroot.select_input_first') }}');
        }
    });

    // Handle calculate button: resolve start and end (coords or geocode), then calculate distance
    document.getElementById('calculate').addEventListener('click', function () {
        var startValue = document.getElementById('start').value.trim();
        var endValue = document.getElementById('end').value.trim();
        var coordRegex = /^-?\d+\.\d+,\s*-?\d+\.\d+$/;

        function setStart() {
            if (!startValue) return Promise.resolve();
            if (startValue.match(coordRegex)) {
                try {
                    var parts = startValue.split(',').map(function (c) { return parseFloat(c.trim()); });
                    startMarker = updateMarker(startMarker, L.latLng(parts[0], parts[1]), 'start');
                } catch (e) {
                    alert('{{ __('customer/busroot.invalid_start_coords') }}');
                }
                return Promise.resolve();
            }
            return geocodePlace(startValue, 'start');
        }
        function setEnd() {
            if (!endValue) return Promise.resolve();
            if (endValue.match(coordRegex)) {
                try {
                    var parts = endValue.split(',').map(function (c) { return parseFloat(c.trim()); });
                    endMarker = updateMarker(endMarker, L.latLng(parts[0], parts[1]), 'end');
                } catch (e) {
                    alert('{{ __('customer/busroot.invalid_end_coords') }}');
                }
                return Promise.resolve();
            }
            return geocodePlace(endValue, 'end');
        }

        setStart().then(function () {
            return new Promise(function (resolve) { setTimeout(resolve, 1100); });
        }).then(setEnd).then(function () {
            if (startMarker && endMarker) {
                map.invalidateSize();
                setTimeout(function () { calculateDistance(); }, 200);
            }
        });
    });

    // Handle clear button
    document.getElementById('clear').addEventListener('click', function () {
        if (startMarker) {
            map.removeLayer(startMarker);
            startMarker = null;
        }
        if (endMarker) {
            map.removeLayer(endMarker);
            endMarker = null;
        }
        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
        document.getElementById('start').value = document.getElementById('routeFrom').value;
        document.getElementById('end').value = document.getElementById('routeTo').value;
        document.getElementById('result').classList.add('hidden');
        document.getElementById('start').classList.remove('bg-yellow-50');
        document.getElementById('end').classList.remove('bg-yellow-50');
        activeInput = null;
        document.getElementById('routeDistance').value = '';
        document.getElementById('routeDistanceDisplay').value = '';
        const fromValue = document.getElementById('routeFrom').value;
        const toValue = document.getElementById('routeTo').value;
        if (fromValue) geocodePlace(fromValue, 'start');
        if (toValue) geocodePlace(toValue, 'end');
    });

    // Handle default point buttons
    document.querySelectorAll('.point-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!activeInput) {
                alert('{{ __('customer/busroot.select_input_first') }}');
                return;
            }
            const pointName = this.getAttribute('data-point');
            geocodePlace(pointName, activeInput);
        });
    });

    // Initialize input handlers
    handleInputChange('start');
    handleInputChange('end');

    // Distance/geocoding disabled for customer round trip: just set labels, no API calls
    window.addEventListener('load', function () {
        const fromValue = document.getElementById('routeFrom').value;
        const toValue = document.getElementById('routeTo').value;
        if (fromValue) document.getElementById('start').value = fromValue;
        if (toValue) document.getElementById('end').value = toValue;
        // Ensure route_distance has a value so form can submit without calculating
        if (!document.getElementById('routeDistance').value) {
            document.getElementById('routeDistance').value = '0';
            document.getElementById('routeDistanceDisplay').value = '0';
        }
    });

    // On submit, ensure route_distance is set (backend accepts 0)
    document.getElementById('busSearchForm').addEventListener('submit', function () {
        if (!document.getElementById('routeDistance').value) {
            document.getElementById('routeDistance').value = '0';
            document.getElementById('routeDistanceDisplay').value = '0';
        }
    });
</script>

<style>
    #map {
        height: 18rem; /* h-72 = 288px */
        width: 100%;
        border-radius: 0.375rem; /* rounded-md */
    }
    /* Ensure route info / directions panel next to the map is readable */
    .leaflet-routing-container {
        background-color: rgba(255, 255, 255, 0.96) !important;
        color: #111827 !important; /* dark text */
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-size: 0.85rem;
    }
    .leaflet-routing-container,
    .leaflet-routing-container * ,
    .leaflet-routing-alt,
    .leaflet-routing-alt * {
        color: #111827 !important;
        background-color: transparent !important;
    }
    .leaflet-routing-alt {
        background-color: rgba(255, 255, 255, 0.96) !important;
        padding: 0.5rem 0.25rem;
        border-radius: 0.5rem;
    }
    .toggle-password {
        float: right;
        cursor: pointer;
        margin-right: 0.625rem; /* 10px */
        margin-top: -1.5625rem; /* -25px */
    }
    .resend-color {
        color: #183C64 !important;
        cursor: pointer;
    }
</style>
@endsection
