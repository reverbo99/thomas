@extends('test.layouts.marketing')

@section('title', __('customer/busroot.complete_your_payment') . ' — ' . __('all.highlink_isgc'))

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => __('all.booking_information'),
        'title' => __('customer/busroot.complete_your_payment'),
        'subtitle' => __('customer/busroot.pay_reserved_subtitle'),
        'image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&q=80',
    ])
@endsection

@section('content')
@php
    $totalPayable = round($price + $fees, 2);
    $routeLabel = ($booking->route_name->from ?? $booking->pickup_point ?? '—')
        . ' → '
        . ($booking->route_name->to ?? $booking->dropping_point ?? '—');
    $reservedUntil = $booking->resaved_until
        ? \Carbon\Carbon::parse($booking->resaved_until)
        : null;
@endphp

<section class="page-section page-section--alt">
    <div class="container mx-auto px-4 max-w-5xl">
        @if ($reservedUntil)
            <div class="customer-alert customer-alert--error mb-6 flex flex-wrap items-center justify-between gap-2" role="status">
                <span>
                    <i class="fas fa-clock mr-2" aria-hidden="true"></i>
                    {{ __('customer/busroot.resaved_until') }}:
                    <strong>{{ $reservedUntil->format('M j, Y g:i A') }}</strong>
                </span>
                <span class="text-sm font-mono" id="resaved-countdown" data-expires="{{ $reservedUntil->toIso8601String() }}"></span>
            </div>
        @endif

        @if (session('error'))
            <div class="customer-alert customer-alert--error mb-6" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="customer-alert customer-alert--error mb-6" role="alert">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="page-card fade-in">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-bookmark" aria-hidden="true" style="color:var(--home-primary)"></i>
                            {{ __('customer/busroot.reserved_ticket_details') }}
                        </h3>
                        <span class="text-sm font-mono text-gray-500">{{ $booking->booking_code }}</span>
                    </div>
                    <dl class="payment-result-rows">
                        <div class="payment-result-row">
                            <dt>{{ __('customer/myticket.booking_id') }}</dt>
                            <dd>{{ $booking->booking_code }}</dd>
                        </div>
                        <div class="payment-result-row">
                            <dt>{{ __('customer/busroot.route') }}</dt>
                            <dd>{{ $routeLabel }}</dd>
                        </div>
                        <div class="payment-result-row">
                            <dt>{{ __('customer/busroot.travel_date') }}</dt>
                            <dd>{{ \Carbon\Carbon::parse($booking->travel_date)->format('l, M j, Y') }}</dd>
                        </div>
                        @if ($booking->bus?->busname?->name)
                            <div class="payment-result-row">
                                <dt>{{ __('customer/myticket.bus_name') }}</dt>
                                <dd>{{ $booking->bus->busname->name }}</dd>
                            </div>
                        @endif
                        <div class="payment-result-row">
                            <dt>{{ __('customer/busroot.seats') }}</dt>
                            <dd>{{ $booking->seat }}</dd>
                        </div>
                        <div class="payment-result-row">
                            <dt>{{ __('customer/busroot.resaved_until') }}</dt>
                            <dd>{{ $reservedUntil ? $reservedUntil->format('M j, Y g:i A') : __('all.not_available_short') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="page-card fade-in">
                    @if ($test_mode ?? false)
                        @include('partials.pay_resaved_test_mode', ['booking' => $booking])
                    @else
                        @include('customer.partials.pay_resaved_payment')
                    @endif
                </div>
            </div>

            <aside class="page-card fade-in h-fit lg:sticky lg:top-24">
                <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-4">
                    <i class="fas fa-receipt" aria-hidden="true" style="color:var(--home-primary)"></i>
                    {{ __('customer/busroot.price_summary') }}
                </h3>
                <dl class="inline-payment__lines">
                    <div class="inline-payment__line">
                        <dt>{{ __('customer/busroot.discount') }}</dt>
                        <dd>{{ $currency }} {{ convert_money($dis) }}</dd>
                    </div>
                    @if (($ins ?? 0) > 0)
                        <div class="inline-payment__line">
                            <dt>{{ __('customer/busroot.insurance') }}</dt>
                            <dd>{{ $currency }} {{ convert_money($ins) }}</dd>
                        </div>
                    @endif
                    <div class="inline-payment__line">
                        <dt>{{ __('customer/busroot.system_charge') }}</dt>
                        <dd>{{ $currency }} {{ convert_money($fees) }}</dd>
                    </div>
                    <div class="inline-payment__line">
                        <dt>{{ __('customer/busroot.bus_fare') }}</dt>
                        <dd>{{ $currency }} {{ convert_money($price - ($ins ?? 0)) }}</dd>
                    </div>
                    <div class="inline-payment__line inline-payment__line--total">
                        <dt>{{ __('customer/busroot.total_payable') }}</dt>
                        <dd>{{ $currency }} {{ convert_money($totalPayable) }}</dd>
                    </div>
                </dl>
                <p class="text-sm text-gray-500 mt-4 flex items-center gap-2">
                    <i class="fas fa-shield-alt" style="color:var(--home-primary)" aria-hidden="true"></i>
                    {{ __('customer/busroot.secure_ssl_payment') }}
                </p>
            </aside>
        </div>

        <div class="mt-6">
            <a href="{{ route('info') }}" class="page-btn page-btn--outline">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                {{ __('all.back_button') }}
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    @include('partials.tz_phone_normalize_js')

    (function () {
        const root = document.querySelector('[data-resaved-payment]');
        if (!root) return;

        root.querySelectorAll('[data-resaved-pay-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const key = btn.dataset.resavedPayTab;
                root.querySelectorAll('[data-resaved-pay-tab]').forEach(function (b) {
                    const active = b === btn;
                    b.classList.toggle('inline-payment__tab--active', active);
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                root.querySelectorAll('[data-resaved-pay-pane]').forEach(function (pane) {
                    const active = pane.dataset.resavedPayPane === key;
                    pane.classList.toggle('inline-payment__pane--active', active);
                    if (active) {
                        pane.removeAttribute('hidden');
                    } else {
                        pane.setAttribute('hidden', '');
                    }
                });
            });
        });

        root.querySelectorAll('.resaved-pay-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const visible = form.querySelector('.resaved-phone-input');
                const hidden = form.querySelector('.resaved-phone-hidden');
                if (!visible || !hidden) return;

                const raw = visible.value ? visible.value.trim() : '';
                if (!raw) {
                    hidden.value = '';
                    return;
                }

                const normalized = typeof normalizePhoneTo255 === 'function'
                    ? normalizePhoneTo255(raw)
                    : raw.replace(/\D/g, '');

                if (!normalized) {
                    e.preventDefault();
                    alert(@json(__('customer/busroot.invalid_payment_phone')));
                    visible.focus();
                    return;
                }

                hidden.value = normalized;
            });
        });

        const resavedCountdownI18n = {
            expired: @json(__('all.reservation_expired')),
            left: @json(__('all.countdown_left')),
        };

        const countdownEl = document.getElementById('resaved-countdown');
        if (countdownEl && countdownEl.dataset.expires) {
            const expires = new Date(countdownEl.dataset.expires).getTime();
            function tick() {
                const diff = expires - Date.now();
                if (diff <= 0) {
                    countdownEl.textContent = resavedCountdownI18n.expired;
                    return;
                }
                const h = Math.floor(diff / 3600000);
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                countdownEl.textContent = (h > 0 ? h + 'h ' : '') + m + 'm ' + s + 's ' + resavedCountdownI18n.left;
            }
            tick();
            setInterval(tick, 1000);
        }
    })();
</script>
@endpush
