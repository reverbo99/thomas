@extends('vender.app')

@section('title', __('vender/busroot.deposit_to_vendor_wallet'))

@php
    $test_mode = \App\Models\Setting::isTestMode();
@endphp

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => __('all.highlink_isgc'),
        'title' => __('vender/busroot.deposit_to_vendor_wallet'),
        'subtitle' => __('assistance/sidebar.transactions'),
    ])
@endsection

@section('content')
<section class="page-section page-section--alt">
    <div class="container mx-auto px-4 max-w-lg">
        <div class="vendor-panel fade-in">
            <div class="vendor-panel__body">
        <h2 class="mb-6 text-center text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ __('vender/busroot.deposit_to_vendor_wallet') }}
        </h2>
        <p class="mb-4 text-center text-sm text-gray-600 dark:text-gray-300">
            {{ __('assistance/transaction.deposit_wallet_explanation') }}
        </p>

        {{-- Success & Error Messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg border-l-4 border-green-500 bg-green-100 p-4 text-green-700 dark:bg-green-900/30 dark:text-green-100" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-100 p-4 text-red-700 dark:bg-red-900/30 dark:text-red-100" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        {{-- Deposit Form --}}
        <form method="POST" action="{{ route('vender.wallet.processDeposit') }}" class="space-y-5">
            @csrf

            {{-- Amount --}}
            <div>
                <label for="amount" class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('vender/busroot.amount') }}
                </label>
                <input id="amount" type="number" name="amount" min="1"
                    value="{{ old('amount') }}"
                    class="h-10 w-full rounded-lg border-gray-300 bg-white px-2 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100 @error('amount') border-red-500 @enderror"
                    required autofocus>
                @error('amount')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            @if($test_mode)
                <input type="hidden" name="payment_method" value="test_mode">
                <input type="hidden" name="test_deposit_token" value="{{ $testDepositToken }}">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-100" role="status">
                    <p class="font-semibold">{{ __('assistance/transaction.test_mode_deposit_notice') }}</p>
                    <p class="mt-1 text-xs">{{ __('assistance/transaction.test_mode_deposit_hint') }}</p>
                </div>
            @else
            {{-- Payment Method --}}
            <div>
                <label for="payment_method" class="block text-sm font-semibold text-gray-700 mb-1">
                    {{ __('vender/busroot.payment_method') }}
                </label>
                <select id="payment_method" name="payment_method" onchange="toggleDepositMethodFields()"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-200 h-10 px-2 focus:border-blue-500 @error('payment_method') border-red-500 @enderror"
                    required>
                    <option value="">{{ __('vender/busroot.select_method') }}</option>
                    {{-- <option value="tigosecure" {{ old('payment_method') == 'tigosecure' ? 'selected' : '' }}>Tigosecure</option> --}}
                    <option value="clickpesa" {{ old('payment_method') == 'clickpesa' ? 'selected' : '' }}>{{ __('assistance/transaction.clickpesa_mobile_money') }}</option>
                    <option value="pdo" {{ old('payment_method') == 'pdo' ? 'selected' : '' }}>{{ __('vender/busroot.pdo') }}</option>
                </select>
                @error('payment_method')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="clickpesa-fields" class="space-y-4"
                style="display: {{ old('payment_method') == 'clickpesa' ? 'block' : 'none' }};">
                <div>
                    <label for="deposit_phone" class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('assistance/transaction.mobile_number_ussd_clickpesa') }}
                    </label>
                    <input id="deposit_phone" type="text" name="deposit_phone"
                        value="{{ old('deposit_phone', auth()->user()->contact ?? auth()->user()->phone ?? '') }}"
                        placeholder="2557xxxxxxxx or 07xxxxxxxx"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-200 h-10 px-2 focus:border-blue-500 @error('deposit_phone') border-red-500 @enderror">
                    <p class="text-xs text-gray-500 mt-1">{{ __('assistance/transaction.deposit_phone_hint') }}</p>
                    @error('deposit_phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Tigosecure Fields --}}
            <div id="tigosecure-fields" class="space-y-4"
                style="display: {{ old('payment_method') == 'tigosecure' ? 'block' : 'none' }};">

                <div>
                    <label for="phone_number" class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('vender/busroot.phone_number_tigosecure') }}
                    </label>
                    <input id="phone_number" type="text" name="phone_number"
                        value="{{ old('phone_number') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-200 h-10 px-2 focus:border-blue-500 @error('phone_number') border-red-500 @enderror">
                    @error('phone_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('vender/busroot.first_name_tigosecure') }}
                    </label>
                    <input id="first_name" type="text" name="first_name"
                        value="{{ old('first_name') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-200 h-10 px-2 focus:border-blue-500 @error('first_name') border-red-500 @enderror">
                    @error('first_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('vender/busroot.last_name_tigosecure') }}
                    </label>
                    <input id="last_name" type="text" name="last_name"
                        value="{{ old('last_name') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-200 h-10 px-2 focus:border-blue-500 @error('last_name') border-red-500 @enderror">
                    @error('last_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">
                        {{ __('vender/busroot.email_tigosecure') }}
                    </label>
                    <input id="email" type="email" name="email"
                        value="{{ old('email') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-200 h-10 px-2 focus:border-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            @endif

            {{-- Submit Button --}}
            <div class="text-center">
                <button type="submit" class="w-full page-btn">
                    {{ $test_mode
                        ? __('assistance/transaction.deposit_test_mode_button')
                        : __('vender/busroot.deposit_button') }}
                </button>
            </div>
        </form>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    function toggleDepositMethodFields() {
        const paymentMethod = document.getElementById('payment_method').value;
        const tigosecureFields = document.getElementById('tigosecure-fields');
        const clickpesaFields = document.getElementById('clickpesa-fields');
        if (tigosecureFields) tigosecureFields.style.display = paymentMethod === 'tigosecure' ? 'block' : 'none';
        if (clickpesaFields) clickpesaFields.style.display = paymentMethod === 'clickpesa' ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', toggleDepositMethodFields);
</script>
@endpush
@endsection
