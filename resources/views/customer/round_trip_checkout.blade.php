@extends('customer.app')

@section('title', __('customer/busroot.complete_your_payment') . ' — ' . __('all.highlink_isgc'))

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => __('customer_sidebar.Round Trip'),
        'title' => __('customer/busroot.complete_your_payment'),
        'subtitle' => __('all.round_trip_checkout_subtitle'),
        'image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&q=80',
    ])
@endsection

@section('content')
    <section class="page-section page-section--alt">
        <div class="container mx-auto px-4 max-w-4xl">
            @include('test.partials.booking_steps', ['currentStep' => 4])

            @if (!empty($legSummaries))
                <div class="home-bus-row__expand-inner home-bus-row__expand-inner--page fade-in">
                    @include('test.partials.round_trip_payment_details_inline', [
                        'price' => $price,
                        'fees' => $fees,
                        'ins' => $ins,
                        'dis' => $dis,
                        'excess_luggage_fee' => $excess_luggage_fee ?? 0,
                        'test_mode' => $test_mode ?? false,
                        'legSummaries' => $legSummaries,
                        'contactPhone' => $contactPhone ?? '',
                        'paymentAction' => $paymentAction ?? round_trip_route('get_payment'),
                        'standalone' => $standalone ?? true,
                    ])
                </div>
            @else
                <div class="booking-alert booking-alert--error">
                    {{ __('customer/busroot.session_expired') }}
                </div>
                <a href="{{ round_trip_route('index') }}" class="page-btn mt-4 inline-flex">{{ __('all.back_button') }}</a>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    @include('test.partials.guest_i18n')
    <script src="{{ asset('js/inline-booking.js') }}?v={{ filemtime(public_path('js/inline-booking.js')) }}"></script>
@endpush
