@extends('system.app')

@section('title', __('vender/luggage.tracking'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @include('excess_luggage.partials.flash')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ __('vender/luggage.admin_tracking') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('vender/luggage.tracking_subtitle') }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('vender/luggage.escrow_tracking_hint') }}</p>
        </div>
        @isset($escrowBalance)
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="rounded-xl border border-cyan-100 bg-cyan-50 px-4 py-3 dark:border-cyan-900/50 dark:bg-cyan-950/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:text-cyan-200">{{ __('vender/luggage.total_luggage_balance') }}</p>
                    <p class="mt-1 text-xl font-bold text-cyan-900 dark:text-cyan-100">{{ session('currency', 'TZS') }} {{ convert_money($luggageBalanceTotal ?? 0) }}</p>
                    <p class="mt-1 text-xs text-cyan-700 dark:text-cyan-300">{{ __('vender/luggage.total_luggage_balance_hint') }}</p>
                </div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 dark:border-indigo-900/50 dark:bg-indigo-950/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">{{ __('vender/luggage.escrow_balance_total') }}</p>
                    <p class="mt-1 text-xl font-bold text-indigo-900 dark:text-indigo-100">{{ session('currency', 'TZS') }} {{ convert_money($escrowBalance) }}</p>
                    <p class="mt-1 text-xs text-indigo-700 dark:text-indigo-300">{{ __('vender/luggage.escrow_balance_total_hint') }}</p>
                    @if(isset($luggageReleasedTotal) && (float) $luggageReleasedTotal > 0)
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                            {{ __('vender/luggage.total_luggage_released') }}: {{ session('currency', 'TZS') }} {{ convert_money($luggageReleasedTotal) }}
                        </p>
                    @endif
                </div>
            </div>
        @endisset
    </div>
    @include('excess_luggage.partials.tracking_table', ['ctx' => ['show_route' => null], 'isAdmin' => true])
</div>
@endsection
