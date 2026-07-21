@extends('vender.app')

@section('title', __('assistance/schedule.bus_routes'))

@php
    $userCompanyId = auth()->user()->campany_id ?? auth()->user()->campany?->id ?? null;
    $schedules = $schedule ?? collect();
    $totalSchedules = $schedules->count();
    $uniqueCompanies = $schedules->map(fn ($s) => $s->bus?->campany?->name)->filter()->unique()->count();
    $uniqueBuses = $schedules->map(fn ($s) => $s->bus?->bus_number)->filter()->unique()->count();
    $thisWeekCount = $schedules->filter(function ($s) {
        if (!$s->schedule_date) return false;
        $date = \Carbon\Carbon::parse($s->schedule_date);
        return $date->isBetween(now()->startOfDay(), now()->addDays(7)->endOfDay());
    })->count();
@endphp

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('assistance/schedule.bus_routes_schedule') }}</h1>
            <p class="vendor-dash__subtitle">{{ __('assistance/schedule.search_routes') }} · {{ now()->format('l, M j, Y') }}</p>
        </div>
        <div class="vendor-dash__actions">
            <a href="{{ route('vender.index') }}" class="page-btn page-btn--outline">
                <i class="fas fa-gauge-high"></i> {{ __('assistance/sidebar.dashboard') }}
            </a>
            @if (auth()->user()->status == 'accept')
                <a href="{{ route('vender.route') }}" class="page-btn">
                    <i class="fas fa-ticket"></i> {{ __('assistance/sidebar.book_ticket') }}
                </a>
            @endif
        </div>
    </header>

    <div class="vendor-kpi-grid">
        <article class="vendor-kpi vendor-kpi--schedules">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-calendar-days"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/sidebar.bus_schedule') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/schedule.bus_routes') }}</p>
            <p class="vendor-kpi__value">{{ $totalSchedules }}</p>
            <p class="vendor-kpi__hint">{{ __('vender/busroot.entries') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--operators">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-building"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/busroot.company') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/busroot.company') }}</p>
            <p class="vendor-kpi__value">{{ $uniqueCompanies }}</p>
            <p class="vendor-kpi__hint">{{ __('assistance/schedule.bus_routes') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--week">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-clock"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/dashboard.this_week') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/dashboard.this_week') }}</p>
            <p class="vendor-kpi__value">{{ $thisWeekCount }}</p>
            <p class="vendor-kpi__hint">{{ __('assistance/schedule.bus_routes_schedule') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--buses">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-bus"></i></div>
                <span class="vendor-kpi__badge">{{ __('vender/busroot.bus_number') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('vender/busroot.bus_number') }}</p>
            <p class="vendor-kpi__value">{{ $uniqueBuses }}</p>
            <p class="vendor-kpi__hint">{{ __('all.search_buses') }}</p>
        </article>
    </div>

    <section class="vendor-table-card">
        <div class="vendor-table-card__head">
            <div class="vendor-table-card__title-wrap">
                <h3>{{ __('assistance/schedule.bus_routes_schedule') }}</h3>
                <p>{{ $totalSchedules }} {{ __('vender/busroot.entries') }}</p>
            </div>
        </div>

        <div class="vendor-table-toolbar">
            <div class="vendor-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="scheduleSearch" class="page-input text-sm" placeholder="{{ __('assistance/schedule.search_routes') }}">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <select id="scheduleRowsPerPage" class="page-input text-sm py-1 w-auto">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>
        </div>

        @if ($schedules->isEmpty())
            <div class="vendor-table-empty">
                <i class="fas fa-bus"></i>
                <h4>{{ __('assistance/schedule.no_bus_routes_found') }}</h4>
                <p>{{ __('assistance/schedule.search_routes') }}</p>
                <a href="{{ route('vender.index') }}" class="page-btn page-btn--outline">{{ __('assistance/sidebar.dashboard') }}</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="vendor-dash-table" id="scheduleTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-sort="number"># <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="company">{{ __('vender/busroot.company') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="bus">{{ __('vender/busroot.bus_number') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="main">{{ __('vender/busroot.main_route') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="leg">{{ __('vender/busroot.next_route') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="fee">{{ __('vender/busroot.bus_fee') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="time">{{ __('vender/busroot.time_24hrs') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="date">{{ __('vender/busroot.date') }} <i class="fas fa-sort"></i></th>
                            <th>{{ __('system.pages.seats') }}</th>
                            @if ($userCompanyId)
                                <th>{{ __('vender/schedule.edit_bus_schedule') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        @foreach ($schedules as $sched)
                            @php
                                $bus = $sched->bus ?? null;
                                $campany = $bus?->campany;
                                $route = $sched->route ?? $bus?->route;
                                $canEdit = $userCompanyId && $bus && $bus->campany_id == $userCompanyId;
                                $mainFrom = $route->from ?? 'N/A';
                                $mainTo = $route->to ?? 'N/A';
                                $legFrom = $sched->from ?? 'N/A';
                                $legTo = $sched->to ?? 'N/A';
                                $fee = $route && isset($route->price) ? $route->price : 0;
                                $scheduleDate = $sched->schedule_date ? \Carbon\Carbon::parse($sched->schedule_date) : null;
                            @endphp
                            <tr class="schedule-row"
                                data-company="{{ strtolower($campany?->name ?? '') }}"
                                data-bus="{{ strtolower($bus?->bus_number ?? '') }}"
                                data-main="{{ strtolower($mainFrom . ' ' . $mainTo) }}"
                                data-leg="{{ strtolower($legFrom . ' ' . $legTo) }}"
                                data-fee="{{ (float) $fee }}"
                                data-time="{{ ($sched->start ?? '') . ($sched->end ?? '') }}"
                                data-date="{{ $scheduleDate?->timestamp ?? 0 }}">
                                <td class="row-index text-gray-400">{{ $loop->iteration }}</td>
                                <td><span class="vendor-schedule-company">{{ $campany?->name ?? 'N/A' }}</span></td>
                                <td><span class="vendor-schedule-busno">{{ $bus?->bus_number ?? 'N/A' }}</span></td>
                                <td>
                                    <span class="vendor-route-chip">
                                        <i class="fas fa-route"></i>
                                        {{ $mainFrom }} → {{ $mainTo }}
                                    </span>
                                </td>
                                <td>
                                    <span class="vendor-route-chip vendor-route-chip--leg">
                                        <i class="fas fa-location-dot"></i>
                                        {{ $legFrom }} → {{ $legTo }}
                                    </span>
                                </td>
                                <td class="vendor-schedule-fee">{{ $currency }} {{ convert_money($fee) }}</td>
                                <td>
                                    <span class="vendor-time-badge">
                                        <i class="fas fa-clock"></i>
                                        {{ $sched->start ?? '—' }}
                                        <span class="vendor-time-badge__sep">→</span>
                                        {{ $sched->end ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($scheduleDate)
                                        <span class="vendor-schedule-date">
                                            <span class="vendor-schedule-date__day">{{ $scheduleDate->format('d M Y') }}</span>
                                            <span class="vendor-schedule-date__sub">{{ $scheduleDate->format('l') }}</span>
                                        </span>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $seatMap = $sched->booked_seat_map ?? [];
                                        $bookedCount = count($seatMap);
                                        $totalSeats = (int) ($bus->total_seats ?? 0);
                                        $availableCount = max(0, $totalSeats - $bookedCount);
                                    @endphp
                                    <button type="button"
                                        class="view-seats-btn page-btn page-btn--outline"
                                        style="padding:.35rem .7rem;font-size:.8rem;"
                                        aria-label="{{ __('system.pages.view_seats') }}"
                                        data-bus-number="{{ $bus?->bus_number ?? '' }}"
                                        data-company="{{ $campany?->name ?? '' }}"
                                        data-route="{{ $legFrom }} → {{ $legTo }}"
                                        data-date="{{ $scheduleDate?->format('d M Y') ?? '' }}"
                                        data-total-seats="{{ $totalSeats }}"
                                        data-booked-count="{{ $bookedCount }}"
                                        data-available-count="{{ $availableCount }}"
                                        data-layout="{{ e($bus?->seate_json ?? '') }}"
                                        data-booked-seats="{{ e(json_encode($seatMap)) }}">
                                        <i class="fas fa-chair"></i> {{ __('system.pages.view_seats') }}
                                    </button>
                                </td>
                                @if ($userCompanyId)
                                    <td>
                                        @if ($canEdit)
                                            <a href="{{ route('vender.edit.schedule', $sched->id) }}" class="vendor-schedule-edit">
                                                <i class="fas fa-pen-to-square"></i>
                                                {{ __('vender/schedule.update_schedule') }}
                                            </a>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="vendor-table-footer">
                <p class="vendor-table-footer__info">
                    {{ __('vender/busroot.showing') }} <span id="scheduleShowingStart">1</span>
                    {{ __('vender/busroot.to') }} <span id="scheduleShowingEnd">10</span>
                    {{ __('vender/busroot.of') }} <span id="scheduleTotal">{{ $totalSchedules }}</span>
                    {{ __('vender/busroot.entries') }}
                </p>
                <nav aria-label="Schedule pagination">
                    <ul class="vendor-pagination" id="schedulePagination"></ul>
                </nav>
            </div>
        @endif
    </section>
</div>

@include('partials.seat_arrangement_modal')
@endsection

@if ($schedules->isNotEmpty())
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = Array.from(document.querySelectorAll('.schedule-row'));
    const searchInput = document.getElementById('scheduleSearch');
    const rowsPerPageSelect = document.getElementById('scheduleRowsPerPage');
    const paginationEl = document.getElementById('schedulePagination');
    const showingStart = document.getElementById('scheduleShowingStart');
    const showingEnd = document.getElementById('scheduleShowingEnd');
    const totalEl = document.getElementById('scheduleTotal');

    let currentPage = 1;
    let rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
    let filteredRows = rows.slice();
    let sortColumn = 'date';
    let sortDirection = 'asc';

    function getSortValue(row, column) {
        switch (column) {
            case 'number': return parseInt(row.querySelector('.row-index')?.textContent || '0', 10);
            case 'company': return row.dataset.company;
            case 'bus': return row.dataset.bus;
            case 'main': return row.dataset.main;
            case 'leg': return row.dataset.leg;
            case 'fee': return parseFloat(row.dataset.fee || '0');
            case 'time': return row.dataset.time;
            case 'date': return parseInt(row.dataset.date || '0', 10);
            default: return '';
        }
    }

    function sortRows() {
        filteredRows.sort((a, b) => {
            const aVal = getSortValue(a, sortColumn);
            const bVal = getSortValue(b, sortColumn);
            if (typeof aVal === 'number' && typeof bVal === 'number') {
                return sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
            }
            const cmp = String(aVal).localeCompare(String(bVal));
            return sortDirection === 'asc' ? cmp : -cmp;
        });
    }

    function renderRows() {
        rows.forEach(r => { r.style.display = 'none'; });
        const start = (currentPage - 1) * rowsPerPage;
        filteredRows.slice(start, start + rowsPerPage).forEach(r => { r.style.display = ''; });
    }

    function renderPagination() {
        paginationEl.innerHTML = '';
        const pageCount = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));

        const prev = document.createElement('li');
        prev.innerHTML = '<a href="#" aria-label="Previous">«</a>';
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
        next.innerHTML = '<a href="#" aria-label="Next">»</a>';
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

    searchInput.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        filteredRows = rows.filter(row => row.textContent.toLowerCase().includes(term));
        currentPage = 1;
        sortRows();
        update();
    });

    rowsPerPageSelect.addEventListener('change', function () {
        rowsPerPage = parseInt(this.value, 10);
        currentPage = 1;
        update();
    });

    document.querySelectorAll('#scheduleTable .sortable').forEach(header => {
        header.addEventListener('click', function () {
            const col = this.dataset.sort;
            if (sortColumn === col) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = col;
                sortDirection = col === 'date' ? 'asc' : 'asc';
            }
            document.querySelectorAll('#scheduleTable .sortable').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
            this.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');
            sortRows();
            update();
        });
    });

    const dateHeader = document.querySelector('#scheduleTable .sortable[data-sort="date"]');
    if (dateHeader) dateHeader.classList.add('sort-asc');

    sortRows();
    update();
});
</script>
@endpush
@endif
