@extends('vender.app')

@section('title', __('vender/history.booking_history'))

@php
    $period = $period ?? request('period', 'today');
    $startDate = $startDate ?? request('start_date', '');
    $endDate = $endDate ?? request('end_date', '');
    $filters = [
        'today' => __('assistance/dashboard.today'),
        'week' => __('assistance/dashboard.this_week'),
        'month' => __('assistance/dashboard.this_month'),
        'year' => __('assistance/dashboard.this_year'),
        'custom' => __('vender/history.custom'),
    ];
    $periodLabel = ($period === 'custom' && $startDate && $endDate)
        ? $startDate . ' – ' . $endDate
        : ($filters[$period] ?? $filters['today']);
    $bookingCount = $bookings->total();
    $exportQuery = request()->only(['period', 'start_date', 'end_date']);
@endphp

@push('styles')
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('vender/history.booking_history') }}</h1>
            <p class="vendor-dash__subtitle">{{ $periodLabel }} · {{ $bookingCount }} {{ __('vender/busroot.entries') }}</p>
        </div>
        <div class="vendor-dash__actions">
            <a href="{{ route('vender.route') }}" class="page-btn">
                <i class="fas fa-ticket"></i> {{ __('assistance/sidebar.book_ticket') }}
            </a>
            <a href="{{ route('vender.index') }}" class="page-btn page-btn--outline">
                <i class="fas fa-gauge-high"></i> {{ __('assistance/sidebar.dashboard') }}
            </a>
        </div>
    </header>

    <div class="vendor-kpi-grid">
        <article class="vendor-kpi vendor-kpi--schedules">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-ticket"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/history.booking_history') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/history.booking_id') }}</p>
            <p class="vendor-kpi__value">{{ number_format($bookingCount) }}</p>
            <p class="vendor-kpi__hint vendor-history-kpi-note">{{ __('vender/history.confirmed') }} · paid</p>
        </article>

        <article class="vendor-kpi vendor-kpi--commission">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-coins"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/history.total_payment') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/history.total_payment') }}</p>
            <p class="vendor-kpi__value" id="totalPayment">0</p>
            <p class="vendor-kpi__hint">{{ $currency }} · filtered rows</p>
        </article>

        <article class="vendor-kpi vendor-kpi--pending">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-tag"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/history.total_discount') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/history.total_discount') }}</p>
            <p class="vendor-kpi__value" id="totalDiscount">0</p>
            <p class="vendor-kpi__hint">{{ $currency }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--withdrawn">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-receipt"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/history.grand_total') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/history.grand_total') }}</p>
            <p class="vendor-kpi__value" id="grandTotal">0</p>
            <p class="vendor-kpi__hint">{{ __('vender/history.total_vat') }}: <span id="totalVAT">0</span></p>
        </article>
    </div>

    <section class="vendor-table-card">
        <div class="vendor-table-card__head">
            <div class="vendor-table-card__title-wrap">
                <h3>{{ __('vender/history.booking_history') }}</h3>
                <p>{{ $bookings->count() }} on this page · {{ $bookingCount }} total</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('vender.history.export.pdf', $exportQuery) }}" target="_blank"
                   class="page-btn page-btn--outline" style="font-size:0.8125rem">
                    <i class="fas fa-file-pdf"></i> {{ __('vender/history.export_pdf') }}
                </a>
                <a href="{{ route('vender.history.export.csv', $exportQuery) }}"
                   class="page-btn page-btn--outline" style="font-size:0.8125rem">
                    <i class="fas fa-file-excel"></i> {{ __('vender/history.export_excel') }}
                </a>
                @include('partials.booking_history_period_filter', [
                    'formAction' => route('vender.history'),
                    'resetUrl' => route('vender.history'),
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
                <input type="text" id="historySearch" class="page-input text-sm" placeholder="{{ __('assistance/transaction.search_all_columns') }}">
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="vendor-history-actions">
                    <button type="button" class="page-btn page-btn--outline" onclick="toggleHistoryMenu(this)">
                        <i class="fas fa-bars"></i> {{ __('vender/history.actions') }}
                    </button>
                    <div class="vendor-history-actions__menu hidden">
                        <form action="{{ route('vender.print.manifest') }}" method="POST" id="manifestForm">
                            @csrf
                            <input type="hidden" name="booking_ids" id="manifestBookingIds" value="">
                            <button type="submit">{{ __('vender/history.print_manifest') }}</button>
                        </form>
                        <form action="{{ route('vender.print') }}" method="POST" id="incomeForm">
                            @csrf
                            <input type="hidden" name="booking_ids" id="incomeBookingIds" value="">
                            <button type="submit">{{ __('vender/history.print_income') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if ($bookings->isEmpty())
            <div class="vendor-table-empty">
                <i class="fas fa-clipboard-list"></i>
                <h4>{{ __('vender/history.no_bookings_found') }}</h4>
                <p>{{ __('vender/history.booking_history') }}</p>
                <a href="{{ route('vender.route') }}" class="page-btn">{{ __('assistance/sidebar.book_ticket') }}</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="vendor-dash-table" id="busTable">
                    <thead>
                        <tr>
                            <th>{{ __('vender/history.sn') }}</th>
                            <th>{{ __('vender/history.booking_id') }}</th>
                            <th>{{ __('vender/history.bus_route') }}</th>
                            <th>{{ __('vender/history.travel_details') }}</th>
                            <th>{{ __('vender/history.passenger') }}</th>
                            <th>{{ __('vender/history.seats_payment') }}</th>
                            <th>{{ __('vender/history.commission') }}</th>
                            <th>{{ __('vender/history.total') }}</th>
                            <th>{{ __('vender/history.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $index => $booking)
                            <tr data-booking-id="{{ $booking->id }}" data-created-at="{{ $booking->created_at->format('Y-m-d') }}">
                                <td class="row-index text-gray-400">{{ ($bookings->currentPage() - 1) * $bookings->perPage() + $index + 1 }}</td>
                                <td>
                                    <span class="font-semibold text-sm">{{ $booking->booking_code ?? __('vender/history.na') }}</span>
                                    <span class="vendor-schedule-date__sub block">{{ __('vender/history.paid_time') }}: {{ $booking->created_at->format('d M Y H:i') }}</span>
                                </td>
                                <td>
                                    <span class="vendor-schedule-company">{{ $booking->campany->name ?? __('vender/history.na') }}</span>
                                    <span class="vendor-route-chip vendor-route-chip--leg mt-1">
                                        <i class="fas fa-location-dot"></i>
                                        {{ $booking->schedule->from ?? __('vender/history.na') }} → {{ $booking->schedule->to ?? __('vender/history.na') }}
                                    </span>
                                    <span class="vendor-schedule-date__sub block">{{ $booking->bus->bus_number ?? __('vender/history.na') }}</span>
                                </td>
                                <td>
                                    <span class="view-booking text-sm" data-id="{{ $booking->id }}">{{ $booking->travel_date ? \Carbon\Carbon::parse($booking->travel_date)->format('d M Y') : __('vender/history.na') }}</span>
                                    <span class="vendor-schedule-date__sub block">{{ __('vender/history.seat') }} {{ $booking->seat ?? __('vender/history.na') }}</span>
                                    <span class="vendor-schedule-date__sub block">{{ $booking->pickup_point ?? '—' }} → {{ $booking->dropping_point ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="font-medium text-sm">{{ $booking->customer_name ?? __('vender/history.na') }}</span>
                                    <span class="vendor-schedule-date__sub block">{{ $booking->customer_phone ?? __('vender/history.na') }}</span>
                                </td>
                                <td>
                                    <span class="vendor-tx-amount payment-amount"
                                        data-amount="{{ $booking->amount ?? 0 }}"
                                        data-vat="{{ $booking->vat ?? 0 }}"
                                        data-discount="{{ $booking->discount_amount ?? 0 }}"
                                        data-fee="{{ $booking->fee ?? 0 }}"
                                        data-vender_fee="{{ $booking->vender_fee ?? 0 }}"
                                        data-fee_vat="{{ $booking->fee_vat ?? 0 }}"
                                        data-service="{{ $booking->service ?? 0 }}"
                                        data-vender_service="{{ $booking->vender_service ?? 0 }}">
                                        {{ $currency }} {{ convert_money(($booking->amount ?? 0) + ($booking->vat ?? 0)) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $totalCommission = ($booking->fee ?? 0) + ($booking->vender_fee ?? 0);
                                        $rowTotal = round((float) ($booking->busFee ?: $booking->amount ?? 0));
                                    @endphp
                                    <span class="vendor-schedule-date__sub block">{{ __('vender/history.commission_total') }} {{ convert_money($totalCommission) }}</span>
                                    <span class="vendor-schedule-date__sub block">{{ __('vender/history.discount') }} {{ convert_money($booking->discount_amount ?? 0) }}</span>
                                </td>
                                <td>
                                    <span class="vendor-tx-amount total-amount" data-total="{{ $rowTotal }}">{{ $currency }} {{ convert_money($rowTotal) }}</span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <form action="{{ route('ticket.print') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="data" value='{{ json_encode(["id" => $booking->id]) }}'>
                                            <button type="submit" class="vendor-tx-print" title="{{ __('vender/history.download_ticket') }}">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </form>
                                        <div class="vendor-history-actions">
                                            <button type="button" class="vendor-tx-print" onclick="toggleHistoryMenu(this)" title="{{ __('vender/history.print') }}">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <div class="vendor-history-actions__menu hidden">
                                                <form action="{{ route('ticket.print') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="data" value='{{ json_encode(["id" => $booking->id]) }}'>
                                                    <button type="submit">{{ __('vender/history.print_ticket') }}</button>
                                                </form>
                                                <form action="{{ route('print.service') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="data" value='{{ json_encode(["id" => $booking->id]) }}'>
                                                    <button type="submit">{{ __('vender/history.print_service') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($bookings->hasPages())
                <div class="vendor-table-footer">
                    <p class="vendor-table-footer__info">{{ __('vender/busroot.showing') }} {{ $bookings->firstItem() }} {{ __('vender/busroot.to') }} {{ $bookings->lastItem() }} {{ __('vender/busroot.of') }} {{ $bookings->total() }}</p>
                    <div>{{ $bookings->links() }}</div>
                </div>
            @endif
        @endif
    </section>
</div>

<div id="viewBookingModal" class="vendor-modal" aria-hidden="true">
    <div class="vendor-modal__dialog vendor-modal__dialog--wide" role="dialog">
        <div class="vendor-modal__head">
            <h2 class="vendor-modal__title">{{ __('vender/history.booking_details') }}</h2>
            <button type="button" class="vendor-modal__close" id="closeViewBookingModal" aria-label="{{ __('vender/history.close') }}">&times;</button>
        </div>
        <div class="vendor-modal__body" id="modalContent"></div>
        <div class="vendor-modal__foot">
            <button type="button" class="page-btn page-btn--outline" id="closeViewBookingModalFooter">{{ __('vender/history.close') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        function toggleHistoryMenu(button) {
            const menu = button.nextElementSibling;
            if (!menu) return;
            menu.classList.toggle('hidden');
            const close = function (e) {
                if (!button.contains(e.target) && !menu.contains(e.target)) {
                    menu.classList.add('hidden');
                    document.removeEventListener('click', close);
                }
            };
            document.addEventListener('click', close);
        }

        $(document).ready(function () {
            const modal = document.getElementById('viewBookingModal');

            function openBookingModal() {
                modal?.classList.add('is-open');
                modal?.setAttribute('aria-hidden', 'false');
            }

            function closeBookingModal() {
                modal?.classList.remove('is-open');
                modal?.setAttribute('aria-hidden', 'true');
            }

            document.getElementById('closeViewBookingModal')?.addEventListener('click', closeBookingModal);
            document.getElementById('closeViewBookingModalFooter')?.addEventListener('click', closeBookingModal);
            modal?.addEventListener('click', function (e) {
                if (e.target === modal) closeBookingModal();
            });

            if (!$('#busTable').length) return;

            DataTable.ext.errMode = 'none';
            const table = $('#busTable').DataTable({
                responsive: true,
                paging: false,
                searching: true,
                ordering: true,
                dom: 't',
                language: { emptyTable: "{{ __('vender/history.no_bookings_found') }}" },
                footerCallback: function () {
                    let totalPayment = 0, totalDiscount = 0, totalVAT = 0, grandTotal = 0;
                    const isUsdDisplay = @json(session('currency') == 'Usd');
                    const usdRate = {{ app('usdToTzs') ?? 2500 }};
                    const formatKpi = function (amount) {
                        const display = isUsdDisplay && usdRate > 0 ? (amount / usdRate) : amount;
                        return display.toLocaleString('en-US', {
                            minimumFractionDigits: isUsdDisplay ? 2 : 0,
                            maximumFractionDigits: isUsdDisplay ? 2 : 0
                        });
                    };
                    const api = this.api();
                    api.rows({ search: 'applied' }).every(function () {
                        const rowNode = this.node();
                        const paymentEl = $(rowNode).find('.payment-amount');
                        const totalEl = $(rowNode).find('.total-amount');
                        totalPayment += (parseFloat(paymentEl.data('amount')) || 0) + (parseFloat(paymentEl.data('vat')) || 0);
                        totalDiscount += parseFloat(paymentEl.data('discount')) || 0;
                        totalVAT += parseFloat(paymentEl.data('vat')) || 0;
                        grandTotal += parseFloat(totalEl.data('total')) || 0;
                    });
                    $('#totalPayment').text(formatKpi(totalPayment));
                    $('#totalDiscount').text(formatKpi(totalDiscount));
                    $('#totalVAT').text(formatKpi(totalVAT));
                    $('#grandTotal').text(formatKpi(grandTotal));
                }
            });

            table.on('draw', function () { table.columns.adjust(); });

            $('#historySearch').on('input', function () {
                table.search(this.value).draw();
            });

            function getVisibleBookingIds() {
                const ids = [];
                table.rows({ search: 'applied' }).every(function () {
                    const id = $(this.node()).attr('data-booking-id');
                    if (id) ids.push(parseInt(id, 10));
                });
                return ids;
            }

            $('#manifestForm, #incomeForm').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const ids = getVisibleBookingIds();
                if (!ids.length) {
                    alert('{{ __('vender/history.no_bookings_found') }}');
                    return false;
                }
                form.find('input[name="booking_ids"]').val(JSON.stringify(ids));
                form.off('submit').submit();
            });

            $(document).on('click', '.view-booking', function () {
                const bookingId = $(this).data('id');
                $.get('{{ route('history.show', ':id') }}'.replace(':id', bookingId), function (response) {
                    $('#modalContent').html(response.html);
                    openBookingModal();
                });
            });

            table.draw();
        });
    </script>
@endpush
