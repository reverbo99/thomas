@extends('system.app')
@section('title', __('system.sidebar.payment_request'))
@section('content')
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <div class="w-full max-w-full px-4 py-6">
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 px-4 py-4 border border-gray-100">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('system.pages.select_date_range') }}</p>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('pay.request.pdf', request()->only(['period', 'start_date', 'end_date'])) }}" target="_blank"
                       class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                        {{ __('system.pages.export_pdf') }}
                    </a>
                    <a href="{{ route('pay.request.csv', request()->only(['period', 'start_date', 'end_date'])) }}"
                       class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
                        {{ __('system.pages.export_csv') }}
                    </a>
                </div>
            </div>
            @include('partials.booking_history_period_filter', [
                'formAction' => route('pay.request'),
                'resetUrl' => route('pay.request'),
                'period' => $period ?? request('period', ''),
                'startDate' => $startDate ?? request('start_date', ''),
                'endDate' => $endDate ?? request('end_date', ''),
            ])
        </div>

        <!-- Requested Transactions Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-4 bg-gradient-to-r from-blue-500 to-blue-400 text-white flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-semibold">{{ __('system.transactions.requested_transactions') }}</h2>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto" id="pendingTransactionsTable">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_company') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_user') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_payment_method') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_payment_details') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_amount') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_status') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_date') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"></th>
                            </tr>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium">#</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.company') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.user') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.payment_method') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.payment_details') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.amount') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.status') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.date') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.transactions.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs">
                            @forelse ($pendingTransactions as $index => $transaction)
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition" data-transaction-id="{{ $transaction->id }}" data-campany-id="{{ $transaction->campany ? $transaction->campany->id : 0 }}" data-vender-id="{{ $transaction->vender_id ?? 0 }}">
                                    <td class="py-2 px-4">{{ $index + 1 }}</td>
                                    <td class="py-2 px-4">{{ $transaction->campany ? $transaction->campany->name : __('system.common.vender_label') }}</td>
                                    <td class="py-2 px-4">{{ $transaction->user ? $transaction->user->name : __('system.common.unknown') }}</td>
                                    <td class="py-2 px-4">{{ $transaction->payment_method ?? __('system.common.unknown') }}</td>
                                    <td class="py-2 px-4">{{ transaction_payment_detail($transaction) }}</td>
                                    <td class="py-2 px-4 amount" data-amount="{{ $transaction->amount }}">{{ $currency }} {{ convert_money($transaction->amount) }}</td>
                                    <td class="py-2 px-4">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $transaction->status === 'Completed' ? 'bg-green-500 text-white' : ($transaction->status === 'Pending' ? 'bg-yellow-500 text-black' : 'bg-red-500 text-white') }}">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4" data-date="{{ $transaction->created_at->format('Y-m-d') }}">{{ $transaction->created_at->format('d M Y H:i:s') }}</td>
                                    <td class="py-2 px-4">
                                        @if ($transaction->status !== 'Completed' && $transaction->status !== 'Cancelled')
                                            <button class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm flex items-center gap-1" onclick="showTransactionModal('{{ $transaction->id }}', '{{ convert_money($transaction->amount) }}', '{{ $transaction->campany ? $transaction->campany->id : 0 }}', '{{ $transaction->vender_id ?? 0 }}')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                {{ __('system.common.edit') }}
                                            </button>
                                        @else
                                            <span class="text-gray-500">{{ __('system.common.no_actions') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-4 px-4 text-center text-gray-500">{{ __('system.common.dt_empty_pending_tx') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- All Transactions Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-blue-500 to-blue-400 text-white flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-semibold">{{ __('system.transactions.all_transactions') }}</h2>
                <span class="text-sm font-medium">{{ __('system.common.total') }}: {{ $currency }} {{ convert_money($allTransactionsTotal ?? 0) }}</span>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto" id="allTransactionsTable">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_company') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_user') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_amount') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_reference') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_status') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"><input type="text" class="w-full px-2 py-1 border rounded text-xs search-input" placeholder="{{ __('system.transactions.search_date') }}"></th>
                                <th class="py-2 px-4 text-left font-medium"></th>
                            </tr>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                                <th class="py-2 px-4 text-left font-medium">#</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.company') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.user') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.amount') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.reference_no') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.status') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.common.date') }}</th>
                                <th class="py-2 px-4 text-left font-medium">{{ __('system.transactions.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-xs">
                            @forelse ($allTransactions as $index => $transaction)
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition" data-transaction-id="{{ $transaction->id }}" data-campany-id="{{ $transaction->campany ? $transaction->campany->id : 0 }}" data-vender-id="{{ $transaction->vender_id ?? 0 }}">
                                    <td class="py-2 px-4">{{ $index + 1 }}</td>
                                    <td class="py-2 px-4">{{ $transaction->campany ? $transaction->campany->name : __('system.common.vender_label') }}</td>
                                    <td class="py-2 px-4">{{ $transaction->user ? $transaction->user->name : __('system.common.unknown') }}</td>
                                    <td class="py-2 px-4 amount" data-amount="{{ $transaction->amount }}">{{ $currency }} {{ convert_money($transaction->amount) }}</td>
                                    <td class="py-2 px-4">{{ $transaction->reference_number }}</td>
                                    <td class="py-2 px-4">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $transaction->status === 'Completed' ? 'bg-green-500 text-white' : ($transaction->status === 'Pending' ? 'bg-yellow-500 text-black' : 'bg-red-500 text-white') }}">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-4" data-date="{{ $transaction->created_at->format('Y-m-d') }}">{{ $transaction->created_at->format('d M Y H:i:s') }}</td>
                                    <td class="py-2 px-4">
                                        @if ($transaction->status == 'Completed')
                                            @if ($transaction->vender_id > 0)
                                                <form action="{{ route('print.recipt2') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="data" value="{{ $transaction }}">
                                                    <button type="submit" class="px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                        </svg>
                                                        {{ __('system.common.print') }}
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('print.recipt') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="data" value="{{ $transaction }}">
                                                    <button type="submit" class="px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                        </svg>
                                                        {{ __('system.common.print') }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 px-4 text-center text-gray-500">{{ __('system.common.dt_empty_tx') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transaction Modal -->
        <div id="transactionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4 transform transition-all">
                <div class="p-4 flex justify-between items-center border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800" id="transactionModalLabel">{{ __('system.transactions.update_status') }}</h2>
                    <button type="button" class="text-gray-500 hover:text-gray-700" onclick="closeTransactionModal()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4" id="transactionModalBody">
                    <div id="modalLoading" class="hidden text-center">
                        <svg class="animate-spin h-5 w-5 text-blue-500 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 mt-2">{{ __('system.common.loading') }}</p>
                    </div>
                    <div id="modalError" class="hidden text-red-500 text-sm mb-4"></div>
                    <div id="modalContent">
                        <p class="text-sm text-gray-600 mb-4" id="transactionAmount">Update status for transaction of {{ $currency }} 0?</p>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <form class="flex-1" id="completeForm" action="" method="POST">
                                @csrf
                                <input required type="text" name="reference_number" class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm mb-2" placeholder="{{ __('system.common.reference_number') }}">
                                <button type="submit" class="w-full px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm">{{ __('system.common.accept') }}</button>
                            </form>
                            <form class="flex-1" id="cancelForm" action="" method="POST">
                                @csrf
                                <textarea name="cancel_reason" required maxlength="500" rows="3"
                                    class="w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-red-500 focus:border-red-500 text-sm mb-2"
                                    placeholder="{{ __('system.transactions.cancel_reason_placeholder') }}"></textarea>
                                <button type="submit" class="w-full px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm">{{ __('system.common.cancel') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="p-4 flex justify-end gap-2 border-t border-gray-200">
                    <button type="button" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm" onclick="closeTransactionModal()">{{ __('system.common.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        const txUpdateTemplate = @json(__('system.transactions.update_status_for', ['currency' => '__CUR__', 'amount' => '__AMT__']));

        $(document).ready(function() {
            $.fn.dataTable.ext.errMode = 'none';

            const pendingTable = $('#pendingTransactionsTable').DataTable({
                responsive: true,
                paging: true,
                pageLength: 5,
                searching: true,
                ordering: true,
                language: {
                    emptyTable: @json(__('system.common.dt_empty_pending_tx'))
                },
                columnDefs: [
                    { orderable: false, targets: 8 },
                    { searchable: false, targets: 8 }
                ]
            });

            const allTable = $('#allTransactionsTable').DataTable({
                responsive: true,
                paging: true,
                pageLength: 5,
                searching: true,
                ordering: true,
                language: {
                    emptyTable: @json(__('system.common.dt_empty_tx'))
                },
                columnDefs: [
                    { orderable: false, targets: 7 },
                    { searchable: false, targets: 7 }
                ]
            });

            $('#pendingTransactionsTable thead tr:first-child th').each(function(index) {
                if (index !== 0 && index !== 8) {
                    $(this).find('input').on('keyup change', function() {
                        pendingTable.column(index).search(this.value).draw();
                    });
                }
            });

            $('#allTransactionsTable thead tr:first-child th').each(function(index) {
                if (index !== 0 && index !== 7) {
                    $(this).find('input').on('keyup change', function() {
                        allTable.column(index).search(this.value).draw();
                    });
                }
            });
        });

        // Function to show transaction modal
        function showTransactionModal(transactionId, amount, campanyId, venderId) {
            if (!transactionId || !amount) {
                console.error('Invalid transaction data:', { transactionId, amount, campanyId, venderId });
                alert(@json(__('system.transactions.invalid_transaction_data')));
                return;
            }

            // Update the amount display
            document.getElementById('transactionAmount').textContent = txUpdateTemplate.replace('__CUR__', '{{ $currency }}').replace('__AMT__', amount);

            // Update form action URLs
            const completeForm = document.getElementById('completeForm');
            const cancelForm = document.getElementById('cancelForm');
            completeForm.action = "{{ route('transactions.complete', ['transaction' => ':transaction', 'campany' => ':campany', 'vender' => ':vender']) }}".replace(':transaction', transactionId).replace(':campany', campanyId).replace(':vender', venderId);
            cancelForm.action = "{{ route('transactions.cancel', ['transaction' => ':transaction', 'campany' => ':campany', 'vender' => ':vender']) }}".replace(':transaction', transactionId).replace(':campany', campanyId).replace(':vender', venderId);

            // Clear any previous reference number input
            document.querySelector('#completeForm input[name="reference_number"]').value = '';

            // Show the modal
            document.getElementById('transactionModal').classList.remove('hidden');
        }

        // Function to close transaction modal
        function closeTransactionModal() {
            const modal = document.getElementById('transactionModal');
            const modalContent = document.getElementById('modalContent');
            const modalError = document.getElementById('modalError');
            const modalLoading = document.getElementById('modalLoading');

            modal.classList.add('hidden');
            modalError.classList.add('hidden');
            modalLoading.classList.add('hidden');
            document.getElementById('transactionAmount').textContent = txUpdateTemplate.replace('__CUR__', '{{ $currency }}').replace('__AMT__', '0');
            document.getElementById('completeForm').action = '';
            document.getElementById('cancelForm').action = '';
        }

        // Close modal when clicking outside
        document.getElementById('transactionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeTransactionModal();
            }
        });

        // Debug: Log when modal is triggered
        window.addEventListener('click', function(e) {
            if (e.target.closest('button[onclick*="showTransactionModal"]')) {
                console.log('Modal open triggered');
            }
        });
    </script>

    <style>
        .search-input {
            width: 100%;
            padding: 4px;
            font-size: 12px;
            border-radius: 4px;
        }
    </style>
@endsection