@extends('vender.app')

@section('title', __('vender/luggage.lookup_ticket'))

@section('content')
<div class="vendor-dash fade-in px-4 py-6 max-w-xl mx-auto">
    @include('excess_luggage.partials.flash')
    <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ __('vender/luggage.lookup_ticket') }}</h1>
    <p class="text-sm text-gray-500 mb-6">{{ __('vender/luggage.lookup_hint') }}</p>
    <form method="POST" action="{{ route($ctx['lookup_post']) }}" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.ticket_or_verification') }}</label>
            <input type="text" name="ticket_code" value="{{ old('ticket_code') }}" required autofocus
                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm"
                   placeholder="{{ __('vender/luggage.ticket_placeholder') }}">
        </div>
        <button type="submit" class="page-btn w-full justify-center">{{ __('vender/luggage.find_booking') }}</button>
    </form>
    <p class="mt-4 text-center text-sm space-x-3">
        <a href="{{ route($ctx['scan_route']) }}" class="text-teal-700 hover:underline">{{ __('vender/luggage.scan_exit_title') }}</a>
        <a href="{{ route($ctx['index_route']) }}" class="text-teal-700 hover:underline">{{ __('vender/luggage.tracking') }}</a>
    </p>
</div>
@endsection
