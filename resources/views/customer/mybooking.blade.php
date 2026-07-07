@extends('customer.app')

@section('title', __('customer_sidebar.My Tickets'))

@php
    $period = $period ?? request('period', '');
    $startDate = $startDate ?? request('start_date', '');
    $endDate = $endDate ?? request('end_date', '');
@endphp

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => __('all.highlink_isgc'),
        'title' => __('customer/myticket.my_ticket'),
        'subtitle' => __('customer_sidebar.My Tickets'),
    ])
@endsection

@section('content')
<section class="page-section page-section--alt">
    <div class="container mx-auto px-4">
        <div class="customer-panel fade-in">
            <div class="customer-panel__header flex flex-col gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h3 class="text-base sm:text-lg">{{ __('customer/myticket.my_ticket') }}</h3>
                    <span class="text-sm opacity-90">{{ count($ticketRows ?? []) }} {{ __('customer/myticket.my_ticket') }}</span>
                </div>
                @if (!empty($ticketRows))
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                        @include('partials.booking_history_period_filter', [
                            'formAction' => route('customer.mybooking'),
                            'resetUrl' => route('customer.mybooking'),
                            'period' => $period,
                            'startDate' => $startDate,
                            'endDate' => $endDate,
                        ])
                        <form action="{{ route('customer.print.report') }}" method="POST" id="customerTicketReportForm" class="flex-shrink-0">
                            @csrf
                            <input type="hidden" name="booking_ids" id="customerTicketReportIds" value="">
                            <button type="submit" class="page-btn page-btn--outline text-sm w-full sm:w-auto">
                                <i class="fas fa-file-pdf"></i> {{ __('customer/myticket.print_report') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <div class="customer-panel__body">
                @if (session('success'))
                    <div class="customer-alert customer-alert--success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="customer-alert customer-alert--error" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @if (empty($ticketRows))
                    <div class="tickets-empty">
                        <div class="tickets-empty__icon"><i class="fas fa-ticket"></i></div>
                        <h4 class="tickets-empty__title">{{ __('customer/myticket.no_booking_found') }}</h4>
                        <p class="tickets-empty__text">{{ __('all.here_are_some_things_you_can_do') }}</p>
                        <a href="{{ route('customer.mybooking.search') }}" class="page-btn">
                            <i class="fas fa-bus"></i> {{ __('all.search_buses') }}
                        </a>
                    </div>
                @else
                    <div class="tickets-table-wrap page-table-wrap">
                        <table id="ticketsTable" class="page-table tickets-table">
                            <thead>
                                <tr>
                                    <th class="tickets-table__col-no">#</th>
                                    <th>{{ __('customer/myticket.booking_id') }}</th>
                                    <th>{{ __('customer/busroot.price') }}</th>
                                    <th>{{ __('customer/myticket.bus_name') }}</th>
                                    <th>{{ __('customer/myticket.departure_date') }}</th>
                                    <th class="tickets-table__col-date">{{ __('customer/busroot.created_at') }}</th>
                                    <th>{{ __('customer/myticket.status') }}</th>
                                    <th class="tickets-table__col-actions">{{ __('customer/myticket.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ticketRows as $row)
                                    @include('customer.partials.mybooking_table_row', [
                                        'row' => $row,
                                        'rowNumber' => $loop->iteration,
                                    ])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@foreach ($booking as $book)
    @if (in_array($book->payment_status, ['Paid', 'Refund Rejected']))
        @include('customer.partials.refund_modal', ['book' => $book])
    @endif
@endforeach
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.refund-trigger').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.getAttribute('data-refund-modal');
                    var modal = id ? document.getElementById(id) : null;
                    if (modal) modal.classList.remove('hidden');
                });
            });

            function closeRefundModal(id) {
                var modal = id ? document.getElementById(id) : null;
                if (modal) modal.classList.add('hidden');
            }

            document.querySelectorAll('[data-close-refund-modal]').forEach(function(el) {
                el.addEventListener('click', function() {
                    closeRefundModal(this.getAttribute('data-close-refund-modal'));
                });
            });

            document.querySelectorAll('.refund-form').forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    var mobile = (form.querySelector('input[name="mobile_number"]') || {}).value || '';
                    var bank = (form.querySelector('input[name="bank_number"]') || {}).value || '';
                    var errEl = form.querySelector('[id^="refundFormError"]');
                    if (!mobile.trim() && !bank.trim()) {
                        event.preventDefault();
                        if (errEl) {
                            errEl.textContent = @json(__('all.please_enter_mobile_or_bank'));
                            errEl.classList.remove('hidden');
                        }
                        return false;
                    }
                    if (errEl) {
                        errEl.classList.add('hidden');
                        errEl.textContent = '';
                    }
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            var refundBookingId = @json(old('booking_id'));
            if (refundBookingId) {
                var modalEl = document.getElementById('refundModal' + refundBookingId);
                if (modalEl) modalEl.classList.remove('hidden');
            }
        });

        $(document).ready(function() {
            if (!$('#ticketsTable').length) return;

            $('#ticketsTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, @json(__('all.dt_all'))]],
                order: [[5, 'desc']],
                autoWidth: false,
                dom: '<"tickets-dt-top"lf>rt<"tickets-dt-bottom"ip>',
                language: {
                    search: '',
                    searchPlaceholder: @json(__('all.dt_search_placeholder')),
                    lengthMenu: @json(__('all.dt_per_page')),
                    info: @json(__('all.dt_showing')) + ' _START_ ' + @json(__('all.dt_to')) + ' _END_ ' + @json(__('all.dt_of')) + ' _TOTAL_',
                    infoEmpty: @json(__('customer/myticket.no_booking_found')),
                    paginate: {
                        first: @json(__('all.dt_first')),
                        last: @json(__('all.dt_last')),
                        next: @json(__('all.next')),
                        previous: @json(__('all.dt_previous'))
                    }
                },
                columnDefs: [
                    { orderable: false, targets: [7] },
                    { searchable: false, targets: [0, 7] }
                ]
            });

            function getVisibleBookingIds() {
                const ids = [];
                const table = $('#ticketsTable').DataTable();
                table.rows({ search: 'applied' }).every(function () {
                    const raw = $(this.node()).attr('data-booking-ids');
                    if (!raw) return;
                    try {
                        const rowIds = JSON.parse(raw);
                        if (Array.isArray(rowIds)) {
                            rowIds.forEach(function (id) {
                                const parsed = parseInt(id, 10);
                                if (parsed && ids.indexOf(parsed) === -1) {
                                    ids.push(parsed);
                                }
                            });
                        }
                    } catch (e) {}
                });
                return ids;
            }

            $('#customerTicketReportForm').on('submit', function (e) {
                const ids = getVisibleBookingIds();
                if (!ids.length) {
                    e.preventDefault();
                    alert(@json(__('customer/myticket.no_booking_found')));
                    return false;
                }
                $('#customerTicketReportIds').val(JSON.stringify(ids));
            });
        });
    </script>
@endpush
