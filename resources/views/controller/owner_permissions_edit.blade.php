@extends('admin.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 xl:w-2/3">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-4 bg-blue-600 text-white">
                    <h3 class="text-lg sm:text-xl font-semibold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('local_bus_owners.edit_local_admin') }} <span class="font-medium ml-1">{{ $user->name }}</span>
                    </h3>
                </div>

                <div class="p-4 sm:p-6">
                    <form action="{{ route('admin.update.role') }}" method="POST" class="space-y-4 md:space-y-0 md:flex md:items-end md:gap-4">
                        @csrf
                        <input type="hidden" name="id" value="{{ $user->id }}">

                        <div class="flex-1 min-w-0">
                            <label for="access" class="block text-sm font-medium text-gray-700 mb-1">{{ __('local_bus_owners.access') }}</label>
                            <select name="link" id="access" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="index">{{ __('vendor_sidebar.dashboard') }}</option>
                                <option value="buses">{{ __('vendor_sidebar.my_buses') }}</option>
                                <option value="routes">{{ __('vendor_sidebar.manage_routes') }}</option>
                                <option value="schedules">{{ __('vendor_sidebar.schedule') }}</option>
                                <option value="cities">{{ __('vendor_sidebar.cities') }}</option>
                                <option value="history">{{ __('vendor_sidebar.booking_history') }}</option>
                                <option value="{{ \App\Models\Access::BUS['EXCESS_LUGGAGE'] }}">{{ __('vendor_sidebar.excess_luggage') }}</option>
                                <option value="resaved.tickets">{{ __('vendor_sidebar.resaved_tickets') }}</option>
                                <option value="erning">{{ __('local_bus_owners.earnings_payments') }}</option>
                                <option value="local.bus.owners">{{ __('local_bus_owners.local_bus_owners') }}</option>
                                <option value="owner.permissions.view">{{ __('local_bus_owners.owner_permissions_view') }}</option>
                                <option value="owner.permissions.edit">{{ __('local_bus_owners.owner_permissions_edit') }}</option>
                                <option value="profile">{{ __('vendor_sidebar.profile') }}</option>
                                <option value="logout">{{ __('vendor_sidebar.logout') }}</option>
                            </select>
                        </div>

                        <div class="flex-1 min-w-0">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('local_bus_owners.status') }}</label>
                            <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="inactive">{{ __('local_bus_owners.inactive') }}</option>
                                <option value="active">{{ __('local_bus_owners.active') }}</option>
                            </select>
                        </div>

                        <div class="w-full md:w-auto">
                            <button type="submit" class="w-full md:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                {{ __('local_bus_owners.update') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-8">
                        <h4 class="text-md sm:text-lg font-medium text-gray-800 mb-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ __('local_bus_owners.current_access_permissions') }}
                        </h4>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('local_bus_owners.access') }}</th>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('local_bus_owners.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($user->access as $index => $access)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-3 sm:px-4 py-2 sm:py-3 whitespace-nowrap">{{ $index + 1 }}</td>
                                        <td class="px-3 sm:px-4 py-2 sm:py-3 whitespace-nowrap">{{ \App\Models\Access::labelForLink($access->link) }}</td>
                                        <td class="px-3 sm:px-4 py-2 sm:py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($access->status == 'active') bg-green-100 text-green-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ $access->status === 'active' ? __('local_bus_owners.active') : __('local_bus_owners.inactive') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
