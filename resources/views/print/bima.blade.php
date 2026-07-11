<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #111; margin: 0; padding: 14px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #2563eb; }
        .meta { font-size: 9px; color: #666; margin-bottom: 12px; }
        .summary { margin-bottom: 12px; font-size: 9px; }
        .summary span { display: inline-block; margin: 0 12px 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 3px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-size: 7px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        @if ($period)
            · {{ __('system.pages.period') }}: {{ ucfirst($period) }}
            @if ($period === 'custom' && $startDate && $endDate)
                ({{ $startDate }} — {{ $endDate }})
            @endif
        @else
            · {{ __('system.common.all_time') }}
        @endif
    </p>

    <div class="summary">
        <strong>{{ __('system.pages.section_total') }}:</strong>
        <span>{{ __('system.pages.records') ?? 'Records' }}: {{ $totals['count'] ?? 0 }}</span>
        <span>{{ __('system.common.amount') }}: {{ number_format($totals['totalAmount'] ?? 0, 2) }}</span>
        <span>VAT: {{ number_format($totals['totalVat'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.combined_total') }}: {{ number_format($totals['grandTotal'] ?? 0, 2) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>{{ __('system.pages.col_booking_code') }}</th>
                <th>{{ __('system.pages.col_booking_date') }}</th>
                <th>{{ __('system.pages.col_username') }}</th>
                <th>{{ __('system.pages.col_phone_number') }}</th>
                <th>{{ __('system.pages.col_company') }}</th>
                <th>{{ __('system.pages.bus_number') ?? 'Bus Number' }}</th>
                <th>{{ __('system.common.route') }}</th>
                <th>{{ __('system.pages.start_date') ?? 'Start Date' }}</th>
                <th>{{ __('system.pages.end_date') ?? 'End Date' }}</th>
                <th>{{ __('system.pages.valid_days') ?? 'Valid Days' }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th class="text-right">VAT</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['booking_code'] }}</td>
                    <td>{{ $row['booking_date'] }}</td>
                    <td>{{ $row['customer_name'] }}</td>
                    <td>{{ $row['customer_phone'] }}</td>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['bus_number'] }}</td>
                    <td>{{ $row['route'] }}</td>
                    <td>{{ $row['start_date'] }}</td>
                    <td>{{ $row['end_date'] }}</td>
                    <td>{{ $row['valid_days'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td class="text-right">{{ $row['vat'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="11" class="text-right">{{ __('system.pages.section_total') }}</td>
                <td class="text-right">{{ number_format($totals['totalAmount'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalVat'] ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
