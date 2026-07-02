@extends('system.app')

@section('title', __('system.pages.cancelled_title'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">{{ __('system.pages.cancelled_title') }}</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('system.pages.total_cancelled') }}</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $totalCancelled }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('system.pages.total_amount') }}</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $currency }} {{ convert_money($totalAmount) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('system.pages.today_cancelled') }}</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $todayCancelled }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">{{ __('system.pages.today_amount') }}</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $currency }} {{ convert_money($todayAmount) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Options -->
    <div class="bg-white shadow-md rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ __('system.pages.filter_options') }}</h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('system.cancelled_bookings') }}" 
               class="px-4 py-2 rounded-md text-sm font-medium {{ !request('filter') ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                All Time
            </a>
            <a href="{{ route('system.cancelled_bookings', ['filter' => 'today']) }}" 
               class="px-4 py-2 rounded-md text-sm font-medium {{ request('filter') == 'today' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                Today
            </a>
            <a href="{{ route('system.cancelled_bookings', ['filter' => 'week']) }}" 
               class="px-4 py-2 rounded-md text-sm font-medium {{ request('filter') == 'week' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                This Week
            </a>
            <a href="{{ route('system.cancelled_bookings', ['filter' => 'month']) }}" 
               class="px-4 py-2 rounded-md text-sm font-medium {{ request('filter') == 'month' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                This Month
            </a>
            <a href="{{ route('system.cancelled_bookings', ['filter' => 'year']) }}" 
               class="px-4 py-2 rounded-md text-sm font-medium {{ request('filter') == 'year' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                This Year
            </a>
        </div>
    </div>

    <!-- Cancelled Bookings Table -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-4">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Cancelled Bookings List</h2>
            <div class="overflow-x-auto">
                <table id="cancelledBookingsTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Code</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bus Company</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Travel Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.amount') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cancelled Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($cancelledBookings as $cancelled)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($cancelled->booking)
                                        {{ $cancelled->booking->booking_code }}
                                    @else
                                        <span class="text-red-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($cancelled->booking)
                                        <div>
                                            <div class="font-medium">{{ $cancelled->booking->customer_name ?? 'N/A' }}</div>
                                            <div class="text-gray-500">{{ $cancelled->booking->customer_phone ?? 'N/A' }}</div>
                                        </div>
                                    @else
                                        <span class="text-red-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($cancelled->booking && $cancelled->booking->campany)
                                        {{ $cancelled->booking->campany->name }}
                                    @else
                                        <span class="text-red-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($cancelled->booking && $cancelled->booking->route)
                                        {{ $cancelled->booking->route->from }} → {{ $cancelled->booking->route->to }}
                                    @else
                                        <span class="text-red-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($cancelled->booking)
                                        {{ \Carbon\Carbon::parse($cancelled->booking->travel_date)->format('M d, Y') }}
                                    @else
                                        <span class="text-red-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ $currency }} {{ convert_money(abs((float) $cancelled->amount)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($cancelled->created_at)->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            {{-- Empty row must have 8 <td> cells so DataTables column count matches thead --}}
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500 text-center" colspan="8" data-orderable="false">
                                    <div class="flex flex-col items-center justify-center py-8">
                                        <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-lg font-medium text-gray-500">{{ __('system.pages.no_cancelled_bookings') }}</p>
                                        <p class="text-sm text-gray-400">There are no cancelled bookings to display at the moment.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    var $table = $('#cancelledBookingsTable');
    // Only init DataTable when table has data rows (8 columns). Empty state has 1 row with 1 cell (colspan=8) which causes "Incorrect column count".
    var firstRowCells = $table.find('tbody tr:first td').length;
    if (firstRowCells === 8) {
        $table.DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            order: [[7, 'desc']], // Sort by cancelled date descending by default
            columnDefs: [
                {
                    targets: [0], // First column (row number)
                    orderable: false,
                    searchable: false
                },
                {
                    targets: [6], // Amount column
                    type: 'num-fmt'
                },
                {
                    targets: [7], // Cancelled date column
                    type: 'date'
                }
            ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: 'Export Excel',
                    className: 'bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium'
                },
                {
                    extend: 'pdf',
                    text: 'Export PDF',
                    className: 'bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium'
                }
            ],
            language: {
                search: "Search cancelled bookings:",
                lengthMenu: "Show _MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ cancelled bookings",
                infoEmpty: "No cancelled bookings available",
                infoFiltered: "(filtered from _MAX_ total cancelled bookings)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                },
                emptyTable: ""
            },
            initComplete: function() {
                // Add custom styling to search box
                $('.dataTables_filter input').addClass('ml-2 px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent');
                
                // Add custom styling to length select
                $('.dataTables_length select').addClass('ml-2 px-3 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent');
                
                // Add custom styling to buttons
                $('.dt-buttons').addClass('mb-4');
            }
        });
    }
});
</script>

<style>
/* Custom DataTables styling */
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_processing,
.dataTables_wrapper .dataTables_paginate {
    color: #374151;
    font-size: 14px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.5rem 0.75rem;
    margin-left: 0.25rem;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    background-color: white;
    color: #374151;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background-color: #2563eb;
    border-color: #2563eb;
}

/* Table row hover effect */
#cancelledBookingsTable tbody tr:hover {
    background-color: #f9fafb;
}

/* Custom button styling */
.dt-buttons .dt-button {
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
}
</style>
@endsection
