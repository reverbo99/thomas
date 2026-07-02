@extends('test.layouts.marketing')

@section('title', __('all.your_booking_details') . ' — ' . __('all.highlink_isgc'))

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => __('all.booking_information'),
        'title' => __('all.your_booking_details'),
        'subtitle' => __('all.view_manage_travel_bookings'),
        'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=1200&q=80',
    ])
@endsection

@section('content')
<section class="page-section page-section--alt">
    <div class="container mx-auto px-4 max-w-7xl">
        <div class="page-search-card fade-in mb-8">
            @if (session('success'))
                <div class="customer-alert customer-alert--success mb-4" role="status">{{ session('success') }}</div>
            @endif
            @include('test.partials.guest_booking_search_form', ['searchQuery' => $searchQuery ?? ''])
        </div>

        @if (!empty($searchQuery))
            <p class="text-sm text-gray-600 mb-4 fade-in">
                {{ __('all.search_button') }}: <strong>{{ $searchQuery }}</strong>
                · {{ $bookings->count() }} {{ $bookings->count() === 1 ? __('all.booking_singular') : __('all.bookings_plural') }}
            </p>
        @endif

        <div class="page-table-wrap fade-in overflow-x-auto">
            <table id="guestBookingsTable" class="page-table">
                <thead>
                    <tr>
                        <th>{{ __('customer/myticket.no') }}</th>
                        <th>{{ __('customer/myticket.booking_id') }}</th>
                        <th>{{ __('all.route_label') }}</th>
                        <th>{{ __('customer/busroot.price') }}</th>
                        <th>{{ __('customer/myticket.bus_name') }}</th>
                        <th>{{ __('customer/myticket.departure_date') }}</th>
                        <th>{{ __('customer/myticket.status') }}</th>
                        <th>{{ __('customer/myticket.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $book)
                        @php
                            $routeLabel = ($book->pickup_point ?? $book->route_name->from ?? '—')
                                . ' → '
                                . ($book->dropping_point ?? $book->route_name->to ?? '—');
                            $canCancel = is_cancel_allowed($book);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="font-semibold font-mono">{{ $book->booking_code }}</td>
                            <td>{{ $routeLabel }}</td>
                            <td>{{ $currency }} {{ convert_money($book->amount) }}</td>
                            <td>{{ $book->campany->name ?? '—' }}</td>
                            <td>
                                {{ $book->travel_date ? \Carbon\Carbon::parse($book->travel_date)->format('D, M d, Y') : __('all.not_available_short') }}
                            </td>
                            <td>
                                @if ($book->payment_status == 'Paid')
                                    <span class="page-badge page-badge--paid">{{ __('customer/myticket.Paid') }}</span>
                                @elseif($book->payment_status == 'Unpaid')
                                    <span class="page-badge page-badge--unpaid">{{ __('customer/myticket.Unpaid') }}</span>
                                @elseif ($book->payment_status == 'resaved')
                                    <span class="page-badge page-badge--resaved">{{ __('customer/busroot.resaved_ticket') }}</span>
                                @elseif ($book->payment_status == 'Cancel')
                                    <span class="page-badge page-badge--cancel">{{ __('all.cancel_button') }}</span>
                                @else
                                    <span class="page-badge page-badge--failed">{{ __('customer/myticket.Failed') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if ($book->payment_status == 'Paid')
                                        @if ($canCancel)
                                            <div x-data="{ openCancelModal: false }">
                                                <button @click="openCancelModal = true" type="button" class="page-btn text-xs py-2 px-3" style="background:#dc2626" title="{{ __('all.cancel_title') }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <div x-show="openCancelModal" x-cloak class="fixed inset-0 overflow-y-auto z-50" role="dialog" aria-modal="true">
                                                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                                        <div class="fixed inset-0 bg-gray-900/60" @click="openCancelModal = false"></div>
                                                        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                                            <div class="p-6">
                                                                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('all.cancel_button') }}</h3>
                                                                <p class="text-sm text-gray-600 mb-4">{{ __('all.cancel_booking_confirmation') }}</p>
                                                                <form action="{{ route('cancel') }}" method="post">
                                                                    @csrf
                                                                    <input type="hidden" name="booking_id" value="{{ $book->id }}">
                                                                    <input type="text" name="key" class="page-input mb-4" placeholder="{{ __('all.enter_your_key') }}" required>
                                                                    <button type="submit" class="page-btn w-full" style="background:#dc2626">{{ __('all.cancel_booking_action') }}</button>
                                                                </form>
                                                                <button type="button" class="page-btn page-btn--outline w-full mt-3" @click="openCancelModal = false">{{ __('all.close_button') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="page-btn text-xs py-2 px-3 opacity-60 cursor-not-allowed" style="background:#9ca3af" title="{{ __('all.cancel_not_allowed') }}">
                                                <i class="fas fa-clock"></i>
                                            </span>
                                        @endif

                                        <form action="{{ route('booking.edit', ['id' => $book->id]) }}" method="get">
                                            @csrf
                                            <input type="hidden" name="booking_id" value="{{ $book->id }}">
                                            <button type="submit" class="page-btn text-xs py-2 px-3" style="background:#16a34a" title="{{ __('all.edit_title') }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </form>

                                        <form action="{{ route('ticket.print') }}" method="post">
                                            @csrf
                                            <input type="hidden" name="data" value="{{ $book }}">
                                            <button type="submit" class="page-btn text-xs py-2 px-3" style="background:#ca8a04" title="{{ __('all.print_title') }}">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </form>
                                    @elseif ($book->payment_status == 'resaved')
                                        <a href="{{ route('login') }}" class="page-btn text-xs py-2 px-3 page-btn--outline">
                                            {{ __('auth.login') ?? 'Login' }} · {{ __('customer/busroot.proceed_to_pay') }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-gray-500">{{ __('customer/myticket.no_booking_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="text-center mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('info') }}" class="page-btn page-btn--outline">{{ __('all.back_button') }}</a>
            <a href="{{ route('home') }}#search" class="page-btn">{{ __('all.search_buses') ?? 'Book New Ticket' }}</a>
        </div>
    </div>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<style>
    [x-cloak] { display: none !important; }
    #guestBookingsTable_wrapper .dataTables_length,
    #guestBookingsTable_wrapper .dataTables_filter,
    #guestBookingsTable_wrapper .dataTables_info { margin-bottom: 1rem; color: #6b7280; font-size: 0.875rem; }
    #guestBookingsTable_wrapper .dataTables_length select,
    #guestBookingsTable_wrapper .dataTables_filter input {
        padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; background: #fff;
    }
    #guestBookingsTable_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--home-primary) !important; border-color: var(--home-primary); color: #fff !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
$(document).ready(function () {
    var $firstRow = $('#guestBookingsTable tbody tr:first');
    if ($firstRow.length && $firstRow.find('td').length === 8) {
        $('#guestBookingsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, @json(__('all.dt_all'))]],
            order: [[5, 'desc']],
            autoWidth: false,
            dom: '<"guest-dt-top"lf>rt<"guest-dt-bottom"ip>',
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
    }
});
</script>
@endpush
