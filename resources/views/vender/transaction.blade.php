@extends('vender.app')

@section('title', __('assistance/transaction.transactions'))

@php
    $vb = auth()->user()->VenderBalances;
    $vendorDualWallet = \Illuminate\Support\Facades\Schema::hasColumn('vender_balances', 'sell_cash_amount');
    $commissionBalance = optional($vb)->amount ?? 0;
    $cashBalance = $vendorDualWallet ? ($vb->sell_cash_amount ?? 0) : 0;
    $txCount = $coll->count();
    $vendorAccount = auth()->user()->VenderAccount;
    $vendorMobileNumber = trim((string) ($vb->payment_number ?? ''));
    $vendorBankName = trim((string) ($vendorAccount?->bank_name ?? ''));
    $vendorBankNumber = trim((string) ($vendorAccount?->bank_number ?? ''));
    $period = $period ?? request('period', 'today');
    $startDate = $startDate ?? request('start_date', '');
    $endDate = $endDate ?? request('end_date', '');
    $filters = [
        'today' => __('assistance/transaction.today'),
        'week' => __('assistance/transaction.this_week'),
        'month' => __('assistance/transaction.this_month'),
        'year' => __('assistance/transaction.this_year'),
        'custom' => __('assistance/transaction.custom_range'),
    ];
    $periodLabel = ($period === 'custom' && $startDate && $endDate)
        ? $startDate . ' – ' . $endDate
        : ($filters[$period] ?? $filters['today']);
    $exportQuery = request()->only(['period', 'start_date', 'end_date']);
@endphp

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('assistance/transaction.transactions') }}</h1>
            <p class="vendor-dash__subtitle">{{ __('assistance/sidebar.transactions') }} · {{ $periodLabel }}</p>
        </div>
        <div class="vendor-dash__actions">
            @if ($vb)
                <button type="button" id="openTransactionModal" class="page-btn">
                    <i class="fas fa-paper-plane"></i> {{ __('assistance/transaction.request_transaction') }}
                </button>
            @endif
            @if ($vb)
                <a href="{{ route('vender.wallet.deposit') }}" class="page-btn page-btn--outline">
                    <i class="fas fa-plus"></i> {{ __('assistance/transaction.deposit') }}
                </a>
            @endif
        </div>
    </header>

    <div class="vendor-kpi-grid">
        <article class="vendor-kpi vendor-kpi--commission">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-wallet"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/transaction.balance') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/transaction.commission_wallet') }}</p>
            <p class="vendor-kpi__value">{{ convert_money($commissionBalance) }}</p>
            <p class="vendor-kpi__hint">{{ $currency }} · {{ __('assistance/transaction.payout_requests_hint') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--cash">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-money-bill-wave"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/transaction.cash_badge') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/transaction.cash_wallet') }}</p>
            <p class="vendor-kpi__value">{{ $vendorDualWallet && $vb ? convert_money($cashBalance) : '—' }}</p>
            <p class="vendor-kpi__hint">{{ $vendorDualWallet ? __('assistance/transaction.sell_cash_float') : __('assistance/transaction.wallet_split_not_active') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--pending">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-hourglass-half"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/transaction.pending') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/transaction.pending') }}</p>
            <p class="vendor-kpi__value">{{ convert_money($pending) }}</p>
            <p class="vendor-kpi__hint">{{ $currency }} {{ __('assistance/transaction.awaiting_approval') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--withdrawn">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-circle-check"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/transaction.withdrawn') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/transaction.withdrawn') }}</p>
            <p class="vendor-kpi__value">{{ convert_money($accept) }}</p>
            <p class="vendor-kpi__hint">{{ $currency }} {{ __('assistance/transaction.completed_payouts') }}</p>
        </article>
    </div>

    @if ($vb && $vendorDualWallet)
    <div class="vendor-wallet-grid">
        <div class="vendor-wallet-card">
            <div class="vendor-wallet-card__head">
                <h3 class="vendor-wallet-card__title">{{ __('assistance/transaction.move_between_wallets') }}</h3>
                <p class="vendor-wallet-card__sub">{{ __('assistance/transaction.transfer_between_wallets') }}</p>
            </div>
            <div class="vendor-wallet-card__body">
                <form method="POST" action="{{ route('vender.wallet.transfer') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="vendor-form-field flex-1 min-w-[10rem]">
                        <label for="transfer-direction">{{ __('assistance/transaction.direction') }}</label>
                        <select name="direction" id="transfer-direction" class="page-input text-sm w-full">
                            <option value="to_sell_cash">{{ __('assistance/transaction.commission_to_cash') }}</option>
                            <option value="to_commission">{{ __('assistance/transaction.cash_to_commission') }}</option>
                        </select>
                    </div>
                    <div class="vendor-form-field">
                        <label for="transfer-amount">{{ __('assistance/transaction.amount_tsh') }}</label>
                        <input type="number" name="amount" id="transfer-amount" step="0.01" min="0.01" required class="page-input text-sm w-36">
                    </div>
                    <button type="submit" class="page-btn">{{ __('assistance/transaction.transfer') }}</button>
                </form>

                @if ((float) ($vb->sell_cash_amount ?? 0) == 0 && (float) ($vb->amount ?? 0) > 0)
                <div class="vendor-legacy-banner">
                    <p>{{ __('assistance/transaction.legacy_wallet_notice') }}</p>
                    <form method="POST" action="{{ route('vender.wallet.migrateLegacyCash') }}" onsubmit="return confirm(@json(__('assistance/transaction.confirm_move_all_to_cash')));">
                        @csrf
                        <button type="submit" class="page-btn page-btn--outline" style="font-size:0.8125rem">{{ __('assistance/transaction.move_all_to_cash_once') }}</button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <div class="vendor-wallet-card">
            <div class="vendor-wallet-card__head">
                <h3 class="vendor-wallet-card__title">{{ __('assistance/transaction.wallet_actions') }}</h3>
                <p class="vendor-wallet-card__sub">{{ __('assistance/transaction.manage_float_payouts') }}</p>
            </div>
            <div class="vendor-wallet-card__body vendor-wallet-actions">
                <a href="{{ route('vender.wallet.deposit') }}" class="page-btn">
                    <i class="fas fa-arrow-down"></i> {{ __('assistance/transaction.deposit_to_cash_wallet') }}
                </a>
                <button type="button" class="page-btn page-btn--outline" id="openTransactionModalSide">
                    <i class="fas fa-paper-plane"></i> {{ __('assistance/transaction.request_transaction') }}
                </button>
                <a href="{{ route('vender.index') }}" class="page-btn page-btn--outline">
                    <i class="fas fa-gauge-high"></i> {{ __('assistance/sidebar.dashboard') }}
                </a>
            </div>
        </div>
    </div>
    @endif

    <section class="vendor-table-card">
        <div class="vendor-table-card__head">
            <div class="vendor-table-card__title-wrap">
                <h3>{{ __('assistance/transaction.transaction_history') }}</h3>
                <p>{{ $txCount }} {{ __('vender/busroot.entries') }} · {{ $periodLabel }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('vender.transaction.export.pdf', $exportQuery) }}" target="_blank"
                   class="page-btn page-btn--outline" style="font-size:0.8125rem">
                    <i class="fas fa-file-pdf"></i> {{ __('assistance/transaction.export_pdf') }}
                </a>
                <a href="{{ route('vender.transaction.export.csv', $exportQuery) }}"
                   class="page-btn page-btn--outline" style="font-size:0.8125rem">
                    <i class="fas fa-file-excel"></i> {{ __('assistance/transaction.export_excel') }}
                </a>
                @include('partials.booking_history_period_filter', [
                    'formAction' => route('vender.transaction'),
                    'resetUrl' => route('vender.transaction'),
                    'period' => $period,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'variant' => 'vendor',
                ])
            </div>
        </div>

        <div class="vendor-table-toolbar">
            <div class="vendor-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="txSearch" class="page-input text-sm" placeholder="{{ __('assistance/transaction.search_all_columns') }}">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <select id="txRowsPerPage" class="page-input text-sm py-1 w-auto">
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </label>
        </div>

        @if ($coll->isEmpty())
            <div class="vendor-table-empty">
                <i class="fas fa-receipt"></i>
                <h4>{{ __('assistance/transaction.no_transactions_found') }}</h4>
                <p>{{ __('assistance/transaction.transaction_history') }}</p>
                @if ($vb)
                    <button type="button" class="page-btn" id="openTransactionModalEmpty">{{ __('assistance/transaction.request_transaction') }}</button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="vendor-dash-table" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('assistance/transaction.vender') }}</th>
                            <th>{{ __('assistance/transaction.payment_method') }}</th>
                            <th>{{ __('assistance/transaction.date') }}</th>
                            <th>{{ __('assistance/transaction.amount') }}</th>
                            <th>{{ __('assistance/transaction.reference_no') }}</th>
                            <th>{{ __('assistance/transaction.status') }}</th>
                            <th>{{ __('assistance/transaction.action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="txTableBody">
                        @foreach ($coll as $data)
                            @php
                                $statusKey = strtolower($data->status ?? '');
                                $statusClass = in_array($statusKey, ['completed', 'complete']) ? 'vendor-status--paid'
                                    : ($statusKey === 'pending' ? 'vendor-status--unpaid' : 'vendor-status--other');
                            @endphp
                            <tr class="tx-row" data-date="{{ $data->created_at->format('Y-m-d') }}">
                                <td class="row-index text-gray-400"></td>
                                <td class="font-medium">{{ $data->user->name ?? '—' }}</td>
                                <td><span class="vendor-tx-method">{{ $data->payment_method }}</span></td>
                                <td>
                                    <span class="vendor-schedule-date__day">{{ $data->created_at->format('d M Y') }}</span>
                                    <span class="vendor-schedule-date__sub">{{ $data->created_at->format('h:i A') }}</span>
                                </td>
                                <td class="vendor-tx-amount">{{ $currency }} {{ convert_money($data->amount) }}</td>
                                <td><span class="vendor-tx-ref">{{ $data->reference_number ?? __('assistance/transaction.na') }}</span></td>
                                <td><span class="vendor-status {{ $statusClass }}">{{ $data->status }}</span></td>
                                <td>
                                    <form action="{{ route('print.recipt2') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="data" value="{{ $data }}">
                                        <button type="submit" class="vendor-tx-print">
                                            <i class="fas fa-print"></i> {{ __('assistance/transaction.print') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="vendor-table-footer">
                <p class="vendor-table-footer__info">
                    {{ __('vender/busroot.showing') }} <span id="txShowingStart">1</span>
                    {{ __('vender/busroot.to') }} <span id="txShowingEnd">10</span>
                    {{ __('vender/busroot.of') }} <span id="txTotal">{{ $txCount }}</span>
                    {{ __('vender/busroot.entries') }}
                </p>
                <nav aria-label="{{ __('assistance/transaction.transaction_pagination') }}">
                    <ul class="vendor-pagination" id="txPagination"></ul>
                </nav>
            </div>
        @endif
    </section>
</div>

@if ($vb)
<div id="requestTransactionModal" class="vendor-modal" aria-hidden="true">
    <div class="vendor-modal__dialog" role="dialog" aria-labelledby="txModalTitle">
        <div class="vendor-modal__head">
            <h2 class="vendor-modal__title" id="txModalTitle">{{ __('assistance/transaction.request_transaction_title') }}</h2>
            <button type="button" class="vendor-modal__close" id="closeTransactionModal" aria-label="{{ __('assistance/transaction.close') }}">&times;</button>
        </div>
        <form id="requestTransactionForm" action="{{ route('vender.transaction.request') }}" method="POST">
            @csrf
            <div class="vendor-modal__body">
                <div class="vendor-form-field">
                    <label for="amount">{{ __('assistance/transaction.amount_tsh') }}</label>
                    <input type="number" class="page-input w-full" id="amount" name="amount" step="0.01" min="1"
                           max="{{ $commissionBalance }}"
                           placeholder="{{ __('assistance/transaction.max_amount', ['amount' => convert_money($commissionBalance)]) }}"
                           required>
                    @error('amount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="vendor-form-hint">{{ __('assistance/transaction.available_balance_hint', ['currency' => $currency, 'amount' => convert_money($commissionBalance)]) }}</p>
                </div>
                <div class="vendor-form-field">
                    <label for="payment_method">{{ __('assistance/transaction.payment_method') }}</label>
                    <select class="page-input w-full" id="payment_method" name="payment_method" required>
                        <option value="" disabled selected>{{ __('assistance/transaction.select_payment_method') }}</option>
                        <option value="MPesa">MPesa</option>
                        <option value="AirtelMoney">Airtel-money</option>
                        <option value="MixxBYYass">Mixx BY Yass</option>
                        <option value="Halopesa">Halopesa</option>
                        <option value="bank">Bank</option>
                    </select>
                    @error('payment_method')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="vendor-form-field" style="margin-bottom:0">
                    <label for="payment_number" id="payment_number_label">{{ __('assistance/transaction.payment_number') }}</label>
                    <input type="text" name="payment_number" id="payment_number" class="page-input w-full bg-gray-50"
                           readonly
                           data-mobile="{{ $vendorMobileNumber }}"
                           data-bank="{{ $vendorBankNumber }}"
                           data-bank-name="{{ $vendorBankName }}"
                           value="{{ $vendorMobileNumber !== '' ? $vendorMobileNumber : __('assistance/transaction.na') }}" required>
                    <p class="vendor-form-hint" id="bank_account_hint" hidden>
                        {{ __('assistance/transaction.bank_account_on_file', ['bank' => $vendorBankName !== '' ? $vendorBankName : __('assistance/transaction.na'), 'account' => $vendorBankNumber !== '' ? $vendorBankNumber : __('assistance/transaction.na')]) }}
                    </p>
                    @error('payment_number')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="vendor-modal__foot">
                <button type="button" class="page-btn page-btn--outline" id="closeTransactionModalFooter">{{ __('assistance/transaction.close') }}</button>
                <button type="submit" class="page-btn">{{ __('assistance/transaction.submit_request') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('requestTransactionModal');
    const openIds = ['openTransactionModal', 'openTransactionModalSide', 'openTransactionModalEmpty'];
    const paymentMethodSelect = document.getElementById('payment_method');
    const paymentNumberInput = document.getElementById('payment_number');
    const paymentNumberLabel = document.getElementById('payment_number_label');
    const bankAccountHint = document.getElementById('bank_account_hint');
    const amountInput = document.getElementById('amount');
    const mobileNumber = @json($vendorMobileNumber);
    const bankNumber = @json($vendorBankNumber);
    const naLabel = @json(__('assistance/transaction.na'));
    const mobileLabel = @json(__('assistance/transaction.payment_number'));
    const bankLabel = @json(__('assistance/transaction.bank_account_number'));

    function syncPayoutAccountField() {
        if (!paymentMethodSelect || !paymentNumberInput || !paymentNumberLabel) {
            return;
        }

        const method = (paymentMethodSelect.value || '').toLowerCase();
        const isBank = method === 'bank';
        paymentNumberLabel.textContent = isBank ? bankLabel : mobileLabel;

        if (isBank) {
            paymentNumberInput.value = bankNumber || naLabel;
        } else {
            paymentNumberInput.value = mobileNumber || naLabel;
        }

        if (bankAccountHint) {
            bankAccountHint.hidden = !isBank;
        }
    }

    function openModal() {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        syncPayoutAccountField();
        amountInput?.focus();
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.getElementById('requestTransactionForm')?.reset();
        syncPayoutAccountField();
    }

    openIds.forEach(id => {
        document.getElementById(id)?.addEventListener('click', openModal);
    });

    document.getElementById('closeTransactionModal')?.addEventListener('click', closeModal);
    document.getElementById('closeTransactionModalFooter')?.addEventListener('click', closeModal);

    modal?.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });

    amountInput?.addEventListener('input', function () {
        const max = parseFloat(this.getAttribute('max'));
        const val = parseFloat(this.value);
        if (val > max) this.value = max;
    });

    paymentMethodSelect?.addEventListener('change', syncPayoutAccountField);
    syncPayoutAccountField();

    @if ($errors->has('amount') || $errors->has('payment_method') || $errors->has('payment_number'))
        openModal();
    @endif

    const rows = Array.from(document.querySelectorAll('.tx-row'));
    if (!rows.length) return;

    const searchInput = document.getElementById('txSearch');
    const rowsPerPageSelect = document.getElementById('txRowsPerPage');
    const paginationEl = document.getElementById('txPagination');
    const showingStart = document.getElementById('txShowingStart');
    const showingEnd = document.getElementById('txShowingEnd');
    const totalEl = document.getElementById('txTotal');

    let currentPage = 1;
    let rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
    let filteredRows = rows.slice();

    function renderRows() {
        rows.forEach(r => { r.style.display = 'none'; });
        const start = (currentPage - 1) * rowsPerPage;
        filteredRows.slice(start, start + rowsPerPage).forEach((row, i) => {
            row.style.display = '';
            row.querySelector('.row-index').textContent = start + i + 1;
        });
    }

    function renderPagination() {
        paginationEl.innerHTML = '';
        const pageCount = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));

        const prev = document.createElement('li');
        prev.innerHTML = '<a href="#">«</a>';
        if (currentPage === 1) prev.className = 'page-link-disabled';
        prev.addEventListener('click', e => { e.preventDefault(); if (currentPage > 1) { currentPage--; update(); } });
        paginationEl.appendChild(prev);

        for (let i = 1; i <= pageCount; i++) {
            const li = document.createElement('li');
            li.className = i === currentPage ? 'page-link-active' : '';
            li.innerHTML = `<a href="#">${i}</a>`;
            li.addEventListener('click', e => { e.preventDefault(); currentPage = i; update(); });
            paginationEl.appendChild(li);
        }

        const next = document.createElement('li');
        next.innerHTML = '<a href="#">»</a>';
        if (currentPage === pageCount) next.className = 'page-link-disabled';
        next.addEventListener('click', e => { e.preventDefault(); if (currentPage < pageCount) { currentPage++; update(); } });
        paginationEl.appendChild(next);
    }

    function updateFooter() {
        const total = filteredRows.length;
        showingStart.textContent = total === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
        showingEnd.textContent = Math.min(currentPage * rowsPerPage, total);
        totalEl.textContent = total;
    }

    function update() {
        renderRows();
        renderPagination();
        updateFooter();
    }

    searchInput?.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        filteredRows = rows.filter(row => row.textContent.toLowerCase().includes(term));
        currentPage = 1;
        update();
    });

    rowsPerPageSelect?.addEventListener('change', function () {
        rowsPerPage = parseInt(this.value, 10);
        currentPage = 1;
        update();
    });

    update();
});
</script>
@endpush
