@extends('admin.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <style>
        /* ---------- Map styles ---------- */
        #map {
            height: 400px;
            width: 100%;
        }

        .active-input {
            background-color: #fefcbf;
        }

        /* Tailwind @apply won't run inside <style>, so use plain CSS: */
        .default-points {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
        }

        .point-btn {
            background-color: #0d9488;
            color: #fff;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
        }

        .point-btn:hover {
            background-color: #0f766e;
        }

        #result {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background-color: #f0fdfa;
            border-radius: 0.5rem;
            display: none;
        }

        /* ---------- Seat Designer styles (minimal + safe) ---------- */
        .sl-grid-cell {
            outline: 1px dashed rgba(148, 163, 184, 0.25);
            position: relative;
        }

        .sl-aisle {
            background: repeating-linear-gradient(45deg, rgba(100, 116, 139, 0.12) 0, rgba(100, 116, 139, 0.12) 6px, transparent 6px, transparent 12px);
        }

        .sl-seat {
            position: absolute;
            inset: 0.25rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #334155;
            color: #fff;
            font-size: 0.85rem;
            user-select: none;
            cursor: grab;
        }

        .sl-seat.selected {
            outline: 2px solid #22d3ee;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.3);
        }
    </style>

    <div class="container-fluid py-4">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white/80 backdrop-blur-lg shadow-xl rounded-2xl">
                <div class="bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-t-2xl p-4">
                    <h5 class="text-lg font-semibold tracking-tight">{{ __('vender/mybus.edit_bus_details') }}</h5>
                </div>
                <div class="p-6">
                    <!-- Display validation errors -->
                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                            <ul class="list-none mb-0 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li class="text-sm text-red-700">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="editBusForm" action="{{ route('update.bus', $bus->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="bus_id" value="{{ $bus->id }}">

                        <!-- Bus Information Fieldset -->
                        <fieldset class="border border-gray-200 p-4 mb-6 rounded-lg">
                            <legend class="text-sm font-semibold text-gray-700 px-2">
                                {{ __('vender/mybus.bus_information') }}</legend>
                            <div class="grid gap-4">
                                <div>
                                    <label for="company_name"
                                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.company') }}</label>
                                    <input type="text"
                                        value="{{ auth()->user() ? auth()->user()->campany->name : __('vender/mybus.no_company') }}"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-100 text-gray-900 cursor-not-allowed"
                                        disabled>
                                    @error('company_id')
                                        <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="bus_number"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.bus_number_plate') }}</label>
                                        <input type="text" id="bus_number" name="bus_number"
                                            value="{{ old('bus_number', $bus->bus_number) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('bus_number') border-red-400 focus:ring-red-400 @enderror"
                                            required>
                                        @error('bus_number')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="bus_type"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.bus_type') }}</label>
                                        <select id="bus_type" name="bus_type"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 transition-all duration-200 @error('bus_type') border-red-400 focus:ring-red-400 @enderror"
                                            required>
                                            <option value="">{{ __('vender/mybus.select_bus_type') }}</option>
                                            <option value="10"
                                                {{ old('bus_type', $bus->bus_type) == '10' ? 'selected' : '' }}>luxury
                                            </option>
                                            <option value="20"
                                                {{ old('bus_type', $bus->bus_type) == '20' ? 'selected' : '' }}>
                                                upper-semiluxury</option>
                                            <option value="30"
                                                {{ old('bus_type', $bus->bus_type) == '30' ? 'selected' : '' }}>
                                                lower-semiluxury</option>
                                            <option value="40"
                                                {{ old('bus_type', $bus->bus_type) == '40' ? 'selected' : '' }}>ordinary
                                            </option>
                                        </select>
                                        @error('bus_type')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="total_seats"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.total_seats') }}</label>
                                        <input type="number" id="total_seats" name="total_seats"
                                            value="{{ old('total_seats', $bus->total_seats) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 transition-all duration-200 @error('total_seats') border-red-400 focus:ring-red-400 @enderror"
                                            required>
                                        @error('total_seats')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="conductor_phone"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.conductor_phone') }}</label>
                                        <input type="tel" id="conductor_phone" name="conductor_phone"
                                            value="{{ old('conductor_phone', $bus->conductor) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 transition-all duration-200 @error('conductor_phone') border-red-400 focus:ring-red-400 @enderror"
                                            required>
                                        @error('conductor_phone')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="driver_name" class="block text-sm font-medium text-gray-700 mb-1">Driver
                                            Name</label>
                                        <input type="text" id="driver_name" name="driver_name"
                                            value="{{ old('driver_name', $bus->driver_name) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('driver_name') border-red-400 focus:ring-red-400 @enderror">
                                        @error('driver_name')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="driver_contact"
                                            class="block text-sm font-medium text-gray-700 mb-1">Driver Contact</label>
                                        <input type="text" id="driver_contact" name="driver_contact"
                                            value="{{ old('driver_contact', $bus->driver_contact) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('driver_contact') border-red-400 focus:ring-red-400 @enderror">
                                        @error('driver_contact')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="driver_name_2"
                                            class="block text-sm font-medium text-gray-700 mb-1">Second Driver
                                            Name</label>
                                        <input type="text" id="driver_name_2" name="driver_name_2"
                                            value="{{ old('driver_name_2', $bus->driver_name_2) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('driver_name_2') border-red-400 focus:ring-red-400 @enderror">
                                        @error('driver_name_2')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="driver_contact_2"
                                            class="block text-sm font-medium text-gray-700 mb-1">Second Driver
                                            Contact</label>
                                        <input type="text" id="driver_contact_2" name="driver_contact_2"
                                            value="{{ old('driver_contact_2', $bus->driver_contact_2) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('driver_contact_2') border-red-400 focus:ring-red-400 @enderror">
                                        @error('driver_contact_2')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="conductor_name"
                                            class="block text-sm font-medium text-gray-700 mb-1">Conductor Name</label>
                                        <input type="text" id="conductor_name" name="conductor_name"
                                            value="{{ old('conductor_name', $bus->conductor_name) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('conductor_name') border-red-400 focus:ring-red-400 @enderror">
                                        @error('conductor_name')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="bus_model" class="block text-sm font-medium text-gray-700 mb-1">Bus
                                            Model (e.g., Yutong D14)</label>
                                        <input type="text" id="bus_model" name="bus_model"
                                            value="{{ old('bus_model', $bus->bus_model) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('bus_model') border-red-400 focus:ring-red-400 @enderror">
                                        @error('bus_model')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="customer_service_name_1"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Name
                                            1</label>
                                        <input type="text" id="customer_service_name_1" name="customer_service_name_1"
                                            value="{{ old('customer_service_name_1', $bus->customer_service_name_1) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_name_1') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_name_1')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="customer_service_contact_1"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Contact
                                            1</label>
                                        <input type="text" id="customer_service_contact_1"
                                            name="customer_service_contact_1"
                                            value="{{ old('customer_service_contact_1', $bus->customer_service_contact_1) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_contact_1') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_contact_1')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="customer_service_name_2"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Name
                                            2</label>
                                        <input type="text" id="customer_service_name_2" name="customer_service_name_2"
                                            value="{{ old('customer_service_name_2', $bus->customer_service_name_2) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_name_2') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_name_2')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="customer_service_contact_2"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Contact
                                            2</label>
                                        <input type="text" id="customer_service_contact_2"
                                            name="customer_service_contact_2"
                                            value="{{ old('customer_service_contact_2', $bus->customer_service_contact_2) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_contact_2') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_contact_2')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="customer_service_name_3"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Name
                                            3</label>
                                        <input type="text" id="customer_service_name_3" name="customer_service_name_3"
                                            value="{{ old('customer_service_name_3', $bus->customer_service_name_3) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_name_3') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_name_3')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="customer_service_contact_3"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Contact
                                            3</label>
                                        <input type="text" id="customer_service_contact_3"
                                            name="customer_service_contact_3"
                                            value="{{ old('customer_service_contact_3', $bus->customer_service_contact_3) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_contact_3') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_contact_3')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="customer_service_name_4"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Name
                                            4</label>
                                        <input type="text" id="customer_service_name_4" name="customer_service_name_4"
                                            value="{{ old('customer_service_name_4', $bus->customer_service_name_4) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_name_4') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_name_4')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="customer_service_contact_4"
                                            class="block text-sm font-medium text-gray-700 mb-1">Customer Service Contact
                                            4</label>
                                        <input type="text" id="customer_service_contact_4"
                                            name="customer_service_contact_4"
                                            value="{{ old('customer_service_contact_4', $bus->customer_service_contact_4) }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('customer_service_contact_4') border-red-400 focus:ring-red-400 @enderror">
                                        @error('customer_service_contact_4')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div>
                                    <label for="bus_features"
                                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.bus_features_optional') }}</label>
                                    <textarea id="bus_features" name="bus_features"
                                        class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 transition-all duration-200 @error('bus_features') border-red-400 focus:ring-red-400 @enderror"
                                        rows="2">{{ old('bus_features', $bus->bus_features) }}</textarea>
                                    @error('bus_features')
                                        <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </fieldset>

                        <!-- Route Information Fieldset -->
                        <fieldset class="border border-gray-200 p-4 mb-6 rounded-lg">
                            <legend class="text-sm font-semibold text-gray-700 px-2">
                                {{ __('vender/mybus.route_information') }}</legend>
                            <div class="grid gap-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="route_from"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.from') }}</label>
                                        <select id="route_from" name="route_from"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 transition-all duration-200 @error('route_from') border-red-400 focus:ring-red-400 @enderror"
                                            required>
                                            <option value="">{{ __('vender/mybus.select_city') }}</option>
                                            @if (isset($cities))
                                                @foreach ($cities as $city)
                                                    <option value="{{ $city->name }}"
                                                        {{ old('route_from', $bus->route->from ?? '') == $city->name ? 'selected' : '' }}>
                                                        {{ $city->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('route_from')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="route_to"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.to') }}</label>
                                        <select id="route_to" name="route_to"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 transition-all duration-200 @error('route_to') border-red-400 focus:ring-red-400 @enderror"
                                            required>
                                            <option value="">{{ __('vender/mybus.select') }}</option>
                                            @if (isset($cities))
                                                @foreach ($cities as $city)
                                                    <option value="{{ $city->name }}"
                                                        {{ old('route_to', $bus->route->to ?? '') == $city->name ? 'selected' : '' }}>
                                                        {{ $city->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('route_to')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                {{--<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="route_start"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.route_start_time') }}</label>
                                        <input type="text" id="route_start" name="route_start"
                                            value="{{ old('route_start', $bus->route->route_start ? date('H:i', strtotime($bus->route->route_start)) : '') }}"
                                            class="timepicker w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('route_start') border-red-400 focus:ring-red-400 @enderror"
                                            placeholder="{{ __('vender/mybus.select_time') }}" required>
                                        @error('route_start')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="route_end"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.route_end_time') }}</label>
                                        <input type="text" id="route_end" name="route_end"
                                            value="{{ old('route_end', $bus->route->route_end ? date('H:i', strtotime($bus->route->route_end)) : '') }}"
                                            class="timepicker w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200 @error('route_end') border-red-400 focus:ring-red-400 @enderror"
                                            placeholder="{{ __('vender/mybus.select_time') }}" required>
                                        @error('route_end')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>--}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="route_price"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.route_price') }}</label>
                                        <input type="number" step="0.01" id="route_price" name="route_price"
                                            value="{{ old('route_price', $bus->route->price ?? '') }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 transition-all duration-200 @error('route_price') border-red-400 focus:ring-red-400 @enderror"
                                            required>
                                        @error('route_price')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="route_distance"
                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.route_distance_km') }}</label>
                                        <div class="flex gap-2">
                                            <input type="number" step="0.01" id="route_distance"
                                                name="route_distance"
                                                value="{{ old('route_distance', $bus->route->distance ?? '') }}"
                                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-100 text-gray-900 transition-all duration-200 @error('route_distance') border-red-400 @enderror"
                                                readonly>
                                            <button type="button"
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-all duration-200"
                                                onclick="openMapModal()">{{ __('vender/mybus.select_route') }}</button>
                                        </div>
                                        @error('route_distance')
                                            <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        {{-- ===================== SEAT LAYOUT (embedded) ===================== --}}
                        <fieldset class="border border-gray-200 p-4 mb-6 rounded-lg">
                            <legend class="text-sm font-semibold text-gray-700 px-2">Seat Layout</legend>

                            {{-- Hidden JSON sent to server --}}
                            <input type="hidden" name="seate_json" id="seate_json">

                            @if ($bus->seate_json && !empty($bus->seate_json))
                                {{-- Show existing seat layout for editing --}}
                                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-blue-800 font-medium">Existing seat layout found. You can
                                            edit the current layout below.</span>
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-12 grid-cols-2 gap-3 items-end">
                                    <label class="md:col-span-3 col-span-2">
                                        <span class="block text-xs text-gray-600 mb-1">Layout name</span>
                                        <input id="sl_name"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50/50"
                                            placeholder="e.g., 2+2 Coach 45 seats"
                                            value="{{ json_decode($bus->seate_json)->name ?? 'Untitled Layout' }}" />
                                    </label>

                                    <label class="md:col-span-2 col-span-1">
                                        <span class="block text-xs text-gray-600 mb-1">Rows</span>
                                        <input id="sl_rows" type="number" min="1"
                                            value="{{ json_decode($bus->seate_json)->rows ?? 10 }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50/50" />
                                    </label>

                                    <label class="md:col-span-2 col-span-1">
                                        <span class="block text-xs text-gray-600 mb-1">Cols</span>
                                        <input id="sl_cols" type="number" min="1"
                                            value="{{ json_decode($bus->seate_json)->cols ?? 4 }}"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50/50" />
                                    </label>

                                    <div class="md:col-span-5 col-span-2 flex gap-2 flex-wrap">
                                        <button type="button" id="sl_apply"
                                            class="rounded-lg px-3 py-2 text-white bg-teal-600 hover:bg-teal-700">Apply</button>
                                        <button type="button" id="sl_autofill"
                                            class="rounded-lg px-3 py-2 text-white bg-blue-600 hover:bg-blue-700" title="Fill all empty cells with seats">Auto Fill</button>
                                        <button type="button" id="sl_add"
                                            class="rounded-lg px-3 py-2 text-white bg-emerald-600 hover:bg-emerald-700">Add
                                            Seat</button>
                                        <button type="button" id="sl_dup"
                                            class="rounded-lg px-3 py-2 text-white bg-amber-600 hover:bg-amber-700">Duplicate</button>
                                        <button type="button" id="sl_ren"
                                            class="rounded-lg px-3 py-2 text-white bg-indigo-600 hover:bg-indigo-700">Rename</button>
                                        <button type="button" id="sl_del"
                                            class="rounded-lg px-3 py-2 text-white bg-rose-600 hover:bg-rose-700">Delete</button>
                                        <button type="button" id="sl_aisle"
                                            class="rounded-lg px-3 py-2 bg-gray-200 hover:bg-gray-300">Aisle</button>
                                    </div>
                                </div>

                                <div class="mt-4 bg-gray-50 rounded-xl p-3 border border-gray-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-gray-700">Edit Existing Layout</h4>
                                        <div id="sl_mode" class="text-xs px-2 py-1 rounded-full bg-gray-200">Mode: Edit
                                        </div>
                                    </div>
                                    <div id="sl_grid"
                                        class="relative w-full aspect-[3/4] bg-white rounded-lg overflow-hidden"></div>
                                    <p class="mt-2 text-xs text-gray-500">
                                        Tip: Click seats to select (Ctrl/⌘ for multi). Drag to move. Double-click to rename.
                                    </p>
                                </div>
                            @else
                                {{-- Show sketch/drawing interface for new layout --}}
                                <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-amber-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-amber-800 font-medium">
                                            @if ($bus->seate_json && !empty($bus->seate_json))
                                                Update or create a new seat layout using the sketch tool below.
                                            @else
                                                No seat layout found. Create a new layout using the sketch tool below.
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-12 grid-cols-2 gap-3 items-end">
                                    <label class="md:col-span-3 col-span-2">
                                        <span class="block text-xs text-gray-600 mb-1">Layout name</span>
                                        <input id="sl_name"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50/50"
                                            placeholder="e.g., 2+2 Coach 45 seats" value="Untitled Layout" />
                                    </label>

                                    <label class="md:col-span-2 col-span-1">
                                        <span class="block text-xs text-gray-600 mb-1">Rows</span>
                                        <input id="sl_rows" type="number" min="1" value="10"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50/50" />
                                    </label>

                                    <label class="md:col-span-2 col-span-1">
                                        <span class="block text-xs text-gray-600 mb-1">Cols</span>
                                        <input id="sl_cols" type="number" min="1" value="4"
                                            class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50/50" />
                                    </label>

                                    <div class="md:col-span-5 col-span-2 flex gap-2 flex-wrap">
                                        <button type="button" id="sl_apply"
                                            class="rounded-lg px-3 py-2 text-white bg-teal-600 hover:bg-teal-700">Apply</button>
                                        <button type="button" id="sl_add"
                                            class="rounded-lg px-3 py-2 text-white bg-emerald-600 hover:bg-emerald-700">Add
                                            Seat</button>
                                        <button type="button" id="sl_dup"
                                            class="rounded-lg px-3 py-2 text-white bg-amber-600 hover:bg-amber-700">Duplicate</button>
                                        <button type="button" id="sl_ren"
                                            class="rounded-lg px-3 py-2 text-white bg-indigo-600 hover:bg-indigo-700">Rename</button>
                                        <button type="button" id="sl_del"
                                            class="rounded-lg px-3 py-2 text-white bg-rose-600 hover:bg-rose-700">Delete</button>
                                        <button type="button" id="sl_aisle"
                                            class="rounded-lg px-3 py-2 bg-gray-200 hover:bg-gray-300">Aisle</button>
                                    </div>
                                </div>

                                <div class="mt-4 bg-gray-50 rounded-xl p-3 border border-gray-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-gray-700">Create New Layout</h4>
                                        <div id="sl_mode" class="text-xs px-2 py-1 rounded-full bg-amber-200">Mode:
                                            Create
                                        </div>
                                    </div>
                                    <div id="sl_grid"
                                        class="relative w-full aspect-[3/4] bg-white rounded-lg overflow-hidden"></div>
                                    <p class="mt-2 text-xs text-gray-500">
                                        Tip: Click seats to select (Ctrl/⌘ for multi). Drag to move. Double-click to rename.
                                    </p>
                                </div>
                            @endif
                        </fieldset>

                        {{-- ===================== ACTIONS ===================== --}}
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-200"
                                onclick="window.location.href='{{ route('buses') }}'">{{ __('vender/mybus.cancel') }}</button>
                            <button type="reset"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-200"
                                onclick="return confirm('{{ __('vender/mybus.confirm_reset_form') }}')">{{ __('vender/mybus.reset_form') }}</button>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-all duration-200">{{ __('vender/mybus.update_bus') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== Map Modal ===================== --}}
    <div id="mapModal"
        class="fixed inset-0 bg-black/50 hidden z-[1001] flex items-center justify-center transition-opacity duration-300"
        aria-labelledby="mapModalLabel" aria-hidden="true">
        <div
            class="bg-white rounded-2xl shadow-xl w-full max-w-xl mx-4 max-h-[80vh] overflow-y-auto transform transition-all duration-300">
            <div
                class="bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-t-2xl p-4 flex justify-between items-center sticky top-0 z-10">
                <h5 class="text-lg font-semibold" id="mapModalLabel">{{ __('vender/mybus.select_route_on_map') }}</h5>
                <button type="button" class="text-white hover:text-gray-300 focus:outline-none"
                    onclick="closeMapModal()">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label for="start"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.start_location') }}</label>
                    <input type="text" id="start" placeholder="{{ __('vender/mybus.enter_place_name') }}"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200">
                </div>
                <div class="mb-4">
                    <label for="end"
                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('vender/mybus.end_location') }}</label>
                    <input type="text" id="end" placeholder="{{ __('vender/mybus.enter_place_name') }}"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:ring-2 focus:ring-teal-400 focus:border-teal-500 bg-gray-50/50 text-gray-900 placeholder-gray-400 transition-all duration-200">
                </div>
                <div class="default-points">
                    <strong class="text-sm text-gray-700 mr-2">{{ __('vender/mybus.quick_points') }}</strong>
                    <span class="point-btn" data-point="Nairobi, Kenya">Nairobi</span>
                    <span class="point-btn" data-point="Mombasa, Kenya">Mombasa</span>
                    <span class="point-btn" data-point="Kisumu, Kenya">Kisumu</span>
                    <span class="point-btn" data-point="Nakuru, Kenya">Nakuru</span>
                    <span class="point-btn" data-point="Eldoret, Kenya">Eldoret</span>
                </div>
                <div class="flex gap-2 mb-4">
                    <button type="button" id="calculate"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-all duration-200">{{ __('vender/mybus.calculate_distance') }}</button>
                    <button type="button" id="clear"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-200">{{ __('vender/mybus.clear_points') }}</button>
                </div>
                <div id="result"></div>
                <div id="map" class="mt-3 rounded-lg"></div>
            </div>
            <div class="p-4 flex justify-end gap-2 border-t border-gray-200">
                <button type="button"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-all duration-200"
                    onclick="closeMapModal()">{{ __('vender/mybus.close') }}</button>
                <button type="button" id="confirmRoute"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-teal-600 rounded-lg hover:bg-teal-700 transition-all duration-200">{{ __('vender/mybus.confirm_route') }}</button>
            </div>
        </div>
    </div>

    {{-- ===================== Scripts ===================== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize flatpickr for time inputs
            document.querySelectorAll('.timepicker').forEach(function(el) {
                flatpickr(el, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true
                });
            });

            const translations = {
                no_results_found: "{{ __('vender/mybus.no_results_found') }}",
                geocoding_error: "{{ __('vender/mybus.geocoding_error') }}",
                select_input_first: "{{ __('vender/mybus.select_input_first') }}",
                invalid_start_coords: "{{ __('vender/mybus.invalid_start_coords') }}",
                invalid_end_coords: "{{ __('vender/mybus.invalid_end_coords') }}",
                calculate_distance_first: "{{ __('vender/mybus.calculate_distance_first') }}",
                road_distance: "{{ __('vender/mybus.road_distance') }}",
                km: "{{ __('vender/mybus.km') }}",
                meters: "{{ __('vender/mybus.meters') }}",
                estimated_travel_time: "{{ __('vender/mybus.estimated_travel_time') }}",
                min: "{{ __('vender/mybus.min') }}",
                sec: "{{ __('vender/mybus.sec') }}",
                start: "{{ __('vender/mybus.start') }}",
                end: "{{ __('vender/mybus.end') }}",
                routing_fallback: "{{ __('vender/mybus.routing_fallback') }}"
            };

            let map, startMarker, endMarker, routingControl, activeInput;
            let calculatedDistance = null;

            function initializeMap() {
                if (map) return;
                map = L.map('map').setView([-1.286389, 36.817223], 6);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
            }

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
                    }).addTo(map).on('dragend', function() {
                        const pos = marker.getLatLng();
                        document.getElementById(inputId).value =
                            `${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`;
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
                const R = 6371, dLat = (lat2 - lat1) * Math.PI / 180, dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)*Math.sin(dLon/2);
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            }

            function setDistanceResult(calculatedDistanceKm, distanceMeters, durationSec, s, e, isFallback) {
                calculatedDistance = calculatedDistanceKm;
                const res = document.getElementById('result');
                if (res) {
                    res.style.display = 'block';
                    const durationStr = durationSec != null ? Math.floor(durationSec/60) + ' ' + translations.min + ' ' + (durationSec%60) + ' ' + translations.sec : '–';
                    const distStr = distanceMeters != null ? distanceMeters.toFixed(0) : (calculatedDistanceKm * 1000).toFixed(0);
                    res.innerHTML = (isFallback ? '<p class="text-amber-700 text-sm mb-2">' + (translations.routing_fallback || 'Route service unavailable; showing straight-line distance.') + '</p>' : '') + '<strong>' + translations.road_distance + '</strong> ' + calculatedDistanceKm + ' ' + translations.km + ' (' + distStr + ' ' + translations.meters + ')<br><strong>' + translations.estimated_travel_time + '</strong> ' + durationStr + '<br><strong>' + translations.start + '</strong> ' + s.lat.toFixed(6) + ', ' + s.lng.toFixed(6) + '<br><strong>' + translations.end + '</strong> ' + e.lat.toFixed(6) + ', ' + e.lng.toFixed(6);
                }
            }

            function calculateDistance() {
                if (!startMarker || !endMarker) return;
                const s = startMarker.getLatLng();
                const e = endMarker.getLatLng();
                if (routingControl) { map.removeControl(routingControl); routingControl = null; }

                routingControl = L.Routing.control({
                    waypoints: [L.latLng(s.lat, s.lng), L.latLng(e.lat, e.lng)],
                    routeWhileDragging: true,
                    showAlternatives: false,
                    addWaypoints: false,
                    draggableWaypoints: false,
                    fitSelectedRoutes: true,
                    lineOptions: { styles: [{ color: 'blue', opacity: 0.7, weight: 5 }] },
                    createMarker: () => null
                }).addTo(map);

                routingControl.on('routesfound', function(ev) {
                    const distance = ev.routes[0].summary.totalDistance;
                    const duration = ev.routes[0].summary.totalTime;
                    setDistanceResult((distance/1000).toFixed(2), distance, duration, s, e, false);
                });

                routingControl.on('routingerror', function() {
                    map.removeControl(routingControl);
                    routingControl = null;
                    const fallbackKm = haversineKm(s.lat, s.lng, e.lat, e.lng).toFixed(2);
                    setDistanceResult(fallbackKm, null, null, s, e, true);
                });

                map.fitBounds(L.latLngBounds(s, e));
            }

            function geocodePlace(place, inputId, retryCount) {
                retryCount = retryCount || 0;
                if (!place) return Promise.resolve();
                return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(place) + '&limit=1', {
                    headers: { 'Accept': 'application/json', 'User-Agent': 'HighlinkRoundTrip/1.0' }
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat), lon = parseFloat(data[0].lon);
                            const ll = L.latLng(lat, lon);
                            document.getElementById(inputId).value = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
                            if (inputId === 'start') startMarker = updateMarker(startMarker, ll, 'start');
                            else endMarker = updateMarker(endMarker, ll, 'end');
                            if (startMarker && endMarker) calculateDistance();
                            else map.setView(ll, 12);
                        } else {
                            alert(translations.no_results_found.replace('{place}', place));
                        }
                    })
                    .catch(() => {
                        if (retryCount < 1) return new Promise(r => setTimeout(r, 1100)).then(() => geocodePlace(place, inputId, 1));
                        alert(translations.geocoding_error);
                    });
            }

            function handleInputChange(inputId) {
                const input = document.getElementById(inputId);
                input.addEventListener('change', function() {
                    const v = this.value.trim();
                    if (!v.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) geocodePlace(v, inputId);
                });
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const v = this.value.trim();
                        if (!v.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) geocodePlace(v, inputId);
                    }
                });
            }

            window.openMapModal = function() {
                const modal = document.getElementById('mapModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => {
                    initializeMap();
                    map.invalidateSize();
                    const rf = document.getElementById('route_from').value;
                    const rt = document.getElementById('route_to').value;
                    if (rf) document.getElementById('start').value = rf;
                    if (rt) document.getElementById('end').value = rt;
                    if (rf && !rf.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) geocodePlace(rf, 'start');
                    if (rt && !rt.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) geocodePlace(rt, 'end');
                }, 10);
            };
            window.closeMapModal = function() {
                const modal = document.getElementById('mapModal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.getElementById('start').addEventListener('focus', function() {
                activeInput = 'start';
                this.classList.add('active-input');
                document.getElementById('end').classList.remove('active-input');
            });
            document.getElementById('end').addEventListener('focus', function() {
                activeInput = 'end';
                this.classList.add('active-input');
                document.getElementById('start').classList.remove('active-input');
            });

            document.addEventListener('click', function(e) {
                if (map && activeInput && e.target.closest && e.target.closest('#map')) {
                    const ll = map.mouseEventToLatLng(e);
                    document.getElementById(activeInput).value =
                        `${ll.lat.toFixed(6)}, ${ll.lng.toFixed(6)}`;
                    if (activeInput === 'start') startMarker = updateMarker(startMarker, ll, 'start');
                    else endMarker = updateMarker(endMarker, ll, 'end');
                    if (startMarker && endMarker) calculateDistance();
                }
            });

            document.getElementById('calculate').addEventListener('click', function() {
                const s = document.getElementById('start').value.trim();
                const e = document.getElementById('end').value.trim();
                const re = /^-?\d+\.\d+,\s*-?\d+\.\d+$/;

                function setStart() {
                    if (!s) return Promise.resolve();
                    if (s.match(re)) {
                        try { const [lat, lon] = s.split(',').map(x => parseFloat(x.trim())); startMarker = updateMarker(startMarker, L.latLng(lat, lon), 'start'); } catch { alert(translations.invalid_start_coords); }
                        return Promise.resolve();
                    }
                    return geocodePlace(s, 'start');
                }
                function setEnd() {
                    if (!e) return Promise.resolve();
                    if (e.match(re)) {
                        try { const [lat, lon] = e.split(',').map(x => parseFloat(x.trim())); endMarker = updateMarker(endMarker, L.latLng(lat, lon), 'end'); } catch { alert(translations.invalid_end_coords); }
                        return Promise.resolve();
                    }
                    return geocodePlace(e, 'end');
                }
                setStart().then(function() { return new Promise(r => setTimeout(r, 1100)); }).then(setEnd).then(function() {
                    if (startMarker && endMarker) { map.invalidateSize(); setTimeout(calculateDistance, 200); }
                });
            });

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
                document.getElementById('start').classList.remove('active-input');
                document.getElementById('end').classList.remove('active-input');
                activeInput = null;
            });

            document.querySelectorAll('.point-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!activeInput) {
                        alert(translations.select_input_first);
                        return;
                    }
                    geocodePlace(this.getAttribute('data-point'), activeInput);
                });
            });

            document.getElementById('confirmRoute').addEventListener('click', function() {
                if (calculatedDistance) {
                    document.getElementById('route_distance').value = calculatedDistance;
                    const s = document.getElementById('start').value;
                    const e = document.getElementById('end').value;
                    if (!s.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) document.getElementById('route_from')
                        .value = s;
                    if (!e.match(/^-?\d+\.\d+,\s*-?\d+\.\d+$/)) document.getElementById('route_to').value =
                        e;
                    closeMapModal();
                } else {
                    alert(translations.calculate_distance_first);
                }
            });

            handleInputChange('start');
            handleInputChange('end');
        });
    </script>

    {{-- ===================== Seat Designer (embedded, self-contained) ===================== --}}
    <script>
        (() => {
            const $ = s => document.querySelector(s);
            const uid = () => (self.crypto?.randomUUID?.() || ('id-' + Math.random().toString(36).slice(2)));
            const key = (r, c) => `${r}-${c}`;

            const gridEl = $('#sl_grid');
            const nameEl = $('#sl_name');
            const rowsEl = $('#sl_rows');
            const colsEl = $('#sl_cols');
            const modeEl = $('#sl_mode');
            const formEl = $('#editBusForm');
            const outEl = $('#seate_json');
            const totalSeatsEl = $('#total_seats');

            const d = {
                layout: {
                    id: null,
                    name: 'Untitled Layout',
                    rows: 10,
                    cols: 4,
                    aisles: [],
                    seats: []
                },
                selected: new Set(),
                aisleMode: false
            };

            // Load existing seat layout if available
            const existingSeatsJson = {!! $bus->seate_json ?? 'null' !!};
            if (existingSeatsJson && existingSeatsJson.seats && existingSeatsJson.seats.length > 0) {
                d.layout = existingSeatsJson;
                // Update UI elements with existing data
                if (existingSeatsJson.name) nameEl.value = existingSeatsJson.name;
                if (existingSeatsJson.rows) rowsEl.value = existingSeatsJson.rows;
                if (existingSeatsJson.cols) colsEl.value = existingSeatsJson.cols;
            } else {
                // Initialize with default values for new layout
                d.layout = {
                    id: null,
                    name: 'Untitled Layout',
                    rows: 10,
                    cols: 4,
                    aisles: [],
                    seats: []
                };
            }

            function seatAt(r, c) {
                return d.layout.seats.find(s => s.row === r && s.col === c);
            }

            function isAisle(r, c) {
                return d.layout.aisles.some(a => a.row === r && a.col === c);
            }

            function setAisle(r, c, val) {
                d.layout.aisles = d.layout.aisles.filter(a => !(a.row === r && a.col === c));
                if (val) d.layout.aisles.push({
                    row: r,
                    col: c
                });
            }

            function renderGrid() {
                const {
                    rows,
                    cols
                } = d.layout;
                gridEl.innerHTML = '';
                gridEl.style.display = 'grid';
                gridEl.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
                gridEl.style.gridTemplateRows = `repeat(${rows}, 1fr)`;

                const cellRefs = {};
                for (let r = 1; r <= rows; r++) {
                    for (let c = 1; c <= cols; c++) {
                        const cell = document.createElement('div');
                        cell.dataset.row = r;
                        cell.dataset.col = c;
                        cell.className = 'sl-grid-cell';
                        if (isAisle(r, c)) cell.classList.add('sl-aisle');
                        cell.addEventListener('click', e => onCellClick(e, r, c));
                        cell.addEventListener('dragover', e => onCellDragOver(e, r, c));
                        cell.addEventListener('dragleave', onCellDragLeave);
                        cell.addEventListener('drop', e => onCellDrop(e, r, c));
                        gridEl.appendChild(cell);
                        cellRefs[key(r, c)] = cell;
                    }
                }

                for (const s of d.layout.seats) {
                    const cell = cellRefs[key(s.row, s.col)];
                    if (!cell) continue;
                    const seat = document.createElement('div');
                    seat.draggable = true;
                    seat.dataset.id = s.id;
                    seat.className = 'sl-seat' + (d.selected.has(s.id) ? ' selected' : '');
                    seat.textContent = s.label;
                    seat.addEventListener('click', e => onSeatClick(e, s.id));
                    seat.addEventListener('dblclick', e => onSeatDblClick(e, s.id));
                    seat.addEventListener('dragstart', e => onSeatDragStart(e, s.id));
                    seat.addEventListener('dragend', onSeatDragEnd);
                    cell.appendChild(seat);
                }
            }

            function refresh() {
                nameEl.value = d.layout.name;
                rowsEl.value = d.layout.rows;
                colsEl.value = d.layout.cols;
                renderGrid();
            }

            function onCellClick(e, r, c) {
                if (d.aisleMode) {
                    if (seatAt(r, c)) return;
                    setAisle(r, c, !isAisle(r, c));
                    renderGrid();
                    return;
                }
                if (!seatAt(r, c)) {
                    d.selected.clear();
                    renderGrid();
                }
            }

            function onSeatClick(e, id) {
                e.stopPropagation();
                const multi = e.metaKey || e.ctrlKey || e.shiftKey;
                if (!multi) d.selected.clear();
                if (d.selected.has(id)) d.selected.delete(id);
                else d.selected.add(id);
                renderGrid();
            }

            function onSeatDblClick(e, id) {
                e.stopPropagation();
                const s = d.layout.seats.find(x => x.id === id);
                if (!s) return;
                const v = prompt('Seat label', s.label);
                if (v && v.trim()) {
                    s.label = v.trim();
                    renderGrid();
                }
            }

            function onSeatDragStart(e, id) {
                e.dataTransfer.setData('text/plain', id);
            }

            function onSeatDragEnd() {
                document.querySelectorAll('.sl-drop-ok,.sl-drop-bad').forEach(el => {
                    el.classList.remove('sl-drop-ok', 'sl-drop-bad');
                    el.style.boxShadow = 'none';
                });
            }

            function onCellDragOver(e, r, c) {
                e.preventDefault();
                const id = e.dataTransfer.getData('text/plain');
                const s = d.layout.seats.find(x => x.id === id);
                const ok = s && !seatAt(r, c) && !isAisle(r, c);
                e.currentTarget.classList.add(ok ? 'sl-drop-ok' : 'sl-drop-bad');
                e.currentTarget.style.boxShadow = ok ? 'inset 0 0 0 4px rgba(34,197,94,0.5)' :
                    'inset 0 0 0 4px rgba(239,68,68,0.5)';
            }

            function onCellDragLeave(e) {
                e.currentTarget.classList.remove('sl-drop-ok', 'sl-drop-bad');
                e.currentTarget.style.boxShadow = 'none';
            }

            function onCellDrop(e, r, c) {
                e.preventDefault();
                onCellDragLeave(e);
                const id = e.dataTransfer.getData('text/plain');
                const s = d.layout.seats.find(x => x.id === id);
                if (!s) return;
                if (seatAt(r, c) || isAisle(r, c)) return;
                s.row = r;
                s.col = c;
                renderGrid();
            }

            function suggestNextLabel() {
                const labels = d.layout.seats.map(s => s.label);
                for (let letter = 65; letter < 91; letter++) {
                    for (let num = 1; num <= 200; num++) {
                        const cand = String.fromCharCode(letter) + num;
                        if (!labels.includes(cand)) return cand;
                    }
                }
                return 'S' + (labels.length + 1);
            }

            function nextLabelVariant(lbl) {
                const m = lbl.match(/^(.*?)(\d+)$/);
                return m ? (m[1] + (parseInt(m[2], 10) + 1)) : (lbl + "'");
            }

            function addSeat() {
                const {
                    rows,
                    cols
                } = d.layout;
                for (let r = 1; r <= rows; r++) {
                    for (let c = 1; c <= cols; c++) {
                        if (!isAisle(r, c) && !seatAt(r, c)) {
                            const id = uid();
                            d.layout.seats.push({
                                id,
                                label: suggestNextLabel(),
                                row: r,
                                col: c
                            });
                            d.selected = new Set([id]);
                            renderGrid();
                            return;
                        }
                    }
                }
                alert('No empty cells available');
            }

            function duplicateSelected() {
                if (d.selected.size === 0) return alert('Select at least one seat');
                const empties = [];
                for (let r = 1; r <= d.layout.rows; r++) {
                    for (let c = 1; c <= d.layout.cols; c++) {
                        if (!isAisle(r, c) && !seatAt(r, c)) empties.push({
                            r,
                            c
                        });
                    }
                }
                if (!empties.length) return alert('No empty space to duplicate into');
                const sel = d.layout.seats.filter(s => d.selected.has(s.id));
                const newIds = [];
                for (const s of sel) {
                    const spot = empties.shift();
                    if (!spot) break;
                    const id = uid();
                    d.layout.seats.push({
                        id,
                        label: nextLabelVariant(s.label),
                        row: spot.r,
                        col: spot.c
                    });
                    newIds.push(id);
                }
                d.selected = new Set(newIds);
                renderGrid();
            }

            function renameSelected() {
                if (d.selected.size === 0) return alert('Select seats to rename');
                const base = prompt('New label. Use {n} to auto-number (starts at 1). Example: R{n}');
                if (!base) return;
                const sel = d.layout.seats.filter(s => d.selected.has(s.id));
                sel.sort((a, b) => a.row - b.row || a.col - b.col);
                sel.forEach((s, i) => s.label = base.includes('{n}') ? base.replace('{n}', i + 1) : base);
                renderGrid();
            }

            function deleteSelected() {
                if (d.selected.size === 0) return;
                d.layout.seats = d.layout.seats.filter(s => !d.selected.has(s.id));
                d.selected.clear();
                renderGrid();
            }

            function applyGrid() {
                const r = Math.max(1, parseInt(rowsEl.value || '1', 10));
                const c = Math.max(1, parseInt(colsEl.value || '1', 10));
                d.layout.rows = r;
                d.layout.cols = c;
                d.layout.seats = d.layout.seats.filter(s => s.row <= r && s.col <= c);
                d.layout.aisles = d.layout.aisles.filter(a => a.row <= r && a.col <= c);
                renderGrid();
            }

            function toggleAisle() {
                d.aisleMode = !d.aisleMode;
                modeEl.textContent = 'Mode: ' + (d.aisleMode ? 'Aisle' : 'Edit');
            }

            // Wire UI
            document.getElementById('sl_apply').addEventListener('click', applyGrid);
            document.getElementById('sl_add').addEventListener('click', addSeat);
            document.getElementById('sl_dup').addEventListener('click', duplicateSelected);
            document.getElementById('sl_ren').addEventListener('click', renameSelected);
            document.getElementById('sl_del').addEventListener('click', deleteSelected);
            document.getElementById('sl_aisle').addEventListener('click', toggleAisle);
            document.getElementById('sl_autofill').addEventListener('click', autoFillAll);
            nameEl.addEventListener('input', e => d.layout.name = e.target.value);

            // Auto-fill all empty cells with seats
            function autoFillAll() {
                const { rows, cols } = d.layout;
                let added = 0;
                for (let r = 1; r <= rows; r++) {
                    for (let c = 1; c <= cols; c++) {
                        if (!isAisle(r, c) && !seatAt(r, c)) {
                            const id = uid();
                            d.layout.seats.push({
                                id,
                                label: suggestNextLabel(),
                                row: r,
                                col: c
                            });
                            added++;
                        }
                    }
                }
                if (added > 0) {
                    d.selected = new Set();
                    renderGrid();
                } else {
                    alert('No empty cells available (all cells are either seats or aisles)');
                }
            }

            // Init
            (function init() {
                refresh();
            })(); // Call refresh to load existing data

            // Submit: put JSON into hidden input
            formEl.addEventListener('submit', function() {
                const want = parseInt(totalSeatsEl?.value || '0', 10);
                const have = d.layout.seats.length;
                if (want > 0 && want !== have) {
                    console.warn(
                        `Total seats (${want}) != seats in layout (${have}). The form will still submit.`);
                }
                document.getElementById('seate_json').value = JSON.stringify(d.layout);
            });
        })();
    </script>
@endsection
