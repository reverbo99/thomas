@extends('special_hire.app')

@section('title', 'Order Details')
@section('page_title', 'Order: ' . $order->order_code)
@section('page_subtitle', 'View and manage order details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <!-- Order Header + lifecycle stepper -->
    <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $order->order_code }}</h2>
                <p class="text-gray-500">Created {{ $order->created_at->format('M d, Y h:i A') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-4 py-2 text-sm font-semibold rounded-full bg-{{ $order->getStatusColor() }}-100 text-{{ $order->getStatusColor() }}-700">
                    {{ \App\Models\SpecialHireOrder::orderStatusLabel($order->order_status) }}
                </span>
                <span class="px-4 py-2 text-sm font-semibold rounded-full bg-{{ $order->getPaymentStatusColor() }}-100 text-{{ $order->getPaymentStatusColor() }}-700">
                    Payment: {{ ucfirst($order->payment_status) }}
                </span>
            </div>
        </div>

        @php
            $pipeline = \App\Models\SpecialHireOrder::orderStatusPipeline();
            $pipeIdx = $order->orderStatusPipelineIndex();
        @endphp

        @if($order->order_status === 'cancelled')
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                This booking was <strong>cancelled</strong>. Coaster should be available again if it was assigned.
            </div>
        @else
            <div class="mt-6" role="group" aria-label="Booking progress">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Booking progress</p>
                <ol class="flex flex-wrap items-center gap-1 sm:gap-0 sm:justify-between">
                    @foreach($pipeline as $i => $st)
                        <li class="flex items-center flex-1 min-w-[4.5rem] sm:min-w-0">
                            @if($i > 0)
                                <span class="hidden sm:block flex-1 h-1 rounded-full mx-1 {{ $pipeIdx >= $i ? 'bg-teal-400' : 'bg-gray-200' }}" aria-hidden="true"></span>
                            @endif
                            @php
                                $current = $order->order_status === $st;
                                $done = $pipeIdx > $i;
                            @endphp
                            <div class="flex flex-col items-center text-center px-1">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold border-2 shrink-0
                                    {{ $current ? 'border-teal-600 bg-teal-600 text-white' : '' }}
                                    {{ !$current && $done ? 'border-teal-400 bg-teal-50 text-teal-800' : '' }}
                                    {{ !$current && !$done ? 'border-gray-200 bg-gray-100 text-gray-400' : '' }}">
                                    {{ $i + 1 }}
                                </span>
                                <span class="mt-1.5 text-[10px] sm:text-xs font-medium max-w-[5.5rem] leading-tight {{ $current ? 'text-teal-800' : ($done ? 'text-teal-900/80' : 'text-gray-500') }}">
                                    {{ \App\Models\SpecialHireOrder::orderStatusLabel($st) }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <p class="text-xs text-gray-500 mt-3">Status updates when the customer pays and finishes steps in the app. The <strong>assigned driver</strong> accepts or declines new hires in the driver app. Use <strong>Booking actions</strong> for cancel or refund when needed.</p>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Info -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Customer Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="font-medium text-gray-800">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <p class="font-medium text-gray-800">{{ $order->customer_phone }}</p>
                    </div>
                    @if($order->customer_email)
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-medium text-gray-800">{{ $order->customer_email }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Route Info -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Route Details</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Pickup</p>
                            <p class="font-medium text-gray-800">{{ $order->pickup_location }}</p>
                        </div>
                    </div>
                    <div class="ml-4 border-l-2 border-dashed border-gray-300 h-8"></div>
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-4 mt-1">
                            <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Dropoff</p>
                            <p class="font-medium text-gray-800">{{ $order->dropoff_location }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Distance</p>
                        <p class="font-bold text-teal-600 text-lg">{{ $order->distance_km }} km</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Passengers</p>
                        <p class="font-bold text-gray-800 text-lg">{{ $order->passengers_count }}</p>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Schedule</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Hire Date</p>
                        <p class="font-medium text-gray-800">{{ $order->hire_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Pickup Time</p>
                        <p class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($order->hire_time)->format('h:i A') }}</p>
                    </div>
                    @if($order->return_date)
                        <div>
                            <p class="text-sm text-gray-500">Return Date</p>
                            <p class="font-medium text-gray-800">{{ $order->return_date->format('M d, Y') }}</p>
                        </div>
                    @endif
                    @if($order->return_time)
                        <div>
                            <p class="text-sm text-gray-500">Return Time</p>
                            <p class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($order->return_time)->format('h:i A') }}</p>
                        </div>
                    @endif
                </div>
                @if($order->purpose)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500">Purpose</p>
                        <p class="font-medium text-gray-800">{{ $order->purpose }}</p>
                    </div>
                @endif
                @if($order->notes)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500">Notes</p>
                        <p class="font-medium text-gray-800">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Coaster Info -->
            @if($order->coaster)
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Coaster</h3>
                    <div class="text-center">
                        @if($order->coaster->image)
                            <img src="{{ $order->coaster->image_url }}" alt="{{ $order->coaster->name }}" 
                                 class="w-full h-32 object-cover rounded-xl mb-4">
                        @else
                            <div class="w-full h-32 bg-gray-100 rounded-xl mb-4 flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                        @endif
                        <h4 class="font-bold text-gray-800">{{ $order->coaster->name }}</h4>
                        <p class="text-sm text-gray-500 font-mono">{{ $order->coaster->plate_number }}</p>
                        <p class="text-sm text-gray-600 mt-2">{{ $order->coaster->capacity }} seats</p>
                        @if($order->coaster->driver_name)
                            <div class="mt-4 pt-4 border-t border-gray-100 text-sm">
                                <p class="text-gray-500">Driver</p>
                                <p class="font-medium text-gray-800">{{ $order->coaster->driver_name }}</p>
                                @if($order->coaster->driver_contact)
                                    <p class="text-teal-600">{{ $order->coaster->driver_contact }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Price Breakdown -->
            <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-4">Price Breakdown</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-teal-100">Distance ({{ $order->distance_km }} km × Tsh {{ number_format($order->price_per_km) }}):</span>
                        <span>Tsh {{ number_format($order->km_amount) }}</span>
                    </div>
                    @if($order->surcharge_amount > 0)
                        <div class="flex justify-between">
                            <span class="text-teal-100">Surcharge ({{ $order->surcharge_percent }}%):</span>
                            <span>Tsh {{ number_format($order->surcharge_amount) }}</span>
                        </div>
                    @endif
                    <hr class="border-teal-400 my-2">
                    <div class="flex justify-between text-xl font-bold">
                        <span>Total:</span>
                        <span>Tsh {{ number_format($order->total_amount) }}</span>
                    </div>
                </div>
            </div>

            @if($order->customer_user_id)
            @php
                $depositRequired = (float) ($order->deposit_amount ?? 0) > 0;
            @endphp
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-amber-950 mb-2">App customer hire flow</h3>
                <ul class="text-sm text-amber-900 space-y-1 list-disc list-inside mb-2">
                    @if($depositRequired)
                        <li>Deposit: {{ $order->deposit_paid_at ? 'Paid' : 'Pending' }} @if($order->deposit_amount)(Tsh {{ number_format($order->deposit_amount, 0) }})@endif</li>
                    @endif
                    <li>Driver acceptance: {{ $order->owner_accepted_at ? 'Yes' : 'Waiting (driver app)' }}</li>
                    <li>Balance (ClickPesa): {{ $order->balance_paid_at ? 'Paid' : 'Pending' }} @if($order->balance_amount)(Tsh {{ number_format($order->balance_amount, 0) }})@endif</li>
                    <li>Passenger names: {{ is_array($order->passenger_seats) && count($order->passenger_seats) >= (int) $order->passengers_count ? 'Submitted' : 'Waiting' }}</li>
                </ul>
                <p class="text-sm text-amber-900/90"><strong>Completed</strong> is set automatically when the customer has paid and finished all steps in the app.</p>
                @if($depositRequired && ! $order->deposit_paid_at)
                    <p class="text-sm text-amber-800 mt-2">Waiting for the customer to pay the deposit via ClickPesa.</p>
                @endif
            </div>
            @endif

            <!-- Operator actions (cancel / refund; driver accepts hire in app) -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-teal-100/60">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Booking actions</h3>
                <p class="text-sm text-gray-500 mb-4">The assigned driver accepts or declines new hires in the <strong>driver app</strong> (customer gets SMS when possible). Cancel stops the booking. Refund records a reversal after you have already received payment.</p>

                @php
                    $canCancel = ! in_array($order->order_status, ['cancelled', 'completed'], true);
                    $canRefund = in_array($order->payment_status, ['paid', 'refund_pending'], true);
                @endphp

                <div class="space-y-3">
                    @if($canCancel)
                        <form action="{{ route('special_hire.orders.cancel_hire', $order->id) }}" method="POST" onsubmit="return confirm('Cancel this booking?');">
                            @csrf
                            <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-semibold border-2 border-red-200 text-red-800 bg-red-50 hover:bg-red-100 transition-colors">
                                Cancel booking
                            </button>
                        </form>
                    @endif

                    @if($canRefund)
                        <form action="{{ route('special_hire.orders.refund_hire', $order->id) }}" method="POST" onsubmit="return confirm('Mark this payment as refunded? The booking will be cancelled.');">
                            @csrf
                            <button type="submit" class="w-full py-3 px-4 rounded-xl text-sm font-semibold border-2 border-amber-300 text-amber-900 bg-amber-50 hover:bg-amber-100 transition-colors">
                                Mark refund
                            </button>
                        </form>
                    @endif

                    @if(! $canCancel && ! $canRefund)
                        <p class="text-sm text-gray-500">No actions available for this booking in its current state.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="flex justify-start">
        <a href="{{ route('special_hire.orders') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium">
            ← Back to Orders
        </a>
    </div>
</div>
@endsection

