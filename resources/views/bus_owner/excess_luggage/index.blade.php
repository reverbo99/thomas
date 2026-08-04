@extends('admin.app')

@section('title', __('vender/luggage.tracking'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @include('excess_luggage.partials.flash')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('vender/luggage.tracking') }}</h1>
            <p class="text-sm text-gray-500">{{ __('vender/luggage.tracking_subtitle') }}</p>
        </div>
        <a href="{{ route($ctx['lookup_route']) }}" class="inline-flex items-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
            {{ __('vender/luggage.lookup_ticket') }}
        </a>
    </div>
    @include('excess_luggage.partials.tracking_table')
</div>
@endsection
