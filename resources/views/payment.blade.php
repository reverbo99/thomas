@extends('test.ap')

@section('content')
<section class="bg-gradient-to-b from-gray-50 to-gray-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Complete your Booking</h2>
            </div>
            <div class="text-sm text-gray-600 bg-white px-4 py-2 rounded-lg shadow-sm">
                <i class="fas fa-route mr-2 text-blue-500"></i>
                {{$info['pickup_point'] ?? $car->schedule->from }} to {{ $info['dropping_point'] ?? $car->schedule->to }} | 
                {{ $info['travel_date'] ?? 'N/A' }} {{ \Carbon\Carbon::parse($time['start'])->subMinutes(30)->format('h:i') ?? 'N/A' }}
            </div>
        </div>

        <form action="{{ route('payment_store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Booking Details -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="p-6">
                        <!-- Bus Info Header -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 pb-4 border-b border-gray-200">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">{{ $car->busname->name ?? 'N/A' }}</h3>
                                @php
                                     $mode = [
                                            '10' => "LUXURY",
                                            '20' => "UPPER-SEMILUXURY", 
                                            '30' => "LOWER-SEMILUXURY",
                                            '40' => "ORDINARY",
                                        ];
                                    $busType = isset($car->bus_type) && array_key_exists($car->bus_type, $mode)
                                        ? $mode[$car->bus_type]
                                        : 'N/A';
                                @endphp
                                <p class="text-sm text-gray-600">
                                    {{ $busType }} | 
                                    <span class="text-purple-600">VIA {{ $car->route->via->name ?? 'N/A' }}</span>
                                </p>
                            </div>
                            <div class="mt-2 sm:mt-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                    <i class="fas fa-chair mr-1"></i> Seat No. {{ $seats ?? 'N/A' }}
                                </span>
                            </div>
                        </div>

                        <!-- Route Timeline -->
                        <div class="relative mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <div class="text-left">
                                    <p class="text-xs text-gray-500">Departure</p>
                                    <p class="font-medium text-gray-700">{{$info['pickup_point'] ?? $car->schedule->from }}</p>
                                    <p class="text-sm text-gray-600">{{ $time['start'] ?? 'N/A' }}</p>
                                </div>
                                <div class="text-center px-4">
                                    <div class="w-16 h-1 bg-gray-300 mx-auto my-3"></div>
                                    <p class="text-xs text-purple-600 font-medium">
                                        <i class="fas fa-route mr-1"></i> VIA {{ $car->route->via->name ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Arrival</p>
                                    <p class="font-medium text-gray-700">{{$info['dropping_point'] ?? $car->schedule->to}}</p>
                                    <p class="text-sm text-gray-600">{{ $time['end'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 text-center">
                                Travel Date: {{ $info['travel_date'] ?? 'N/A' }}
                            </p>
                        </div>

                        <!-- Passenger Details -->
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">
                                <i class="fas fa-user mr-2 text-blue-500"></i> Passenger Details
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Name -->
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="customer" id="full_name" 
                                           class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           maxlength="30" style="text-transform: capitalize;" required>
                                </div>
                                
                                <!-- Gender -->
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">
                                        Gender
                                    </label>
                                    <select name="gender" id="gender" 
                                            class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                
                                <!-- Age -->
                                <div>
                                    <label for="age" class="block text-sm font-medium text-gray-700 mb-1">
                                        Age <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="age" id="age" 
                                           class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           min="0" max="120" required>
                                </div>

                                <!-- Infant Child Checkbox -->
                                <div class="flex items-center mt-4">
                                    <input type="checkbox" id="infant_child" name="infant_child" value="1" 
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="infant_child" class="ml-2 block text-sm font-medium text-gray-700">
                                        {{ __('customer/busroot.user_has_infant_child') }}
                                    </label>
                                </div>

                                <!-- Excess Luggage Checkbox -->
                                <div class="md:col-span-2">
                                    <div class="flex items-center mt-4">
                                        <input type="checkbox" id="excess_luggage_root_payment" name="excess_luggage" value="1" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                               onchange="toggleExcessLuggageDescription()">
                                        <label for="excess_luggage_root_payment" class="ml-2 block text-sm font-medium text-gray-700">
                                            @php
                                                $luggageFeePerKg = (float) (\App\Models\Setting::first()->excess_luggage_fee_per_kg ?? 0);
                                                if ($luggageFeePerKg <= 0) {
                                                    $luggageFeePerKg = 2500;
                                                }
                                            @endphp
                                            {{ __('customer/busroot.excess_luggage', ['dimensions' => '60X45X50', 'weight' => '20kg', 'fee' => ($currency . ' ' . convert_money($luggageFeePerKg) . '/kg')]) }}
                                        </label>
                                    </div>
                                    <div id="excessLuggageDescriptionField" class="hidden mt-2">
                                        <label for="excess_luggage_description" class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ __('customer/busroot.excess_luggage_description') }}
                                        </label>
                                        <input type="text" name="excess_luggage_description" id="excess_luggage_description"
                                               class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="{{ __('customer/busroot.e_g_1_extra_bag_large_box') }}">
                                        <label for="estimated_weight" class="block text-sm font-medium text-gray-700 mb-1 mt-2">
                                            {{ __('customer/busroot.estimated_weight_kg') }}
                                        </label>
                                        <input type="number" step="0.1" min="0" name="estimated_weight" id="estimated_weight"
                                               class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="20">
                                    </div>
                                </div>

                                <!-- Age Group -->
                                <div>
                                    <label for="age_group" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('customer/busroot.age_group') }} <span class="text-red-500">*</span>
                                    </label>
                                    <select name="age_group" id="age_group" 
                                            class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                        <option value="Adult">{{ __('customer/busroot.adult') }}</option>
                                        <option value="Child">{{ __('customer/busroot.child') }}</option>
                                        <option value="Infant">{{ __('customer/busroot.infant') }}</option>
                                        <option value="Senior">{{ __('customer/busroot.senior') }}</option>
                                    </select>
                                </div>
                                
                                <!-- Discount Coupon -->
                                <div>
                                    <label for="discount" class="block text-sm font-medium text-gray-700 mb-1">
                                        Discount Coupon
                                    </label>
                                    <input type="text" id="discount" name="discount" 
                                           class="w-full text-gray-800 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <!-- Seat Class -->
                                <div>
                                    <label for="selectedSeatClass" class="block text-sm font-medium text-gray-700 mb-1">
                                        Seat Class
                                    </label>
                                    <input type="text" id="selectedSeatClass" value="Normal Seat" readonly
                                           class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                                </div>
                                
                                <!-- Fare -->
                                <div>
                                    <label for="selectedSeatFare" class="block text-sm font-medium text-gray-700 mb-1">
                                        Fare
                                    </label>
                                    <input type="text" id="selectedSeatFare" value="{{ $price ?? 'N/A' }}" readonly
                                           class=" text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                                </div>

                                 @livewire('temp');
                                
                                <!-- Insurance (Conditional) -->
                                @if (isset($distance) && $distance > 99)
                                    @if (isset($info['travel_date']) && $info['travel_date'] != date('Y-m-d'))
                                        @php
                                            date_default_timezone_set('Africa/Nairobi');
                                            $currentDate = date('Y-m-d');
                                            try {
                                                $travelDate = (new DateTime($info['travel_date']))->format('Y-m-d');
                                                $minDate = $travelDate >= $currentDate ? $travelDate : $currentDate;
                                            } catch (Exception $e) {
                                                $minDate = $currentDate;
                                            }
                                        @endphp
                                        <div class="md:col-span-2">
                                            <div class="flex items-center mb-2">
                                                <input type="checkbox" id="Insurance" name="Insurance" value="1" 
                                                       onchange="toggleDateInput()"
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <label for="Insurance" class="ml-2 block text-sm font-medium text-gray-700">
                                                    {{ __('all.insurance') }} ({{ insurance_local_rate_display() }}/{{ __('all.day') ?? 'day' }})
                                                </label>
                                            </div>
                                            
                                            <div id="insuranceFields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                                <div>
                                                    <select name="type" id="type" 
                                                            class="text-gray-800 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                        <option value="local">Local</option>
                                                        <option value="foreign">Foreign</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <input type="date" id="insuranceDate" name="insuranceDate"
                                                           min="{{ $minDate }}"
                                                           class="w-full px-4 text-gray-800 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        
                        <!-- Boarding Point & Plate Number -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-6 pt-4 border-t border-gray-200">
                            <div>
                                <p class="text-sm text-gray-500">Boarding Point & Time</p>
                                <p class="text-sm font-medium text-gray-700">
                                    {{ $info['pickup_point'] ?? $car->schedule->from }} at {{ \Carbon\Carbon::parse($time['start'])->subMinutes(30)->format('h:i') ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="mt-2 sm:mt-0">
                                <p class="text-sm text-gray-500">Plate No.</p>
                                <p class="text-sm font-medium text-gray-700">{{ $car->bus_number ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Price Summary -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden h-fit sticky top-6">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-receipt mr-2 text-blue-500"></i> Price Summary
                        </h4>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Discount</span>
                                <span class="text-sm font-medium text-black">{{ $currency }} 0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">System Charge</span>
                                <span class="text-sm font-medium text-black">{{ $currency }} {{ convert_money($fees ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Bus Fare</span>
                                <span class="text-sm font-medium text-black">{{ $currency }} {{ convert_money($price ?? 0) }}</span>
                            </div>
                            <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between">
                                <span class="text-base font-semibold">Total Payable</span>
                                <span class="text-base font-bold text-blue-600" id="total_payable_amount_root_payment">
                                    {{ $currency }} {{ convert_money(($price ?? 0) + ($fees ?? 0)) }}
                                </span>
                            </div>
                        </div>
                        
                        <button type="submit" 
                                class="w-full py-3 bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-medium rounded-lg shadow-md transition-all duration-300 flex items-center justify-center"
                                >
                            <i class="fas fa-arrow-right mr-2"></i> Continue to Payment
                        </button>
                        
                        <div class="mt-4 text-xs text-gray-500">
                            <p class="flex items-center">
                                <i class="fas fa-lock mr-2 text-green-500"></i> Secure SSL encrypted payment
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    const luggageFeePerKg = {{ json_encode((float) $luggageFeePerKg) }};

    function currentExcessLuggageFee() {
        const cb = document.getElementById('excess_luggage_root_payment');
        const w = parseFloat(document.getElementById('estimated_weight')?.value || '0');
        if (!cb?.checked || !(w > 0)) return 0;
        return w * luggageFeePerKg;
    }

    function toggleDateInput() {
        const checkbox = document.getElementById('Insurance');
        const insuranceFields = document.getElementById('insuranceFields');
        
        if (checkbox.checked) {
            insuranceFields.classList.remove('hidden');
        } else {
            insuranceFields.classList.add('hidden');
        }
    }

    function toggleExcessLuggageDescription() {
        const excessLuggageCheckbox = document.getElementById('excess_luggage_root_payment');
        const excessLuggageDescriptionField = document.getElementById('excessLuggageDescriptionField');
        const estimatedWeightInput = document.getElementById('estimated_weight');
        if (excessLuggageCheckbox.checked) {
            excessLuggageDescriptionField.classList.remove('hidden');
            if (estimatedWeightInput) {
                estimatedWeightInput.required = true;
            }
        } else {
            excessLuggageDescriptionField.classList.add('hidden');
            if (estimatedWeightInput) {
                estimatedWeightInput.required = false;
            }
        }
        updateTotalPayableRootPayment();
    }

    function updateTotalPayableRootPayment() {
        const totalPayableElement = document.getElementById('total_payable_amount_root_payment');
        const currencyCode = @json($currency);
        const isUsdDisplay = @json(session('currency') == 'Usd');
        const usdRate = parseFloat(@json($usdToTzs ?? 2500));
        let currentTotal = parseFloat("{{ ($price ?? 0) + ($fees ?? 0) }}");
        currentTotal += currentExcessLuggageFee();

        const displayTotal = isUsdDisplay && usdRate > 0 ? (currentTotal / usdRate) : currentTotal;
        totalPayableElement.innerHTML = currencyCode + " " + formatMoney(displayTotal);
    }

    function formatMoney(amount) {
        return amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.getElementById('estimated_weight')?.addEventListener('input', updateTotalPayableRootPayment);
</script>
@endsection
