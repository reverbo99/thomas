@extends('system.app')

@section('title', __('system.pages.bus_routes_schedule'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Card Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <svg class="h-5 w-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                </svg>
                {{ __('system.pages.bus_routes_schedule') }}
            </h2>
            <div class="mt-3 sm:mt-0 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" id="searchInput" placeholder="{{ __('system.pages.search_routes') }}"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table id="busesTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="number">
                            <div class="flex items-center">
                                No
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="text">
                            <div class="flex items-center">
                                Company
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="text">
                            <div class="flex items-center">
                                Bus Number
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="text">
                            <div class="flex items-center">
                                Main Route
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="text">
                            <div class="flex items-center">
                                Next Route
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="text">
                            <div class="flex items-center">
                                Bus Fee
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="text">
                            <div class="flex items-center">
                                Time (24HRS)
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sortable" data-sort="date">
                            <div class="flex items-center">
                                Date
                                <svg class="ml-1 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('system.pages.seats') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($cars as $car)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $loop->iteration }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $car->campany->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                            {{ $car->bus_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $car->route->from }} → {{ $car->route->to }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $car->schedule->from }} → {{ $car->schedule->to }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="font-medium text-indigo-600">{{ $currency }} {{ convert_money($car->route->price) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @php
                                $route = $car->schedule->route;
                                $timeStart = ($route && $route->route_start) ? $route->route_start : $car->schedule->start;
                                $timeEnd = ($route && $route->route_end) ? $route->route_end : $car->schedule->end;
                                $timeDisplay = '–';
                                if ($timeStart && $timeEnd) {
                                    $timeDisplay = \Carbon\Carbon::parse($timeStart)->format('H:i') . ' → ' . \Carbon\Carbon::parse($timeEnd)->format('H:i');
                                } elseif ($timeStart) {
                                    $timeDisplay = \Carbon\Carbon::parse($timeStart)->format('H:i') . ' → –';
                                } elseif ($timeEnd) {
                                    $timeDisplay = '– → ' . \Carbon\Carbon::parse($timeEnd)->format('H:i');
                                }
                            @endphp
                            {{ $timeDisplay }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-sort-value="{{ $car->schedule->schedule_date ?? '' }}">
                            {{ $car->schedule->schedule_date ? \Carbon\Carbon::parse($car->schedule->schedule_date)->format('d M Y') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php
                                $seatMap = $car->booked_seat_map ?? [];
                                $bookedCount = count($seatMap);
                                $totalSeats = (int) ($car->total_seats ?? 0);
                                $availableCount = max(0, $totalSeats - $bookedCount);
                            @endphp
                            <button type="button"
                                class="view-seats-btn inline-flex items-center px-3 py-1.5 border border-indigo-200 text-indigo-700 bg-indigo-50 rounded-md text-xs font-medium hover:bg-indigo-100 transition"
                                data-bus-number="{{ $car->bus_number }}"
                                data-company="{{ $car->campany->name ?? '' }}"
                                data-route="{{ $car->schedule->from }} → {{ $car->schedule->to }}"
                                data-date="{{ $car->schedule->schedule_date ? \Carbon\Carbon::parse($car->schedule->schedule_date)->format('d M Y') : 'N/A' }}"
                                data-total-seats="{{ $totalSeats }}"
                                data-booked-count="{{ $bookedCount }}"
                                data-available-count="{{ $availableCount }}"
                                data-layout="{{ e($car->seate_json ?? '') }}"
                                data-booked-seats="{{ e(json_encode($seatMap)) }}">
                                <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 7a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2M4 7V5a2 2 0 012-2h12a2 2 0 012 2v2" />
                                </svg>
                                {{ __('system.pages.view_seats') }}
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('system.pages.no_bus_routes') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">There are currently no scheduled bus routes.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500 mb-3 sm:mb-0">
                Showing <span class="font-medium" id="startItem">1</span> to <span class="font-medium" id="endItem">{{ min(10, count($cars)) }}</span> of <span class="font-medium" id="totalItems">{{ count($cars) }}</span> results
            </div>
            <nav class="flex items-center space-x-2" id="pagination">
                <!-- Pagination will be inserted here by JavaScript -->
            </nav>
        </div>
    </div>
</div>

@include('partials.seat_arrangement_modal')

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('busesTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr:not([colspan])'));
    const searchInput = document.getElementById('searchInput');
    const sortableHeaders = table.querySelectorAll('.sortable');
    const itemsPerPage = 10;
    let currentPage = 1;
    let currentSort = {
        column: 7, // Default sort by Date column
        direction: 'desc'
    };

    // Initialize table
    function initTable() {
        updateTable();
        setupPagination();
        updatePagination();
    }

    // Filter and sort rows
    function updateTable() {
        const searchTerm = searchInput.value.toLowerCase();

        // Filter rows
        const filteredRows = rows.filter(row => {
            const cells = row.querySelectorAll('td');
            return Array.from(cells).some(cell => 
                cell.textContent.toLowerCase().includes(searchTerm)
            );
        });

        // Sort rows
        filteredRows.sort((a, b) => {
            const aValue = a.querySelectorAll('td')[currentSort.column].getAttribute('data-sort-value') || 
                          a.querySelectorAll('td')[currentSort.column].textContent;
            const bValue = b.querySelectorAll('td')[currentSort.column].getAttribute('data-sort-value') || 
                          b.querySelectorAll('td')[currentSort.column].textContent;

            if (currentSort.column === 7) { // Date column
                const aDate = aValue && aValue !== 'N/A' ? new Date(aValue) : null;
                const bDate = bValue && bValue !== 'N/A' ? new Date(bValue) : null;

                if (!aDate && !bDate) return 0;
                if (!aDate) return 1;
                if (!bDate) return -1;

                return currentSort.direction === 'asc' ? 
                    aDate - bDate : 
                    bDate - aDate;
            } else {
                return currentSort.direction === 'asc' ? 
                    aValue.localeCompare(bValue) : 
                    bValue.localeCompare(aValue);
            }
        });

        // Clear existing rows
        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }

        // Paginate and add filtered/sorted rows
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedRows = filteredRows.slice(startIndex, endIndex);

        paginatedRows.forEach(row => tbody.appendChild(row));

        // Update info
        document.getElementById('startItem').textContent = filteredRows.length > 0 ? startIndex + 1 : 0;
        document.getElementById('endItem').textContent = Math.min(endIndex, filteredRows.length);
        document.getElementById('totalItems').textContent = filteredRows.length;
    }

    // Setup pagination
    function setupPagination() {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        const totalPages = Math.ceil(rows.length / itemsPerPage);
        
        // Previous button
        const prevButton = document.createElement('button');
        prevButton.className = 'px-3 py-1 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50';
        prevButton.disabled = currentPage === 1;
        prevButton.innerHTML = 'Previous';
        prevButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                updateTable();
                updatePagination();
            }
        });
        pagination.appendChild(prevButton);

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            const firstPageButton = document.createElement('button');
            firstPageButton.className = 'px-3 py-1 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50';
            firstPageButton.textContent = '1';
            firstPageButton.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = 1;
                updateTable();
                updatePagination();
            });
            pagination.appendChild(firstPageButton);
            
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'px-3 py-1 text-sm text-gray-500';
                ellipsis.textContent = '...';
                pagination.appendChild(ellipsis);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('button');
            pageButton.className = `px-3 py-1 rounded-md border text-sm font-medium ${i === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50'}`;
            pageButton.textContent = i;
            pageButton.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = i;
                updateTable();
                updatePagination();
            });
            pagination.appendChild(pageButton);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'px-3 py-1 text-sm text-gray-500';
                ellipsis.textContent = '...';
                pagination.appendChild(ellipsis);
            }
            
            const lastPageButton = document.createElement('button');
            lastPageButton.className = 'px-3 py-1 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50';
            lastPageButton.textContent = totalPages;
            lastPageButton.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = totalPages;
                updateTable();
                updatePagination();
            });
            pagination.appendChild(lastPageButton);
        }

        // Next button
        const nextButton = document.createElement('button');
        nextButton.className = 'px-3 py-1 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50';
        nextButton.disabled = currentPage === totalPages;
        nextButton.innerHTML = 'Next';
        nextButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < totalPages) {
                currentPage++;
                updateTable();
                updatePagination();
            }
        });
        pagination.appendChild(nextButton);
    }

    // Update pagination active state
    function updatePagination() {
        setupPagination(); // Rebuild pagination controls
    }

    // Event listeners
    searchInput.addEventListener('input', () => {
        currentPage = 1;
        updateTable();
        updatePagination();
    });

    sortableHeaders.forEach((header, index) => {
        header.addEventListener('click', () => {
            // Update sort direction
            if (currentSort.column === index) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.column = index;
                currentSort.direction = 'desc';
            }

            // Update table
            updateTable();
        });
    });

    // Initialize table
    initTable();
});
</script>
@endsection
