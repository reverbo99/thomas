@extends('customer.app')

@section('content')
    <!-- Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        /* Light-themed Select2 */
        .select2-container--default .select2-selection--single {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            height: 40px;
            color: #1f2937;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1f2937;
            line-height: 40px;
            padding-left: 0.75rem;
            padding-right: 2rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 8px;
        }

        .select2-dropdown {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            color: #1f2937;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5;
            color: white;
        }

        /* Icon and input styling */
        .fa-map-marker-alt,
        .fa-bus,
        .fa-calendar-day {
            pointer-events: none;
            z-index: 10;
        }

        input[type="date"] {
            position: relative;
            z-index: 1;
            background: #f9fafb;
            color: #1f2937;
            appearance: auto;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        /* DataTable styling */
        #busTable {
            background-color: #ffffff;
            color: #1f2937;
        }

        #busTable th,
        #busTable td {
            border-color: #e5e7eb;
        }

        #busTable thead {
            background-color: #f3f4f6;
        }

        #busTable tbody tr:hover {
            background-color: #f9fafb;
        }

        /* Modal styling */
        dialog {
            background-color: #ffffff;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }
    </style>

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">{{ __('customer/busroot.search_bus_schedules') }}</h2>

            <!-- Tab Navigation -->
            <div class="flex space-x-2 mb-6 bg-gray-100 rounded-lg p-1">
                <button
                    class="search-tab flex-1 py-2 px-4 rounded-lg bg-indigo-500 text-white font-medium text-sm uppercase transition-colors duration-200"
                    data-tab="one-way">{{ __('all.route_tab') }}</button>
                <button
                    class="search-tab flex-1 py-2 px-4 rounded-lg text-gray-500 font-medium text-sm uppercase transition-colors duration-200"
                    data-tab="bus-name">{{ __('customer/busroot.bus_name') }}</button>
            </div>

            <!-- One Way Form -->
            {{-- <form action="{{ route('vender.route.by_route_search') }}" method="GET" class="search-form" id="one-way-form"> --}}
            <form action="{{ route('customer.round.trip.by.routesearch') }}" method="GET" class="search-form" id="one-way-form">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">{{ __('customer/busroot.from') }}</label>
                        <div class="relative">
                            <select name="departure_city" id="departure_city"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800">
                                <option value="">{{ __('customer/busroot.select_departure_city') }}</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}"
                                        {{ old('departure_city') == $city->id ? 'selected' : '' }}>{{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="fas fa-map-marker-alt absolute right-3 top-3 text-gray-500"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">{{ __('customer/busroot.to') }}</label>
                        <div class="relative">
                            <select name="arrival_city" id="arrival_city"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800">
                                <option value="">{{ __('customer/busroot.select_arrival_city') }}</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}"
                                        {{ old('arrival_city') == $city->id ? 'selected' : '' }}>{{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                            <i class="fas fa-map-marker-alt absolute right-3 top-3 text-gray-500"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">{{ __('customer/busroot.date') }}</label>
                        <div class="relative">
                            <input type="date" name="departure_date" id="departure_date"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800">
                            <i class="fas fa-calendar-day absolute right-3 top-3 text-gray-500"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">{{ __('customer/busroot.bus_class') }}</label>
                        <div class="relative">
                            <select name="bus_type" id="bus_type"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800">
                                <option value="any">{{ __('customer/busroot.any') }}</option>
                                <option value="10" {{ old('bus_type') == '10' ? 'selected' : '' }}>{{ __('all.luxury') }}</option>
                                <option value="20" {{ old('bus_type') == '20' ? 'selected' : '' }}>{{ __('all.upper_semiluxury') }}</option>
                                <option value="30" {{ old('bus_type') == '30' ? 'selected' : '' }}>{{ __('all.lower_semiluxury') }}</option>
                                <option value="40" {{ old('bus_type') == '40' ? 'selected' : '' }}>{{ __('all.ordinary') }}</option>
                            </select>
                            <i class="fas fa-bus absolute right-3 top-3 text-gray-500"></i>
                        </div>
                    </div>
                </div>
                <button type="submit"
                    class="mt-4 w-full bg-indigo-500 hover:bg-indigo-600 text-white py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-search mr-2"></i> {{ __('customer/busroot.find_buses') }}
                </button>
            </form>

            <!-- Bus Name Form -->
            {{-- <form action="{{ route('vender.route') }}" method="GET" class="search-form hidden" id="bus-name-form"> --}}
            <form action="{{ route('round.trip.by.bus') }}" method="GET" class="search-form hidden" id="bus-name-form">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-gray-600 text-sm mb-1">{{ __('customer/busroot.bus_name') }}</label>
                        <div class="relative">
                            <select name="bus_id" id="bus_name"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-800">
                                <option value="">{{ __('customer/busroot.select_bus_name') }}</option>
                                @forelse (App\Models\Campany::all() as $bus)
                                    <option value="{{ $bus->id }}"
                                        {{ request('bus_name') == $bus->id ? 'selected' : '' }}>{{ $bus->name ?? 'N/A' }}
                                    </option>
                                @empty
                                    <option>{{ __('customer/busroot.no_companies_found') }}</option>
                                @endforelse
                            </select>
                            <i class="fas fa-bus absolute right-3 top-3 text-gray-500"></i>
                        </div>
                    </div>
                </div>
                <button type="submit"
                    class="mt-4 w-full bg-indigo-500 hover:bg-indigo-600 text-white py-2 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-search mr-2"></i> {{ __('customer/busroot.find_buses') }}
                </button>
            </form>

            <a href="{{ route('customer.by_route') }}"
                class="text-indigo-500 hover:text-indigo-600 mt-4 inline-block transition-colors duration-200">{{ __('customer/busroot.search_by_route') }}</a>
        </div>

        @if (!empty($busList))
            <div class="mt-6 bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('customer/busroot.bus_schedules') }}</h2>
                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-md text-sm">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">{{ session('error') }}</div>
                @endif
                <div class="overflow-x-auto">
                    <table id="busTable" class="w-full text-sm text-gray-100">
                        <thead class="bg-gray-100 text-xs uppercase text-gray-500 font-semibold">
                            <tr>
                                <th class="px-4 py-3 text-left">{{ __('customer/busroot.no') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('customer/busroot.bus_number') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('customer/busroot.from') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('customer/busroot.to') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('customer/busroot.time') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('customer/busroot.schedule_date') }}</th>
                                <th class="px-4 py-3 text-left">{{ __('customer/busroot.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($busList as $bus)
                                @if (!empty($bus['schedules']))
                                    @foreach ($bus['schedules'] as $schedule)
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors duration-150"
                                            data-bus="{{ $bus['bus_number'] ?? 'N/A' }}"
                                            data-from="{{ $schedule['from'] ?? 'N/A' }}"
                                            data-to="{{ $schedule['to'] ?? 'N/A' }}"
                                            data-time="{{ $schedule['start'] ?? 'N/A' }} -> {{ $schedule['end'] ?? 'N/A' }}"
                                            data-date="{{ $schedule['schedule_date'] ?? 'N/A' }}">
                                            <td class="px-4 py-3">{{ $loop->iteration }}</td>
                                            <td class="px-4 py-3">{{ $bus['bus_number'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ $schedule['from'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ $schedule['to'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ $schedule['start'] ?? 'N/A' }} ->
                                                {{ $schedule['end'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3">{{ $schedule['schedule_date'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3">
                                                <form action="{{ route('round.trip.schedule') }}" method="GET">
                                                    @csrf
                                                    <input type="hidden" name="departure_city"
                                                        value="{{ App\Models\City::where('name', $schedule['from'])->value('id') ?? '' }}">
                                                    <input type="hidden" name="arrival_city"
                                                        value="{{ App\Models\City::where('name', $schedule['to'])->value('id') ?? '' }}">
                                                    <input type="hidden" name="departure_date"
                                                        value="{{ $schedule['schedule_date'] ?? '' }}">
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-1 bg-indigo-500 text-white rounded-md hover:bg-indigo-600 transition-colors">
                                                        <i class="fas fa-ticket-alt mr-1"></i>
                                                        {{ __('customer/busroot.book') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-3">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3">{{ $bus['bus_number'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3">N/A</td>
                                        <td class="px-4 py-3">N/A</td>
                                        <td class="px-4 py-3">N/A</td>
                                        <td class="px-4 py-3">N/A</td>
                                        <td class="px-4 py-3">
                                            <span class="text-gray-500">{{ __('customer/busroot.no_schedule') }}</span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Modal -->
        <dialog id="scheduleModal" class="p-6 rounded-lg max-w-lg w-full">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">{{ __('customer/busroot.schedule_details') }}</h2>
                <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-2 text-gray-700">
                <p><strong>{{ __('customer/busroot.bus_number') }}:</strong> <span id="modal-bus"></span></p>
                <p><strong>{{ __('customer/busroot.from') }}:</strong> <span id="modal-from"></span></p>
                <p><strong>{{ __('customer/busroot.to') }}:</strong> <span id="modal-to"></span></p>
                <p><strong>{{ __('customer/busroot.time') }}:</strong> <span id="modal-time"></span></p>
                <p><strong>{{ __('customer/busroot.schedule_date') }}:</strong> <span id="modal-date"></span></p>
            </div>
            <div class="mt-6 flex justify-end">
                <button id="closeModalBtn"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">{{ __('customer/busroot.close') }}</button>
            </div>
        </dialog>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#departure_city, #arrival_city, #bus_name, #bus_type').select2({
                placeholder: "{{ __('customer/busroot.select_departure_city') }}", // Using a translation as a placeholder
                allowClear: true,
                width: '100%'
            });

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            $('#departure_date').attr('min', today);

            // Tab switching
            $('.search-tab').click(function() {
                $('.search-tab').removeClass('bg-indigo-500 text-white').addClass('text-gray-500');
                $(this).addClass('bg-indigo-500 text-white').removeClass('text-gray-500');
                $('.search-form').addClass('hidden');
                $(`#${$(this).data('tab')}-form`).removeClass('hidden');
            });

            // Ensure date input is clickable
            $('#departure_date').on('click', function() {
                $(this).focus();
            });

            // Initialize DataTable
            $('#busTable').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                language: {
                    emptyTable: "{{ __('customer/busroot.no_buses_available') }}"
                }
            });

            // Modal logic
            $('tbody tr').on('click', function(e) {
                if ($(e.target).closest('button, a').length) return;
                const row = $(this);
                if (row.find('td').eq(2).text() !== 'N/A') {
                    $('#modal-bus').text(row.data('bus'));
                    $('#modal-from').text(row.data('from'));
                    $('#modal-to').text(row.data('to'));
                    $('#modal-time').text(row.data('time'));
                    $('#modal-date').text(row.data('date'));
                    document.getElementById('scheduleModal').showModal();
                }
            });

            $('#closeModal, #closeModalBtn').on('click', function() {
                document.getElementById('scheduleModal').close();
            });
        });
    </script>
@endsection
