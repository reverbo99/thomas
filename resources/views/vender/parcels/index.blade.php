@extends('vender.app')

@section('title', __('vender/parcels.add_new_parcel'))

@php
    $stats = $parcelStats ?? ['total' => 0, 'amount' => 0, 'today' => 0, 'assigned' => 0];
@endphp

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('all.parcels') }}</h1>
            <p class="vendor-dash__subtitle">{{ __('assistance/sidebar.vendor_panel') }} · {{ $stats['total'] }} {{ __('vender/busroot.entries') }}</p>
        </div>
        <div class="vendor-dash__actions">
            <a href="{{ route('vender.parcels.find_bus') }}" class="page-btn">
                <i class="fas fa-plus"></i> {{ __('vender/parcels.add_new_parcel') }}
            </a>
            <a href="{{ route('vender.index') }}" class="page-btn page-btn--outline">
                <i class="fas fa-gauge-high"></i> {{ __('assistance/sidebar.dashboard') }}
            </a>
        </div>
    </header>

    <div class="vendor-kpi-grid">
        <article class="vendor-kpi vendor-kpi--schedules">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-box"></i></div>
                <span class="vendor-kpi__badge">{{ __('all.parcels') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/parcels.total_parcels') }}</p>
            <p class="vendor-kpi__value">{{ number_format($stats['total']) }}</p>
            <p class="vendor-kpi__hint">{{ __('vender/parcels.all_time') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--commission">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-coins"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/parcels.revenue') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/parcels.amount_collected') }}</p>
            <p class="vendor-kpi__value">{{ convert_money($stats['amount']) }}</p>
            <p class="vendor-kpi__hint">{{ $currency }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--week">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-calendar-day"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/dashboard.today') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/parcels.added_today') }}</p>
            <p class="vendor-kpi__value">{{ number_format($stats['today']) }}</p>
            <p class="vendor-kpi__hint">{{ __('vender/parcels.new_parcels') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--pending">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-truck-loading"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/parcels.assigned') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/parcels.pending_on_bus') }}</p>
            <p class="vendor-kpi__value">{{ number_format($stats['assigned']) }}</p>
            <p class="vendor-kpi__hint">{{ __('vender/parcels.awaiting_dispatch') }}</p>
        </article>
    </div>

    @if(session('success'))
        <div class="booking-alert booking-alert--success mb-4 mx-0" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <section class="vendor-table-card">
        <div class="vendor-table-card__head">
            <div class="vendor-table-card__title-wrap">
                <h3>{{ __('vender/parcels.parcel_registry') }}</h3>
                <p>{{ __('vender/parcels.on_this_page_total', ['count' => $parcels->count(), 'total' => $parcels->total()]) }}</p>
            </div>
        </div>

        <div class="vendor-table-toolbar">
            <div class="vendor-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="parcelSearch" class="page-input text-sm" placeholder="{{ __('vender/parcels.search_placeholder') }}">
            </div>
        </div>

        @if($parcels->isEmpty())
            <div class="vendor-table-empty">
                <i class="fas fa-box-open"></i>
                <h4>{{ __('vender/parcels.no_parcels_found') }}</h4>
                <p>{{ __('vender/parcels.register_parcel_hint') }}</p>
                <a href="{{ route('vender.parcels.find_bus') }}" class="page-btn">{{ __('vender/parcels.add_new_parcel') }}</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="vendor-dash-table" id="parcelsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('vender/parcels.parcel_no') }}</th>
                            <th>{{ __('vender/parcels.type') }}</th>
                            <th>{{ __('vender/parcels.bus') }}</th>
                            <th>{{ __('vender/parcels.travel') }}</th>
                            <th>{{ __('vender/parcels.amount') }}</th>
                            <th>{{ __('vender/parcels.date_added') }}</th>
                            <th>{{ __('vender/parcels.status') }}</th>
                            <th>{{ __('vender/parcels.print_receipt') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parcels as $parcel)
                            <tr class="parcel-row">
                                <td class="row-index text-gray-400">{{ ($parcels->currentPage() - 1) * $parcels->perPage() + $loop->iteration }}</td>
                                <td><a href="{{ route('vender.parcels.show', $parcel->id) }}" class="font-semibold text-teal-700 hover:underline">{{ $parcel->parcel_number }}</a></td>
                                <td><span class="vendor-tx-method">{{ $parcel->parcel_type }}</span></td>
                                <td>
                                    <span class="vendor-schedule-busno">{{ $parcel->bus->bus_number ?? '—' }}</span>
                                    <span class="vendor-schedule-date__sub block">{{ $parcel->bus->campany->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($parcel->bus && $parcel->bus->schedule)
                                        <span class="vendor-schedule-date__day">{{ \Carbon\Carbon::parse($parcel->bus->schedule->schedule_date)->format('d M Y') }}</span>
                                        <span class="vendor-schedule-date__sub block">{{ \Carbon\Carbon::parse($parcel->bus->schedule->start)->format('h:i A') }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('vender/parcels.not_scheduled') }}</span>
                                    @endif
                                </td>
                                <td class="vendor-tx-amount">{{ $currency }} {{ convert_money($parcel->amount_paid) }}</td>
                                <td>
                                    <span class="vendor-schedule-date__day">{{ $parcel->created_at->format('d M Y') }}</span>
                                    <span class="vendor-schedule-date__sub block">{{ $parcel->created_at->format('h:i A') }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusKey = strtolower($parcel->status ?? 'pending');
                                        $statusClass = $statusKey === 'completed' ? 'vendor-status--paid' : ($statusKey === 'cancelled' ? 'vendor-status--other' : 'vendor-status--unpaid');
                                    @endphp
                                    <span class="vendor-status {{ $statusClass }}">{{ ucfirst($parcel->status ?? 'pending') }}</span>
                                </td>
                                <td>
                                    @if(app(\App\Services\ParcelFlowService::class)->canPrintReceipt($parcel))
                                        <a href="{{ route('vender.parcels.print', $parcel->id) }}" target="_blank" class="page-btn page-btn--outline">
                                            <i class="fas fa-print"></i> {{ __('vender/parcels.print_receipt') }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400" title="{{ __('vender/parcels.print_payment_required') }}">{{ __('vender/parcels.print_receipt') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($parcels->hasPages())
                <div class="vendor-table-footer">
                    <p class="vendor-table-footer__info">{{ __('vender/busroot.showing') }} {{ $parcels->firstItem() }} {{ __('vender/busroot.to') }} {{ $parcels->lastItem() }} {{ __('vender/busroot.of') }} {{ $parcels->total() }}</p>
                    <div>{{ $parcels->links() }}</div>
                </div>
            @endif
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('parcelSearch');
    const rows = Array.from(document.querySelectorAll('.parcel-row'));
    if (!input || !rows.length) return;
    input.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        rows.forEach(function (row) {
            row.style.display = !term || row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });
});
</script>
@endpush
