@extends('admin.app')

@section('title', __('vender/parcels.manage_parcels'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @if (session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm dark:bg-green-900/30 dark:border-green-600">
            <div class="flex">
                <div class="flex-shrink-0 text-green-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-100">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm dark:bg-red-900/30 dark:border-red-600">
            <div class="flex">
                <div class="flex-shrink-0 text-red-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-100">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('vender/parcels.parcel_management') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('vender/parcels.manage_status_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-3 md:mt-0">
            <a href="{{ route('bus_owner.parcels.find_bus') }}" class="inline-flex items-center rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">{{ __('vender/parcels.add_new_parcel') }}</a>
            <a href="{{ route('bus_owner.parcels.manifest') }}" class="inline-flex items-center rounded-lg border px-4 py-2 text-sm">{{ __('vender/parcels.manifest_title') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($buses as $bus)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">{{ $bus->bus_number }}</h3>
                    <p class="text-sm text-gray-500">{{ $bus->bus_model }}</p>
                </div>

                <form action="{{ route('bus_owner.parcels.toggle_acceptance') }}" method="POST">
                    @csrf
                    <input type="hidden" name="bus_id" value="{{ $bus->id }}">
                    <button type="submit"
                        class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 {{ $bus->accept_parcels ? 'bg-green-500' : 'bg-gray-200' }}"
                        role="switch" aria-checked="{{ $bus->accept_parcels ? 'true' : 'false' }}">
                        <span class="sr-only">{{ __('vender/parcels.accept_parcels') }}</span>
                        <span aria-hidden="true"
                            class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200 {{ $bus->accept_parcels ? 'translate-x-5' : 'translate-x-0' }}">
                        </span>
                    </button>
                    <span class="block text-xs mt-1 {{ $bus->accept_parcels ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $bus->accept_parcels ? __('vender/parcels.accepting_parcels') : __('vender/parcels.not_accepting') }}
                    </span>
                </form>
            </div>
            <form action="{{ route('bus_owner.parcels.capacity') }}" method="POST" class="mt-3 flex items-center gap-2">
                @csrf
                <input type="hidden" name="bus_id" value="{{ $bus->id }}">
                <label class="text-xs text-gray-500">{{ __('vender/parcels.max_weight_kg') }}</label>
                <input type="number" step="0.1" min="0" name="max_parcel_weight_kg" value="{{ $bus->max_parcel_weight_kg }}" class="w-24 rounded border-gray-300 text-sm">
                <button class="text-xs text-teal-700">{{ __('vender/parcels.save') ?? 'Save' }}</button>
            </form>
        </div>
        @endforeach
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ __('vender/parcels.recent_parcels') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table id="parcelsTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.parcel_no') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.bus') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.details') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.amount') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.current_status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.update_status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('vender/parcels.print_receipt') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($parcels as $parcel)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <a href="{{ route('bus_owner.parcels.show', $parcel->id) }}" class="text-teal-700 hover:underline">{{ $parcel->parcel_number }}</a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $parcel->bus->bus_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $parcel->parcel_type }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="text-xs">
                                    @if($parcel->weight) <div>{{ __('vender/parcels.weight_label') }} {{ $parcel->weight }}kg</div> @endif
                                    @if($parcel->height) <div>{{ __('vender/parcels.height_label') }} {{ $parcel->height }}cm</div> @endif
                                    @if($parcel->width) <div>{{ __('vender/parcels.width_label') }} {{ $parcel->width }}cm</div> @endif
                                    @if(!$parcel->weight && !$parcel->height && !$parcel->width) - @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                {{ $currency }} {{ convert_money($parcel->amount_paid) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $parcel->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $parcel->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $parcel->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                    @if($parcel->status === 'completed')
                                        {{ __('vender/parcels.completed') }}
                                    @elseif($parcel->status === 'pending')
                                        {{ __('vender/parcels.pending') }}
                                    @else
                                        {{ __('vender/parcels.cancelled') }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if($parcel->status === 'completed')
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('vender/parcels.completed_status_locked') }}</span>
                                @else
                                    <form action="{{ route('bus_owner.parcels.update_status', $parcel->id) }}" method="POST" class="flex items-center space-x-2">
                                        @csrf
                                        <select name="status" class="text-xs border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-100">
                                            <option value="pending" {{ $parcel->status === 'pending' ? 'selected' : '' }}>{{ __('vender/parcels.pending') }}</option>
                                            <option value="completed" {{ $parcel->status === 'completed' ? 'selected' : '' }}>{{ __('vender/parcels.completed') }}</option>
                                            <option value="cancelled" {{ $parcel->status === 'cancelled' ? 'selected' : '' }}>{{ __('vender/parcels.cancelled') }}</option>
                                        </select>
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-xs font-bold">{{ __('vender/parcels.update') }}</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                @if(app(\App\Services\ParcelFlowService::class)->canPrintReceipt($parcel))
                                    <a href="{{ route('bus_owner.parcels.print', $parcel->id) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-print"></i> {{ __('vender/parcels.print_receipt') }}
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs" title="{{ __('vender/parcels.print_payment_required') }}">{{ __('vender/parcels.print_receipt') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">{{ __('vender/parcels.no_parcels_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $parcels->links() }}
        </div>
    </div>
</div>
@endsection
