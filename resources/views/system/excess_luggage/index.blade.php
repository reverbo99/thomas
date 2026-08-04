@extends('system.app')

@section('title', __('vender/luggage.tracking'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @include('excess_luggage.partials.flash')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('vender/luggage.admin_tracking') }}</h1>
        <p class="text-sm text-gray-500">{{ __('vender/luggage.tracking_subtitle') }}</p>
    </div>
    @include('excess_luggage.partials.tracking_table', ['ctx' => ['show_route' => null]])
</div>
@endsection
