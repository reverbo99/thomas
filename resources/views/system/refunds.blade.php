@extends('system.app')

@section('title', __('system.refunds.title'))

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-indigo-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Page header --}}
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-white shadow-sm border border-gray-200">
                    <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 tracking-tight">{{ __('system.refunds.title') }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">{{ __('system.refunds.subtitle') }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('system.refunds.pdf') }}" target="_blank"
                   class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm shadow-sm">
                    {{ __('system.pages.export_pdf') }}
                </a>
                <a href="{{ route('system.refunds.csv') }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm shadow-sm">
                    {{ __('system.pages.export_csv') }}
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-3 shadow-sm" role="alert">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <span class="text-sm font-medium text-green-800">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-4 py-3 flex items-center gap-3 shadow-sm" role="alert">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <span class="text-sm font-medium text-red-800">{{ session('error') }}</span>
            </div>
        @endif

        @php
            $pendingCount = $refunds->where('status', 'Pending')->count();
            $approvedCount = $refunds->where('status', 'Approved')->count();
            $rejectedCount = $refunds->where('status', 'Rejected')->count();
            $pendingTotal = $refunds->where('status', 'Pending')->sum('amount');
            $approvedTotal = $refunds->where('status', 'Approved')->sum('amount');
        @endphp

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="refund-card bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-yellow-600">{{ __('system.common.pending') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-800">{{ $pendingCount }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $currency }} {{ convert_money($pendingTotal) }}</p>
                </div>
                <div class="h-1 bg-yellow-400"></div>
            </div>
            <div class="refund-card bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-green-600">{{ __('system.common.approved') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-800">{{ $approvedCount }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $currency }} {{ convert_money($approvedTotal) }}</p>
                </div>
                <div class="h-1 bg-green-400"></div>
            </div>
            <div class="refund-card bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-red-600">{{ __('system.common.rejected') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-800">{{ $rejectedCount }}</p>
                </div>
                <div class="h-1 bg-red-400"></div>
            </div>
            <div class="refund-card bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">{{ __('system.refunds.total_requests') }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-800">{{ $refunds->count() }}</p>
                </div>
                <div class="h-1 bg-indigo-400"></div>
            </div>
        </div>

        {{-- Table card --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800">{{ __('system.pages.all_refunds') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('system.pages.refunds_table_subtitle') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table id="refundsTable" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('system.common.no') }}</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('system.pages.booking_id') }}</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('system.common.amount') }}</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('system.common.status') }}</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('system.pages.phone') }}</th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('system.pages.full_name') }}</th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('system.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($refunds as $refund)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $refund->booking_code }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ convert_money($refund->amount) }} {{ $currency }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($refund->status == 'Pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('system.common.pending') }}</span>
                                    @elseif($refund->status == 'Approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('system.common.approved') }}</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('system.common.rejected') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $refund->phone }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $refund->fullname }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($refund->status == 'Pending')
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('system.refunds.approve', $refund->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-green-500 text-white hover:bg-green-600 transition-colors shadow-sm">{{ __('system.refunds.approve') }}</button>
                                            </form>
                                            <form action="{{ route('system.refunds.reject', $refund->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500 text-white hover:bg-red-600 transition-colors shadow-sm">{{ __('system.refunds.reject') }}</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">{{ __('system.refunds.no_actions_short') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-14 h-14 mb-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-sm font-medium text-gray-500">{{ __('system.pages.no_refunds_table') }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ __('system.pages.no_refunds_table_hint') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    var $table = $('#refundsTable');
    var firstRowCells = $table.find('tbody tr:first td').length;
    if (firstRowCells === 7) {
        $table.DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            order: [[0, 'asc']],
            language: {
                search: '',
                searchPlaceholder: @json(__('system.pages.dt_search_refunds')),
                lengthMenu: @json(__('all.dt_show_entries')),
                info: @json(__('system.pages.dt_info_refunds')),
                paginate: {
                    first: @json(__('all.dt_first')),
                    last: @json(__('all.dt_last')),
                    next: @json(__('all.dt_next')),
                    previous: @json(__('all.dt_previous'))
                }
            },
            columnDefs: [{ orderable: false, targets: 6 }],
            dom: '<"flex flex-wrap justify-between items-center gap-4 mb-4 px-2"lf>rt<"flex justify-between items-center gap-4 mt-4 px-2"ip>',
            drawCallback: function() {
                $('.dataTables_filter input').addClass('rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm');
                $('.dataTables_length select').addClass('rounded-lg border-gray-300 text-sm');
            }
        });
    }
});
</script>
<style>
    .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 0.5rem; padding: 0.375rem 0.75rem; margin: 0 0.125rem; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #4f46e5 !important; color: #fff !important; border: none !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #e0e7ff !important; color: #4f46e5 !important; border: none !important; }
    #refundsTable .bg-green-500 { background-color: #10b981 !important; color: #fff !important; }
    #refundsTable .bg-green-500:hover { background-color: #059669 !important; }
    #refundsTable .bg-red-500 { background-color: #ef4444 !important; color: #fff !important; }
    #refundsTable .bg-red-500:hover { background-color: #dc2626 !important; }
</style>
@endsection
