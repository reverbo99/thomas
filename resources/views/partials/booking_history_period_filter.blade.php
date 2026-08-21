@php
    $period = $period ?? request('period', '');
    $startDate = $startDate ?? request('start_date', '');
    $endDate = $endDate ?? request('end_date', '');
    $formAction = $formAction ?? url()->current();
    $resetUrl = $resetUrl ?? $formAction;
    $variant = $variant ?? 'default';
    $extraFields = $extraFields ?? [];
    // Optional extra visible filter inputs rendered inside this same form, e.g.
    // [['name' => 'bus_name', 'type' => 'text', 'label' => 'Bus name', 'value' => request('bus_name')]]
    $columnFilters = $columnFilters ?? [];
    $isVendor = $variant === 'vendor';
    // Allow callers that place the filter on a coloured header to override the
    // label colour so the labels stay legible (e.g. white text on a teal card).
    // Inputs must NEVER inherit that colour — force readable field text in light/dark.
    $labelClass = $labelClass ?? 'text-gray-600 dark:text-gray-300';
    $fieldClass = $isVendor
        ? 'page-input text-sm'
        : 'px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-slate-400 focus:ring-1 focus:ring-teal-500 focus:border-teal-500 text-sm';
    $selectClass = $fieldClass;
    $dateClass = $fieldClass;
    $btnClass = $isVendor
        ? 'page-btn page-btn--outline text-sm'
        : 'px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium';
    $resetClass = $isVendor
        ? 'page-btn page-btn--outline text-sm'
        : 'px-3 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition text-sm font-medium';
@endphp

<form method="GET" action="{{ $formAction }}" class="flex flex-wrap items-end gap-2 booking-history-period-filter" id="bookingHistoryPeriodForm">
    @foreach ($extraFields as $name => $value)
        @if ($value !== null && $value !== '')
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach

    <div class="flex flex-col gap-1">
        @unless($isVendor)
            <label for="historyPeriodSelect" class="text-xs font-medium {{ $labelClass }}">{{ __('system.pages.period') }}</label>
        @endunless
        <select name="period" id="historyPeriodSelect" class="{{ $selectClass }}" onchange="window.toggleHistoryCustomDates && window.toggleHistoryCustomDates(this)">
            <option value="" @selected($period === '' || $period === null)>{{ __('system.common.all_time') }}</option>
            <option value="today" @selected($period === 'today')>{{ __('system.sidebar.today') }}</option>
            <option value="week" @selected($period === 'week')>{{ __('system.common.this_week') }}</option>
            <option value="month" @selected($period === 'month')>{{ __('system.common.this_month') }}</option>
            <option value="year" @selected($period === 'year')>{{ __('system.common.this_year') }}</option>
            <option value="custom" @selected($period === 'custom' || ($startDate && $endDate))>{{ __('system.common.custom_range') }}</option>
        </select>
    </div>

    <div class="flex flex-col gap-1 history-custom-dates {{ ($period === 'custom' || ($startDate && $endDate)) ? '' : 'hidden' }}">
        <label for="historyStartDate" class="text-xs font-medium {{ $labelClass }}">{{ __('system.common.start_date') }}</label>
        <input type="date" name="start_date" id="historyStartDate" value="{{ $startDate }}" class="{{ $dateClass }}">
    </div>

    <div class="flex flex-col gap-1 history-custom-dates {{ ($period === 'custom' || ($startDate && $endDate)) ? '' : 'hidden' }}">
        <label for="historyEndDate" class="text-xs font-medium {{ $labelClass }}">{{ __('system.common.end_date') }}</label>
        <input type="date" name="end_date" id="historyEndDate" value="{{ $endDate }}" class="{{ $dateClass }}">
    </div>

    @foreach ($columnFilters as $filter)
        <div class="flex flex-col gap-1">
            <label for="colFilter_{{ $filter['name'] }}" class="text-xs font-medium {{ $labelClass }}">{{ $filter['label'] }}</label>
            <input type="{{ $filter['type'] ?? 'text' }}" name="{{ $filter['name'] }}" id="colFilter_{{ $filter['name'] }}"
                   value="{{ $filter['value'] ?? '' }}" placeholder="{{ $filter['label'] }}" class="{{ $dateClass }}">
        </div>
    @endforeach

    <button type="submit" class="{{ $btnClass }}">{{ __('system.pages.apply_filter') }}</button>
    <a href="{{ $resetUrl }}" class="{{ $resetClass }}">{{ __('system.pages.reset') }}</a>
</form>

<script>
    window.toggleHistoryCustomDates = function (select) {
        const form = select.closest('form');
        if (!form) return;
        const show = select.value === 'custom';
        form.querySelectorAll('.history-custom-dates').forEach(function (el) {
            el.classList.toggle('hidden', !show);
            el.querySelectorAll('input[type="date"]').forEach(function (input) {
                input.disabled = !show;
                if (!show) {
                    input.value = '';
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#historyPeriodSelect').forEach(function (select) {
            window.toggleHistoryCustomDates(select);
        });
    });
</script>
