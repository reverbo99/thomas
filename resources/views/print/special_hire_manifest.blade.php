<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('system.pages.manifest') }} — {{ $hireOrder->order_code }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 0; }
        h1 { font-size: 15px; margin: 0 0 4px; color: #1d4ed8; text-transform: uppercase; }
        .meta { font-size: 9px; color: #444; margin-bottom: 4px; line-height: 1.5; }
        .meta span { margin-right: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; text-align: left; vertical-align: middle; }
        th { background: #eff6ff; font-size: 8px; text-transform: uppercase; }
        .text-center { text-align: center; }
        .no-data { text-align: center; padding: 14px; font-style: italic; }
    </style>
</head>
<body>
    <h1>{{ __('system.pages.manifest') }}</h1>
    <p class="meta">
        <span><strong>{{ __('system.pages.order') }}:</strong> {{ $hireOrder->order_code }}</span>
        <span><strong>{{ __('system.pages.operator') }}:</strong> {{ $hireOrder->user->name ?? 'N/A' }}</span>
        <span><strong>{{ __('system.pages.coaster') }}:</strong> {{ $hireOrder->coaster->name ?? 'N/A' }} ({{ $hireOrder->coaster->plate_number ?? 'N/A' }})</span>
    </p>
    <p class="meta">
        <span><strong>{{ __('system.pages.col_travel_details') }}:</strong> {{ $hireOrder->pickup_location }} → {{ $hireOrder->dropoff_location }}</span>
        <span><strong>{{ __('system.pages.hire_date') }}:</strong> {{ $hireOrder->hire_date?->format('Y-m-d') }} {{ $hireOrder->hire_time }}</span>
        <span><strong>{{ __('system.common.date') }}:</strong> {{ $hireOrder->return_date?->format('Y-m-d') }} {{ $hireOrder->return_time }}</span>
    </p>
    <p class="meta">
        <span><strong>Driver:</strong> {{ $hireOrder->coaster->driver->name ?? $hireOrder->coaster->driver_name ?? 'N/A' }} ({{ $hireOrder->coaster->driver->contact ?? $hireOrder->coaster->driver_contact ?? 'N/A' }})</span>
        <span><strong>{{ __('system.pages.customer') }}:</strong> {{ $hireOrder->customer_name }} ({{ $hireOrder->customer_phone }})</span>
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">{{ __('system.pages.passenger_name') }}</th>
                <th style="width: 20%;">{{ __('system.pages.passenger_phone') }}</th>
                <th style="width: 15%;">{{ __('system.pages.gender') }}</th>
                <th style="width: 25%;">{{ __('system.pages.infant') }}</th>
            </tr>
        </thead>
        <tbody>
            @if (!empty($passengers))
                @foreach ($passengers as $index => $passenger)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ strtoupper($passenger['name'] ?? '') }}</td>
                        <td>{{ $passenger['phone'] ?? '' }}</td>
                        <td class="text-center">{{ $passenger['gender'] ? ucfirst($passenger['gender']) : '' }}</td>
                        <td class="text-center">{{ !empty($passenger['is_infant']) ? __('system.pages.yes_label') : __('system.pages.no_label') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="5" class="no-data">{{ __('system.pages.no_passengers_recorded') }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
