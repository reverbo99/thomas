@extends('vender.app')

@section('title', __('vender/luggage.tracking'))

@section('content')
<div class="vendor-dash fade-in px-4 py-6">
    @include('excess_luggage.partials.flash')
    <header class="vendor-dash__header mb-6">
        <div class="vendor-dash__welcome">
            <h1 class="vendor-dash__title">{{ __('vender/luggage.tracking') }}</h1>
            <p class="vendor-dash__subtitle">{{ __('vender/luggage.tracking_subtitle') }}</p>
        </div>
        <div class="vendor-dash__actions">
            <a href="{{ route($ctx['lookup_route']) }}" class="page-btn">{{ __('vender/luggage.lookup_ticket') }}</a>
        </div>
    </header>
    @include('excess_luggage.partials.tracking_table')
</div>
@endsection
