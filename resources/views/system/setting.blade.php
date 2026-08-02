@extends('system.app')

@section('content')
<style>
    .toggle-visual {
        width: 44px;
        height: 24px;
        border-radius: 9999px;
        background-color: #e5e7eb;
        position: relative;
        transition: background-color 0.2s ease;
    }
    .toggle-visual::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 18px;
        height: 18px;
        border-radius: 9999px;
        background-color: #fff;
        transition: transform 0.2s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }
    .toggle-visual.on {
        background-color: #2563eb;
    }
    .toggle-visual.on::after {
        transform: translateX(20px);
    }
</style>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                {{ __('system.settings.title') }}
            </h1>
        </div>

        <!-- Settings Form -->
        <form action="{{ route('setting.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Insurance Amounts Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('system.settings.insurance_amounts') }}
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Foreign Insurance -->
                    <div>
                        <label for="international" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.foreign_insurance') }}</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" id="international" name="international" value="{{ old('international', $settings->international ?? '') }}" step="0.01" required
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 pr-12 py-2 border border-gray-300 rounded-md" placeholder="0.00">
                        </div>
                        @error('international')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Local Insurance -->
                    <div>
                        <label for="local" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.local_insurance') }}</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" id="local" name="local" value="{{ old('local', $settings->local ?? '') }}" step="0.01" required
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 pr-12 py-2 border border-gray-300 rounded-md" placeholder="0.00">
                        </div>
                        @error('local')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="px-6 pb-6 border-t border-gray-200 pt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500 mb-4">{{ __('system.settings.ticket_insurance_note') }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="insurance_company" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.insurance_company') }}</label>
                        <input type="text" id="insurance_company" name="insurance_company"
                               value="{{ old('insurance_company', $settings->insurance_company ?? 'G.A Insurance') }}"
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="G.A Insurance">
                        @error('insurance_company')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="insurance_policy_local" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.local_policy') }}</label>
                        <input type="text" id="insurance_policy_local" name="insurance_policy_local"
                               value="{{ old('insurance_policy_local', $settings->insurance_policy_local ?? 'Safiri salama - Domestic') }}"
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="Safiri salama - Domestic">
                        @error('insurance_policy_local')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="insurance_policy_foreign" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.foreign_policy') }}</label>
                        <input type="text" id="insurance_policy_foreign" name="insurance_policy_foreign"
                               value="{{ old('insurance_policy_foreign', $settings->insurance_policy_foreign ?? 'Safiri salama - Foreign') }}"
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="Safiri salama - Foreign">
                        @error('insurance_policy_foreign')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Service Amounts Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        {{ __('system.settings.service_amounts') }}
                    </h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Service Amount -->
                    <div>
                        <label for="service" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.service_amount') }}</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" id="service" name="service" value="{{ old('service', $settings->service ?? '') }}" step="0.01" required
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 pr-12 py-2 border border-gray-300 rounded-md" placeholder="0.00">
                        </div>
                        @error('service')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Service Percentage -->
                    <div>
                        <label for="service_percentage" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.service_percentage') }}</label>
                        <div class="relative rounded-md shadow-sm">
                            <input style="padding-left: 30px;" type="number" id="service_percentage" name="service_percentage" value="{{ old('service_percentage', $settings->service_percentage ?? '') }}" step="0.01" required
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 py-2 border border-gray-300 rounded-md" placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        @error('service_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parcel Commission Percentage -->
                    <div>
                        <label for="parcel_commission_percentage" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.parcel_commission_percentage') }}</label>
                        <div class="relative rounded-md shadow-sm">
                            <input style="padding-left: 30px;" type="number" id="parcel_commission_percentage" name="parcel_commission_percentage" value="{{ old('parcel_commission_percentage', $settings->parcel_commission_percentage ?? 0) }}" step="0.01" min="0" max="100" required
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 py-2 border border-gray-300 rounded-md" placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.parcel_commission_percentage_desc') }}</p>
                        @error('parcel_commission_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Excess Luggage Fee Per Kg -->
                    <div>
                        <label for="excess_luggage_fee_per_kg" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.excess_luggage_fee_per_kg') }}</label>
                        <input type="number" id="excess_luggage_fee_per_kg" name="excess_luggage_fee_per_kg" value="{{ old('excess_luggage_fee_per_kg', $settings->excess_luggage_fee_per_kg ?? 0) }}" step="0.01" min="0" required
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="0.00">
                        <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.excess_luggage_fee_per_kg_desc') }}</p>
                        @error('excess_luggage_fee_per_kg')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parcel Transport Fee Per Kg -->
                    <div>
                        <label for="parcel_fee_per_kg" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.parcel_fee_per_kg') }}</label>
                        <input type="number" id="parcel_fee_per_kg" name="parcel_fee_per_kg" value="{{ old('parcel_fee_per_kg', $settings->parcel_fee_per_kg ?? 0) }}" step="0.01" min="0" required
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="0.00">
                        <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.parcel_fee_per_kg_desc') }}</p>
                        @error('parcel_fee_per_kg')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Payment Gateway Settings -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden space-y-6">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        {{ __('system.settings.payment_gateway') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('system.settings.payment_gateway_desc') }}</p>
                </div>

                <div class="px-6 pb-6 space-y-4">
                    <div class="flex items-start justify-between border rounded-lg p-4 bg-blue-50 border-blue-200">
                        <div>
                            <p class="text-base font-medium text-gray-800 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4s-3 1.567-3 3.5S10.343 11 12 11zm0 0v2m-7 7h14a2 2 0 002-2v-2a7 7 0 10-14 0v2a2 2 0 002 2z" />
                                </svg>
                                {{ __('system.settings.enforce_2fa') }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">{{ __('system.settings.enforce_2fa_desc') }}</p>
                        </div>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enforce_2fa" value="1"
                                   class="sr-only toggle-input"
                                   data-toggle-target="toggle-enforce-2fa"
                                   {{ ($settings->enforce_2fa ?? true) ? 'checked' : '' }}>
                            <div id="toggle-enforce-2fa" class="toggle-visual {{ ($settings->enforce_2fa ?? true) ? 'on' : '' }}"></div>
                        </label>
                    </div>

                    <div class="flex items-start justify-between border rounded-lg p-4 bg-indigo-50 border-indigo-200">
                        <div>
                            <p class="text-base font-medium text-gray-800 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ __('system.settings.enforce_customer_email_verification') }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">{{ __('system.settings.enforce_customer_email_verification_desc') }}</p>
                        </div>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enforce_customer_email_verification" value="1"
                                   class="sr-only toggle-input"
                                   data-toggle-target="toggle-enforce-customer-email"
                                   {{ ($settings->enforce_customer_email_verification ?? true) ? 'checked' : '' }}>
                            <div id="toggle-enforce-customer-email" class="toggle-visual {{ ($settings->enforce_customer_email_verification ?? true) ? 'on' : '' }}"></div>
                        </label>
                    </div>

                    <div class="flex items-start justify-between border rounded-lg p-4 bg-amber-50 border-amber-200">
                        <div>
                            <p class="text-base font-medium text-gray-800 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('system.settings.test_mode') }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">{{ __('system.settings.test_mode_desc') }}</p>
                            <p class="text-xs text-amber-600 mt-2 font-medium">{{ __('system.settings.test_mode_warning') }}</p>
                        </div>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="test_mode" value="1"
                                   class="sr-only toggle-input"
                                   data-toggle-target="toggle-test-mode"
                                   {{ ($settings->test_mode ?? false) ? 'checked' : '' }}>
                            <div id="toggle-test-mode" class="toggle-visual {{ ($settings->test_mode ?? false) ? 'on' : '' }}"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SMS Gateway -->
            @php
                $smsDriver = old('sms_driver', $settings->sms_driver ?? 'smscotz');
                $atKeySaved = $settings && filled($settings->getRawOriginal('at_api_key'));
                $cotzPasswordSaved = $settings && filled($settings->getRawOriginal('cotz_password'));
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z" />
                        </svg>
                        {{ __('system.settings.sms_gateway') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('system.settings.sms_gateway_desc') }}</p>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="sms_driver" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.sms_driver') }}</label>
                            <select id="sms_driver" name="sms_driver"
                                    class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                                <option value="smscotz" {{ $smsDriver === 'smscotz' ? 'selected' : '' }}>{{ __('system.settings.sms_driver_smscotz') }}</option>
                                <option value="africastalking" {{ $smsDriver === 'africastalking' ? 'selected' : '' }}>{{ __('system.settings.sms_driver_africastalking') }}</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.sms_driver_desc') }}</p>
                            @error('sms_driver')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="sms_sender_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.sms_sender_id') }}</label>
                            <input type="text" id="sms_sender_id" name="sms_sender_id" maxlength="11"
                                   value="{{ old('sms_sender_id', $settings->sms_sender_id ?? '') }}"
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                                   placeholder="HIGHLINK">
                            <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.sms_sender_id_desc') }}</p>
                            @error('sms_sender_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Africa's Talking credentials -->
                    <div data-sms-panel="africastalking" class="border rounded-lg p-4 bg-emerald-50 border-emerald-200 space-y-4 {{ $smsDriver === 'africastalking' ? '' : 'hidden' }}">
                        <h3 class="text-md font-semibold text-gray-800">{{ __('system.settings.at_section') }}</h3>
                        <p class="text-xs text-gray-600">{{ __('system.settings.at_section_desc') }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="at_username" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.at_username') }}</label>
                                <input type="text" id="at_username" name="at_username" autocomplete="off"
                                       value="{{ old('at_username', $settings->at_username ?? '') }}"
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                                       placeholder="sandbox">
                                <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.at_username_desc') }}</p>
                                @error('at_username')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="at_api_key" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.at_api_key') }}</label>
                                <input type="password" id="at_api_key" name="at_api_key" autocomplete="new-password"
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                                       placeholder="{{ $atKeySaved ? __('system.settings.secret_saved_placeholder') : __('system.settings.secret_empty_placeholder') }}">
                                <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.at_api_key_desc') }}</p>
                                @error('at_api_key')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-start justify-between border rounded-lg p-4 bg-white">
                            <div>
                                <p class="text-base font-medium text-gray-800">{{ __('system.settings.at_sandbox') }}</p>
                                <p class="text-sm text-gray-500">{{ __('system.settings.at_sandbox_desc') }}</p>
                            </div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="at_sandbox" value="1"
                                       class="sr-only toggle-input"
                                       data-toggle-target="toggle-at-sandbox"
                                       {{ ($settings->at_sandbox ?? true) ? 'checked' : '' }}>
                                <div id="toggle-at-sandbox" class="toggle-visual {{ ($settings->at_sandbox ?? true) ? 'on' : '' }}"></div>
                            </label>
                        </div>

                        <p class="text-xs text-gray-600">
                            {{ __('system.settings.at_dlr_hint') }}
                            <code class="bg-white px-1 py-0.5 rounded border">{{ url('/webhooks/africastalking/dlr') }}</code>
                        </p>
                    </div>

                    <!-- sms.co.tz credentials -->
                    <div data-sms-panel="smscotz" class="border rounded-lg p-4 bg-gray-50 border-gray-200 space-y-4 {{ $smsDriver === 'smscotz' ? '' : 'hidden' }}">
                        <h3 class="text-md font-semibold text-gray-800">{{ __('system.settings.cotz_section') }}</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="cotz_username" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.cotz_username') }}</label>
                                <input type="text" id="cotz_username" name="cotz_username" autocomplete="off"
                                       value="{{ old('cotz_username', $settings->cotz_username ?? '') }}"
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                                       placeholder="HIGHLINK">
                                @error('cotz_username')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="cotz_password" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.cotz_password') }}</label>
                                <input type="password" id="cotz_password" name="cotz_password" autocomplete="new-password"
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                                       placeholder="{{ $cotzPasswordSaved ? __('system.settings.secret_saved_placeholder') : __('system.settings.secret_empty_placeholder') }}">
                                @error('cotz_password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500">{{ __('system.settings.sms_env_fallback_note') }}</p>
                </div>
            </div>

            <!-- Notification Preferences -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden space-y-6">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        {{ __('system.settings.notification_preferences') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('system.settings.notification_desc') }}</p>
                </div>

                <div class="px-6 pb-6 space-y-4">
                    <h3 class="text-md font-semibold text-gray-700">{{ __('system.settings.customer_notifications') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start justify-between border rounded-lg p-4">
                            <div>
                                <p class="text-base font-medium text-gray-800">{{ __('system.settings.customer_sms') }}</p>
                                <p class="text-sm text-gray-500">{{ __('system.settings.customer_sms_desc') }}</p>
                            </div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_customer_sms_notifications" value="1"
                                       class="sr-only toggle-input"
                                       data-toggle-target="toggle-customer-sms"
                                       {{ ($settings->enable_customer_sms_notifications ?? true) ? 'checked' : '' }}>
                                <div id="toggle-customer-sms" class="toggle-visual {{ ($settings->enable_customer_sms_notifications ?? true) ? 'on' : '' }}"></div>
                            </label>
                        </div>
                        <div class="flex items-start justify-between border rounded-lg p-4">
                            <div>
                                <p class="text-base font-medium text-gray-800">{{ __('system.settings.customer_email') }}</p>
                                <p class="text-sm text-gray-500">{{ __('system.settings.customer_email_desc') }}</p>
                            </div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_customer_email_notifications" value="1"
                                       class="sr-only toggle-input"
                                       data-toggle-target="toggle-customer-email"
                                       {{ ($settings->enable_customer_email_notifications ?? true) ? 'checked' : '' }}>
                                <div id="toggle-customer-email" class="toggle-visual {{ ($settings->enable_customer_email_notifications ?? true) ? 'on' : '' }}"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-6 pb-6 space-y-4 border-t border-gray-100">
                    <h3 class="text-md font-semibold text-gray-700">{{ __('system.settings.conductor_notifications') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start justify-between border rounded-lg p-4">
                            <div>
                                <p class="text-base font-medium text-gray-800">{{ __('system.settings.conductor_sms') }}</p>
                                <p class="text-sm text-gray-500">{{ __('system.settings.conductor_sms_desc') }}</p>
                            </div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_conductor_sms_notifications" value="1"
                                       class="sr-only toggle-input"
                                       data-toggle-target="toggle-conductor-sms"
                                       {{ ($settings->enable_conductor_sms_notifications ?? true) ? 'checked' : '' }}>
                                <div id="toggle-conductor-sms" class="toggle-visual {{ ($settings->enable_conductor_sms_notifications ?? true) ? 'on' : '' }}"></div>
                            </label>
                        </div>
                        <div class="flex items-start justify-between border rounded-lg p-4">
                            <div>
                                <p class="text-base font-medium text-gray-800">{{ __('system.settings.conductor_email') }}</p>
                                <p class="text-sm text-gray-500">{{ __('system.settings.conductor_email_desc') }}</p>
                            </div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_conductor_email_notifications" value="1"
                                       class="sr-only toggle-input"
                                       data-toggle-target="toggle-conductor-email"
                                       {{ ($settings->enable_conductor_email_notifications ?? true) ? 'checked' : '' }}>
                                <div id="toggle-conductor-email" class="toggle-visual {{ ($settings->enable_conductor_email_notifications ?? true) ? 'on' : '' }}"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors">
                    {{ __('system.settings.save_settings') }}
                </button>
            </div>
        </form>

        <!-- Test SMS — separate form so it never submits the settings above -->
        <form action="{{ route('setting.sms.test') }}" method="POST" class="mt-6">
            @csrf
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        {{ __('system.settings.sms_test') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">{{ __('system.settings.sms_test_desc') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                    <div>
                        <label for="test_phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.sms_test_phone') }}</label>
                        <input type="text" id="test_phone" name="test_phone" value="{{ old('test_phone') }}"
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="0712345678">
                        @error('test_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="test_message" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.sms_test_message') }}</label>
                        <input type="text" id="test_message" name="test_message" maxlength="300" value="{{ old('test_message') }}"
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
                               placeholder="{{ __('system.settings.sms_test_default_message') }}">
                        @error('test_message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="px-6 pb-6 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg shadow-sm transition-colors">
                        {{ __('system.settings.sms_test_send') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var driverSelect = document.getElementById('sms_driver');
        if (driverSelect) {
            var syncPanels = function () {
                document.querySelectorAll('[data-sms-panel]').forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.dataset.smsPanel !== driverSelect.value);
                });
            };
            driverSelect.addEventListener('change', syncPanels);
            syncPanels();
        }

        document.querySelectorAll('.toggle-input').forEach(function (input) {
            var target = document.getElementById(input.dataset.toggleTarget);
            if (!target) return;
            var sync = function () {
                target.classList.toggle('on', input.checked);
            };
            input.addEventListener('change', sync);
            sync();
        });
    });
</script>
@endsection