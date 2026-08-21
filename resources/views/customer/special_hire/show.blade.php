@extends('customer.app')

@section('title', $order->order_code . ' — ' . __('customer/special_hire.title'))

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => __('customer/special_hire.title'),
        'title' => $order->order_code,
        'subtitle' => __('customer/special_hire.details'),
    ])
@endsection

@section('content')
<section class="page-section page-section--alt">
    <div class="container mx-auto px-4 space-y-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('customer.special_hire.index') }}" class="page-btn page-btn--outline text-sm">
                <i class="fas fa-arrow-left"></i> {{ __('customer/special_hire.back_list') }}
            </a>
        </div>

        @if (session('success'))
            <div class="customer-alert customer-alert--success" role="alert">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="customer-alert customer-alert--error" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="customer-alert customer-alert--error" role="alert">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="customer-panel fade-in">
            <div class="customer-panel__header">
                <h3 class="text-base sm:text-lg text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.details') }}</h3>
            </div>
            <div class="customer-panel__body">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.order_code') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $order->order_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.coaster') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $order->coaster->name ?? '—' }} ({{ $order->coaster->plate_number ?? '—' }})</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.pickup') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $order->pickup_location }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.dropoff') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $order->dropoff_location }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.hire_date') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">
                            {{ $order->hire_date?->format('Y-m-d') }}
                            {{ is_string($order->hire_time) ? substr($order->hire_time, 0, 5) : $order->hire_time }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.passengers') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ $order->passengers_count }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.total') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ number_format((float) $order->total_amount, 0) }} TZS</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.order_status') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.status_' . $order->order_status) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.payment_status') }}</dt>
                        <dd class="text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.payment_' . ($order->payment_status ?: 'pending')) }}</dd>
                    </div>
                    @if ($order->purpose)
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('customer/special_hire.purpose') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $order->purpose }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        @if (!empty($reorderPrefill) && is_array($reorderPrefill))
            <div class="customer-panel fade-in border border-blue-200 dark:border-blue-800">
                <div class="customer-panel__header">
                    <h3 class="text-base text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.reorder_prefill_title') }}</h3>
                </div>
                <div class="customer-panel__body">
                    <pre class="text-xs overflow-x-auto p-3 rounded bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">{{ json_encode($reorderPrefill, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="customer-panel fade-in">
                <div class="customer-panel__header">
                    <h3 class="text-base text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.actions') }}</h3>
                </div>
                <div class="customer-panel__body flex flex-col gap-3">
                    <form action="{{ route('customer.special_hire.reorder', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="page-btn page-btn--outline text-sm w-full sm:w-auto">
                            <i class="fas fa-rotate"></i> {{ __('customer/special_hire.reorder') }}
                        </button>
                    </form>

                    @if ($canReceipt)
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('customer.special_hire.receipt.pdf', $order->id) }}" class="page-btn page-btn--outline text-sm">
                                <i class="fas fa-file-pdf"></i> {{ __('customer/special_hire.receipt_pdf') }}
                            </a>
                            <a href="{{ route('customer.special_hire.receipt.print', $order->id) }}" target="_blank" class="page-btn page-btn--outline text-sm">
                                <i class="fas fa-print"></i> {{ __('customer/special_hire.receipt_print') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            @if ($canTransfer)
                <div class="customer-panel fade-in">
                    <div class="customer-panel__header">
                        <h3 class="text-base text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.transfer') }}</h3>
                    </div>
                    <div class="customer-panel__body">
                        @if ($coasters->isEmpty())
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('customer/special_hire.no_coasters') }}</p>
                        @else
                            <form action="{{ route('customer.special_hire.transfer', $order->id) }}" method="POST" class="space-y-3">
                                @csrf
                                <label class="block text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('customer/special_hire.select_coaster') }}
                                    <select name="coaster_id" required
                                            class="mt-1 w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2">
                                        <option value="">{{ __('customer/special_hire.select_coaster') }}</option>
                                        @foreach ($coasters as $coaster)
                                            <option value="{{ $coaster->id }}">
                                                {{ $coaster->name }} ({{ $coaster->plate_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <button type="submit" class="page-btn text-sm">
                                    {{ __('customer/special_hire.transfer_submit') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            @if ($canRefund)
                <div class="customer-panel fade-in lg:col-span-2">
                    <div class="customer-panel__header">
                        <h3 class="text-base text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.refund_request') }}</h3>
                    </div>
                    <div class="customer-panel__body">
                        <form action="{{ route('customer.special_hire.refund', $order->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @csrf
                            <label class="block text-sm text-gray-700 dark:text-gray-300 sm:col-span-2">
                                {{ __('customer/special_hire.refund_reason') }}
                                <textarea name="reason" rows="2"
                                          class="mt-1 w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2">{{ old('reason') }}</textarea>
                            </label>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">
                                {{ __('customer/special_hire.refund_phone') }}
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2">
                            </label>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">
                                {{ __('customer/special_hire.refund_bank') }}
                                <input type="text" name="bank" value="{{ old('bank') }}"
                                       class="mt-1 w-full rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2">
                            </label>
                            <div class="sm:col-span-2">
                                <button type="submit" class="page-btn text-sm">
                                    {{ __('customer/special_hire.refund_submit') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
