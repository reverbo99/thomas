@php
    $bookingFlowRoutes = [
        'vender.route', 'vender.route.by_route_search', 'vender.route.road', 'vender.busname',
        'vender.booking.inline.form', 'vender.booking.inline.prepare', 'vender.booking.inline.wallet',
        'vender.booking_form', 'vender.get_form', 'vender.store', 'seates.vender', 'vender.get_seats',
        'vender.pay', 'vender.payment_store', 'vender.verify',
    ];
    $historyActive = request()->routeIs('vender.history', 'vender.print', 'vender.print.manifest');
    $resavedActive = request()->routeIs('vender.resaved.tickets', 'vender.pay.resaved', 'vender.edit.resaved', 'vender.cancel.resaved', 'vender.update.resaved');
@endphp

<div class="vendor-sidebar__inner">
    <div class="vendor-sidebar__brand">
        <img src="{{ asset('ChatGPT Image Jul 7, 2025, 12_18_13 PM.png') }}" alt="" class="vendor-sidebar__logo" loading="lazy">
        <div>
            <p class="vendor-sidebar__title">{{ __('all.highlink_isgc') }}</p>
            <p class="vendor-sidebar__subtitle">{{ __('assistance/sidebar.vendor_panel') }}</p>
        </div>
    </div>

    <nav class="vendor-sidebar__nav">
        <p class="vendor-sidebar__section">{{ __('assistance/sidebar.vendor_management') }}</p>

        <a href="{{ route('vender.index') }}"
           class="vendor-sidebar__link {{ request()->routeIs('vender.index') ? 'vendor-sidebar__link--active' : '' }}"
           @if(request()->routeIs('vender.index')) aria-current="page" @endif>
            <i class="fas fa-gauge-high" aria-hidden="true"></i>
            <span>{{ __('assistance/sidebar.dashboard') }}</span>
        </a>

        <a href="{{ route('vender.bus_route') }}"
           class="vendor-sidebar__link {{ request()->routeIs('vender.bus_route', 'vender.edit.schedule', 'vender.update_schedule') ? 'vendor-sidebar__link--active' : '' }}"
           @if(request()->routeIs('vender.bus_route')) aria-current="page" @endif>
            <i class="fas fa-calendar-days" aria-hidden="true"></i>
            <span>{{ __('assistance/sidebar.bus_schedule') }}</span>
        </a>

        <a href="{{ route('vender.transaction') }}"
           class="vendor-sidebar__link {{ request()->routeIs('vender.transaction', 'vender.transaction.request', 'vender.wallet.*', 'vender.dpo.*') ? 'vendor-sidebar__link--active' : '' }}"
           @if(request()->routeIs('vender.transaction')) aria-current="page" @endif>
            <i class="fas fa-money-bill-transfer" aria-hidden="true"></i>
            <span>{{ __('assistance/sidebar.transactions') }}</span>
        </a>

        @if (auth()->user()->status == 'accept')
            <a href="{{ route('vender.route') }}"
               class="vendor-sidebar__link {{ request()->routeIs($bookingFlowRoutes) ? 'vendor-sidebar__link--active' : '' }}"
               @if(request()->routeIs($bookingFlowRoutes)) aria-current="page" @endif>
                <i class="fas fa-ticket" aria-hidden="true"></i>
                <span>{{ __('assistance/sidebar.book_ticket') }}</span>
            </a>
        @endif

        <div class="vendor-sidebar__group">
            <button type="button"
                    class="vendor-sidebar__link vendor-sidebar__link--toggle {{ $historyActive ? 'vendor-sidebar__link--active' : '' }}"
                    data-vendor-submenu-toggle="vendor-history-submenu"
                    aria-expanded="{{ $historyActive ? 'true' : 'false' }}"
                    aria-controls="vendor-history-submenu">
                <i class="fas fa-clock-rotate-left" aria-hidden="true"></i>
                <span>{{ __('assistance/sidebar.booking_history') }}</span>
                <i class="fas fa-chevron-down vendor-sidebar__chevron" aria-hidden="true"></i>
            </button>
            <div id="vendor-history-submenu" class="vendor-sidebar__submenu {{ $historyActive ? '' : 'hidden' }}">
                <a href="{{ route('vender.history') }}?period=today" class="vendor-sidebar__sublink">{{ __('assistance/sidebar.today') }}</a>
                <a href="{{ route('vender.history') }}?period=week" class="vendor-sidebar__sublink">{{ __('assistance/sidebar.week') }}</a>
                <a href="{{ route('vender.history') }}?period=month" class="vendor-sidebar__sublink">{{ __('assistance/sidebar.month') }}</a>
                <a href="{{ route('vender.history') }}?period=year" class="vendor-sidebar__sublink">{{ __('assistance/sidebar.year') }}</a>
                <a href="{{ route('vender.history') }}?period=custom" class="vendor-sidebar__sublink">{{ __('assistance/sidebar.custom_range') }}</a>
            </div>
        </div>

        <a href="{{ route('vender.resaved.tickets') }}"
           class="vendor-sidebar__link {{ $resavedActive ? 'vendor-sidebar__link--active' : '' }}"
           @if($resavedActive) aria-current="page" @endif>
            <i class="fas fa-bookmark" aria-hidden="true"></i>
            <span>{{ __('assistance/sidebar.reserved_tickets') }}</span>
        </a>

        <a href="{{ route('vender.parcels.index') }}"
           class="vendor-sidebar__link {{ request()->routeIs('vender.parcels.*') ? 'vendor-sidebar__link--active' : '' }}"
           @if(request()->routeIs('vender.parcels.index')) aria-current="page" @endif>
            <i class="fas fa-box" aria-hidden="true"></i>
            <span>{{ __('all.parcels') }}</span>
        </a>

        <p class="vendor-sidebar__section">{{ __('assistance/sidebar.account') }}</p>

        <a href="{{ route('vender.profile') }}"
           class="vendor-sidebar__link {{ request()->routeIs('vender.profile', 'vender.profile.update') ? 'vendor-sidebar__link--active' : '' }}"
           @if(request()->routeIs('vender.profile')) aria-current="page" @endif>
            <i class="fas fa-user" aria-hidden="true"></i>
            <span>{{ __('assistance/sidebar.profile') }}</span>
        </a>

        <form action="{{ route('logout') }}" method="POST" class="vendor-sidebar__logout-form">
            @csrf
            <button type="submit" class="vendor-sidebar__link vendor-sidebar__link--logout w-full text-left">
                <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                <span>{{ __('assistance/sidebar.logout') }}</span>
            </button>
        </form>
    </nav>

    <div class="vendor-sidebar__footer">
        <p>{{ __('assistance/sidebar.highlink_isgc') }}</p>
        <p class="vendor-sidebar__version">{{ __('assistance/sidebar.version') }}</p>
    </div>
</div>
