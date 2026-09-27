@extends('system.app')

@section('title', __('system.sidebar.cities'))

@section('content')
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('hl-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var theme = stored || (prefersDark ? 'dark' : 'light');
                if (theme === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            } catch (e) {}
        })();
    </script>

    <style>
        .cty {
            --cty-surface: #ffffff;
            --cty-surface-2: #f3f4f6;
            --cty-border: #e5e7eb;
            --cty-text: #374151;
            --cty-muted: #6b7280;
            --cty-hover: #f9fafb;
        }
        html.dark .cty {
            --cty-surface: #1f2937;
            --cty-surface-2: #111827;
            --cty-border: #374151;
            --cty-text: #e5e7eb;
            --cty-muted: #9ca3af;
            --cty-hover: #374151;
        }
        .cty-card { background: var(--cty-surface); border: 1px solid var(--cty-border); }
        .cty-thead { background: var(--cty-surface-2); color: var(--cty-muted); }
        .cty-row { border-color: var(--cty-border); color: var(--cty-text); }
        .cty-row:hover { background: var(--cty-hover); }
        .cty-modal { background: var(--cty-surface); border: 1px solid var(--cty-border); }
        .cty-label { color: var(--cty-text); }
        .cty-input {
            background: var(--cty-surface);
            border-color: var(--cty-border);
            color: var(--cty-text);
        }
        html.dark .cty-alert-success { background: #064e3b; color: #a7f3d0; }
        html.dark .cty-alert-error { background: #7f1d1d; color: #fecaca; }
    </style>

    <div class="cty container mx-auto px-4 py-6 max-w-4xl">
        @if (session('success'))
            <div class="cty-alert-success mb-4 p-3 bg-green-100 text-green-800 rounded-lg flex justify-between items-center shadow-sm text-sm">
                {{ session('success') }}
                <button type="button" class="opacity-80 hover:opacity-100" onclick="this.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif
        @if (session('error'))
            <div class="cty-alert-error mb-4 p-3 bg-red-100 text-red-800 rounded-lg flex justify-between items-center shadow-sm text-sm">
                {{ session('error') }}
                <button type="button" class="opacity-80 hover:opacity-100" onclick="this.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif
        @if ($errors->has('ids') || $errors->has('ids.*'))
            <div class="cty-alert-error mb-4 p-3 bg-red-100 text-red-800 rounded-lg text-sm">
                {{ $errors->first('ids') ?: $errors->first('ids.*') }}
            </div>
        @endif

        <div class="cty-card rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-gradient-to-r from-blue-500 to-blue-400 text-white flex flex-wrap gap-2 justify-between items-center">
                <h2 class="text-lg font-semibold">{{ __('system.sidebar.cities') }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" form="cityDeleteForm" id="cityDeleteBtn"
                        class="hidden items-center gap-1 px-3 py-1 rounded-lg bg-red-600 text-white hover:bg-red-700 transition text-sm disabled:opacity-50"
                        disabled>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>{{ __('system.pages.cities_delete_selected') }}</span>
                        <span id="citySelectedCount" class="text-xs opacity-90"></span>
                    </button>
                    <button type="button" class="bg-white text-blue-500 px-3 py-1 rounded-lg hover:bg-blue-50 transition flex items-center gap-1 text-sm" onclick="document.getElementById('addCityModal').classList.remove('hidden')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('system.pages.add') }}
                    </button>
                </div>
            </div>

            <form id="cityDeleteForm" action="{{ route('system.city.destroy') }}" method="POST"
                onsubmit="return confirm(@json(__('system.messages.city_delete_confirm')));">
                @csrf
                @method('DELETE')
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table id="citiesTable" class="w-full table-auto">
                            <thead>
                                <tr class="cty-thead uppercase text-xs leading-normal">
                                    <th class="py-2 px-3 text-left w-10">
                                        <input type="checkbox" id="citySelectAll" class="rounded border-gray-400 text-blue-600 focus:ring-blue-500"
                                            aria-label="{{ __('system.pages.cities_select_all') }}"
                                            @if($cities->isEmpty()) disabled @endif>
                                    </th>
                                    <th class="py-2 px-3 text-left font-medium w-12">#</th>
                                    <th class="py-2 px-3 text-left font-medium">{{ __('system.common.name') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs">
                                @forelse ($cities as $city)
                                    <tr class="cty-row border-b transition">
                                        <td class="py-2 px-3">
                                            <input type="checkbox" name="ids[]" value="{{ $city->id }}" class="city-row-check rounded border-gray-400 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="py-2 px-3">{{ $loop->iteration }}</td>
                                        <td class="py-2 px-3">{{ $city->name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-4 px-4 text-center" style="color: var(--cty-muted);">{{ __('system.pages.no_cities') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        <div id="addCityModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="cty-modal rounded-lg shadow-lg w-full max-w-md mx-4 transform transition-all">
                <div class="p-4 flex justify-between items-center border-b" style="border-color: var(--cty-border);">
                    <h2 class="text-lg font-semibold cty-label">{{ __('system.pages.add_new_city') }}</h2>
                    <button type="button" class="cty-muted opacity-70 hover:opacity-100" style="color: var(--cty-muted);" onclick="document.getElementById('addCityModal').classList.add('hidden')">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('system.city.store') }}" method="POST">
                    @csrf
                    <div class="p-4 space-y-3">
                        <div>
                            <label for="cityName" class="block text-xs font-medium cty-label mb-1">{{ __('system.pages.city_name') }}</label>
                            <input type="text" class="cty-input w-full px-3 py-2 border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" id="cityName" name="name" required>
                            @error('name')
                                <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="p-4 flex justify-end gap-2 border-t" style="border-color: var(--cty-border);">
                        <button type="button" class="px-3 py-1 rounded-lg transition text-sm cty-row" onclick="document.getElementById('addCityModal').classList.add('hidden')">{{ __('system.common.close') }}</button>
                        <button type="submit" class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition text-sm">{{ __('system.common.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var selectAll = document.getElementById('citySelectAll');
            var deleteBtn = document.getElementById('cityDeleteBtn');
            var countEl = document.getElementById('citySelectedCount');
            var rowChecks = function () {
                return Array.prototype.slice.call(document.querySelectorAll('.city-row-check'));
            };
            var selectedLabel = @json(__('system.pages.cities_selected_count', ['count' => ':count']));

            function syncSelectionUi() {
                var checks = rowChecks();
                var selected = checks.filter(function (c) { return c.checked; });
                var n = selected.length;
                if (deleteBtn) {
                    if (n > 0) {
                        deleteBtn.classList.remove('hidden');
                        deleteBtn.classList.add('flex');
                        deleteBtn.disabled = false;
                    } else {
                        deleteBtn.classList.add('hidden');
                        deleteBtn.classList.remove('flex');
                        deleteBtn.disabled = true;
                    }
                }
                if (countEl) {
                    countEl.textContent = n > 0 ? '(' + selectedLabel.replace(':count', String(n)) + ')' : '';
                }
                if (selectAll && checks.length) {
                    selectAll.checked = n === checks.length;
                    selectAll.indeterminate = n > 0 && n < checks.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    rowChecks().forEach(function (c) { c.checked = selectAll.checked; });
                    syncSelectionUi();
                });
            }
            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('city-row-check')) {
                    syncSelectionUi();
                }
            });
            syncSelectionUi();
        })();
    </script>
@endsection
