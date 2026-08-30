@extends('customer.app')

@section('content')

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <!-- Add Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <section class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-gray-100 rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 sm:p-8">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">{{ __('customer/busroot.select_your_journey_points') }}</h2>
                        <p class="text-gray-600 mt-2">{{ __('customer/busroot.choose_pickup_dropping') }}</p>
                    </div>

                    <form id="busSearchForm" method="POST" action="{{ route('customer.get_form') }}" class="space-y-6">
                        @csrf

                        <!-- Bus Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Bus Operator -->
                            <div class="glass-card p-4 rounded-lg">
                                <label for="busOperator" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-bus text-blue-500 mr-2"></i> {{ __('customer/busroot.bus_operator') }}
                                </label>
                                <input type="text" name="bus_name" value="{{ $car->busname->name }}" readonly
                                    class="text-gray-900 w-full glass-card border border-gray-700 rounded-md px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Route Information -->
                            <div class="glass-card p-4 rounded-lg">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-route text-blue-500 mr-2"></i> {{ __('customer/busroot.route') }}
                                </label>
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-gray-900">{{ $car->schedule->from }}</span>
                                    <i class="fas fa-arrow-right text-gray-400 mx-1"></i>
                                    <span class="font-medium text-gray-900">{{ $car->schedule->to }}</span>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="bus_id" value="{{ $car->id }}">
                        <input type="hidden" name="route_id" value="{{ $car->route->id }}">

                        <!-- Points Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Pickup Point -->
                            <div class="glass-card p-4 rounded-lg">
                                <label for="pickupPoint" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-map-marker-alt text-green-500 mr-2"></i> {{ __('customer/busroot.pickup_point') }}
                                </label>
                                <select
                                    class="p-3 w-full border bg-gray-400 text-black border-gray-200 rounded-md px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 select2"
                                    id="pickupPoint" name="pickup_point">
                                    <option value="">{{ __('customer/busroot.select_pickup_point') }}</option>
                                    @if (isset($car->filtered_points))
                                        @foreach ($car->filtered_points as $value)
                                            @if ($value->point_mode == 1)
                                                <option value="{{ $value->point }}"
                                                    {{ request('pickup_point_id') == $value->point ? 'selected' : '' }}>
                                                    {{ $value->point }}
                                                </option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Dropoff Point -->
                            <div class="glass-card p-4 rounded-lg">
                                <label for="dropoffPoint" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-flag-checkered text-red-500 mr-2"></i> {{ __('customer/busroot.dropoff_point') }}
                                </label>
                                <select
                                    class="p-3 w-full border bg-gray-400 text-black border-gray-200 rounded-md px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 select2"
                                    id="dropoffPoint" name="dropping_point">
                                    <option value="">{{ __('customer/busroot.select_dropping_point') }}</option>
                                    @if (isset($car->filtered_points))
                                        @foreach ($car->filtered_points as $value)
                                            @if ($value->point_mode == 2)
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
                        <div class="glass-card p-4 rounded-lg">
                            <label for="routeDistanceDisplay" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-arrows-alt-h text-blue-500 mr-2"></i> {{ __('customer/busroot.route_distance') }}
                            </label>
                            <div class="flex items-center">
                                <input type="text" id="routeDistanceDisplay" readonly
                                    class="w-full border border-gray-200 rounded-l-md px-4 py-2 text-sm text-gray-900"
                                    name="route_distance_display" placeholder="{{ __('customer/busroot.distance_will_be_calculated') }}">
                                <span class="bg-blue-500 text-black px-4 py-2 rounded-r-md text-sm">{{ __('all.km') }}</span>
                            </div>
                        </div>

                        <!-- Interactive Map Section -->
                        <div class="glass-card border border-gray-200 rounded-xl overflow-hidden">
                            <div class="p-4 border-b border-gray-200 glass-card">
                                <h3 class="font-medium text-gray-800">
                                    <i class="fas fa-map-marked-alt text-blue-500 mr-2"></i> {{ __('customer/busroot.interactive_map') }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">{{ __('customer/busroot.select_points_map') }}</p>
                            </div>

                            <div class="p-4 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="start" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fas fa-circle text-green-500 mr-2"></i> {{ __('customer/busroot.pickup_location') }}
                                        </label>
                                        <input type="text"
                                            class="text-gray-900 w-full border border-gray-200 rounded-md px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            id="start" placeholder="{{ __('customer/busroot.search_pickup_location') }}"
                                            value="{{ $car->schedule->from }}">
                                    </div>
                                    <div>
                                        <label for="end" class="block text-sm font-medium text-gray-700 mb-1">
                                            <i class="fas fa-circle text-red-500 mr-2"></i> {{ __('customer/busroot.dropping_location') }}
                                        </label>
                                        <input type="text"
                                            class="text-gray-900 w-full border border-gray-200 rounded-md px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            id="end" placeholder="{{ __('customer/busroot.search_dropping_location') }}"
                                            value="{{ $car->schedule->to }}">
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span class="text-sm font-medium text-gray-700">{{ __('customer/busroot.quick_locations') }}</span>
                                    <button type="button"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200 transition"
                                        data-point="Nairobi, Kenya">{{ __('customer/busroot.nairobi') }}</button>
                                    <button type="button"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200 transition"
                                        data-point="Mombasa, Kenya">{{ __('customer/busroot.mombasa') }}</button>
                                    <button type="button"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200 transition"
                                        data-point="Kisumu, Kenya">{{ __('customer/busroot.kisumu') }}</button>
                                    <button type="button"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200 transition"
                                        data-point="Nakuru, Kenya">{{ __('customer/busroot.nakuru') }}</button>
                                    <button type="button"
                                        class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200 transition"
                                        data-point="Eldoret, Kenya">{{ __('customer/busroot.eldoret') }}</button>
                                </div>

                                <div class="flex gap-3 pt-2">
                                    <button type="button" id="calculate"
                                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition flex items-center justify-center">
                                        <i class="fas fa-calculator mr-2"></i> {{ __('customer/busroot.calculate_distance') }}
                                    </button>
                                    <button type="button" id="clear"
                                        class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition flex items-center justify-center">
                                        <i class="fas fa-eraser mr-2"></i> {{ __('customer/busroot.clear_points') }}
                                    </button>
                                </div>

                                <div id="result" class="mt-3 p-3 glass-card rounded-md border border-green-100 hidden">
                                    <div class="flex items-start">
                                        <i class="fas fa-info-circle text-black mt-1 mr-2"></i>
                                        <div id="result-content"></div>
                                    </div>
                                </div>

                                <div id="map" class="h-80 w-full rounded-lg mt-4 border border-gray-200 shadow-sm">
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="dropping_point_amount" id="droppingPointAmount">
                        <input type="hidden" name="route_distance" id="routeDistance">

                        <button type="submit"
                            class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg hover:from-blue-700 hover:to-blue-900 transition shadow-md flex items-center justify-center">
                            <i class="fas fa-search mr-3"></i> {{ __('customer/busroot.search_available_buses') }}
                        </button>
                        <input type="hidden" name="schedule_id" value="{{ $car->schedule->id }}">
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
    <script src="{{ asset('js/hashes.min.js') }}" type="text/javascript"></script>
    <script defer src="{{ asset('js/common.js@3') }}" type="text/javascript"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 on the select elements
            $('.select2').select2({
                placeholder: "{{ __('customer/busroot.select_a_point') }}",
                allowClear: true,
                width: '100%'
            });
        });
    </script>

    <script>
        // Handle dropoff point selection with Select2
    $('#dropoffPoint').on('select2:select', function(e) {
        var selectedOption = e.params.data.element;
        var amount = $(selectedOption).data('amount'); // Get data-amount using jQuery
        $('#droppingPointAmount').val(amount); // Set the hidden input value

        // Optional: Debugging to verify the amount
        console.log('Selected dropoff point amount:', amount);

        // Update hidden input for dropping point (if needed)
        var selectedValue = $(this).val();
        let hiddenInput = $('#hiddenDropoffPoint');
        if (!hiddenInput.length) {
            hiddenInput = $('<input>').attr({
                type: 'hidden',
                id: 'hiddenDropoffPoint',
                name: 'hidden_dropping_point'
            });
            $('#busSearchForm').append(hiddenInput);
        }
        hiddenInput.val(selectedValue);
    });

        // Map JavaScript
        let map, startMarker, endMarker, routingControl, activeInput;
        let calculatedDistance = null;

        // Initialize map on page load
        map = L.map('map').setView([-1.286389, 36.817223], 6); // Centered on Nairobi
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '{{ __('customer/busroot.map_attribution') }}'
        }).addTo(map);

        function createMarkerIcon(color) {
            return L.divIcon({
                className: 'custom-icon',
                html: `<div style="background-color:${color}; width:24px; height:24px; border-radius:50%; border:3px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.3);"></div>`,
                iconSize: [30, 30]
            });
        }

        function updateMarker(marker, latlng, inputId) {
            if (marker) {
                marker.setLatLng(latlng);
            } else {
                const color = inputId === 'start' ? '#10B981' : '#EF4444';
                marker = L.marker(latlng, {
                    icon: createMarkerIcon(color),
                    draggable: true
                }).addTo(map).on('dragend', function(e) {
                    const position = marker.getLatLng();
                    document.getElementById(inputId).value =
                        `${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;
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
            var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)*Math.sin(dLon/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        function setDistanceResult(calculatedDistanceKm, durationSec, startLatLng, endLatLng, isFallback) {
            calculatedDistance = calculatedDistanceKm;
            const resultDiv = document.getElementById('result');
            const resultContent = document.getElementById('result-content');
            if (resultDiv) resultDiv.style.display = 'block';
            if (resultContent) {
                var durationStr = durationSec != null ? Math.floor(durationSec/60) + ' min ' + (durationSec%60) + ' sec' : '–';
                resultContent.innerHTML = (isFallback ? '<p class="text-amber-700 text-sm mb-2">{{ __("customer/busroot.routing_fallback") ?? "Route service unavailable; showing straight-line distance." }}</p>' : '') + '<div class="grid grid-cols-2 gap-2 text-sm text-black"><div><span class="font-medium">{{ __('customer/busroot.distance') }}</span> ' + calculatedDistanceKm + ' km</div><div><span class="font-medium">{{ __('customer/busroot.duration') }}</span> ' + durationStr + '</div><div><span class="font-medium">{{ __('customer/busroot.start') }}</span> ' + startLatLng.lat.toFixed(6) + ', ' + startLatLng.lng.toFixed(6) + '</div><div><span class="font-medium">{{ __('customer/busroot.end') }}</span> ' + endLatLng.lat.toFixed(6) + ', ' + endLatLng.lng.toFixed(6) + '</div></div>';
            }
            var rd = document.getElementById('routeDistance'), rdd = document.getElementById('routeDistanceDisplay');
            if (rd) rd.value = calculatedDistanceKm;
            if (rdd) rdd.value = calculatedDistanceKm;
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
                lineOptions: { styles: [{ color: '#3B82F6', opacity: 0.8, weight: 6 }] },
                createMarker: function() { return null; }
            }).addTo(map);

            routingControl.on('routesfound', function(e) {
                const routes = e.routes;
                const distance = routes[0].summary.totalDistance;
                const duration = routes[0].summary.totalTime;
                setDistanceResult((distance/1000).toFixed(2), duration, startLatLng, endLatLng, false);
            });

            routingControl.on('routingerror', function() {
                map.removeControl(routingControl);
                routingControl = null;
                var fallbackKm = haversineKm(startLatLng.lat, startLatLng.lng, endLatLng.lat, endLatLng.lng).toFixed(2);
                setDistanceResult(fallbackKm, null, startLatLng, endLatLng, true);
            });

            map.fitBounds(L.latLngBounds(startLatLng, endLatLng));
        }

        function geocodePlace(place, inputId, retryCount) {
            retryCount = retryCount || 0;
            if (!place) return Promise.resolve();
            const input = document.getElementById(inputId);
            input.classList.add('bg-blue-50');
            input.readOnly = true;
            function done() {
                input.classList.remove('bg-blue-50');
                input.readOnly = false;
            }
            return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(place) + '&limit=1', {
                headers: { 'Accept': 'application/json', 'User-Agent': 'HighlinkRoundTrip/1.0' }
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        const latlng = L.latLng(lat, lon);
                        document.getElementById(inputId).value = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
                        if (inputId === 'start') {
                            startMarker = updateMarker(startMarker, latlng, 'start');
                        } else {
                            endMarker = updateMarker(endMarker, latlng, 'end');
                        }
                        if (startMarker && endMarker) calculateDistance();
                        else map.setView(latlng, 12);
                    } else {
                        showAlert('{{ __('customer/busroot.no_results_found') }}'.replace('[place]', place), 'error');
                        document.getElementById(inputId).value = '';
                    }
                })
                .catch(error => {
                    if (retryCount < 1) {
                        return new Promise(r => setTimeout(r, 1100)).then(() => geocodePlace(place, inputId, 1));
                    }
                    console.error('Geocoding error:', error);
                    showAlert('{{ __('customer/busroot.geocoding_error') }}', 'error');
                    document.getElementById(inputId).value = '';
                })
                .finally(done);
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg text-white ${
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            alertDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(alertDiv);
            setTimeout(() => {
                alertDiv.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                setTimeout(() => alertDiv.remove(), 300);
            }, 3000);
        }

        function handleInputChange(inputId) {
            const input = document.getElementById(inputId);
            input.addEventListener('change', function() {
                const value = this.value.trim();
                if (!value.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) {
                    geocodePlace(value, inputId);
                }
            });
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const value = this.value.trim();
                    if (!value.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) {
                        geocodePlace(value, inputId);
                    }
                }
            });
        }

        // Handle input focus
        document.getElementById('start').addEventListener('focus', function() {
            activeInput = 'start';
            this.classList.add('ring-2', 'ring-blue-400');
            document.getElementById('end').classList.remove('ring-2', 'ring-blue-400');
        });

        document.getElementById('end').addEventListener('focus', function() {
            activeInput = 'end';
            this.classList.add('ring-2', 'ring-blue-400');
            document.getElementById('start').classList.remove('ring-2', 'ring-blue-400');
        });

        // Handle map clicks
        map.on('click', function(e) {
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
            }
        });

        // Handle calculate button (delay between geocodes for Nominatim 1 req/sec)
        document.getElementById('calculate').addEventListener('click', function() {
            const startValue = document.getElementById('start').value.trim();
            const endValue = document.getElementById('end').value.trim();
            const coordRegex = /^-?\d+\.\d+,\s*-?\d+\.\d+$/;

            function setStart() {
                if (!startValue) return Promise.resolve();
                if (startValue.match(coordRegex)) {
                    try {
                        const parts = startValue.split(',').map(c => parseFloat(c.trim()));
                        startMarker = updateMarker(startMarker, L.latLng(parts[0], parts[1]), 'start');
                    } catch (e) { showAlert('{{ __('customer/busroot.invalid_start_coords') }}', 'error'); }
                    return Promise.resolve();
                }
                return geocodePlace(startValue, 'start');
            }
            function setEnd() {
                if (!endValue) return Promise.resolve();
                if (endValue.match(coordRegex)) {
                    try {
                        const parts = endValue.split(',').map(c => parseFloat(c.trim()));
                        endMarker = updateMarker(endMarker, L.latLng(parts[0], parts[1]), 'end');
                    } catch (e) { showAlert('{{ __('customer/busroot.invalid_end_coords') }}', 'error'); }
                    return Promise.resolve();
                }
                return geocodePlace(endValue, 'end');
            }

            setStart().then(function() { return new Promise(r => setTimeout(r, 1100)); }).then(setEnd).then(function() {
                if (startMarker && endMarker) {
                    map.invalidateSize();
                    setTimeout(calculateDistance, 200);
                }
            });
        });

        // Handle clear button
        document.getElementById('clear').addEventListener('click', function() {
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
            document.getElementById('start').value = '';
            document.getElementById('end').value = '';
            document.getElementById('result').style.display = 'none';
            document.getElementById('start').classList.remove('ring-2', 'ring-blue-400');
            document.getElementById('end').classList.remove('ring-2', 'ring-blue-400');
            activeInput = null;
            document.getElementById('routeDistance').value = '';
            document.getElementById('routeDistanceDisplay').value = '';
        });

        // Handle quick location buttons
        document.querySelectorAll('[data-point]').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!activeInput) {
                    showAlert('{{ __('customer/busroot.select_input_first') }}', 'error');
                    return;
                }
                const pointName = this.getAttribute('data-point');
                geocodePlace(pointName, activeInput);
            });
        });

        // Sync pickup and dropoff points with map inputs
        document.getElementById('pickupPoint').addEventListener('change', function() {
            const startInput = document.getElementById('start');
            if (this.value) {
                startInput.value = this.value;
                geocodePlace(this.value, 'start');
            }
        });

        document.getElementById('dropoffPoint').addEventListener('change', function() {
            const endInput = document.getElementById('end');
            if (this.value) {
                endInput.value = this.value;
                geocodePlace(this.value, 'end');
            }
        });

        // Initialize input handlers
        handleInputChange('start');
        handleInputChange('end');

        // Auto-geocode default points on load
        window.addEventListener('load', function() {
            const fromValue = document.getElementById('routeFrom').value;
            const toValue = document.getElementById('routeTo').value;
            if (fromValue) {
                document.getElementById('start').value = fromValue;
                geocodePlace(fromValue, 'start');
            }
            if (toValue) {
                document.getElementById('end').value = toValue;
                geocodePlace(toValue, 'end');
            }
        });
    </script>

    <style>
        .custom-icon {
            background: transparent !important;
            border: none !important;
        }

        #map {
            z-index: 0;
        }

        .toggle-password {
            float: right;
            cursor: pointer;
            margin-right: 10px;
            margin-top: -25px;
        }

        .resend-color {
            color: #183C64 !important;
            cursor: pointer;
        }

        /* Animation for route display */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #result {
            animation: fadeIn 0.3s ease-out;
        }

        /* Custom scrollbar for selects */
        select::-webkit-scrollbar {
            width: 8px;
        }

        select::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        select::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        select::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>

@endsection
