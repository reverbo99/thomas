@extends('vender.app')

@section('title', __('vender/luggage.scan_exit_title'))

@section('content')
<div class="vendor-dash fade-in px-4 py-6 max-w-xl mx-auto">
    @include('excess_luggage.partials.flash')
    <h1 class="mb-2 text-2xl font-bold text-gray-800">{{ __('vender/luggage.scan_exit_title') }}</h1>
    <p class="mb-6 text-sm text-gray-500">{{ __('vender/luggage.scan_exit_hint') }}</p>
    <form method="POST" action="{{ route($ctx['scan_post']) }}" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.qr_payload_label') }}</label>
            <input type="text" name="qr_payload" value="{{ old('qr_payload') }}" required autofocus
                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                   placeholder="{{ __('vender/luggage.qr_payload_placeholder') }}"
                   autocomplete="off">
            <p class="mt-1 text-xs text-gray-500">{{ __('vender/luggage.qr_payload_help') }}</p>
        </div>
        <button type="submit" class="w-full rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
            {{ __('vender/luggage.scan_and_reclaim') }}
        </button>
    </form>
    <p class="mt-4 text-center text-sm space-x-3">
        <a href="{{ route($ctx['lookup_route']) }}" class="text-teal-700 hover:underline">{{ __('vender/luggage.lookup_ticket') }}</a>
        <a href="{{ route($ctx['index_route']) }}" class="text-teal-700 hover:underline">{{ __('vender/luggage.tracking') }}</a>
    </p>
</div>
@endsection
