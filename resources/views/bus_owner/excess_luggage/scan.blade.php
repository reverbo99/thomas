@extends('admin.app')

@section('title', __('vender/luggage.scan_exit_title'))

@section('content')
<div class="container mx-auto px-4 py-6 max-w-xl">
    @include('excess_luggage.partials.flash')
    <h1 class="mb-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('vender/luggage.scan_exit_title') }}</h1>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">{{ __('vender/luggage.scan_exit_hint') }}</p>
    <form method="POST" action="{{ route($ctx['scan_post']) }}" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm space-y-4 dark:border-gray-700 dark:bg-gray-900">
        @csrf
        <div>
            <label for="luggage_scan_code" class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('vender/luggage.scan_code_label') }}</label>
            <input type="text" id="luggage_scan_code" name="qr_payload" value="{{ old('qr_payload') }}" required autofocus
                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                   placeholder="{{ __('vender/luggage.scan_code_placeholder') }}"
                   aria-describedby="luggage_scan_help"
                   autocomplete="off" autocapitalize="characters" spellcheck="false" enterkeyhint="go">
            <p id="luggage_scan_help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('vender/luggage.scan_code_help') }}</p>
        </div>
        <button type="submit" class="w-full rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
            {{ __('vender/luggage.scan_and_reclaim') }}
        </button>
    </form>
    <p class="mt-4 text-center text-sm space-x-3">
        <a href="{{ route($ctx['lookup_route']) }}" class="text-teal-700 hover:underline dark:text-teal-300">{{ __('vender/luggage.lookup_ticket') }}</a>
        <a href="{{ route($ctx['index_route']) }}" class="text-teal-700 hover:underline dark:text-teal-300">{{ __('vender/luggage.tracking') }}</a>
    </p>
</div>
@endsection
