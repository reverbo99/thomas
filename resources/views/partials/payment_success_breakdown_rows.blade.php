@php
    $bpBooking = $booking ?? $data ?? null;
    extract(booking_payment_amounts($bpBooking));
@endphp
<div class="payment-result-row">
    <span class="payment-result-row__label">{{ __('all.bus_fare') }}</span>
    <span class="payment-result-row__value">{{ $currency }} {{ convert_money($breakdownTicketFee) }}</span>
</div>
@if ($breakdownLuggageFee > 0)
<div class="payment-result-row">
    <span class="payment-result-row__label">{{ __('all.excess_luggage') }}</span>
    <span class="payment-result-row__value">{{ $currency }} {{ convert_money($breakdownLuggageFee) }}</span>
</div>
@endif
<div class="payment-result-row">
    <span class="payment-result-row__label">{{ __('all.system_charge') }}</span>
    <span class="payment-result-row__value">{{ $currency }} {{ convert_money($breakdownServiceFee) }}</span>
</div>
@if (($bpBooking->bima ?? 0) == 1)
<div class="payment-result-row">
    <span class="payment-result-row__label">{{ __('all.insurance') }}</span>
    <span class="payment-result-row__value">{{ $currency }} {{ convert_money($breakdownInsurance) }}</span>
</div>
@endif
<div class="payment-result-row payment-result-row--total">
    <span class="payment-result-row__label">{{ __('all.total_payable') }}</span>
    <span class="payment-result-row__value">{{ $currency }} {{ convert_money($breakdownAmountPaid) }}</span>
</div>
