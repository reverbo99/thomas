@extends('system.app')

@section('title', __('system.operators.title'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0 text-red-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ __('system.common.submission_errors') }}</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Dashboard Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('system.operators.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('system.operators.subtitle') }}</p>
        </div>
        <div class="mt-4 md:mt-0 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <a href="{{ route('system.campany.print') }}" target="_blank"
               class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v2h12V3z"/>
                </svg>
                {{ __('system.operators.print_all') }}
            </a>
            <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-200">
                <span class="text-sm font-medium text-gray-700">{{ __('system.common.total_balance') }}:</span>
                <span class="ml-2 font-bold text-indigo-600" id="companyTotal">{{ $currency }} 0.00</span>
            </div>
        </div>
    </div>

    <!-- Companies Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ __('system.operators.company_data') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table id="companyTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <!-- Search Row -->
                    <tr>
                        <th class="px-6 py-3"><input type="text" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ __('system.operators.search_no') }}"></th>
                        <th class="px-6 py-3"><input type="text" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ __('system.operators.search_company') }}"></th>
                        <th class="px-6 py-3"><input type="text" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ __('system.operators.search_owner') }}"></th>
                        <th class="px-6 py-3"><input type="text" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ __('system.operators.search_contact') }}"></th>
                        <th class="px-6 py-3"><input type="text" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ __('system.operators.search_balance') }}"></th>
                        <th class="px-6 py-3"><input type="text" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search %"></th>
                        <th class="px-6 py-3"><input type="text" class="w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ __('system.operators.search_status') }}"></th>
                        <th class="px-6 py-3"></th> <!-- Empty header for Actions -->
                    </tr>
                    <!-- Column Headers -->
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.no') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.company') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.owner') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.contact') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.balance') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('system.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @isset($campanies)
                        @php $i = 1; @endphp
                        @foreach ($campanies as $campany)
                            <tr class="hover:bg-gray-50">
                                <form action="{{ route('system.campany.status') }}" method="POST">
                                    @csrf
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $i++ }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('system.campany.show', $campany) }}" class="text-indigo-600 hover:text-indigo-800 hover:underline transition-colors">{{ $campany->name }}</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $campany->user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @php
                                            $contact = preg_replace('/\s+/', '', $campany->user->contact ?? '');
                                            $contact = preg_replace('/^\+?255/', '', $contact);
                                            $contact = ltrim($contact, '0');
                                            echo $contact !== '' ? '255' . $contact : '—';
                                        @endphp
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 amount" data-amount="{{ $campany->balance->amount ?? 0 }}">
                                        {{ $currency }} {{ convert_money($campany->balance->amount ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-500">{{ $campany->percentage ?? 0 }}%</span>
                                            <input type="number" name="percentage" min="0" max="100" 
                                                   class="w-16 text-sm border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" 
                                                   value="{{ $campany->percentage }}" placeholder="%">
                                            <span class="text-sm text-gray-500">{{ __('system.common.or') }}</span>
                                            <input type="number" name="commission_amount" step="0.01"
                                                   class="w-24 text-sm border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" 
                                                   value="{{ $campany->commission_amount }}" placeholder="{{ __('system.operators.amount_placeholder') }}">
                                        </div>
                                    </td>
                                    <input type="hidden" name="campany_id" value="{{ $campany->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('busowner', ['id' => $campany->user->id]) }}" 
                                           class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                  {{ $campany->status == 1 ? 'bg-green-100 text-green-800' : 
                                                     ($campany->status == 2 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ $campany->status == 1 ? __('system.common.active') : ($campany->status == 2 ? __('system.common.disabled') : __('system.common.pending')) }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <select name="status" class="text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="0" {{ $campany->status == 0 ? 'selected' : '' }}>{{ __('system.common.pending') }}</option>
                                                <option value="1" {{ $campany->status == 1 ? 'selected' : '' }}>{{ __('system.common.active') }}</option>
                                                <option value="2" {{ $campany->status == 2 ? 'selected' : '' }}>{{ __('system.common.disabled') }}</option>
                                            </select>
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                {{ __('system.common.save') }}
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('system.operators.no_companies') }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ __('system.operators.no_companies_desc') }}</p>
                            </td>
                        </tr>
                    @endisset
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include jQuery and DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';
    window.companyCurrency = @json(session('currency', 'Tzs'));
    window.companyUsdToTzs = {{ app('usdToTzs') ?? 2500 }};
    var $companyTable = $('#companyTable');
    var columnCount = $companyTable.find('thead tr:last th').length;
    var firstRowCells = $companyTable.find('tbody tr:first td').length;
    if (firstRowCells !== 0 && firstRowCells !== columnCount) {
        return;
    }

    const companyTable = $companyTable.DataTable({
        responsive: true,
        paging: true,
        searching: true,
        ordering: true,
        lengthChange: true,
        info: true,
        autoWidth: false,
        language: {
            emptyTable: @json(__('system.common.dt_empty_companies')),
            search: "_INPUT_",
            searchPlaceholder: @json(__('system.common.search')),
            lengthMenu: @json(__('all.dt_show_entries')),
            paginate: {
                first: @json(__('all.dt_first')),
                last: @json(__('all.dt_last')),
                next: @json(__('all.dt_next')),
                previous: @json(__('all.dt_previous'))
            }
        },
        columnDefs: [
            { orderable: false, targets: [7] }, // Disable sorting on Actions column
            { searchable: false, targets: [7] } // Disable searching on Actions column
        ],
        initComplete: function() {
            // Apply search to each column
            $('#companyTable thead tr:first-child th').each(function(index) {
                if (index !== 7) { // Skip Actions column
                    $(this).find('input').on('keyup change', function() {
                        companyTable.column(index).search(this.value).draw();
                    });
                }
            });
        },
        footerCallback: function(row, data, start, end, display) {
            let api = this.api();
            let total = api
                .rows({ page: 'current' })
                .nodes()
                .toArray()
                .reduce((sum, row) => {
                    let amount = $(row).find('.amount').data('amount') || 0;
                    return sum + parseFloat(amount);
                }, 0);
            let displayTotal = window.companyCurrency === 'Usd' ? (total / (window.companyUsdToTzs || 2500)).toLocaleString('en-US', { minimumFractionDigits: 2 }) : total.toLocaleString('en-US', { minimumFractionDigits: 2 });
            $('#companyTotal').text('{{ $currency }} ' + displayTotal);
        }
    });

    // Log form submissions for debugging
    $('form').on('submit', function(e) {
        console.log('Form submitted:', $(this).serialize());
        return true;
    });
});
</script>

<style>
    /* Custom DataTables styling to match Tailwind */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.75rem;
        margin-left: 0.25rem;
        border-radius: 0.375rem;
        border: 1px solid transparent;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #4f46e5;
        color: white !important;
        border: 1px solid #4f46e5;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #e0e7ff;
        border: 1px solid #c7d2fe;
        color: #4f46e5 !important;
    }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
    }
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.375rem 1.75rem 0.375rem 0.75rem;
    }
</style>
@endsection