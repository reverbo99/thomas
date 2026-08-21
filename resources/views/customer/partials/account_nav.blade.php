@php
    $walletAmount = convert_money(auth()->user()->temp_wallets->amount ?? '0');
    $navItems = [
        ['route' => 'customer.index', 'label' => __('customer_sidebar.Dashboard'), 'icon' => 'fa-gauge-high', 'active' => request()->routeIs('customer.index', 'customer.dashboard')],
        ['route' => 'customer.mybooking', 'label' => __('customer_sidebar.My Tickets'), 'icon' => 'fa-ticket', 'active' => request()->routeIs('customer.mybooking', 'customer.pay.resaved', 'customer.cancel.resaved')],
        ['route' => 'customer.mybooking.search', 'label' => __('customer_sidebar.Bus Route'), 'icon' => 'fa-route', 'active' => request()->routeIs('customer.mybooking.search', 'customer.mybooking.search.form', 'customer.by_route', 'customer.booking_form', 'customer.get_form', 'customer.seats', 'customer.get_seats', 'customer.pay', 'customer.payment_store', 'customer.verify', 'customer.booking.inline.form', 'customer.booking.inline.prepare', 'customer.booking.inline.wallet', 'customer.busname', 'customer.round.trip*')],
        ['route' => 'customer.special_hire.index', 'label' => __('customer_sidebar.Special Hire'), 'icon' => 'fa-van-shuttle', 'active' => request()->routeIs('customer.special_hire.*')],
        ['route' => 'customer.profile', 'label' => __('customer_sidebar.Profile'), 'icon' => 'fa-user', 'active' => request()->routeIs('customer.profile', 'customer.profile.update', 'customer.edit', 'customer.update')],
    ];
@endphp

<nav class="customer-account-nav" aria-label="{{ __('customer_sidebar.Dashboard') }}">
    <div class="container mx-auto px-3 sm:px-4">
        <div class="customer-account-nav__inner">
            <div class="customer-account-nav__links-wrap">
                <div class="customer-account-nav__links" id="customer-account-links">
                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                           class="customer-account-nav__link {{ $item['active'] ? 'customer-account-nav__link--active' : '' }}"
                           aria-label="{{ $item['label'] }}"
                           @if($item['active']) aria-current="page" @endif>
                            <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span class="customer-account-nav__label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="customer-account-nav__meta">
                <div class="customer-account-nav__wallet" title="{{ __('customer_sidebar.Wallet') }}">
                    <i class="fas fa-wallet" aria-hidden="true"></i>
                    <span>{{ $walletAmount }} {{ $currency }}</span>
                </div>

                <div class="customer-account-nav__selects">
                    <select class="customer-account-nav__select"
                            aria-label="{{ __('all.currency_label') }}"
                            onchange="window.location.href = '{{ route('set.currency', ['currency' => ':currency']) }}'.replace(':currency', this.value)">
                        <option value="Tsh" {{ session('currency') == 'Tsh' ? 'selected' : '' }}>TSH</option>
                        <option value="Usd" {{ session('currency') == 'Usd' ? 'selected' : '' }}>USD</option>
                    </select>

                    <select class="customer-account-nav__select"
                            aria-label="{{ __('all.language') }}"
                            onchange="window.location.href = '{{ route('set.locale', ['lang' => '']) }}' + this.value">
                        <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>{{ __('customer/busroot.english') }}</option>
                        <option value="sw" {{ app()->getLocale() == 'sw' ? 'selected' : '' }}>{{ __('customer/busroot.kiswahili') }}</option>
                    </select>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="customer-account-nav__logout">
                    @csrf
                    <button type="submit" class="customer-account-nav__logout-btn" title="{{ __('customer_sidebar.Logout') }}">
                        <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                        <span class="customer-account-nav__logout-text">{{ __('customer_sidebar.Logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
