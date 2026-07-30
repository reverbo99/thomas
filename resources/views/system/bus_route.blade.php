@extends('system.app')

@section('title', __('system.pages.bus_routes_schedule'))

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800">{{ __('system.pages.bus_routes_schedule') }}</h2>
            <p class="text-sm text-slate-500 mt-1">{{ __('system.pages.schedule_subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('system.pages.schedule_total') }}</p>
            <p class="text-2xl font-semibold text-slate-800 mt-2">{{ number_format($schedules->total()) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('system.pages.schedule_buses_involved') }}</p>
            <p class="text-2xl font-semibold text-slate-800 mt-2">{{ number_format($busCount) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">{{ __('system.pages.schedule_on_date', ['date' => \Carbon\Carbon::parse($todayDate)->format('d M Y')]) }}</p>
            <p class="text-2xl font-semibold text-slate-800 mt-2">{{ number_format($todayCount) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('system.bus_route') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <label for="search" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.common.search') }}</label>
                <input id="search" type="text" name="search" value="{{ $search }}"
                       placeholder="{{ __('system.pages.schedule_search_placeholder') }}"
                       class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label for="campany_id" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.common.company') }}</label>
                <select id="campany_id" name="campany_id" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">{{ __('system.pages.schedule_all_companies') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" {{ (int) $companyId === (int) $company->id ? 'selected' : '' }}>
                            {{ $company->name }}{{ (int) $company->status === 1 ? '' : ' — ' . __('system.common.disabled') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="scope" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.pages.schedule_scope') }}</label>
                <select id="scope" name="scope" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="upcoming" {{ $scope === 'upcoming' ? 'selected' : '' }}>{{ __('system.pages.schedule_scope_upcoming') }}</option>
                    <option value="past" {{ $scope === 'past' ? 'selected' : '' }}>{{ __('system.pages.schedule_scope_past') }}</option>
                    <option value="all" {{ $scope === 'all' ? 'selected' : '' }}>{{ __('system.common.all_time') }}</option>
                </select>
            </div>
            <div>
                <label for="start_date" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.common.start_date') }}</label>
                <input id="start_date" type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.common.end_date') }}</label>
                <input id="end_date" type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label for="sort" class="block text-xs font-medium text-slate-500 mb-1">{{ __('system.pages.schedule_sort') }}</label>
                <select id="sort" name="sort" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="asc" {{ $sort === 'asc' ? 'selected' : '' }}>{{ __('system.pages.schedule_sort_soonest') }}</option>
                    <option value="desc" {{ $sort === 'desc' ? 'selected' : '' }}>{{ __('system.pages.schedule_sort_latest') }}</option>
                </select>
            </div>
            <div class="flex items-end gap-2 lg:col-span-2">
                <button type="submit" class="w-full md:w-auto px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
                    {{ __('system.pages.apply_filter') }}
                </button>
                <a href="{{ route('system.bus_route') }}" class="w-full md:w-auto text-center px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium hover:bg-slate-200 transition">
                    {{ __('system.pages.reset') }}
                </a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.common.no') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.common.company') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.schedule_bus') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.common.route') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.schedule_fare') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.schedule_time') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.common.date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">{{ __('system.pages.seats') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)
                        @php
                            $bus = $schedule->bus;
                            $company = $bus?->campany;
                            $scheduleRoute = $schedule->route;
                            $busRoute = $bus?->route;
                            $fare = $scheduleRoute->price ?? $busRoute->price ?? null;

                            $timeStart = $schedule->start ?: ($scheduleRoute->route_start ?? null);
                            $timeEnd = $schedule->end ?: ($scheduleRoute->route_end ?? null);
                            $timeDisplay = '–';
                            if ($timeStart && $timeEnd) {
                                $timeDisplay = \Carbon\Carbon::parse($timeStart)->format('H:i') . ' → ' . \Carbon\Carbon::parse($timeEnd)->format('H:i');
                            } elseif ($timeStart) {
                                $timeDisplay = \Carbon\Carbon::parse($timeStart)->format('H:i') . ' → –';
                            } elseif ($timeEnd) {
                                $timeDisplay = '– → ' . \Carbon\Carbon::parse($timeEnd)->format('H:i');
                            }

                            $scheduleDate = $schedule->schedule_date ? \Carbon\Carbon::parse($schedule->schedule_date) : null;
                            $departed = false;
                            if ($scheduleDate) {
                                $departed = $scheduleDate->format('Y-m-d') < $todayDate
                                    || ($scheduleDate->format('Y-m-d') === $todayDate && (string) $schedule->start <= $currentTime);
                            }

                            $seatMap = $schedule->booked_seat_map ?? [];
                            $bookedCount = count($seatMap);
                            $totalSeats = (int) ($bus->total_seats ?? 0);
                            $availableCount = max(0, $totalSeats - $bookedCount);
                            $layoutData = is_string($bus?->seate_json) ? $bus->seate_json : json_encode($bus?->seate_json);
                            $routeLabel = trim(($schedule->from ?? '') . ' → ' . ($schedule->to ?? ''), ' →');
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-sm text-slate-500">{{ $schedules->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="font-medium text-slate-800">{{ $company->name ?? __('system.common.na') }}</span>
                                @if ($company && (int) $company->status !== 1)
                                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">{{ __('system.common.disabled') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <span class="font-medium">{{ $bus->bus_number ?? __('system.common.na') }}</span>
                                @if (!empty($bus?->bus_type))
                                    <span class="block text-xs text-slate-400">{{ $bus->bus_type }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">
                                {{ $routeLabel !== '' ? $routeLabel : __('system.common.na') }}
                                @if ($busRoute && ($busRoute->from !== $schedule->from || $busRoute->to !== $schedule->to))
                                    <span class="block text-xs text-slate-400">{{ __('system.pages.schedule_main_route') }}: {{ $busRoute->from }} → {{ $busRoute->to }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-indigo-600">
                                @if ($fare !== null)
                                    {{ $currency }} {{ convert_money($fare) }}
                                @else
                                    {{ __('system.common.na') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ $timeDisplay }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">
                                {{ $scheduleDate ? $scheduleDate->format('D, d M Y') : __('system.common.na') }}
                                @if ($departed)
                                    <span class="block mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">{{ __('system.pages.schedule_departed') }}</span>
                                @else
                                    <span class="block mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">{{ __('system.pages.schedule_upcoming') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-500">{{ $bookedCount }}/{{ $totalSeats }}</span>
                                    <button type="button"
                                        class="view-seats-btn inline-flex items-center px-3 py-1.5 border border-indigo-200 text-indigo-700 bg-indigo-50 rounded-md text-xs font-medium hover:bg-indigo-100 transition"
                                        data-bus-number="{{ $bus->bus_number ?? '' }}"
                                        data-company="{{ $company->name ?? '' }}"
                                        data-route="{{ $routeLabel }}"
                                        data-date="{{ $scheduleDate ? $scheduleDate->format('d M Y') : '' }}"
                                        data-total-seats="{{ $totalSeats }}"
                                        data-booked-count="{{ $bookedCount }}"
                                        data-available-count="{{ $availableCount }}"
                                        data-layout="{{ $layoutData ?? '' }}"
                                        data-booked-seats="{{ json_encode($seatMap) }}">
                                        <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 7a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2M4 7V5a2 2 0 012-2h12a2 2 0 012 2v2" />
                                        </svg>
                                        {{ __('system.pages.view_seats') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-slate-800">{{ __('system.pages.no_bus_routes') }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __('system.pages.schedule_empty_hint') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($schedules->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</div>

@include('partials.seat_arrangement_modal')
@endsection
