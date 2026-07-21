@extends('system.app')

@section('title', __('system.pages.transfer_order') . ' — ' . $hireOrder->order_code)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('system.special_hire.show', $hireOrder->user_id) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← {{ optional($hireOrder->user)->name ?? 'Operator' }}</a>
        <h1 class="text-2xl font-semibold text-gray-800 mt-2">{{ __('system.pages.transfer_order') }}</h1>
        <p class="text-gray-600 mt-1">
            {{ __('system.pages.order') }} <span class="font-mono">{{ $hireOrder->order_code }}</span>
            · {{ $hireOrder->customer_name }} · {{ $hireOrder->hire_date?->format('Y-m-d') }}
            · {{ $hireOrder->passengers_count }} {{ __('all.seats') }}
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <p class="text-sm font-semibold text-gray-800 mb-1">{{ __('system.pages.current_coaster') }}</p>
        <p class="text-sm text-gray-600">
            {{ optional($hireOrder->coaster)->name ?? 'N/A' }} ({{ optional($hireOrder->coaster)->plate_number ?? 'N/A' }})
            · {{ __('system.pages.col_owner') }}: {{ optional($hireOrder->user)->name ?? 'N/A' }}
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-800">{{ __('system.pages.available_coasters') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.pages.coaster') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.pages.col_owner') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase text-xs">{{ __('system.common.status') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs">{{ __('system.pages.col_capacity') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase text-xs"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($coasters as $coaster)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $coaster->name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $coaster->plate_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ optional($coaster->user)->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full
                                    {{ $coaster->status === 'available' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $coaster->status === 'on_hire' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $coaster->status === 'maintenance' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $coaster->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ number_format($coaster->capacity) }}
                                @if($coaster->capacity < $hireOrder->passengers_count)
                                    <div class="text-xs text-amber-600 mt-0.5">{{ __('system.pages.capacity_warning') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('system.special_hire.order.transfer.update', $hireOrder->id) }}" method="post"
                                    onsubmit="return confirm(@json(__('system.pages.confirm_transfer_order', ['coaster' => $coaster->name])))">
                                    @csrf
                                    <input type="hidden" name="new_coaster_id" value="{{ $coaster->id }}">
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-medium hover:bg-blue-700">
                                        {{ __('system.pages.transfer_here') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">{{ __('system.pages.no_other_coasters') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
