 
<div class="px-4">
    <div class="text-center mb-6 pt-4">
        <h4 class="text-white text-xl font-semibold tracking-tight">{{ trans('vendor_sidebar.bus_owner_panel') }}</h4>
        <hr class="border-gray-300/50 mt-2">
    </div>
    
     
     <ul class="space-y-2">
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['DASHBOARD']))
            <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['DASHBOARD']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['DASHBOARD']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                {{ trans('vendor_sidebar.dashboard') }}
            </a>
        </li>
        @endif
        <!-- Bus Management Section -->
        <li>
            <p class="text-xs text-white/70 uppercase font-semibold mt-4 mb-2">{{ trans('vendor_sidebar.bus_management') }}</p>
        </li>
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['BUSES']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['BUSES']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['BUSES']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM4 8h16v2H4V8zm0 4h16v6H4v-6z"/>
                </svg>
                {{ trans('vendor_sidebar.my_buses') }}
            </a>
        </li>
        @endif
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['ROUTES']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['ROUTES']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['ROUTES']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>
                </svg>
                {{ trans('vendor_sidebar.manage_routes') }}
            </a>
        </li>
        @endif
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['SCHEDULES']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['SCHEDULES']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['SCHEDULES']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17 3H7c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H8v-2h8v2zm0-4H8v-2h8v2zm-8-6V7h8v2H8z"/>
                </svg>
                {{ trans('vendor_sidebar.schedule') }}
            </a>
        </li>
        @endif
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['CITIES']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['CITIES']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['CITIES']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z"/>
                </svg>
                {{ trans('vendor_sidebar.cities') }}
            </a>
        </li>
        @endif
        <!-- Booking & Sales Section -->
        <li>
            <p class="text-xs text-white/70 uppercase font-semibold mt-4 mb-2">{{ trans('vendor_sidebar.booking_sales') }}</p>
        </li>
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['BOOKING_HISTORY']))
        <li>
            <div class="relative">
                <button class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 w-full text-left {{ request()->routeIs(\App\Models\Access::BUS['BOOKING_HISTORY']) ? 'bg-teal-600' : '' }}"
                        aria-expanded="false" 
                        aria-controls="booking-history-collapse"
                        onclick="toggleBookingHistoryCollapse()">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                    </svg>
                    {{ trans('vendor_sidebar.booking_history') }}
                    <svg class="w-4 h-4 ml-auto transition-transform duration-200 booking-history-chevron" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7 10l5 5 5-5H7z"/>
                    </svg>
                </button>
                <div id="booking-history-collapse" class="hidden">
                    <ul class="space-y-1 pl-6 mt-1">
                        <li>
                            <a class="block px-3 py-2 text-white text-sm rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->query('period') == 'today' ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['BOOKING_HISTORY']) }}?period=today">{{ trans('vendor_sidebar.today') }}</a>
                        </li>
                        <li>
                            <a class="block px-3 py-2 text-white text-sm rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->query('period') == 'week' ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['BOOKING_HISTORY']) }}?period=week">{{ trans('vendor_sidebar.week') }}</a>
                        </li>
                        <li>
                            <a class="block px-3 py-2 text-white text-sm rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->query('period') == 'month' ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['BOOKING_HISTORY']) }}?period=month">{{ trans('vendor_sidebar.month') }}</a>
                        </li>
                        <li>
                            <a class="block px-3 py-2 text-white text-sm rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->query('period') == 'year' ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['BOOKING_HISTORY']) }}?period=year">{{ trans('vendor_sidebar.year') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </li>
        @endif
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['BOOKING_HISTORY']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs('booking.transfer.form') ? 'bg-teal-600' : '' }}" href="{{ route('booking.transfer.form') }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2zm-2 6h14V5H5v16z"/>
                </svg>
                {{ trans('vendor_sidebar.transfer_booking') }}
            </a>
        </li>
        @endif
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['RESAVED_TICKETS']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['RESAVED_TICKETS']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['RESAVED_TICKETS']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                 {{ trans('vendor_sidebar.resaved_tickets') }}
            </a>
        </li>
        @endif
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['EARNINGS_PAYMENTS']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['EARNINGS_PAYMENTS']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['EARNINGS_PAYMENTS']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11 2h2v2h8v16H3V4h8V2zm2 2v2h-2V4h2zm-2 6h2v6h-2v-6zm-4 0h2v6H7v-6zm8 0h2v6h-2v-6z"/>
                </svg>
                {{ trans('vendor_sidebar.earnings_payments') }}
            </a>
        </li>
        @endif

        <!-- Account Management Section -->
        <li>
            <p class="text-xs text-white/70 uppercase font-semibold mt-4 mb-2">{{ trans('vendor_sidebar.account') }}</p>
        </li>
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['LOCAL_BUS_OWNERS']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['LOCAL_BUS_OWNERS']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['LOCAL_BUS_OWNERS']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                {{ trans('vendor_sidebar.local_bus_owners') }}
            </a>
        </li>
        @endif 
       
        @if(auth()->user()->hasAccessTo(\App\Models\Access::BUS['PROFILE']))
        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 {{ request()->routeIs(\App\Models\Access::BUS['PROFILE']) ? 'bg-teal-600' : '' }}" href="{{ route(\App\Models\Access::BUS['PROFILE']) }}">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                {{ trans('vendor_sidebar.profile') }}
            </a>
        </li>
        @endif
        {{-- Show logout button to all users (bus owners and local bus owners) --}}
        <li>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-teal-700 transition-all duration-200 w-full text-left {{ request()->routeIs('logout') ? 'bg-teal-600' : '' }}">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    {{ trans('vendor_sidebar.logout') }}
                </button>
            </form>
        </li>
    </ul> 
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Namespace for booking history collapse to avoid conflicts
        const bookingHistoryToggle = document.querySelector('button[aria-controls="booking-history-collapse"]');
        const bookingHistoryCollapse = document.getElementById('booking-history-collapse');
        const bookingHistoryChevron = document.querySelector('.booking-history-chevron');

        window.toggleBookingHistoryCollapse = function() {
            const isExpanded = bookingHistoryToggle.getAttribute('aria-expanded') === 'true';
            bookingHistoryToggle.setAttribute('aria-expanded', !isExpanded);
            bookingHistoryCollapse.classList.toggle('hidden');
            bookingHistoryChevron.classList.toggle('rotate-180');
        };

        // Ensure collapse is initialized based on active route
        if ({{ request()->routeIs('history', 'booking.transfer.form') ? 'true' : 'false' }}) {
            bookingHistoryToggle.setAttribute('aria-expanded', 'true');
            bookingHistoryCollapse.classList.remove('hidden');
            bookingHistoryChevron.classList.add('rotate-180');
        }
    });
</script>
