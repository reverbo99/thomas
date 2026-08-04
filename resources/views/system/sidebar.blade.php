<div class="px-4 py-6 h-screen overflow-y-auto">
    <div class="text-center mb-6">
        <h4 class="text-xl font-semibold text-white">{{ __('system.sidebar.system_panel') }}</h4>
        <hr class="border-gray-600 mt-2">
    </div>

    <ul class="space-y-2">
        <li>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mt-4 mb-2">{{ __('system.sidebar.bus_management') }}</p>
        </li>

        <li>
            <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.index') ? 'bg-blue-500' : '' }}" href="{{ route('system.index') }}">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                {{ __('system.sidebar.dashboard') }}
            </a>
        </li>

        @auth
            @if(auth()->user()->isActive())
                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['BUS_OPERATORS']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.campany') ? 'bg-blue-500' : '' }}" href="{{ route('system.campany') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2H6zm2 2h8v2H8V7zm0 4h8v2H8v-2zm0 4h8v2H8v-2z"></path></svg>
                        {{ __('system.sidebar.bus_operators') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['BUS_SCHEDULE']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.bus_route') ? 'bg-blue-500' : '' }}" href="{{ route('system.bus_route') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2H6zm2 2h8v2H8V7zm0 4h8v2H8v-2zm0 4h8v2H8v-2z"></path></svg>
                        {{ __('system.sidebar.bus_schedule') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['BUSES']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.buses') ? 'bg-blue-500' : '' }}" href="{{ route('system.buses') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2H6zm2 2h8v2H8V7zm0 4h8v2H8v-2zm0 4h8v2H8v-2z"></path></svg>
                        {{ __('system.sidebar.buses') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['CITIES']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.cities') ? 'bg-blue-500' : '' }}" href="{{ route('system.cities') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"></path></svg>
                        {{ __('system.sidebar.cities') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['VENDORS']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.vender') ? 'bg-blue-500' : '' }}" href="{{ route('system.vender') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        {{ __('system.sidebar.vendors') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['DISCOUNTS']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.discount') ? 'bg-blue-500' : '' }}" href="{{ route('system.discount') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 17h.01m9.98-9.98h.01M17 17h.01m-9.99-9.99l10 10"></path></svg>
                        {{ __('system.sidebar.discounts') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['INSURANCE']))
                <li>
                    <div class="relative">
                        <button 
                            type="button"
                            class="bus-system-dropdown-toggle flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors w-full text-left {{ request()->routeIs('bima.index') ? 'bg-blue-500' : '' }}"
                            aria-expanded="false"
                            aria-controls="bus-system-insurance"
                            data-bus-system-toggle="bus-system-insurance"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            {{ __('system.sidebar.insurance_cancelled') }}
                            <svg class="w-4 h-4 ml-auto transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <ul 
                            id="bus-system-insurance"
                            class="bus-system-dropdown-menu hidden absolute left-0 mt-1 w-full bg-gray-700 text-white rounded-lg shadow-lg z-10"
                            role="menu"
                        >
                            <li role="menuitem"><a class="block px-4 py-2 hover:bg-gray-600 rounded-t-lg" href="{{ route('bima.index') }}">{{ __('system.sidebar.insurance_data') }}</a></li>
                            <li role="menuitem"><a class="block px-4 py-2 hover:bg-gray-600 rounded-b-lg" href="{{ route('system.cancelled_bookings') }}">{{ __('system.sidebar.cancelled_bookings') }}</a></li>
                        </ul>
                    </div>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['BOOKING_HISTORY']))
                <li>
                    <div class="relative">
                        <button 
                            type="button"
                            class="bus-system-dropdown-toggle flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors w-full text-left {{ request()->routeIs('system.history') ? 'bg-blue-500' : '' }}"
                            aria-expanded="false"
                            aria-controls="bus-system-booking-history"
                            data-bus-system-toggle="bus-system-booking-history"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V10a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            {{ __('system.sidebar.booking_history') }}
                            <svg class="w-4 h-4 ml-auto transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <ul 
                            id="bus-system-booking-history"
                            class="bus-system-dropdown-menu hidden absolute left-0 mt-1 w-full bg-gray-700 text-white rounded-lg shadow-lg z-10"
                            role="menu"
                        >
                            <li role="menuitem"><a class="block px-4 py-2 hover:bg-gray-600 rounded-t-lg" href="{{ route('system.history') }}?period=today">{{ __('system.sidebar.today') }}</a></li>
                            <li role="menuitem"><a class="block px-4 py-2 hover:bg-gray-600" href="{{ route('system.history') }}?period=week">{{ __('system.sidebar.week') }}</a></li>
                            <li role="menuitem"><a class="block px-4 py-2 hover:bg-gray-600" href="{{ route('system.history') }}?period=month">{{ __('system.sidebar.month') }}</a></li>
                            <li role="menuitem"><a class="block px-4 py-2 hover:bg-gray-600" href="{{ route('system.history') }}?period=year">{{ __('system.sidebar.year') }}</a></li>
                            <li role="menuitem"><a class="block px-4 py-2 hover:bg-gray-600 rounded-b-lg" href="{{ route('system.history') }}?period=custom">{{ __('system.common.custom_range') }}</a></li>
                        </ul>
                    </div>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['SPECIAL_HIRE']))
                <li>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mt-4 mb-2">{{ __('system.sidebar.special_hire') }}</p>
                </li>
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.special_hire*') ? 'bg-blue-500' : '' }}" href="{{ route('system.special_hire') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        {{ __('system.sidebar.special_hire_overview') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['SYSTEM_INCOME']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.payments') ? 'bg-blue-500' : '' }}" href="{{ route('system.payments') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        {{ __('system.sidebar.system_income') }}
                    </a>
                </li>
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.excess_luggage') ? 'bg-blue-500' : '' }}" href="{{ route('system.excess_luggage') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        {{ __('system.sidebar.excess_luggage') }}
                    </a>
                </li>
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.parcels*') ? 'bg-blue-500' : '' }}" href="{{ route('system.parcels') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        {{ __('system.sidebar.parcels') }}
                    </a>
                </li>
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.government_levy') ? 'bg-blue-500' : '' }}" href="{{ route('system.government_levy') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.12-4 2.5S9.79 13 12 13s4 1.12 4 2.5S14.21 18 12 18s-4-1.12-4-2.5M12 6v2m0 10v2"></path></svg>
                        {{ __('system.sidebar.government_levy') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['PAYMENT_REQUEST']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('pay.request') ? 'bg-blue-500' : '' }}" href="{{ route('pay.request') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                        {{ __('system.sidebar.payment_request') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['REFUNDS']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.refunds') ? 'bg-blue-500' : '' }}" href="{{ route('system.refunds') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        {{ __('system.sidebar.refunds') }}
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess(\App\Models\Access::LINKS['LOCAL_ADMINS']))
                <li>
                    <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.local_admin') ? 'bg-blue-500' : '' }}" href="{{ route('system.local_admin') }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ __('system.sidebar.local_admins') }}
                    </a>
                </li>
                @endif

            @endif

            <li>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mt-4 mb-2">{{ __('system.sidebar.account') }}</p>
            </li>
            @if(auth()->user()->isActive())
            <li>
                <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.commands') ? 'bg-blue-500' : '' }}" href="{{ route('system.commands') }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    {{ __('system.sidebar.command') }}
                </a>
            </li>
            @endif
            <li>
                <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.setting') ? 'bg-blue-500' : '' }}" href="{{ route('system.setting') }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ __('system.sidebar.settings') }}
                </a>
            </li>
            <li>
                <a class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors {{ request()->routeIs('system.profile') ? 'bg-blue-500' : '' }}" href="{{ route('system.profile') }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    {{ __('system.sidebar.profile') }}
                </a>
            </li>
            <li>
                <form action="{{ route('logout') }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit" class="flex items-center px-3 py-2 text-white rounded-lg hover:bg-gray-700 transition-colors w-full text-left {{ request()->routeIs('logout') ? 'bg-blue-500' : '' }}">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h5a3 3 0 013 3v1"></path></svg>
                        {{ __('system.sidebar.logout') }}
                    </button>
                </form>
            </li>
        @endauth
    </ul>
</div>

<script>
(function () {
    const bookingHistoryToggle = document.querySelector('[data-bus-system-toggle="bus-system-booking-history"]');
    const bookingHistoryMenu = document.getElementById('bus-system-booking-history');
    const insuranceToggle = document.querySelector('[data-bus-system-toggle="bus-system-insurance"]');
    const insuranceMenu = document.getElementById('bus-system-insurance');

    function handleDropdownToggle(toggleButton, dropdownMenu) {
        if (toggleButton && dropdownMenu) {
            toggleButton.addEventListener('click', function (event) {
                event.preventDefault();
                const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
                toggleButton.setAttribute('aria-expanded', !isExpanded);
                dropdownMenu.classList.toggle('hidden');
                const chevron = toggleButton.querySelector('svg:last-child');
                chevron.classList.toggle('rotate-180');
            });

            document.addEventListener('click', function (event) {
                if (!toggleButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                    toggleButton.setAttribute('aria-expanded', 'false');
                    dropdownMenu.classList.add('hidden');
                    const chevron = toggleButton.querySelector('svg:last-child');
                    chevron.classList.remove('rotate-180');
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && toggleButton.getAttribute('aria-expanded') === 'true') {
                    toggleButton.setAttribute('aria-expanded', 'false');
                    dropdownMenu.classList.add('hidden');
                    const chevron = toggleButton.querySelector('svg:last-child');
                    chevron.classList.remove('rotate-180');
                    toggleButton.focus();
                }
            });
        }
    }

    handleDropdownToggle(bookingHistoryToggle, bookingHistoryMenu);
    handleDropdownToggle(insuranceToggle, insuranceMenu);
})();
</script>
