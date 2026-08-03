@extends('admin.app')

@section('title', trans('vendor_sidebar.resaved_tickets'))

@section('content')
<div class="container-fluid py-4">
    <div class="bg-white/80 backdrop-blur-lg shadow-xl rounded-2xl p-6">
        {{-- Success / Error messages --}}
        @if (session('success'))
            <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ trans('vendor_sidebar.resaved_tickets') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('vender/resaved_tickets.subtitle') }}</p>
            </div>
        </div>

        @if ($resavedBookings->isEmpty())
            <p class="text-gray-600 py-6">{{ __('vender/resaved_tickets.no_tickets') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.booking_code') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.bus_number') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.customer_name') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.route') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.travel_date') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.seats') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.amount') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.resaved_until') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.status') }}</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/resaved_tickets.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($resavedBookings as $booking)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $booking->booking_code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $booking->bus->bus_number ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $booking->customer_name ?? $booking->user->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @php
                                        $routeFrom = $booking->schedule->from ?? $booking->route_name->from ?? null;
                                        $routeTo = $booking->schedule->to ?? $booking->route_name->to ?? null;
                                    @endphp
                                    {{ $routeFrom && $routeTo ? $routeFrom . ' - ' . $routeTo : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $booking->travel_date ? \Carbon\Carbon::parse($booking->travel_date)->format('Y-m-d') : 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $booking->seat ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $currency ?? 'TSH' }} {{ convert_money($booking->amount ?? $booking->busFee ?? 0) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $booking->resaved_until ? \Carbon\Carbon::parse($booking->resaved_until)->format('Y-m-d H:i') : 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ ucfirst($booking->payment_status ?? __('vender/resaved_tickets.resaved')) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <form action="{{ route('ticket.print') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="data" value='{"id": {{ $booking->id }}, "booking_code": "{{ $booking->booking_code }}"}' />
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900" title="Booking: {{ $booking->booking_code }}">{{ __('vender/resaved_tickets.view') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 px-2">
                {{ $resavedBookings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
