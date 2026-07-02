@extends('customer.app')

@section('content')
    <section class="bg-gradient-to-b from-gray-50 to-gray-100 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ __('customer/busroot.complete_your_payment') }}</h2>
                </div>
                <div class="text-sm text-white bg-red-600 px-4 py-2 rounded-lg shadow-sm flex items-center">
                    <i class="fas fa-clock mr-2"></i>
                    <span>{{ __('customer/busroot.your_session_expires_in') }} <span id="minutes">06</span>
                        {{ __('customer/busroot.mins') }} <span id="seconds">40</span>
                        {{ __('customer/busroot.secs') }}</span>
                </div>
            </div>

            @if (session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm font-semibold text-red-800 mb-1">{{ __('customer/busroot.payment_error') }}:</p>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm font-semibold text-red-800 mb-1">{{ __('customer/busroot.payment_error') }}:</p>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Payment Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Details Card -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                {{ __('customer/busroot.contact_details') }}
                            </h3>
                            <p class="text-sm text-gray-600 mb-4">{{ __('customer/busroot.fill_traveler_details') }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Country Code -->
                                <div>
                                    <label for="countrycode"
                                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.country_code') }}</label>
                                    <select id="countrycode" onchange="setPhoneMaxLength()"
                                        class="w-full px-4 text-gray-600 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">{{ __('customer/busroot.select_country_code') }}</option>
                                        <option value="+255" selected>{{ __('customer/busroot.tz_code') }}</option>
                                    </select>
                                </div>

                                <!-- Mobile Number -->
                                <div>
                                    <label for="contactNumber"
                                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.mobile_number') }}</label>
                                    <input type="text" id="contactNumber" maxlength="12" onkeyup="CheckMobLen(this)"
                                        class="w-full text-black px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 onlydigits"
                                        placeholder="{{ __('customer/busroot.enter_mobile_number') }}" required>
                                </div>

                                <!-- Email -->
                                <div class="md:col-span-2">
                                    <label for="contactEmail"
                                        class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.email_address') }}</label>
                                    <input type="email" id="contactEmail" maxlength="50" autocomplete="off"
                                        class="w-full text-black px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="{{ __('customer/busroot.enter_email_address') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (!($test_mode ?? false))
                    <!-- Payment Options Card -->
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                {{ __('customer/busroot.payment_options') }}
                            </h3>

                            <div class="flex flex-col md:flex-row gap-6">
                                <!-- Payment Methods Sidebar -->
                                <div class="md:w-1/3">
                                    <div class="space-y-2" role="tablist"
                                        aria-label="{{ __('customer/busroot.payment_methods') }}">
                                        <button type="button"
                                            class="w-full text-left px-4 py-3 rounded-lg bg-blue-100 text-blue-700 font-medium"
                                            id="tab1-btn" data-bs-toggle="tab" data-bs-target="#tab1" role="tab"
                                            aria-controls="tab1" aria-selected="true">
                                            <i class="fas fa-mobile-alt mr-2"></i> {{ __('customer/busroot.mixx_by_yas') }}
                                        </button>
                                        <button type="button"
                                            class="w-full text-left px-4 py-3 rounded-lg bg-white hover:bg-gray-100 text-blue-700" id="tab2-btn"
                                            data-bs-toggle="tab" data-bs-target="#tab2" role="tab" aria-controls="tab2">
                                            <i class="fas fa-credit-card mr-2"></i>
                                            {{ __('customer/busroot.dpo_payment') }}
                                        </button>
                                        @if (auth()->user()->role == 'vender')
                                            <button type="button"
                                                class="w-full text-left px-4 py-3 rounded-lg bg-white hover:bg-gray-100 text-blue-700" id="tab3-btn"
                                                data-bs-toggle="tab" data-bs-target="#tab3" role="tab" aria-controls="tab3">
                                                <i class="fas fa-money-bill mr-2"></i>
                                                {{ __('customer/busroot.cash_payment') }}
                                            </button>
                                        @endif
                                        <button type="button"
                                            class="w-full text-left px-4 py-3 rounded-lg bg-white hover:bg-gray-100 text-blue-700" id="tab5-btn"
                                            data-bs-toggle="tab" data-bs-target="#tab5" role="tab" aria-controls="tab5">
                                            <i class="fas fa-wallet mr-2"></i>
                                            {{ __('customer/busroot.clickpesa_payment') }}
                                        </button>
                                        <button type="button"
                                            class="w-full text-left px-4 py-3 rounded-lg bg-white hover:bg-gray-100 text-blue-700" id="tab7-btn"
                                            data-bs-toggle="tab" data-bs-target="#tab7" role="tab" aria-controls="tab7">
                                            <i class="fas fa-wallet mr-2"></i>
                                            {{ __('all.wallet') }}
                                        </button>
                                        <button type="button"
                                            class="w-full text-left px-4 py-3 rounded-lg bg-white hover:bg-gray-100 text-blue-700" id="tab6-btn"
                                            data-bs-toggle="tab" data-bs-target="#tab6" role="tab" aria-controls="tab6">
                                            <i class="fas fa-sim-card mr-2"></i> {{ __('all.airtel_money') }}
                                        </button>
                                        <button type="button"
                                            class="w-full text-left px-4 py-3 rounded-lg bg-white hover:bg-gray-100 text-blue-700" id="tab4-btn"
                                            data-bs-toggle="tab" data-bs-target="#tab4" role="tab" aria-controls="tab4">
                                            <i class="fas fa-bookmark mr-2"></i> {{ __('customer/busroot.resave_ticket') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Payment Method Content -->
                                <div class="md:w-2/3">
                                    <div class="tab-content">
                                        <!-- Mixx By Yas Payment -->
                                        <div id="tab1" class="tab-pane active" role="tabpanel" aria-labelledby="tab1-btn">
                                            <form id="tigo" action="{{ route('customer.verify') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="payment_method" value="mixx">
                                                <div class="space-y-4">
                                                    <div class="p-4 bg-blue-50 rounded-lg">
                                                        <p class="text-sm text-gray-700 mb-1">
                                                            {{ __('customer/busroot.session_expiry_warning') }}
                                                        </p>
                                                    </div>

                                                    <p class="text-gray-700">
                                                        {{ __('customer/busroot.enter_yas_mobile_number') }}
                                                    </p>

                                                    <div>
                                                        <label for="paymentContact"
                                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.mobile_number') }}</label>
                                                        <input type="text" name="payment_contact" id="paymentContact"
                                                            maxlength="10"
                                                            class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 onlydigits"
                                                            placeholder="{{ __('customer/busroot.connected_mobile_number') }}"
                                                            required>
                                                    </div>

                                                    <input type="hidden" name="amount" id="cust_mixx_amount" value="{{ round($price + $fees, 2) }}">

                                                    <div class="flex items-start">
                                                        <div class="flex items-center h-5">
                                                            <input id="payment_term_0" name="payment_term_0" type="checkbox"
                                                                value="1" checked
                                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                        </div>
                                                        <div class="ml-3 text-sm">
                                                            <label for="payment_term_0"
                                                                class="font-medium text-gray-700">{{ __('customer/busroot.i_accept') }}
                                                                <a href="{{ route('ticket.purchase') }}"
                                                                    class="text-blue-600 hover:text-blue-500">{{ __('customer/busroot.terms_and_conditions') }}</a></label>
                                                        </div>
                                                    </div>

                                                    <div class="hidden bg-white rounded-xl shadow-md overflow-hidden mt-4">
                                                        <div class="p-4">
                                                            <h4 class="text-md font-semibold text-gray-800 mb-3">
                                                                <i class="fas fa-receipt mr-2 text-blue-500"></i>
                                                                {{ __('customer/busroot.order_summary') }}
                                                            </h4>
                                                            <div class="space-y-2">
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.discount') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($dis) }}</span>
                                                                </div>
                                                                @if (isset($ins) && $ins > 0)
                                                                    <div class="flex justify-between">
                                                                        <span
                                                                            class="text-sm text-gray-600">{{ __('customer/busroot.insurance') }}</span>
                                                                        <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                            {{ convert_money($ins) }}</span>
                                                                    </div>
                                                                @endif
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.system_charge') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($fees) }}</span>
                                                                </div>
                                                                @if(($excess_luggage_fee ?? 0) > 0)
                                                                    <div class="flex justify-between">
                                                                        <span
                                                                            class="text-sm text-gray-600">{{ __('customer/busroot.excess_luggage') }}</span>
                                                                        <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                            {{ convert_money($excess_luggage_fee) }}</span>
                                                                    </div>
                                                                @endif
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.bus_fare') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($price - $ins) }}</span>
                                                                </div>
                                                                <div
                                                                    class="border-t border-gray-200 pt-2 mt-2 flex justify-between">
                                                                    <span
                                                                        class="text-base font-semibold">{{ __('customer/busroot.total_payable') }}</span>
                                                                    <span class="text-base font-bold text-blue-600">
                                                                        {{ $currency }} {{ convert_money($price + $fees) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="submit"
                                                        class="w-full mt-4 py-3 px-6 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                                                        <i class="fas fa-lock mr-2"></i>
                                                        {{ __('customer/busroot.proceed_to_pay') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- DPO Payment -->
                                        <div id="tab2" class="tab-pane" role="tabpanel" aria-labelledby="tab2-btn">
                                            <form id="dpo" action="{{ route('customer.verify') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="payment_method" value="dpo">
                                                <div class="space-y-4">
                                                    <div class="p-4 bg-blue-50 rounded-lg">
                                                        <p class="text-sm text-gray-700 mb-1">
                                                            {{ __('customer/busroot.session_expiry_warning') }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <label for="dpo_amount_display"
                                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.amount') }}</label>
                                                        <input type="text" id="dpo_amount_display"
                                                            value="{{ convert_money($price + $fees) }}" readonly
                                                            class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            required>
                                                        <input type="hidden" name="amount" id="dpo_amount"
                                                            value="{{ round($price + $fees, 2) }}">
                                                    </div>

                                                    <!--
                                                        <div>
                                                            <label for="first_name"
                                                                class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.first_name') }}</label>
                                                            <input type="text" name="first_name" id="first_name"
                                                                class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="{{ __('customer/busroot.enter_first_name') }}"
                                                                required>
                                                        </div>
                                                        -->

                                                    <!--
                                                        <div>
                                                            <label for="last_name"
                                                                class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.last_name') }}</label>
                                                            <input type="text" name="last_name" id="last_name"
                                                                class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="{{ __('customer/busroot.enter_last_name') }}"
                                                                required>
                                                        </div>
                                                        -->

                                                    <div>
                                                        <label for="phone"
                                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.mobile_number') }}</label>
                                                        <!-- <input type="text" name="customer_number" id="phone"
                                                                maxlength="12"
                                                                class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 onlydigits"
                                                                placeholder="{{ __('customer/busroot.enter_mobile_number') }}"
                                                                required> -->
                                                    </div>

                                                    <!--
                                                        <div>
                                                            <label for="email"
                                                                class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.email_address') }}</label>
                                                            <input type="email" name="customer_email" id="email"
                                                                class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="{{ __('customer/busroot.enter_email_address') }}"
                                                                required>
                                                        </div>
                                                        -->

                                                    <div class="flex items-start">
                                                        <div class="flex items-center h-5">
                                                            <input id="dpo_terms" name="dpo_terms" type="checkbox" value="1"
                                                                checked
                                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                        </div>
                                                        <div class="ml-3 text-sm">
                                                            <label for="dpo_terms"
                                                                class="font-medium text-gray-700">{{ __('customer/busroot.i_accept') }}
                                                                <a href="{{ route('ticket.purchase') }}"
                                                                    class="text-blue-600 hover:text-blue-500">{{ __('customer/busroot.terms_and_conditions') }}</a></label>
                                                        </div>
                                                    </div>

                                                    <div class="hidden bg-white rounded-xl shadow-md overflow-hidden mt-4">
                                                        <div class="p-4">
                                                            <h4 class="text-md font-semibold text-gray-800 mb-3">
                                                                <i class="fas fa-receipt mr-2 text-blue-500"></i>
                                                                {{ __('customer/busroot.order_summary') }}
                                                            </h4>
                                                            <div class="space-y-2">
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.discount') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($dis) }}</span>
                                                                </div>
                                                                @if (isset($ins) && $ins > 0)
                                                                    <div class="flex justify-between">
                                                                        <span
                                                                            class="text-sm text-gray-600">{{ __('customer/busroot.insurance') }}</span>
                                                                        <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                            {{ convert_money($ins) }}</span>
                                                                    </div>
                                                                @endif
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.system_charge') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($fees) }}</span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.bus_fare') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($price - $ins) }}</span>
                                                                </div>
                                                                <div
                                                                    class="border-t border-gray-200 pt-2 mt-2 flex justify-between">
                                                                    <span
                                                                        class="text-base font-semibold">{{ __('customer/busroot.total_payable') }}</span>
                                                                    <span class="text-base font-bold text-blue-600">
                                                                        {{ $currency }} {{ convert_money($price + $fees) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="submit"
                                                        class="w-full mt-4 py-3 px-6 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                                                        <i class="fas fa-lock mr-2"></i>
                                                        {{ __('customer/busroot.proceed_to_pay') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Cash Payment -->
                                        <div id="tab3" class="tab-pane" role="tabpanel" aria-labelledby="tab3-btn">
                                            <form id="cash" action="{{ route('customer.verify') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="payment_method" value="cash">
                                                <input type="hidden" name="amount" value="{{ $price + $fees }}">
                                                <div class="space-y-4">
                                                    <div class="p-4 bg-blue-50 rounded-lg">
                                                        <p class="text-sm text-gray-700 mb-1">
                                                            {{ __('customer/busroot.session_expiry_warning') }}
                                                        </p>
                                                    </div>

                                                    <div class="hidden bg-white rounded-xl shadow-md overflow-hidden mt-4">
                                                        <div class="p-4">
                                                            <h4 class="text-md font-semibold text-gray-800 mb-3">
                                                                <i class="fas fa-receipt mr-2 text-blue-500"></i>
                                                                {{ __('customer/busroot.order_summary') }}
                                                            </h4>
                                                            <div class="space-y-2">
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.discount') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($dis) }}</span>
                                                                </div>
                                                                @if (isset($ins) && $ins > 0)
                                                                    <div class="flex justify-between">
                                                                        <span
                                                                            class="text-sm text-gray-600">{{ __('customer/busroot.insurance') }}</span>
                                                                        <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                            {{ convert_money($ins) }}</span>
                                                                    </div>
                                                                @endif
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.system_charge') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($fees) }}</span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.bus_fare') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($price - $ins) }}</span>
                                                                </div>
                                                                <div
                                                                    class="border-t border-gray-200 pt-2 mt-2 flex justify-between">
                                                                    <span
                                                                        class="text-base font-semibold">{{ __('customer/busroot.total_payable') }}</span>
                                                                    <span class="text-base font-bold text-blue-600">
                                                                        {{ $currency }} {{ convert_money($price + $fees) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="submit"
                                                        class="w-full mt-4 py-3 px-6 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                                                        <i class="fas fa-money-bill mr-2"></i>
                                                        {{ __('customer/busroot.confirm_cash_payment') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- Resave Ticket -->
                                        <!-- Airtel Money Tab -->
                                        <div id="tab6" class="tab-pane" role="tabpanel" aria-labelledby="tab6-btn">
                                            <div class="space-y-4">
                                                <div class="p-4 bg-red-50 rounded-lg border border-red-100">
                                                    <p class="text-sm text-gray-700">{{ __('all.airtel_payment_prompt') }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('all.airtel_money_number') }}</label>
                                                    <input type="text" id="cust_airtel_phone" maxlength="12"
                                                        class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 onlydigits"
                                                        placeholder="e.g. 0780000000">
                                                </div>
                                                <p class="text-sm text-gray-600">{{ __('all.total_to_pay_label') }} <strong id="cust_airtel_total_display">{{ $currency }} {{ convert_money($price + $fees) }}</strong></p>
                                                <button type="button" id="cust_airtel_pay_btn"
                                                    class="w-full mt-2 py-3 px-6 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                                                    <i class="fas fa-lock mr-2"></i> {{ __('all.pay_with_airtel_money') }}
                                                </button>
                                                <p id="cust_airtel_status_msg" class="text-sm text-center hidden"></p>
                                            </div>
                                        </div>

                                        <div id="tab4" class="tab-pane" role="tabpanel" aria-labelledby="tab4-btn">
                                            <form id="resave-form" action="{{ route('customer.verify') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="payment_method" value="resave">
                                                <input type="hidden" name="resave_ticket" value="1">
                                                <input type="hidden" name="amount" value="{{ $price + $fees }}">
                                                <div class="space-y-4">
                                                    <div class="p-4 bg-yellow-50 rounded-lg">
                                                        <p class="text-sm text-yellow-700 mb-1">
                                                            {{ __('customer/busroot.resave_warning') }}
                                                        </p>
                                                    <p class="text-lg font-bold text-yellow-800">
                                                        {{ __('customer/busroot.total_to_resave') }}
                                                        {{ $currency }} {{ convert_money($price + $fees) }}
                                                    </p>
                                                    </div>
                                                    <p class="text-gray-700">
                                                        {{ __('customer/busroot.resave_description') }}
                                                    </p>

                                                    <div class="flex items-start">
                                                        <div class="flex items-center h-5">
                                                            <input id="resave_terms" name="resave_terms" type="checkbox"
                                                                value="1" checked
                                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                        </div>
                                                        <div class="ml-3 text-sm">
                                                            <label for="resave_terms"
                                                                class="font-medium text-gray-700">{{ __('customer/busroot.i_accept') }}
                                                                <a href="{{ route('ticket.purchase') }}"
                                                                    class="text-blue-600 hover:text-blue-500">{{ __('customer/busroot.terms_and_conditions') }}</a></label>
                                                        </div>
                                                    </div>

                                                    <div class="hidden bg-white rounded-xl shadow-md overflow-hidden mt-4">
                                                        <div class="p-4">
                                                            <h4 class="text-md font-semibold text-gray-800 mb-3">
                                                                <i class="fas fa-receipt mr-2 text-blue-500"></i>
                                                                {{ __('customer/busroot.order_summary') }}
                                                            </h4>
                                                            <div class="space-y-2">
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.discount') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($dis) }}</span>
                                                                </div>
                                                                @if (isset($ins) && $ins > 0)
                                                                    <div class="flex justify-between">
                                                                        <span
                                                                            class="text-sm text-gray-600">{{ __('customer/busroot.insurance') }}</span>
                                                                        <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                            {{ convert_money($ins) }}</span>
                                                                    </div>
                                                                @endif
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.system_charge') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($fees) }}</span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.bus_fare') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($price - $ins) }}</span>
                                                                </div>
                                                                <div
                                                                    class="border-t border-gray-200 pt-2 mt-2 flex justify-between">
                                                                    <span
                                                                        class="text-base font-semibold">{{ __('customer/busroot.total_payable') }}</span>
                                                                    <span class="text-base font-bold text-blue-600">
                                                                        {{ $currency }} {{ convert_money($price + $fees) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="submit"
                                                        class="w-full mt-4 py-3 px-6 bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                                                        <i class="fas fa-bookmark mr-2"></i>
                                                        {{ __('customer/busroot.resave_ticket_button') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <!-- ClickPesa Payment -->
                                        <div id="tab5" class="tab-pane" role="tabpanel" aria-labelledby="tab5-btn">
                                            <form id="clickpesa" action="{{ route('customer.verify') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="payment_method" value="clickpesa">
                                                <div class="space-y-4">
                                                    <div class="p-4 bg-blue-50 rounded-lg">
                                                        <p class="text-sm text-gray-700 mb-1">
                                                            {{ __('customer/busroot.session_expiry_warning') }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <label for="clickpesa_amount_display"
                                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.amount') }}</label>
                                                        <input type="text" id="clickpesa_amount_display"
                                                            value="{{ convert_money($price + $fees) }}" readonly
                                                            class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            required>
                                                        <input type="hidden" name="amount" id="clickpesa_amount"
                                                            value="{{ round($price + $fees, 2) }}">
                                                    </div>

                                                    <div>
                                                        <label for="clickpesaPaymentContact"
                                                            class="block text-sm font-medium text-gray-700 mb-1">{{ __('customer/busroot.mobile_number') }}</label>
                                                        <input type="text" name="payment_contact" id="clickpesaPaymentContact"
                                                            maxlength="12"
                                                            class="text-black w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 onlydigits"
                                                            placeholder="{{ __('customer/busroot.connected_mobile_number') }}"
                                                            required>
                                                    </div>

                                                    <div class="flex items-start">
                                                        <div class="flex items-center h-5">
                                                            <input id="clickpesa_terms" name="clickpesa_terms"
                                                                type="checkbox" value="1" checked
                                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                        </div>
                                                        <div class="ml-3 text-sm">
                                                            <label for="clickpesa_terms"
                                                                class="font-medium text-gray-700">{{ __('customer/busroot.i_accept') }}
                                                                <a href="{{ route('ticket.purchase') }}"
                                                                    class="text-blue-600 hover:text-blue-500">{{ __('customer/busroot.terms_and_conditions') }}</a></label>
                                                        </div>
                                                    </div>

                                                    <div class="hidden bg-white rounded-xl shadow-md overflow-hidden mt-4">
                                                        <div class="p-4">
                                                            <h4 class="text-md font-semibold text-gray-800 mb-3">
                                                                <i class="fas fa-receipt mr-2 text-blue-500"></i>
                                                                {{ __('customer/busroot.order_summary') }}
                                                            </h4>
                                                            <div class="space-y-2">
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.discount') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($dis) }}</span>
                                                                </div>
                                                                @if (isset($ins) && $ins > 0)
                                                                    <div class="flex justify-between">
                                                                        <span
                                                                            class="text-sm text-gray-600">{{ __('customer/busroot.insurance') }}</span>
                                                                        <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                            {{ convert_money($ins) }}</span>
                                                                    </div>
                                                                @endif
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.system_charge') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($fees) }}</span>
                                                                </div>
                                                                <div class="flex justify-between">
                                                                    <span
                                                                        class="text-sm text-gray-600">{{ __('customer/busroot.bus_fare') }}</span>
                                                                    <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                                                        {{ convert_money($price - $ins) }}</span>
                                                                </div>
                                                                <div
                                                                    class="border-t border-gray-200 pt-2 mt-2 flex justify-between">
                                                                    <span
                                                                        class="text-base font-semibold">{{ __('customer/busroot.total_payable') }}</span>
                                                                    <span class="text-base font-bold text-blue-600">
                                                                        {{ $currency }} {{ convert_money($price + $fees) }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="submit"
                                                        class="w-full mt-4 py-3 px-6 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                                                        <i class="fas fa-lock mr-2"></i>
                                                        {{ __('customer/busroot.proceed_to_pay') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <div id="tab7" class="tab-pane" role="tabpanel" aria-labelledby="tab7-btn">
                                            <form id="walletpay" action="{{ route('customer.verify') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="payment_method" value="wallet">
                                                <input type="hidden" name="amount" value="{{ round($price + $fees, 2) }}">
                                                <div class="space-y-4">
                                                    <div class="p-4 bg-blue-50 rounded-lg">
                                                        <p class="text-sm text-gray-700 mb-1">
                                                            {{ __('all.wallet_balance') }}:
                                                            <strong>{{ $currency }}
                                                                {{ convert_money(auth()->user()->temp_wallets->amount ?? 0) }}</strong>
                                                        </p>
                                                        <p class="text-sm text-gray-700 mb-1">
                                                            {{ __('all.amount_to_pay') }}:
                                                            <strong>{{ $currency }} {{ convert_money($price + $fees) }}</strong>
                                                        </p>
                                                    </div>
                                                    <div class="flex items-start">
                                                        <div class="flex items-center h-5">
                                                            <input id="wallet_terms" name="wallet_terms"
                                                                type="checkbox" value="1" checked
                                                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                        </div>
                                                        <div class="ml-3 text-sm">
                                                            <label for="wallet_terms"
                                                                class="font-medium text-gray-700">{{ __('customer/busroot.i_accept') }}
                                                                <a href="{{ route('ticket.purchase') }}"
                                                                    class="text-blue-600 hover:text-blue-500">{{ __('customer/busroot.terms_and_conditions') }}</a></label>
                                                        </div>
                                                    </div>
                                                    <button type="submit"
                                                        class="w-full mt-4 py-3 px-6 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
                                                        <i class="fas fa-lock mr-2"></i>
                                                        {{ __('all.pay_with_wallet') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    @include('partials.payment_checkout_test_mode', [
                        'verifyAction' => route('customer.verify'),
                        'amount' => round($price + $fees, 2),
                        'langNs' => 'customer/busroot',
                    ])
                    @endif
                </div>

                <!-- Right Column - Price Summary -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden h-fit sticky top-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-receipt mr-2 text-blue-500"></i> {{ __('customer/busroot.price_summary') }}
                        </h3>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">{{ __('customer/busroot.discount') }}</span>
                                <span class="text-sm font-medium text-gray-500">{{ $currency }} {{ convert_money($dis) }}</span>
                            </div>

                            @if (isset($ins) && $ins > 0)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">{{ __('customer/busroot.insurance') }}</span>
                                    <span class="text-sm font-medium text-gray-500">{{ $currency }} {{ convert_money($ins) }}</span>
                                </div>
                            @endif

                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">{{ __('customer/busroot.system_charge') }}</span>
                                <span class="text-sm font-medium text-gray-500">{{ $currency }} {{ convert_money($fees) }}</span>
                            </div>
                            @if(($excess_luggage_fee ?? 0) > 0)
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">{{ __('customer/busroot.excess_luggage') }}</span>
                                    <span class="text-sm font-medium text-gray-500">{{ $currency }} {{ convert_money($excess_luggage_fee) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">{{ __('customer/busroot.bus_fare') }}</span>
                                <span class="text-sm font-medium text-gray-500">{{ $currency }}
                                    {{ convert_money($price - $ins) }}</span>
                            </div>

                            <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between">
                                <span class="text-base font-semibold">{{ __('customer/busroot.total_payable') }}</span>
                                <span class="text-base font-bold text-blue-600" id="total-payable-display">
                                    {{ $currency }} {{ convert_money($price + $fees) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-50 rounded-lg">
                            <p class="flex items-center text-sm text-blue-700">
                                <i class="fas fa-shield-alt mr-2"></i> {{ __('customer/busroot.secure_ssl_payment') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        @include('partials.tz_phone_normalize_js')

        const custPayI18n = {
            enterPhone: @json(__('all.enter_phone_required')),
            enterMobileMoney: @json(__('all.enter_mobile_money_to_charge')),
            enterAirtel: @json(__('all.enter_airtel_money_number')),
            payAirtel: @json(__('all.pay_with_airtel_money')),
            promptSent: @json(__('all.prompt_sent_phone')),
            paymentFailed: @json(__('all.payment_failed_try_again')),
            networkError: @json(__('all.network_error_try_again')),
        };

        // Timer countdown functionality
        function startTimer(duration, displayMinutes, displaySeconds) {
            let timer = duration,
                minutes, seconds;
            setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                displayMinutes.textContent = minutes;
                displaySeconds.textContent = seconds;

                if (--timer < 0) {
                    timer = duration;
                }
            }, 1000);
        }

        window.onload = function () {
            const fiveMinutes = 60 * 6 + 40, // 6 minutes and 40 seconds
                displayMinutes = document.querySelector('#minutes'),
                displaySeconds = document.querySelector('#seconds');
            startTimer(fiveMinutes, displayMinutes, displaySeconds);
        };

        @unless($test_mode ?? false)
        // Form submission handler for Tigo form
        document.getElementById('tigo').addEventListener('submit', function (event) {
            event.preventDefault();

            // Get contact details
            const code = document.getElementById('countrycode').value;
            const phone = normalizePhoneTo255(document.getElementById('contactNumber').value);
            const email = document.getElementById('contactEmail').value.trim();

            if (!phone) {
                alert(custPayI18n.enterPhone);
                return;
            }

            var paymentContactEl = document.getElementById('paymentContact');
            if (paymentContactEl) {
                paymentContactEl.value = normalizePhoneTo255(paymentContactEl.value);
            }

            // Create hidden inputs
            const codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'countrycode';
            codeInput.value = code;

            const phoneInput = document.createElement('input');
            phoneInput.type = 'hidden';
            phoneInput.name = 'contactNumber';
            phoneInput.value = phone;

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'contactEmail';
            emailInput.value = email;

            // Append to form
            this.appendChild(codeInput);
            this.appendChild(phoneInput);
            this.appendChild(emailInput);

            // Submit form
            this.submit();
        });

        // Form submission handler for DPO form
        document.getElementById('dpo').addEventListener('submit', function (event) {
            event.preventDefault();

            // Get contact details
            const code = document.getElementById('countrycode').value;
            const phone = normalizePhoneTo255(document.getElementById('contactNumber').value);
            const email = document.getElementById('contactEmail').value.trim();

            if (!phone) {
                alert(custPayI18n.enterPhone);
                return;
            }

            // Create hidden inputs
            const codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'countrycode';
            codeInput.value = code;

            const phoneInput = document.createElement('input');
            phoneInput.type = 'hidden';
            phoneInput.name = 'contactNumber';
            phoneInput.value = phone;

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'contactEmail';
            emailInput.value = email;

            // Append to form
            this.appendChild(codeInput);
            this.appendChild(phoneInput);
            this.appendChild(emailInput);

            // Submit form
            this.submit();
        });

        // Form submission handler for Cash form
        document.getElementById('cash').addEventListener('submit', function (event) {
            event.preventDefault();

            // Get contact details
            const code = document.getElementById('countrycode').value;
            const phone = normalizePhoneTo255(document.getElementById('contactNumber').value);
            const email = document.getElementById('contactEmail').value.trim();

            if (!phone) {
                alert(custPayI18n.enterPhone);
                return;
            }

            // Create hidden inputs
            const codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'countrycode';
            codeInput.value = code;

            const phoneInput = document.createElement('input');
            phoneInput.type = 'hidden';
            phoneInput.name = 'contactNumber';
            phoneInput.value = phone;

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'contactEmail';
            emailInput.value = email;

            // Append to form
            this.appendChild(codeInput);
            this.appendChild(phoneInput);
            this.appendChild(emailInput);

            // Submit form
            this.submit();
        });

        // Form submission handler for Resave form
        document.getElementById('resave-form').addEventListener('submit', function (event) {
            event.preventDefault();

            // Get contact details
            const code = document.getElementById('countrycode').value;
            const phone = normalizePhoneTo255(document.getElementById('contactNumber').value);
            const email = document.getElementById('contactEmail').value.trim();

            if (!phone) {
                alert(custPayI18n.enterPhone);
                return;
            }

            // Create hidden inputs
            const codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'countrycode';
            codeInput.value = code;

            const phoneInput = document.createElement('input');
            phoneInput.type = 'hidden';
            phoneInput.name = 'contactNumber';
            phoneInput.value = phone;

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'contactEmail';
            emailInput.value = email;

            // Append to form
            this.appendChild(codeInput);
            this.appendChild(phoneInput);
            this.appendChild(emailInput);

            // Submit form
            this.submit();
        });

        // Form submission handler for ClickPesa form
        document.getElementById('clickpesa').addEventListener('submit', function (event) {
            event.preventDefault();

            // Get contact details
            const code = document.getElementById('countrycode').value;
            const phone = normalizePhoneTo255(document.getElementById('contactNumber').value);
            const email = document.getElementById('contactEmail').value.trim();

            // ClickPesa charges this mobile money number (must be 255 + 9 digits)
            const payContactEl = document.getElementById('clickpesaPaymentContact');
            const payPhone = normalizePhoneTo255(payContactEl ? payContactEl.value : '');

            if (!phone) {
                alert(custPayI18n.enterPhone);
                return;
            }

            if (!payPhone) {
                alert(custPayI18n.enterMobileMoney);
                return;
            }

            // Normalize the mobile money number that ClickPesa will push to
            if (payContactEl) {
                payContactEl.value = payPhone;
            }

            // Create hidden inputs
            const codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'countrycode';
            codeInput.value = code;

            const phoneInput = document.createElement('input');
            phoneInput.type = 'hidden';
            phoneInput.name = 'contactNumber';
            phoneInput.value = phone;

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'contactEmail';
            emailInput.value = email;

            // Append to form
            this.appendChild(codeInput);
            this.appendChild(phoneInput);
            this.appendChild(emailInput);

            // Submit form
            this.submit();
        });

        document.getElementById('walletpay').addEventListener('submit', function (event) {
            event.preventDefault();

            const code = document.getElementById('countrycode').value;
            const phone = normalizePhoneTo255(document.getElementById('contactNumber').value);
            const email = document.getElementById('contactEmail').value.trim();

            if (!phone) {
                alert(custPayI18n.enterPhone);
                return;
            }

            const codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'countrycode';
            codeInput.value = code;

            const phoneInput = document.createElement('input');
            phoneInput.type = 'hidden';
            phoneInput.name = 'contactNumber';
            phoneInput.value = phone;

            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'contactEmail';
            emailInput.value = email;

            this.appendChild(codeInput);
            this.appendChild(phoneInput);
            this.appendChild(emailInput);
            this.submit();
        });

        // Tab functionality
        document.querySelectorAll('[role="tablist"] button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[role="tablist"] button').forEach(btn => {
                    btn.classList.remove('bg-blue-100', 'text-blue-700', 'font-medium');
                    btn.classList.add('bg-white', 'text-blue-700', 'hover:bg-gray-100');
                });
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });
                button.classList.add('bg-blue-100', 'text-blue-700', 'font-medium');
                button.classList.remove('bg-white', 'hover:bg-gray-100');
                document.querySelector(button.dataset.bsTarget).classList.add('active');

            });
        });

        // ---- Airtel Money handler ----
        document.getElementById('cust_airtel_pay_btn').addEventListener('click', function () {
            const phone  = document.getElementById('cust_airtel_phone').value.trim();
            const total  = baseTotal;
            if (!phone) { alert(custPayI18n.enterAirtel); return; }
            this.disabled = true;
            this.textContent = 'Processing…';
            const statusEl = document.getElementById('cust_airtel_status_msg');
            statusEl.classList.remove('hidden');
            statusEl.textContent = 'Sending payment request…';
            statusEl.className = 'text-sm text-center text-blue-600';
            fetch('{{ route("airtel.booking.payment") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    amount: Math.round(total),
                    phone_number: phone,
                    booking_code: '',
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    statusEl.textContent = data.message || custPayI18n.promptSent;
                    statusEl.className = 'text-sm text-center text-green-600';
                } else {
                    statusEl.textContent = data.message || custPayI18n.paymentFailed;
                    statusEl.className = 'text-sm text-center text-red-600';
                    document.getElementById('cust_airtel_pay_btn').disabled = false;
                    document.getElementById('cust_airtel_pay_btn').innerHTML = '<i class="fas fa-lock mr-2"></i> ' + custPayI18n.payAirtel;
                }
            })
            .catch(() => {
                statusEl.textContent = custPayI18n.networkError;
                statusEl.className = 'text-sm text-center text-red-600';
                document.getElementById('cust_airtel_pay_btn').disabled = false;
                document.getElementById('cust_airtel_pay_btn').innerHTML = '<i class="fas fa-lock mr-2"></i> ' + custPayI18n.payAirtel;
            });
        });

        const baseTotal = {{ round($price + $fees, 2) }};
        @endunless
    </script>

    <style>
        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .onlydigits {
            -moz-appearance: textfield;
        }

        .onlydigits::-webkit-outer-spin-button,
        .onlydigits::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
@endsection