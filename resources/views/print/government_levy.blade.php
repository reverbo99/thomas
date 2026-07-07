<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 14px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #047857; }
        .meta { font-size: 9px; color: #666; margin-bottom: 12px; }
        .summary { margin-bottom: 12px; font-size: 9px; }
        .summary span { display: inline-block; margin: 0 12px 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; text-align: left; vertical-align: top; }
        th { background: #ecfdf5; font-size: 8px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        · {{ __('system.pages.period') }}: {{ ucfirst($period) }}
        @if ($period === 'custom' && $startDate && $endDate)
            ({{ $startDate }} — {{ $endDate }})
        @endif
    </p>

    <div class="summary">
        <strong>{{ __('system.pages.section_total') }}:</strong>
        <span>{{ __('system.pages.paid_amount') }}: {{ number_format($totals['totalPaidAmount'] ?? 0, 2) }}</span>
        <span>VAT: {{ number_format($totals['totalVat'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.gov_levy_fare') }}: {{ number_format($totals['totalGovLevyOnFare'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.gov_levy_service') }}: {{ number_format($totals['totalGovLevyOnService'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.total_gov_levy') }}: {{ number_format($totals['totalGovernmentLevy'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.service_fee') }}: {{ number_format($totals['totalSystemServiceFee'] ?? 0, 2) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('system.pages.col_booking_id') }}</th>
                <th>{{ __('system.common.date') }}</th>
                <th>{{ __('system.common.route') }}</th>
                <th>{{ __('system.pages.vendor_involvement') }}</th>
                <th class="text-right">{{ __('system.pages.paid_amount') }}</th>
                <th class="text-right">VAT</th>
                <th class="text-right">{{ __('system.pages.gov_levy_fare') }}</th>
                <th class="text-right">{{ __('system.pages.gov_levy_service') }}</th>
                <th class="text-right">{{ __('system.pages.total_gov_levy') }}</th>
                <th class="text-right">{{ __('system.pages.service_fee') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['booking_code'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['route'] }}</td>
                    <td>{{ $row['vendor'] }}</td>
                    <td class="text-right">{{ $row['paid_amount'] }}</td>
                    <td class="text-right">{{ $row['vat'] }}</td>
                    <td class="text-right">{{ $row['gov_levy_fare'] }}</td>
                    <td class="text-right">{{ $row['gov_levy_service'] }}</td>
                    <td class="text-right">{{ $row['total_gov_levy'] }}</td>
                    <td class="text-right">{{ $row['system_service_fee'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">{{ __('system.pages.section_total') }}</td>
                <td class="text-right">{{ number_format($totals['totalPaidAmount'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalVat'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalGovLevyOnFare'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalGovLevyOnService'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalGovernmentLevy'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalSystemServiceFee'] ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
