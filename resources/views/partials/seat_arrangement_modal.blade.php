{{--
    Shared seat-arrangement modal.

    Include this once on any page that renders one or more buttons with the
    class `.view-seats-btn`. Each button must carry these data attributes:

      data-bus-number      Bus plate/number (modal title)
      data-company         Company name (modal title, optional)
      data-route           Route label, e.g. "Dar → Mbeya" (subtitle)
      data-date            Travel date (subtitle)
      data-total-seats     Total seats on the bus (int)
      data-booked-count    Number of booked seats (int)
      data-available-count Number of available seats (int)
      data-layout          The bus `seate_json` string (may be empty)
      data-booked-seats    JSON object { "A1": "Passenger Name", ... }

    Clicking a button opens the modal and renders a read-only seat map with
    booked seats greyed out (passenger name on hover) vs available seats.
--}}
<div id="seatModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="sm-panel bg-white rounded-xl shadow-lg w-full max-w-2xl">
        <div class="sm-panel-head px-6 py-4 border-b border-gray-200 flex items-start justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800" id="seatModalTitle">{{ __('system.pages.seat_arrangement') }}</h3>
                <p class="text-sm text-gray-500 mt-1" id="seatModalSubtitle"></p>
            </div>
            <button type="button" id="seatModalClose" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="sm-panel-legend px-6 pt-4">
            <div class="flex flex-wrap gap-4 mb-4 text-sm">
                <span class="inline-flex items-center"><span class="sm-swatch sm-available"></span> {{ __('customer/busroot.available') }}: <b class="ml-1" id="seatModalAvailable">0</b></span>
                <span class="inline-flex items-center"><span class="sm-swatch sm-booked"></span> {{ __('customer/busroot.booked') }}: <b class="ml-1" id="seatModalBooked">0</b></span>
                <span class="inline-flex items-center text-gray-500">{{ __('system.pages.seats') }}: <b class="ml-1" id="seatModalTotal">0</b></span>
            </div>
        </div>
        <div class="sm-panel-body px-6 pb-4">
            <div id="seatModalGrid" class="sm-grid"></div>
            <p id="seatModalEmpty" class="hidden text-center text-gray-500 py-8">{{ __('system.pages.no_seat_layout') }}</p>
        </div>
    </div>
</div>

<style>
    /* Sizing/scroll live here (not Tailwind classes) because this page loads the
       precompiled Tailwind 2.2.19 CDN build, which has no arbitrary-value utilities
       (e.g. max-h-[90vh]) and no v3 `shrink-0`. Plain CSS works regardless. */
    #seatModal .sm-panel { display:flex; flex-direction:column; max-height:90vh; min-height:0; }
    #seatModal .sm-panel-head,
    #seatModal .sm-panel-legend { flex:0 0 auto; }
    #seatModal .sm-panel-body { flex:1 1 auto; min-height:0; overflow-y:auto; overscroll-behavior:contain; -webkit-overflow-scrolling:touch; }
    .sm-swatch { display:inline-block; width:14px; height:14px; border-radius:.25rem; margin-right:.4rem; }
    .sm-available { background:#e2e8f0; }
    .sm-booked { background:#94a3b8; }
    .sm-grid { display:grid; gap:8px; padding:1rem; background:#f8fafc; border-radius:.75rem; justify-content:center; }
    .sm-cell { position:relative; width:44px; height:44px; display:flex; align-items:center; justify-content:center; border-radius:.5rem; }
    .sm-cell.sm-aisle { background:repeating-linear-gradient(45deg, rgba(100,116,139,0.12) 0, rgba(100,116,139,0.12) 6px, transparent 6px, transparent 12px); }
    .sm-seat { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:12px; border-radius:.5rem; }
    .sm-seat.sm-seat-available { background:#e2e8f0; color:#334155; }
    .sm-seat.sm-seat-booked { background:#94a3b8; color:#fff; }
</style>

<script>
(function () {
    function initSeatArrangementModal() {
        var modal = document.getElementById('seatModal');
        if (!modal || modal.dataset.bound === '1') return;
        modal.dataset.bound = '1';

        var grid = document.getElementById('seatModalGrid');
        var emptyMsg = document.getElementById('seatModalEmpty');

        function closeModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.getElementById('seatModalClose').addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

        function buildSeat(label, isBooked, name) {
            var seat = document.createElement('div');
            seat.className = 'sm-seat ' + (isBooked ? 'sm-seat-booked' : 'sm-seat-available');
            seat.textContent = label;
            if (isBooked && name) { seat.title = label + ' — ' + name; }
            return seat;
        }

        // 2+2 fallback layout when no seate_json is stored (mirrors the customer seat page).
        function fallbackLayout(total) {
            var rows = [], made = 0, rowNo = 0;
            while (made < total) {
                var L = String.fromCharCode(65 + rowNo);
                var remain = total - made;
                if (remain >= 4 || (remain > 1 && rowNo === 0)) {
                    var n = Math.min(4, remain);
                    if (n === 4) { rows.push([L + '4', L + '3', '', L + '2', L + '1']); made += 4; }
                    else if (n === 2) { rows.push([L + '2', L + '1', '', '', '']); made += 2; }
                    else if (n === 3) { rows.push([L + '2', L + '1', '', L + '3', '']); made += 3; }
                } else if (remain === 1) {
                    rows.push(['', '', L + '1', '', '']); made += 1;
                }
                rowNo++;
            }
            return rows;
        }

        function renderSeats(layoutRaw, bookedMap, totalSeats) {
            grid.innerHTML = '';
            var layout = null;
            try { layout = (layoutRaw && typeof layoutRaw === 'string') ? JSON.parse(layoutRaw) : layoutRaw; } catch (e) { layout = null; }

            var isBooked = function (lbl) { return Object.prototype.hasOwnProperty.call(bookedMap, lbl); };

            if (layout && Number.isInteger(layout.rows) && Number.isInteger(layout.cols)) {
                var rows = Math.max(1, layout.rows | 0);
                var cols = Math.max(1, layout.cols | 0);
                var aisles = Array.isArray(layout.aisles) ? layout.aisles : [];
                var seats = Array.isArray(layout.seats) ? layout.seats : [];
                grid.style.gridTemplateColumns = 'repeat(' + cols + ', 44px)';
                var seatAt = function (r, c) { return seats.find(function (s) { return s.row === r && s.col === c; }); };
                var aisleAt = function (r, c) { return aisles.some(function (a) { return a.row === r && a.col === c; }); };
                for (var r = 1; r <= rows; r++) {
                    for (var c = 1; c <= cols; c++) {
                        var cell = document.createElement('div');
                        cell.className = 'sm-cell' + (aisleAt(r, c) ? ' sm-aisle' : '');
                        var s = seatAt(r, c);
                        if (s) {
                            var lbl = s.label != null ? s.label : '';
                            cell.appendChild(buildSeat(lbl, isBooked(lbl), bookedMap[lbl]));
                        }
                        grid.appendChild(cell);
                    }
                }
                emptyMsg.classList.add('hidden');
                grid.classList.remove('hidden');
                return;
            }

            if (totalSeats > 0) {
                var fbRows = fallbackLayout(totalSeats);
                grid.style.gridTemplateColumns = 'repeat(5, 44px)';
                fbRows.forEach(function (row) {
                    row.forEach(function (lbl) {
                        var cell = document.createElement('div');
                        cell.className = 'sm-cell' + (lbl === '' ? ' sm-aisle' : '');
                        if (lbl !== '') cell.appendChild(buildSeat(lbl, isBooked(lbl), bookedMap[lbl]));
                        grid.appendChild(cell);
                    });
                });
                emptyMsg.classList.add('hidden');
                grid.classList.remove('hidden');
                return;
            }

            grid.classList.add('hidden');
            emptyMsg.classList.remove('hidden');
        }

        document.querySelectorAll('.view-seats-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var bookedMap = {};
                try { bookedMap = JSON.parse(btn.dataset.bookedSeats || '{}') || {}; } catch (e) { bookedMap = {}; }
                document.getElementById('seatModalTitle').textContent =
                    (btn.dataset.company ? btn.dataset.company + ' — ' : '') + (btn.dataset.busNumber || '');
                document.getElementById('seatModalSubtitle').textContent =
                    (btn.dataset.route || '') + '  ·  ' + (btn.dataset.date || '');
                document.getElementById('seatModalTotal').textContent = btn.dataset.totalSeats || '0';
                document.getElementById('seatModalBooked').textContent = btn.dataset.bookedCount || '0';
                document.getElementById('seatModalAvailable').textContent = btn.dataset.availableCount || '0';
                renderSeats(btn.dataset.layout || '', bookedMap, parseInt(btn.dataset.totalSeats || '0', 10));
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSeatArrangementModal);
    } else {
        initSeatArrangementModal();
    }
})();
</script>
