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
        .section-title { font-size: 11px; font-weight: bold; margin: 14px 0 6px; color: #065f46; }
        .section-title.amber { color: #92400e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; text-align: left; vertical-align: top; }
        th { background: #ecfdf5; font-size: 8px; text-transform: uppercase; }
        th.amber-header { background: #fef3c7; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
        .page-break { page-break-before: always; }
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
        <strong>Totals:</strong>
        <span>Paid Amount: {{ number_format($totals['totalPaidAmount'] ?? 0, 2) }}</span>
        <span>VAT: {{ number_format($totals['totalVat'] ?? 0, 2) }}</span>
        <span>System Service Fee: {{ number_format($totals['totalSystemServiceFee'] ?? 0, 2) }}</span>
    </div>
    <div class="summary">
        <strong>Government Levy breakdown:</strong>
        <span>Levy on Fare: {{ number_format($totals['totalGovLevyOnFare'] ?? 0, 2) }}</span>
        <span>Levy on Service Fee: {{ number_format($totals['totalGovLevyOnService'] ?? 0, 2) }}</span>
        <span>Levy on Special Hire (est.): {{ number_format($totals['specialHireLevyTotal'] ?? 0, 2) }}</span>
        <span><strong>Grand Total Govt Levy: {{ number_format($totals['totalGovernmentLevy'] ?? 0, 2) }}</strong></span>
    </div>

    {{-- Paid bookings table --}}
    <div class="section-title">Paid Bookings — Levy on Fare + Levy on Service Fee</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('system.pages.col_booking_id') }}</th>
                <th>{{ __('system.common.date') }}</th>
                <th>{{ __('system.common.route') }}</th>
                <th>{{ __('system.pages.vendor_involvement') }}</th>
                <th class="text-right">{{ __('system.pages.paid_amount') }}</th>
                <th class="text-right">VAT</th>
                <th class="text-right">Gov Levy (Fare)</th>
                <th class="text-right">Gov Levy (Service)</th>
                <th class="text-right">Total Gov Levy</th>
                <th class="text-right">Service Fee</th>
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
                <td colspan="4" class="text-right">Subtotal</td>
                <td class="text-right">{{ number_format($totals['totalPaidAmount'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalVat'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalGovLevyOnFare'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalGovLevyOnService'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format(($totals['totalGovLevyOnFare'] ?? 0) + ($totals['totalGovLevyOnService'] ?? 0), 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalSystemServiceFee'] ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Special hire orders table --}}
    @if (!empty($specialHireRows) && $specialHireRows->isNotEmpty())
        <div class="section-title amber">Special Hire Orders — Estimated Govt Levy (5%)</div>
        <table>
            <thead>
                <tr>
                    <th class="amber-header">Order Code</th>
                    <th class="amber-header">Date</th>
                    <th class="amber-header">Pickup → Dropoff</th>
                    <th class="amber-header">Operator</th>
                    <th class="amber-header text-right">Total Amount</th>
                    <th class="amber-header text-right">Est. Gov Levy (5%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($specialHireRows as $row)
                    <tr>
                        <td>{{ $row['booking_code'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['route'] }}</td>
                        <td>{{ $row['vendor'] }}</td>
                        <td class="text-right">{{ $row['paid_amount'] }}</td>
                        <td class="text-right">{{ $row['total_gov_levy'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">Subtotal</td>
                    <td class="text-right">{{ number_format($totals['specialHireTotalAmount'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($totals['specialHireLevyTotal'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- Final grand total row --}}
    <div class="summary" style="margin-top: 14px;">
        <strong>Grand Total Government Levy Collected: {{ number_format($totals['totalGovernmentLevy'] ?? 0, 2) }}</strong>
    </div>
</body>
</html>