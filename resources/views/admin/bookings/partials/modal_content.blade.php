
<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Journey Details</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Bus:</span>
                    <span class="fw-bold">{{ $booking->bus_name->name }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Route:</span>
                    <span class="fw-bold">{{ $booking->schedule->from ?? 'N/A' }} to {{ $booking->schedule->to ?? 'N/A' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Travel Date:</span>
                    <span class="fw-bold">{{ $booking->travel_date }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Pickup Point:</span>
                    <span class="fw-bold">{{ $booking->pickup_point }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Dropoff Point:</span>
                    <span class="fw-bold">{{ $booking->dropoff_point }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Seat Number:</span>
                    <span class="fw-bold">{{ $booking->seat }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Passenger Details</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Name:</span>
                    <span class="fw-bold">{{ $booking->user->name }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Phone:</span>
                    <span class="fw-bold">{{ $booking->phone_number }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Email:</span>
                    <span class="fw-bold">{{ $booking->user->email }}</span>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Payment Details</h6>
            </div>
            <div class="card-body">
                @php
                    extract(booking_payment_amounts($booking ?? null));
                @endphp
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Ticket Fee:</span>
                    <span class="fw-bold">{{ $currency }} {{ convert_money($breakdownTicketFee) }}</span>
                </div>
                @if ($breakdownLuggageFee > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('all.excess_luggage') }}:</span>
                    <span class="fw-bold">{{ $currency }} {{ convert_money($breakdownLuggageFee) }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('all.service_fee') }}:</span>
                    <span class="fw-bold">{{ $currency }} {{ convert_money($breakdownServiceFee) }}</span>
                </div>
                @if ($booking->bima == 1)
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Insurance:</span>
                    <span class="fw-bold">{{ $currency }} {{ convert_money($breakdownInsurance) }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between mb-2 border-top pt-2">
                    <span class="text-muted fw-semibold">Total Paid:</span>
                    <span class="fw-bold">{{ $currency }} {{ convert_money($breakdownAmountPaid) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Payment Method:</span>
                    <span class="fw-bold">{{ $booking->payment_method }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Transaction Ref:</span>
                    <span class="fw-bold">{{ $booking->transaction_ref }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Status:</span>
                    <span class="badge bg-success">Paid</span>
                </div>
            </div>
        </div>
    </div>
</div> 