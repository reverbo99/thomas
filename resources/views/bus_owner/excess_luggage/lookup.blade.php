@extends('admin.app')

@section('title', __('vender/luggage.lookup_ticket'))

@section('content')
<div class="container mx-auto px-4 py-6 max-w-xl">
    @include('excess_luggage.partials.flash')
    <h1 class="mb-2 text-2xl font-bold text-gray-800">{{ __('vender/luggage.lookup_ticket') }}</h1>
    <p class="mb-6 text-sm text-gray-500">{{ __('vender/luggage.lookup_hint') }}</p>
    <form method="POST" action="{{ route($ctx['lookup_post']) }}" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.ticket_or_verification') }}</label>
            <input type="text" name="ticket_code" value="{{ old('ticket_code') }}" required autofocus
                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                   placeholder="{{ __('vender/luggage.ticket_placeholder') }}">
        </div>
        <button type="submit" class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
            {{ __('vender/luggage.find_booking') }}
        </button>
    </form>
    <p class="mt-4 text-center text-sm">
        <a href="{{ route($ctx['index_route']) }}" class="text-teal-700 hover:underline">{{ __('vender/luggage.tracking') }}</a>
    </p>
</div>
@endsection
