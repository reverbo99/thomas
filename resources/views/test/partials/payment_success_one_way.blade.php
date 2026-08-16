@php
    extract(booking_payment_amounts($data ?? null));
    $ticketFee = $breakdownTicketFee;
    $luggageFee = $breakdownLuggageFee;
    $insuranceAmount = $breakdownInsurance;
    $displayServiceFee = $breakdownServiceFee;
    $amountPaid = $breakdownAmountPaid;
    $useStoredCustomerTotal = $breakdownUseStoredTotal;
@endphp

<div class="payment-result-panel fade-in">
    <div class="payment-result-panel__body">
        <div class="payment-result-status payment-result-status--success">
            <div class="payment-result-status__icon" aria-hidden="true">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="payment-result-status__title">{{ __('all.payment_successful') }}</h2>
            <p class="payment-result-status__subtitle">{{ __('all.thank_you_booking') }}</p>
        </div>

        <div class="payment-result-grid">
            <div class="payment-result-card">
                <h3 class="payment-result-card__title">
                    <i class="fas fa-ticket" aria-hidden="true"></i>
                    {{ __('all.booking_summary') }}
                </h3>
                <dl class="payment-result-rows">
                    <div class="payment-result-row">
                        <dt>{{ __('all.bus') ?? 'Bus' }}</dt>
                        <dd>{{ $data->bus->busname->name ?? 'N/A' }} · {{ $data->bus->bus_number ?? 'N/A' }}</dd>
                    </div>
                    <div class="payment-result-row">
                        <dt>{{ __('all.booking_code') ?? 'Booking Code' }}</dt>
                        <dd>{{ $data->booking_code }}</dd>
                    </div>
                    <div class="payment-result-row">
                        <dt>{{ __('all.route') ?? 'Route' }}</dt>
                        <dd>{{ $data->schedule->from ?? 'N/A' }} → {{ $data->schedule->to ?? 'N/A' }}</dd>
                    </div>
                    <div class="payment-result-row">
                        <dt>{{ __('all.pickup_point_label') ?? 'Pickup' }} / {{ __('all.dropoff_point_label') ?? 'Drop' }}</dt>
                        <dd>{{ $data->pickup_point ?? 'N/A' }} → {{ $data->dropping_point ?? 'N/A' }}</dd>
                    </div>
                    <div class="payment-result-row">
                        <dt>{{ __('all.travel_date') ?? 'Travel Date' }}</dt>
                        <dd>{{ $data->travel_date }} {{ $data->schedule->start ?? '' }}</dd>
                    </div>
                    <div class="payment-result-row">
                        <dt>{{ __('all.seats') ?? 'Seats' }}</dt>
                        <dd>{{ $data->seat }}</dd>
                    </div>
                </dl>
            </div>

            <div class="payment-result-card">
                <h3 class="payment-result-card__title">
                    <i class="fas fa-credit-card" aria-hidden="true"></i>
                    {{ __('all.payment_details') }}
                </h3>
                <dl class="payment-result-rows">
                    <div class="payment-result-row">
                        <dt>{{ __('all.bus_fare') ?? 'Ticket Fee' }}</dt>
                        <dd>{{ $currency }} {{ convert_money($ticketFee) }}</dd>
                    </div>
                    @if ($luggageFee > 0)
                        <div class="payment-result-row">
                            <dt>{{ __('all.excess_luggage') }}</dt>
                            <dd>{{ $currency }} {{ convert_money($luggageFee) }}</dd>
                        </div>
                    @endif
                    <div class="payment-result-row">
                        <dt>{{ __('all.system_charge') }}</dt>
                        <dd>{{ $currency }} {{ convert_money($displayServiceFee) }}</dd>
                    </div>
                    @if ($data->bima == 1)
                        <div class="payment-result-row">
                            <dt>{{ __('all.insurance') ?? 'Insurance' }}</dt>
                            <dd>{{ $currency }} {{ convert_money($data->bima_amount) }}</dd>
                        </div>
                    @endif
                    @if (!empty($data->discount))
                        <div class="payment-result-row">
                            <dt>{{ __('all.discount') ?? 'Discount' }}</dt>
                            <dd>{{ $data->discounta->percentage ?? 0 }}%</dd>
                        </div>
                    @endif
                    <div class="payment-result-row payment-result-row--total">
                        <dt>{{ __('all.total_payable') ?? 'Amount Paid' }}</dt>
                        <dd>{{ $currency }} {{ convert_money($amountPaid) }}</dd>
                    </div>
                    <div class="payment-result-row">
                        <dt>{{ __('all.transaction_id') ?? 'Transaction ID' }}</dt>
                        <dd class="break-all">{{ $data->transaction_ref_id }}</dd>
                    </div>
                    <div class="payment-result-row">
                        <dt>{{ __('all.status') ?? 'Status' }}</dt>
                        <dd><span class="payment-result-badge">{{ __('all.confirmed') }}</span></dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="payment-result-code">
            <p class="payment-result-code__label">{{ __('all.verification_code') ?? 'Your Verification Code' }}</p>
            <p class="payment-result-code__value">{{ $data->booking_code }}</p>
            <p class="payment-result-code__hint">{{ __('all.present_code_boarding') }}</p>
        </div>

        @if ($data->tra_vnum ?? false)
            <div class="payment-result-tra">
                <p class="payment-result-code__label">{{ __('all.tra_receipt') ?? 'TRA Receipt Verification' }}</p>
                <div class="flex justify-center mb-3">
                    <div class="bg-white p-2 rounded shadow-sm inline-block">
                        {!! DNS2D::getBarcodeHTML($data->tra_qr_url, 'QRCODE', 4, 4) !!}
                    </div>
                </div>
                <p class="text-sm text-gray-600">{{ __('all.verification') ?? 'Verification' }}: <strong class="font-mono">{{ $data->tra_vnum }}</strong></p>
                @php
                    $govLevyPercentLabel = rtrim(rtrim(number_format(government_levy_percent(), 2, '.', ''), '0'), '.');
                    $receiptGovLevy = booking_row_total_government_levy($data);
                @endphp
                <p class="text-sm text-gray-600">{{ __('all.government_levy_with_percent', ['percent' => $govLevyPercentLabel]) ?? 'Government Levy (' . $govLevyPercentLabel . '%)' }}: <strong>{{ number_format((float) $receiptGovLevy, 2) }} TZS</strong></p>
            </div>
        @endif

        <div class="payment-result-actions">
            <a href="{{ route('home') }}" class="page-btn">
                <i class="fas fa-home" aria-hidden="true"></i>
                {{ __('all.return_home') ?? 'Return Home' }}
            </a>
            <form action="{{ route('ticket.print') }}" method="POST">
                @csrf
                <input type="hidden" name="data" value="{{ $data }}">
                <button type="submit" class="page-btn page-btn--outline w-full sm:w-auto">
                    <i class="fas fa-print" aria-hidden="true"></i>
                    {{ __('all.print_ticket') }}
                </button>
            </form>
            @auth
                @if (auth()->user()->role === 'customer')
                    <a href="{{ route('customer.mybooking') }}" class="page-btn page-btn--outline">
                        <i class="fas fa-ticket" aria-hidden="true"></i>
                        {{ __('all.view_my_bookings') }}
                    </a>
                @endif
            @endauth
        </div>

        <div class="payment-result-footer">
            <p>{{ __('all.confirmation_email_sent') }} {{ $data->customer_email }}</p>
        </div>
    </div>
</div>

<script>
    (function () {
        var homeUrl = "{{ route('home') }}";
        if (window.history && window.history.pushState) {
            window.history.pushState({ ticketSuccess: true }, '', window.location.href);
            window.addEventListener('popstate', function () {
                window.location.href = homeUrl;
            });
        }
    })();
</script>
