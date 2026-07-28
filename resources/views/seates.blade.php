@extends('test.layouts.marketing')

@section('title', __('customer/busroot.select_your_seats') . ' — ' . __('all.higlink_premium_travel'))

@section('page_hero')
    @include('test.partials.page_hero', [
        'eyebrow' => 'Step 2 of 3',
        'title' => __('customer/busroot.select_your_seats'),
        'subtitle' => ($car->bus_model ?? $car->busname->name ?? 'Bus') . ' · ' . convert_money($price) . ' ' . $currency . ' ' . __('customer/busroot.per_seat'),
        'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=1200&q=80',
    ])
@endsection

@section('content')
<section class="page-section page-section--alt">
    <div class="container mx-auto px-4 max-w-6xl">
        @include('test.partials.booking_steps', ['currentStep' => 2])

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="booking-seat-panel fade-in">
                    <form id="seatSelectionForm" action="{{ route('get_seats') }}" method="POST">
                        @csrf
                        <input type="hidden" name="selected_seats" id="hiddenSelectedSeats" value="">
                        <input type="hidden" name="total_amount" id="hiddenTotalAmount" value="0">

                        <div id="seatMapGrid" class="seat-map-grid"></div>
                        <div id="seatMapFallback" class="seat-map" style="display:none;"></div>

                        <div class="booking-seat-footer">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">{{ __('customer/busroot.selected_seats') }}</p>
                                    <p class="text-xl font-bold text-gray-900" id="selectedSeatsList">{{ __('customer/busroot.none') }}</p>
                                </div>
                                <div class="text-center sm:text-right">
                                    <p class="text-sm font-medium text-gray-500">{{ __('customer/busroot.total_amount') }}</p>
                                    <p class="booking-seat-footer__total">
                                        <span id="totalAmount">0</span> {{ $currency }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-center gap-4 mt-4 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <span class="legend-swatch seat-available-legend"></span> {{ __('customer/busroot.available') }}
                                </div>
                                <div class="flex items-center">
                                    <span class="legend-swatch seat-selected-legend"></span> {{ __('customer/busroot.selected') }}
                                </div>
                                <div class="flex items-center">
                                    <span class="legend-swatch seat-booked-legend"></span> {{ __('customer/busroot.booked') }}
                                </div>
                            </div>

                            <button type="submit" id="confirmSeatsBtn" disabled class="page-btn w-full mt-6 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none">
                                <i class="fas fa-check-circle"></i> {{ __('customer/busroot.confirm_seats') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="booking-sidebar fade-in">
                <div class="booking-sidebar__card">
                    <div class="booking-sidebar__head">
                        <i class="fas fa-info-circle mr-2"></i> {{ __('customer/busroot.trip_details') }}
                    </div>
                    <div class="booking-sidebar__body">
                        <div class="booking-detail-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <p class="booking-detail-row__label">{{ __('customer/busroot.route') }}</p>
                                <p class="booking-detail-row__value">{{ $info['pickup_point'] ?? $car->schedule->from }} → {{ $info['dropping_point'] ?? $car->schedule->to }}</p>
                            </div>
                        </div>
                        <div class="booking-detail-row">
                            <i class="fas fa-calendar-day"></i>
                            <div>
                                <p class="booking-detail-row__label">{{ __('customer/busroot.date') }}</p>
                                <p class="booking-detail-row__value">{{ $info['travel_date'] }}</p>
                            </div>
                        </div>
                        <div class="booking-detail-row">
                            <i class="fas fa-clock"></i>
                            <div>
                                <p class="booking-detail-row__label">{{ __('customer/busroot.departure') }}</p>
                                <p class="booking-detail-row__value">{{ $info['start'] ?? $car->schedule->start }}</p>
                            </div>
                        </div>
                        <div class="booking-detail-row">
                            <i class="fas fa-flag-checkered"></i>
                            <div>
                                <p class="booking-detail-row__label">{{ __('customer/busroot.arrival') }}</p>
                                <p class="booking-detail-row__value">{{ $info['end'] ?? $car->schedule->end }}</p>
                            </div>
                        </div>
                        <div class="booking-detail-row">
                            <i class="fas fa-bus"></i>
                            <div>
                                <p class="booking-detail-row__label">{{ __('customer/busroot.bus') }}</p>
                                <p class="booking-detail-row__value">{{ $car->bus_model ?? $car->busname->name }} {{ $car->bus_number }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="booking-note">
                    <strong>{{ __('customer/busroot.important_notes') }}</strong>
                    <ul>
                        <li>{{ __('customer/busroot.seats_first_come') }}</li>
                        <li>{{ __('customer/busroot.max_seats_limit') }}</li>
                        <li>{{ __('customer/busroot.complete_payment') }}</li>
                    </ul>
                </div>

                <div class="booking-sidebar__card text-center">
                    <div class="booking-sidebar__body">
                        <div class="text-[var(--home-primary)] mb-3 text-3xl"><i class="fas fa-headset"></i></div>
                        <h4 class="font-bold text-gray-900">{{ __('customer/busroot.need_help') }}</h4>
                        <p class="text-gray-600 mt-1 text-sm">{{ __('customer/busroot.call_our_conductor') }}</p>
                        <p class="text-lg font-bold text-[var(--home-primary)] mt-2">{{ $car->conductor }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .seat-map-grid {
        --rows: 10;
        --cols: 4;
        --cell: 48px;
        width: 100%;
        display: grid;
        gap: 10px;
        padding: 1rem;
        background-color: #f8fafc;
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
        grid-template-columns: repeat(var(--cols), 1fr);
        grid-template-rows: repeat(var(--rows), 1fr);
        aspect-ratio: var(--cols) / var(--rows);
    }
    .grid-cell {
        position: relative;
        outline: 1px dashed rgba(148,163,184,0.25);
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cell-aisle {
        background: repeating-linear-gradient(45deg, rgba(100,116,139,0.12) 0, rgba(100,116,139,0.12) 6px, transparent 6px, transparent 12px);
    }
    .seat {
        width: 100%;
        height: 100%;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: clamp(10px, calc(var(--cell) * 0.33), 18px);
        border: 2px solid transparent;
        cursor: pointer;
        transition: all .2s ease;
        user-select: none;
    }
    .seat-available { background-color: #e2e8f0; color: #334155; }
    .seat-available:hover { background-color: #cbd5e1; transform: translateY(-2px); }
    .seat-selected { background-color: var(--home-primary); color: #fff; box-shadow: 0 4px 6px -1px rgba(46, 48, 147, 0.25); }
    .seat-booked { background-color: #94a3b8; color: #fff; cursor: not-allowed; }
    .legend-swatch { display: inline-block; width: 16px; height: 16px; border-radius: 0.25rem; margin-right: .5rem; }
    .seat-available-legend { background: #e2e8f0; }
    .seat-selected-legend { background: var(--home-primary); }
    .seat-booked-legend { background: #94a3b8; }
    .seat-map { display: flex; flex-direction: column; gap: 12px; padding: 1rem; background: #f8fafc; border-radius: .75rem; margin-bottom: 1.5rem; }
    .seat-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; justify-content: center; }
    .seat-row .seat, .seat-row .aisle {
        width: 100%;
        aspect-ratio: 1/1;
        height: auto;
        border-radius: .5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }
    @media (max-width: 768px) {
        .seat-map-grid { gap: 8px; }
    }
</style>
@endpush

@push('scripts')
<script>
    const seatPrice   = {{ $price }};
    const bookedSeats = @json($booked_seats ?? []);
    const maxSeats    = 5;
    const totalSeats  = {{ $car->total_seats }};
    let layoutRaw     = @json($car->seate_json ?? null);

    let layout = null;
    try {
        if (layoutRaw && typeof layoutRaw === 'string') layout = JSON.parse(layoutRaw);
        else layout = layoutRaw;
    } catch (e) { layout = null; }

    let selectedSeats = [];

    function renderFromLayoutJSON(){
        const grid = document.getElementById('seatMapGrid');

        if (!layout || typeof layout !== 'object' || !Number.isInteger(layout.rows) || !Number.isInteger(layout.cols)) {
            grid.style.display = 'none';
            document.getElementById('seatMapFallback').style.display = '';
            generateFallbackSeatMap();
            return;
        }

        let rows   = Math.max(1, layout.rows | 0);
        let cols   = Math.max(1, layout.cols | 0);
        const aisles = Array.isArray(layout.aisles) ? layout.aisles : [];
        const seats  = Array.isArray(layout.seats)  ? layout.seats  : [];

        // Expand grid to accommodate house seats placed outside declared bounds
        seats.forEach(s => {
            if (s.row > rows) rows = s.row;
            if (s.col > cols) cols = s.col;
        });

        grid.innerHTML = '';
        grid.style.display = 'grid';
        grid.style.setProperty('--rows', rows);
        grid.style.setProperty('--cols', cols);
        grid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        grid.style.gridTemplateRows    = `repeat(${rows}, 1fr)`;
        grid.style.aspectRatio         = `${cols} / ${rows}`;

        const setCellSizeVar = () => {
            const cell = grid.clientWidth / cols;
            grid.style.setProperty('--cell', `${cell}px`);
        };
        setCellSizeVar();

        const isAisle = (r,c) => aisles.some(a => a.row === r && a.col === c);
        const seatAt  = (r,c) => seats.find(s => s.row === r && s.col === c);

        for(let r=1; r<=rows; r++){
            for(let c=1; c<=cols; c++){
                const cell = document.createElement('div');
                cell.className = 'grid-cell' + (isAisle(r,c) ? ' cell-aisle':'');
                const s = seatAt(r,c);

                if (s){
                    const lbl = s.label ?? '';
                    const seatDiv = document.createElement('div');
                    seatDiv.className = 'seat seat-available';
                    seatDiv.textContent = lbl;
                    seatDiv.dataset.seat = lbl;

                    if (bookedSeats.includes(lbl)) {
                        seatDiv.classList.remove('seat-available');
                        seatDiv.classList.add('seat-booked');
                    } else {
                        seatDiv.onclick = () => toggleSeat(lbl, seatDiv);
                    }
                    cell.appendChild(seatDiv);
                }
                grid.appendChild(cell);
            }
        }

        if (!window.__seatGridRO) {
            window.__seatGridRO = new ResizeObserver(() => {
                if (!document.body.contains(grid)) return;
                grid.style.aspectRatio = `${cols} / ${rows}`;
                const cell = grid.clientWidth / cols;
                grid.style.setProperty('--cell', `${cell}px`);
            });
            window.__seatGridRO.observe(grid);
        }
    }

    function generateSeatLayout2p2(total){
        const rows = [];
        let made = 0, rowNo = 0;
        while(made < total){
            const L = String.fromCharCode(65 + rowNo);
            const remain = total - made;
            if (remain >= 4 || (remain > 1 && rowNo === 0)){
                const n = Math.min(4, remain);
                if (n === 4){ rows.push([`${L}4`,`${L}3`,'',`${L}2`,`${L}1`]); made += 4; }
                else {
                    if (n===2){ rows.push([`${L}2`,`${L}1`,'','','']); }
                    else if (n===3){ rows.push([`${L}2`,`${L}1`,'',`${L}3`,'' ]); }
                    made += n;
                }
            } else if (remain === 1){
                if (rows.length === 0) rows.push(['','',`${L}1`,'','']);
                else {
                    const last = rows[rows.length-1];
                    if (last[2] === '') last[2] = `${L}1`; else rows.push(['','',`${L}1`,'','']);
                }
                made += 1;
            }
            rowNo++;
        }
        return rows;
    }

    function generateFallbackSeatMap(){
        const seatMap = document.getElementById('seatMapFallback');
        seatMap.innerHTML = '';
        const seatLayout = generateSeatLayout2p2(totalSeats);

        seatLayout.forEach(row => {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'seat-row';

            row.forEach(seat => {
                if (seat === ''){
                    const aisle = document.createElement('div');
                    aisle.className = 'aisle';
                    rowDiv.appendChild(aisle);
                } else {
                    const seatDiv = document.createElement('div');
                    seatDiv.className = 'seat seat-available';
                    seatDiv.textContent = seat;
                    seatDiv.dataset.seat = seat;

                    if (bookedSeats.includes(seat)){
                        seatDiv.classList.remove('seat-available');
                        seatDiv.classList.add('seat-booked');
                    } else {
                        seatDiv.onclick = () => toggleSeat(seat, seatDiv);
                    }
                    rowDiv.appendChild(seatDiv);
                }
            });

            seatMap.appendChild(rowDiv);
        });
    }

    function toggleSeat(seat, seatDiv){
        if (seatDiv.classList.contains('seat-booked')) return;

        if (selectedSeats.includes(seat)){
            selectedSeats = selectedSeats.filter(s => s !== seat);
            seatDiv.classList.remove('seat-selected');
            seatDiv.classList.add('seat-available');
        } else {
            if (selectedSeats.length >= maxSeats){
                alert(@json(__('customer/busroot.max_seats_limit')));
                return;
            }
            selectedSeats.push(seat);
            seatDiv.classList.remove('seat-available');
            seatDiv.classList.add('seat-selected');
        }
        updateTotals();
    }

    function updateTotals(){
        const count = selectedSeats.length;
        const total = count * seatPrice;

        document.getElementById('selectedSeatsList').textContent =
            count > 0 ? selectedSeats.join(', ') : @json(__('customer/busroot.none'));

        document.getElementById('totalAmount').textContent =
            @if (session('currency') == 'Usd')
                (total / {{ $usdToTzs }}).toFixed(2)
            @else
                total
            @endif;

        document.getElementById('hiddenSelectedSeats').value = selectedSeats.join(',');
        document.getElementById('hiddenTotalAmount').value = total;

        const confirmBtn = document.getElementById('confirmSeatsBtn');
        if (confirmBtn) confirmBtn.disabled = count === 0;
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderFromLayoutJSON();
    });
</script>
@endpush
