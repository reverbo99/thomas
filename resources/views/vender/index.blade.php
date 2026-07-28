@extends('vender.app')

@section('title', __('assistance/sidebar.dashboard'))

@php
    $todayCount = $TodayBookings->count();
    $weekCount = $WeekBookings->count();
    $todayRevenue = $TodayBookings->sum('amount');
    $weekRevenue = $WeekBookings->sum('amount');
    $balance = optional(auth()->user()->VenderBalances)->amount ?? 0;
    $paidTotal = $bookings->where('payment_status', 'Paid')->count();
    $chartTotal = array_sum($monthlyData);
    $filters = [
        'today' => __('assistance/dashboard.today'),
        'week' => __('assistance/dashboard.this_week'),
        'month' => __('assistance/dashboard.this_month'),
        'year' => __('assistance/dashboard.this_year'),
    ];
@endphp

@section('content')
<div class="vendor-dash fade-in">
    <header class="vendor-dash__header">
        <div class="vendor-dash__welcome">
            <p class="vendor-dash__eyebrow">{{ __('all.highlink_isgc') }}</p>
            <h1 class="vendor-dash__title">{{ __('all.welcome') }} {{ auth()->user()->name }}</h1>
            <p class="vendor-dash__subtitle">{{ __('assistance/dashboard.dashboard_overview') }} · {{ now()->format('l, M j, Y') }}</p>
        </div>
        <div class="vendor-dash__actions">
            @if (auth()->user()->status == 'accept')
                <a href="{{ route('vender.route') }}" class="page-btn">
                    <i class="fas fa-ticket"></i> {{ __('assistance/sidebar.book_ticket') }}
                </a>
            @endif
            <a href="{{ route('vender.bus_route') }}" class="page-btn page-btn--outline">
                <i class="fas fa-bus"></i> {{ __('assistance/dashboard.view_bus_routes') }}
            </a>
        </div>
    </header>

    <div class="vendor-kpi-grid">
        <article class="vendor-kpi vendor-kpi--today">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-calendar-day"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/dashboard.today') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/dashboard.todays_bookings') }}</p>
            <p class="vendor-kpi__value">{{ $todayCount }}</p>
            <p class="vendor-kpi__hint">{{ __('assistance/dashboard.total_bookings_today') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--week">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-calendar-week"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/dashboard.this_week') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/dashboard.weekly_bookings') }}</p>
            <p class="vendor-kpi__value">{{ $weekCount }}</p>
            <p class="vendor-kpi__hint">{{ __('assistance/dashboard.total_bookings_week') }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--revenue">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-coins"></i></div>
                <span class="vendor-kpi__badge">{{ $currency }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/dashboard.todays_bookings') }} · {{ __('assistance/dashboard.amount') }}</p>
            <p class="vendor-kpi__value">{{ convert_money($todayRevenue) }}</p>
            <p class="vendor-kpi__hint">{{ __('assistance/dashboard.this_week') }}: {{ $currency }} {{ convert_money($weekRevenue) }}</p>
        </article>

        <article class="vendor-kpi vendor-kpi--balance">
            <div class="vendor-kpi__top">
                <div class="vendor-kpi__icon"><i class="fas fa-wallet"></i></div>
                <span class="vendor-kpi__badge">{{ __('assistance/sidebar.transactions') }}</span>
            </div>
            <p class="vendor-kpi__label">{{ __('assistance/dashboard.available_balance') }}</p>
            <p class="vendor-kpi__value">{{ convert_money($balance) }}</p>
            <p class="vendor-kpi__hint">{{ $paidTotal }} {{ strtolower(__('assistance/dashboard.bookings')) }} {{ strtolower(__('all.paid')) }}</p>
        </article>
    </div>

    {{-- ── Vendor Fee Summary Cards ──────────────────────────────────────── --}}
    <section class="mb-6">
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="vendor-side-card__title" style="margin:0">
                <i class="fas fa-receipt" style="margin-right:0.5rem"></i>{{ __('assistance/dashboard.fee_summary') }}
            </h2>
            <a href="{{ route('vender.history') }}" class="page-btn page-btn--outline" style="font-size:0.8125rem;padding:0.4rem 0.9rem">
                <i class="fas fa-clock-rotate-left" style="margin-right:0.35rem"></i>{{ __('assistance/dashboard.view_details') }}
            </a>
        </div>
        <div class="vendor-kpi-grid">
            {{-- Commission Fee --}}
            <article class="vendor-kpi" style="--kpi-accent:#2E3093">
                <div class="vendor-kpi__top">
                    <div class="vendor-kpi__icon"><i class="fas fa-percent"></i></div>
                    <span class="vendor-kpi__badge">{{ __('assistance/dashboard.commission') }}</span>
                </div>
                <p class="vendor-kpi__label">{{ __('assistance/dashboard.vendor_commission_fee') }}</p>
                <p class="vendor-kpi__value">{{ convert_money($totalVenderFee) }}</p>
                <p class="vendor-kpi__hint">{{ $currency }} · {{ __('assistance/dashboard.total_earned') }}</p>
            </article>

            {{-- Service Fee --}}
            <article class="vendor-kpi" style="--kpi-accent:#0891b2">
                <div class="vendor-kpi__top">
                    <div class="vendor-kpi__icon"><i class="fas fa-hand-holding-dollar"></i></div>
                    <span class="vendor-kpi__badge">{{ __('assistance/dashboard.service') }}</span>
                </div>
                <p class="vendor-kpi__label">{{ __('assistance/dashboard.vendor_service_fee') }}</p>
                <p class="vendor-kpi__value">{{ convert_money($totalVenderService) }}</p>
                <p class="vendor-kpi__hint">{{ $currency }} · {{ __('assistance/dashboard.total_earned') }}</p>
            </article>

            {{-- Parcel Fee --}}
            <article class="vendor-kpi" style="--kpi-accent:#d97706">
                <div class="vendor-kpi__top">
                    <div class="vendor-kpi__icon"><i class="fas fa-box"></i></div>
                    <span class="vendor-kpi__badge">{{ __('assistance/dashboard.parcel') }}</span>
                </div>
                <p class="vendor-kpi__label">{{ __('assistance/dashboard.vendor_parcel_fee') }}</p>
                <p class="vendor-kpi__value">{{ convert_money($totalParcelFee) }}</p>
                <p class="vendor-kpi__hint">{{ $currency }} · {{ __('assistance/dashboard.total_collected') }}</p>
            </article>

            {{-- Excess Luggage Fee --}}
            <article class="vendor-kpi" style="--kpi-accent:#dc2626">
                <div class="vendor-kpi__top">
                    <div class="vendor-kpi__icon"><i class="fas fa-suitcase-rolling"></i></div>
                    <span class="vendor-kpi__badge">{{ __('assistance/dashboard.luggage') }}</span>
                </div>
                <p class="vendor-kpi__label">{{ __('assistance/dashboard.vendor_excess_luggage_fee') }}</p>
                <p class="vendor-kpi__value">{{ convert_money($totalExcessLuggageFee) }}</p>
                <p class="vendor-kpi__hint">{{ $currency }} · {{ __('assistance/dashboard.total_collected') }}</p>
            </article>

            {{-- Cancellation Fee --}}
            <article class="vendor-kpi" style="--kpi-accent:#6b7280">
                <div class="vendor-kpi__top">
                    <div class="vendor-kpi__icon"><i class="fas fa-ban"></i></div>
                    <span class="vendor-kpi__badge">{{ __('assistance/dashboard.cancelled') }}</span>
                </div>
                <p class="vendor-kpi__label">{{ __('assistance/dashboard.vendor_cancellation_fee') }}</p>
                <p class="vendor-kpi__value">{{ convert_money($totalCancellationFee) }}</p>
                <p class="vendor-kpi__hint">{{ $currency }} · {{ __('assistance/dashboard.total_cancelled') }}</p>
            </article>
        </div>
    </section>

    <div class="vendor-dash__grid">
        <section class="vendor-chart-card">
            <div class="vendor-chart-card__head">
                <div>
                    <h2 class="vendor-chart-card__title">{{ __('assistance/dashboard.booking_analytics') }}</h2>
                    <p class="vendor-dash__subtitle" style="margin-top:0.25rem;font-size:0.8125rem">{{ $chartTotal }} {{ strtolower(__('assistance/dashboard.bookings')) }} · {{ $filters[$filter] ?? $filters['month'] }}</p>
                </div>
                <form method="GET" id="vendorChartFilterForm">
                    <div class="vendor-filter-pills" role="group" aria-label="{{ __('assistance/dashboard.booking_analytics') }}">
                        @foreach ($filters as $key => $label)
                            <button type="submit" name="filter" value="{{ $key }}"
                                    class="vendor-filter-pill {{ $filter === $key ? 'vendor-filter-pill--active' : '' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </form>
            </div>
            <div class="vendor-chart-card__body">
                <div class="vendor-chart-card__canvas">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
        </section>

        <aside class="vendor-side-card">
            <div class="vendor-side-card__head">
                <h2 class="vendor-side-card__title">{{ __('all.quick_actions') }}</h2>
            </div>
            <nav class="vendor-quick-list" aria-label="{{ __('all.quick_actions') }}">
                @if (auth()->user()->status == 'accept')
                    <a href="{{ route('vender.route') }}" class="vendor-quick-link">
                        <span class="vendor-quick-link__icon"><i class="fas fa-ticket"></i></span>
                        <span class="vendor-quick-link__text">
                            <strong>{{ __('assistance/sidebar.book_ticket') }}</strong>
                            <span>{{ __('all.search_buses') }}</span>
                        </span>
                        <i class="fas fa-chevron-right vendor-quick-link__arrow"></i>
                    </a>
                @endif
                <a href="{{ route('vender.transaction') }}" class="vendor-quick-link">
                    <span class="vendor-quick-link__icon"><i class="fas fa-money-bill-transfer"></i></span>
                    <span class="vendor-quick-link__text">
                        <strong>{{ __('assistance/sidebar.transactions') }}</strong>
                        <span>{{ __('assistance/dashboard.available_balance') }}</span>
                    </span>
                    <i class="fas fa-chevron-right vendor-quick-link__arrow"></i>
                </a>
                <a href="{{ route('vender.history') }}?period=today" class="vendor-quick-link">
                    <span class="vendor-quick-link__icon"><i class="fas fa-clock-rotate-left"></i></span>
                    <span class="vendor-quick-link__text">
                        <strong>{{ __('assistance/sidebar.booking_history') }}</strong>
                        <span>{{ __('assistance/sidebar.today') }}</span>
                    </span>
                    <i class="fas fa-chevron-right vendor-quick-link__arrow"></i>
                </a>
                <a href="{{ route('vender.parcels.index') }}" class="vendor-quick-link">
                    <span class="vendor-quick-link__icon"><i class="fas fa-box"></i></span>
                    <span class="vendor-quick-link__text">
                        <strong>{{ __('all.parcels') }}</strong>
                        <span>{{ __('assistance/sidebar.vendor_panel') }}</span>
                    </span>
                    <i class="fas fa-chevron-right vendor-quick-link__arrow"></i>
                </a>
            </nav>
        </aside>
    </div>

    <section class="vendor-table-card">
        <div class="vendor-table-card__head">
            <div class="vendor-table-card__title-wrap">
                <h3>{{ __('assistance/dashboard.recent_bookings') }}</h3>
                <p>{{ __('assistance/dashboard.showing') }} {{ $bookings->count() }} {{ __('assistance/dashboard.entries') }}</p>
            </div>
            <a href="{{ route('vender.history') }}" class="page-btn page-btn--outline" style="font-size:0.8125rem;padding:0.5rem 1rem">
                {{ __('assistance/dashboard.view_all_bookings') }} <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="vendor-table-toolbar">
            <div class="vendor-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" id="bookingSearch" class="page-input text-sm" placeholder="{{ __('assistance/dashboard.search_bookings') }}">
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <span class="hidden sm:inline">{{ __('assistance/dashboard.rows_10') }}</span>
                <select id="rowsPerPage" class="page-input text-sm py-1 w-auto">
                    <option value="5">{{ __('assistance/dashboard.rows_5') }}</option>
                    <option value="10" selected>{{ __('assistance/dashboard.rows_10') }}</option>
                    <option value="20">{{ __('assistance/dashboard.rows_20') }}</option>
                    <option value="50">{{ __('assistance/dashboard.rows_50') }}</option>
                </select>
            </label>
        </div>

        @if ($bookings->isEmpty())
            <div class="vendor-table-empty">
                <i class="fas fa-ticket"></i>
                <h4>{{ __('assistance/dashboard.recent_bookings') }}</h4>
                <p>{{ __('assistance/dashboard.search_bookings') }}</p>
                @if (auth()->user()->status == 'accept')
                    <a href="{{ route('vender.route') }}" class="page-btn">{{ __('assistance/sidebar.book_ticket') }}</a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="vendor-dash-table" id="bookingsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="sortable" data-sort="id">{{ __('assistance/dashboard.booking_id') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="customer">{{ __('assistance/dashboard.customer') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="bus">{{ __('assistance/dashboard.bus') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="date">{{ __('assistance/dashboard.date') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="seats">{{ __('assistance/dashboard.seats') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="amount">{{ __('assistance/dashboard.amount') }} <i class="fas fa-sort"></i></th>
                            <th class="sortable" data-sort="status">{{ __('assistance/dashboard.status') }} <i class="fas fa-sort"></i></th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTableBody">
                        @foreach ($bookings as $index => $booking)
                            @php
                                $statusKey = strtolower($booking->payment_status);
                                $statusClass = $statusKey === 'paid' ? 'vendor-status--paid' : ($statusKey === 'unpaid' ? 'vendor-status--unpaid' : 'vendor-status--other');
                            @endphp
                            <tr class="booking-row"
                                data-id="{{ $booking->id }}"
                                data-customer="{{ $booking->customer_name }}"
                                data-bus="{{ $booking->bus->busname->name ?? 'N/A' }}"
                                data-date="{{ $booking->created_at->timestamp }}"
                                data-seats="{{ $booking->seat }}"
                                data-amount="{{ $booking->amount }}"
                                data-status="{{ $statusKey }}">
                                <td class="row-index text-gray-400">{{ $index + 1 }}</td>
                                <td><span class="vendor-dash-table__code">{{ $booking->booking_code }}</span></td>
                                <td>{{ $booking->customer_name }}</td>
                                <td>{{ $booking->bus->busname->name ?? 'N/A' }}</td>
                                <td class="text-gray-600">{{ $booking->created_at->format('M d, Y · h:i A') }}</td>
                                <td>{{ $booking->seat }}</td>
                                <td class="vendor-dash-table__amount">{{ $currency }} {{ convert_money($booking->amount) }}</td>
                                <td><span class="vendor-status {{ $statusClass }}">{{ $booking->payment_status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="vendor-table-footer">
                <p class="vendor-table-footer__info">
                    {{ __('assistance/dashboard.showing') }} <span id="showingStart">1</span>
                    {{ __('assistance/dashboard.to') }} <span id="showingEnd">10</span>
                    {{ __('assistance/dashboard.of') }} <span id="totalEntries">{{ $bookings->count() }}</span>
                    {{ __('assistance/dashboard.entries') }}
                </p>
                <nav aria-label="Bookings pagination">
                    <ul class="vendor-pagination" id="bookingsPagination"></ul>
                </nav>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    @if ($bookings->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = Array.from(document.querySelectorAll('.booking-row'));
            const searchInput = document.getElementById('bookingSearch');
            const rowsPerPageSelect = document.getElementById('rowsPerPage');
            const paginationContainer = document.getElementById('bookingsPagination');
            const showingStart = document.getElementById('showingStart');
            const showingEnd = document.getElementById('showingEnd');
            const totalEntries = document.getElementById('totalEntries');

            let currentPage = 1;
            let rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
            let filteredRows = rows;
            let sortColumn = 'date';
            let sortDirection = 'desc';

            function sortTable() {
                filteredRows.sort((a, b) => {
                    let aValue, bValue;
                    switch (sortColumn) {
                        case 'id': aValue = parseInt(a.dataset.id, 10); bValue = parseInt(b.dataset.id, 10); break;
                        case 'customer': aValue = a.dataset.customer.toLowerCase(); bValue = b.dataset.customer.toLowerCase(); break;
                        case 'bus': aValue = a.dataset.bus.toLowerCase(); bValue = b.dataset.bus.toLowerCase(); break;
                        case 'date': aValue = parseInt(a.dataset.date, 10); bValue = parseInt(b.dataset.date, 10); break;
                        case 'seats': aValue = parseInt(a.dataset.seats, 10); bValue = parseInt(b.dataset.seats, 10); break;
                        case 'amount': aValue = parseFloat(a.dataset.amount); bValue = parseFloat(b.dataset.amount); break;
                        case 'status': aValue = a.dataset.status; bValue = b.dataset.status; break;
                        default: aValue = a.dataset[sortColumn]; bValue = b.dataset[sortColumn];
                    }
                    if (sortDirection === 'asc') return aValue > bValue ? 1 : -1;
                    return aValue < bValue ? 1 : -1;
                });
            }

            function renderTable() {
                rows.forEach(row => { row.style.display = 'none'; });
                const startIndex = (currentPage - 1) * rowsPerPage;
                filteredRows.slice(startIndex, startIndex + rowsPerPage).forEach(row => { row.style.display = ''; });
            }

            function renderPagination() {
                paginationContainer.innerHTML = '';
                const pageCount = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));

                const prevLi = document.createElement('li');
                prevLi.innerHTML = `<a href="#" aria-label="Previous">«</a>`;
                if (currentPage === 1) prevLi.className = 'page-link-disabled';
                prevLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (currentPage > 1) { currentPage--; updateTable(); }
                });
                paginationContainer.appendChild(prevLi);

                for (let i = 1; i <= pageCount; i++) {
                    const pageLi = document.createElement('li');
                    pageLi.className = i === currentPage ? 'page-link-active' : '';
                    pageLi.innerHTML = `<a href="#">${i}</a>`;
                    pageLi.addEventListener('click', (e) => {
                        e.preventDefault();
                        currentPage = i;
                        updateTable();
                    });
                    paginationContainer.appendChild(pageLi);
                }

                const nextLi = document.createElement('li');
                nextLi.innerHTML = `<a href="#" aria-label="Next">»</a>`;
                if (currentPage === pageCount) nextLi.className = 'page-link-disabled';
                nextLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (currentPage < pageCount) { currentPage++; updateTable(); }
                });
                paginationContainer.appendChild(nextLi);
            }

            function updateShowingEntries() {
                const total = filteredRows.length;
                showingStart.textContent = total === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
                showingEnd.textContent = Math.min(currentPage * rowsPerPage, total);
                totalEntries.textContent = total;
            }

            function updateTable() {
                renderTable();
                renderPagination();
                updateShowingEntries();
            }

            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                filteredRows = rows.filter(row => {
                    const cells = row.querySelectorAll('td:not(.row-index)');
                    let rowMatches = false;
                    cells.forEach(cell => {
                        const cellText = cell.textContent.toLowerCase();
                        cell.innerHTML = cell.textContent;
                        if (cellText.includes(searchTerm)) {
                            rowMatches = true;
                            if (searchTerm) {
                                const regex = new RegExp(searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                                cell.innerHTML = cell.textContent.replace(regex, match => `<span class="highlight">${match}</span>`);
                            }
                        }
                    });
                    return rowMatches;
                });
                currentPage = 1;
                sortTable();
                updateTable();
            });

            rowsPerPageSelect.addEventListener('change', function() {
                rowsPerPage = parseInt(this.value, 10);
                currentPage = 1;
                updateTable();
            });

            document.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', function() {
                    const column = this.dataset.sort;
                    if (sortColumn === column) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortColumn = column;
                        sortDirection = 'asc';
                    }
                    document.querySelectorAll('.sortable').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                    this.classList.add(`sort-${sortDirection}`);
                    sortTable();
                    updateTable();
                });
            });

            sortTable();
            updateTable();
        });
    </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('incomeChart');
            if (!ctx || typeof Chart === 'undefined') return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($monthlyLabels),
                    datasets: [{
                        label: @json(__('assistance/dashboard.bookings')),
                        data: @json($monthlyData),
                        backgroundColor: 'rgba(46, 48, 147, 0.08)',
                        borderColor: 'rgba(46, 48, 147, 1)',
                        pointBackgroundColor: '#fff',
                        pointBorderColor: 'rgba(46, 48, 147, 1)',
                        pointBorderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1a1f4b',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: (context) => `${@json(__('assistance/dashboard.bookings'))}: ${context.parsed.y}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 11 } },
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            border: { display: false }
                        },
                        x: {
                            ticks: { font: { size: 11 }, maxRotation: 0 },
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
        });
    </script>
@endpush
