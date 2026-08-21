@extends('customer.app')

@section('title', __('customer/special_hire.title'))

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => __('all.highlink_isgc'),
        'title' => __('customer/special_hire.title'),
        'subtitle' => __('customer/special_hire.subtitle'),
    ])
@endsection

@section('content')
<section class="page-section page-section--alt">
    <div class="container mx-auto px-4">
        <div class="customer-panel fade-in">
            <div class="customer-panel__header flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h3 class="text-base sm:text-lg text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.title') }}</h3>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $orders->total() }} {{ __('customer/special_hire.title') }}</span>
            </div>

            <div class="customer-panel__body">
                @if (session('success'))
                    <div class="customer-alert customer-alert--success" role="alert">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="customer-alert customer-alert--error" role="alert">{{ session('error') }}</div>
                @endif

                @if ($orders->isEmpty())
                    <div class="tickets-empty">
                        <div class="tickets-empty__icon"><i class="fas fa-van-shuttle"></i></div>
                        <h4 class="tickets-empty__title text-gray-900 dark:text-gray-100">{{ __('customer/special_hire.empty') }}</h4>
                    </div>
                @else
                    <div class="tickets-table-wrap page-table-wrap overflow-x-auto">
                        <table class="page-table tickets-table w-full">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('customer/special_hire.order_code') }}</th>
                                    <th>{{ __('customer/special_hire.coaster') }}</th>
                                    <th>{{ __('customer/special_hire.route') }}</th>
                                    <th>{{ __('customer/special_hire.hire_date') }}</th>
                                    <th>{{ __('customer/special_hire.total') }}</th>
                                    <th>{{ __('customer/special_hire.order_status') }}</th>
                                    <th>{{ __('customer/special_hire.payment_status') }}</th>
                                    <th>{{ __('customer/special_hire.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td>{{ $orders->firstItem() + $loop->index }}</td>
                                        <td class="font-medium text-gray-900 dark:text-gray-100">{{ $order->order_code }}</td>
                                        <td>{{ $order->coaster->name ?? '—' }}</td>
                                        <td class="max-w-xs truncate" title="{{ $order->pickup_location }} → {{ $order->dropoff_location }}">
                                            {{ $order->pickup_location }} → {{ $order->dropoff_location }}
                                        </td>
                                        <td>
                                            {{ $order->hire_date?->format('Y-m-d') }}
                                            {{ is_string($order->hire_time) ? substr($order->hire_time, 0, 5) : $order->hire_time }}
                                        </td>
                                        <td>{{ number_format((float) $order->total_amount, 0) }}</td>
                                        <td>{{ __('customer/special_hire.status_' . $order->order_status) }}</td>
                                        <td>{{ __('customer/special_hire.payment_' . ($order->payment_status ?: 'pending')) }}</td>
                                        <td>
                                            <a href="{{ route('customer.special_hire.show', $order->id) }}" class="page-btn page-btn--outline text-sm">
                                                {{ __('customer/special_hire.view') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
