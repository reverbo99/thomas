@php
    $totalPayable = round($price + $fees, 2);
    $contactPhone = session('booking_form.customer_number') ?? '';
    $br = booking_routes();
    $verifyAction = route($br['verify']);
    $isCustomerPayment = ($br['channel'] ?? '') === 'customer';
    $isVendorPayment = ($br['channel'] ?? '') === 'vender';
    // Logged-in customers can reserve even when booking via the public (guest) search routes.
    $supportsReserve = $isCustomerPayment
        || $isVendorPayment
        || (auth()->check() && auth()->user()->role === 'customer');
@endphp

<div class="inline-payment"
    data-inline-payment
    data-inline-payment-total="{{ $totalPayable }}"
    data-airtel-url="{{ route('airtel.booking.payment') }}">

    {{-- Ticket summary --}}
    @php
        $summary = $summary ?? [];
        $travelDateLabel = $summary['travel_date'] ?? null;
        if (!empty($travelDateLabel)) {
            try {
                $travelDateLabel = \Carbon\Carbon::parse($travelDateLabel)->format('d M Y');
            } catch (\Throwable $e) {
                // Keep original value if parsing fails.
            }
        }
    @endphp
    @if (!empty($summary))
        <div class="inline-ticket-summary">
            <h4 class="inline-payment__heading">
                <i class="fas fa-ticket-alt" aria-hidden="true"></i> {{ __('all.ticket_summary') }}
            </h4>

            <div class="inline-ticket-summary__card">
                <div class="inline-ticket-summary__head">
                    @if (!empty($summary['bus_name']))
                        <div class="inline-ticket-summary__bus">
                            <span class="inline-ticket-summary__bus-name">{{ $summary['bus_name'] }}</span>
                            @if (!empty($summary['bus_number']))
                                <span class="inline-ticket-summary__bus-no">{{ $summary['bus_number'] }}</span>
                            @endif
                        </div>
                    @endif
                    @if (!empty($summary['via']))
                        <span class="inline-ticket-summary__via-badge">{{ __('all.via') }} {{ $summary['via'] }}</span>
                    @endif
                </div>

                <div class="inline-ticket-summary__route">
                    <div class="inline-ticket-summary__point">
                        <span class="inline-ticket-summary__point-label">{{ __('all.from') }}</span>
                        <span class="inline-ticket-summary__point-value">{{ $summary['pickup'] ?? '—' }}</span>
                    </div>
                    <span class="inline-ticket-summary__arrow" aria-hidden="true">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                    <div class="inline-ticket-summary__point inline-ticket-summary__point--to">
                        <span class="inline-ticket-summary__point-label">{{ __('all.to') }}</span>
                        <span class="inline-ticket-summary__point-value">{{ $summary['dropping'] ?? '—' }}</span>
                    </div>
                </div>

                <dl class="inline-ticket-summary__grid">
                    <div class="inline-ticket-summary__item">
                        <dt>{{ __('all.travel_date') }}</dt>
                        <dd>{{ $travelDateLabel ?? '—' }}</dd>
                    </div>
                    @if (!empty($summary['depart_time']))
                        <div class="inline-ticket-summary__item">
                            <dt>{{ __('all.departure_time') }}</dt>
                            <dd>{{ $summary['depart_time'] }}</dd>
                        </div>
                    @endif
                    <div class="inline-ticket-summary__item">
                        <dt>{{ __('all.selected_seats') }}</dt>
                        <dd>{{ !empty($summary['seats']) ? implode(', ', $summary['seats']) : '—' }}</dd>
                    </div>
                </dl>

                @if (!empty($summary['passengers']))
                    <div class="inline-ticket-summary__pax">
                        <span class="inline-ticket-summary__pax-title">{{ __('all.passengers') }}</span>
                        @foreach ($summary['passengers'] as $i => $pax)
                            <div class="inline-ticket-summary__pax-row">
                                <span class="inline-ticket-summary__pax-seat">{{ $summary['seats'][$i] ?? ($i + 1) }}</span>
                                <span class="inline-ticket-summary__pax-name">{{ $pax['name'] ?? '—' }}</span>
                                <span class="inline-ticket-summary__pax-meta">{{ $pax['age_group'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Contact details --}}
    <h4 class="inline-payment__heading">
        <i class="fas fa-phone" aria-hidden="true"></i> {{ __('all.contact_details') }}
    </h4>

    <div class="booking-grid booking-grid--2" style="margin-bottom:0.75rem;">
        <div class="booking-field">
            <label class="booking-field__label" for="countrycode">{{ __('all.country_code') }}</label>
            <select class="page-input" id="countrycode">
                <option value="+255" selected>{{ __('all.tz_code') }}</option>
            </select>
        </div>
        <div class="booking-field">
            <label class="booking-field__label" for="contactNumber">{{ __('all.mobile_number') }}</label>
            <input type="tel" class="page-input" id="contactNumber" maxlength="12"
                inputmode="numeric" autocomplete="tel"
                value="{{ $contactPhone }}" placeholder="{{ __('all.enter_mobile_number') }}" required>
        </div>
        <div class="booking-field booking-field--full">
            <label class="booking-field__label" for="contactEmail">{{ __('all.email_address') }}</label>
            <input type="email" class="page-input" id="contactEmail" maxlength="50"
                placeholder="{{ __('all.enter_email_address') }}">
        </div>
    </div>

    @if (!($test_mode ?? false))
        {{-- Payment options side-by-side: tabs left, pane right --}}
        <h4 class="inline-payment__heading">
            <i class="fas fa-credit-card" aria-hidden="true"></i> {{ __('all.payment_options') }}
        </h4>

        <div class="inline-payment__gateway">
            <div class="inline-payment__layout">
                {{-- Left: tab buttons --}}
                <div class="inline-payment__tabs" role="tablist" aria-label="{{ __('all.payment_methods') }}">
                    <button type="button" class="inline-payment__tab inline-payment__tab--active"
                        data-inline-pay-tab="mixx" role="tab" aria-selected="true">
                        <i class="fas fa-mobile-alt" aria-hidden="true"></i> {{ __('all.mixx_by_yas') }}
                    </button>
                    <button type="button" class="inline-payment__tab"
                        data-inline-pay-tab="dpo" role="tab" aria-selected="false">
                        <i class="fas fa-credit-card" aria-hidden="true"></i> {{ __('all.dpo_payment') }}
                    </button>
                    <button type="button" class="inline-payment__tab"
                        data-inline-pay-tab="clickpesa" role="tab" aria-selected="false">
                        <i class="fas fa-wallet" aria-hidden="true"></i> {{ __('all.clickpesa_payment') }}
                    </button>
                    <button type="button" class="inline-payment__tab"
                        data-inline-pay-tab="airtel" role="tab" aria-selected="false">
                        <i class="fas fa-sim-card" aria-hidden="true"></i> Airtel Money
                    </button>
                    @if ($isVendorPayment)
                        <button type="button" class="inline-payment__tab"
                            data-inline-pay-tab="cash" role="tab" aria-selected="false">
                            <i class="fas fa-money-bill" aria-hidden="true"></i> {{ __('customer/busroot.cash_payment') }}
                        </button>
                    @endif
                    @if ($supportsReserve)
                        <button type="button" class="inline-payment__tab"
                            data-inline-pay-tab="reserve" role="tab" aria-selected="false">
                            <i class="fas fa-bookmark" aria-hidden="true"></i> {{ __('customer/busroot.resave_ticket') }}
                        </button>
                    @endif
                </div>

                {{-- Right: pane content — each tab shows amount only --}}
                <div class="inline-payment__panes">
                    {{-- Mixx --}}
                    <div class="inline-payment__pane inline-payment__pane--active" data-inline-pay-pane="mixx" role="tabpanel">
                        <div class="inline-payment__amount-row">
                            <span>{{ __('all.amount') }}</span>
                            <strong>{{ $currency }} {{ convert_money($totalPayable) }}</strong>
                        </div>
                        <form id="inlineMixxForm" action="{{ $verifyAction }}" method="POST">
                            @csrf
                            <input type="hidden" name="payment_method" value="mixx">
                            <input type="hidden" name="amount" value="{{ $totalPayable }}">
                        </form>
                    </div>

                    {{-- DPO --}}
                    <div class="inline-payment__pane" data-inline-pay-pane="dpo" role="tabpanel" hidden>
                        <div class="inline-payment__amount-row">
                            <span>{{ __('all.amount') }}</span>
                            <strong>{{ $currency }} {{ convert_money($totalPayable) }}</strong>
                        </div>
                        <form id="inlineDpoForm" action="{{ $verifyAction }}" method="POST">
                            @csrf
                            <input type="hidden" name="payment_method" value="dpo">
                            <input type="hidden" name="amount" value="{{ $totalPayable }}">
                        </form>
                    </div>

                    {{-- ClickPesa --}}
                    <div class="inline-payment__pane" data-inline-pay-pane="clickpesa" role="tabpanel" hidden>
                        <div class="inline-payment__amount-row">
                            <span>{{ __('all.amount') }}</span>
                            <strong>{{ $currency }} {{ convert_money($totalPayable) }}</strong>
                        </div>
                        <form id="inlineClickpesaForm" action="{{ $verifyAction }}" method="POST">
                            @csrf
                            <input type="hidden" name="payment_method" value="clickpesa">
                            <input type="hidden" name="amount" value="{{ $totalPayable }}">
                        </form>
                    </div>

                    {{-- Airtel Money --}}
                    <div class="inline-payment__pane" data-inline-pay-pane="airtel" role="tabpanel" hidden>
                        <div class="inline-payment__amount-row">
                            <span>{{ __('all.amount') }}</span>
                            <strong>{{ $currency }} {{ convert_money($totalPayable) }}</strong>
                        </div>
                        <p class="inline-payment__airtel-status hidden" data-inline-airtel-status role="status"></p>
                    </div>

                    @if ($isVendorPayment)
                        <div class="inline-payment__pane" data-inline-pay-pane="cash" role="tabpanel" hidden>
                            <div class="inline-payment__amount-row">
                                <span>{{ __('all.amount') }}</span>
                                <strong>{{ $currency }} {{ convert_money($totalPayable) }}</strong>
                            </div>
                            <p class="text-sm text-gray-600 mb-3">{{ __('customer/busroot.session_expiry_warning') }}</p>
                            <form id="inlineCashForm" action="{{ $verifyAction }}" method="POST">
                                @csrf
                                <input type="hidden" name="payment_method" value="cash">
                                <input type="hidden" name="amount" value="{{ $totalPayable }}">
                            </form>
                        </div>
                    @endif

                    @if ($supportsReserve)
                        {{-- Reserve ticket (pay within 24 hours) --}}
                        <div class="inline-payment__pane" data-inline-pay-pane="reserve" role="tabpanel" hidden>
                            <div class="booking-alert booking-alert--info mb-4" role="status">
                                <p class="mb-2">{{ __('customer/busroot.resave_warning') }}</p>
                                <p class="font-semibold m-0">
                                    {{ __('customer/busroot.total_to_resave') }}
                                    {{ $currency }} {{ convert_money($totalPayable) }}
                                </p>
                            </div>
                            <p class="text-sm text-gray-600 mb-4">{{ __('customer/busroot.resave_description') }}</p>
                            <form id="inlineResaveForm" action="{{ $verifyAction }}" method="POST">
                                @csrf
                                <input type="hidden" name="payment_method" value="resave">
                                <input type="hidden" name="resave_ticket" value="1">
                                <input type="hidden" name="amount" value="{{ $totalPayable }}">
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <label class="inline-extras-form__check inline-payment__terms">
                <input type="checkbox" id="inlinePaymentTerms" value="1" checked>
                <span>{{ __('all.i_accept') }}
                    <a href="{{ route('ticket.purchase') }}" target="_blank" rel="noopener">{{ __('all.terms_and_conditions') }}</a>
                </span>
            </label>
        </div>
    @else
        @include('partials.payment_checkout_test_mode', [
            'verifyAction' => $verifyAction,
            'amount' => $totalPayable,
            'langNs' => 'customer/busroot',
            'formIdSuffix' => '_inline',
            'supportsReserve' => $supportsReserve,
            'reserveDescription' => $isVendorPayment
                ? __('customer/busroot.resave_description_vendor')
                : __('customer/busroot.resave_description'),
        ])
    @endif

    {{-- Price summary + actions --}}
    <div class="inline-payment__footer">
        <dl class="inline-payment__lines">
            @if (($dis ?? 0) > 0)
                <div class="inline-payment__line">
                    <dt>{{ __('all.discount') }}</dt>
                    <dd>{{ $currency }} {{ convert_money($dis) }}</dd>
                </div>
            @endif
            @if (($ins ?? 0) > 0)
                <div class="inline-payment__line">
                    <dt>{{ __('all.insurance') }}</dt>
                    <dd>{{ $currency }} {{ convert_money($ins) }}</dd>
                </div>
            @endif
            <div class="inline-payment__line">
                <dt>{{ __('all.system_charge') }}</dt>
                <dd>{{ $currency }} {{ convert_money($fees) }}</dd>
            </div>
            @if (($excess_luggage_fee ?? 0) > 0)
                <div class="inline-payment__line">
                    <dt>{{ __('all.excess_luggage') }}</dt>
                    <dd>{{ $currency }} {{ convert_money($excess_luggage_fee) }}</dd>
                </div>
            @endif
            <div class="inline-payment__line">
                <dt>{{ __('all.bus_fare') }}</dt>
                    <dd>{{ $currency }} {{ convert_money($price - ($ins ?? 0) - ($excess_luggage_fee ?? 0)) }}</dd>
            </div>
            <div class="inline-payment__line inline-payment__line--total">
                <dt>{{ __('all.total_payable') }}</dt>
                <dd>{{ $currency }} {{ convert_money($totalPayable) }}</dd>
            </div>
        </dl>

        <div class="inline-booking-actions">
            <button type="button" class="page-btn page-btn--outline" data-inline-nav-back="extras">
                {{ __('all.back_button') }}
            </button>
            <button type="button" class="page-btn" data-inline-pay-next>
                {{ __('all.proceed_to_pay') }}
            </button>
        </div>
    </div>
</div>
