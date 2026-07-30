<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
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
        .section-title.teal { color: #0f766e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; text-align: left; vertical-align: top; }
        th { background: #ecfdf5; font-size: 8px; text-transform: uppercase; }
        th.teal-header { background: #ccfbf1; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    @php $pct = rtrim(rtrim(number_format($levyPercent ?? 5, 2, '.', ''), '0'), '.'); @endphp
    <h1>{{ $title }}</h1>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        · {{ __('system.pages.period') }}: {{ ucfirst($period) }}
        @if ($period === 'custom' && $startDate && $endDate)
            ({{ $startDate }} — {{ $endDate }})
        @endif
        · {{ __('system.pages.levy_rate_label', ['percent' => $pct]) }}
    </p>

    <div class="summary">
        <strong>{{ __('system.pages.levy_six_categories_label') }}</strong>
        <span>{{ __('system.pages.levy_cat_commission') }}: {{ number_format($totals['levyCommission'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.levy_cat_service') }}: {{ number_format($totals['levyService'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.levy_cat_luggage') }}: {{ number_format($totals['levyLuggage'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.levy_cat_cancellation') }}: {{ number_format($totals['levyCancellation'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.levy_cat_parcel') }}: {{ number_format($totals['levyParcel'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.levy_cat_special_hire') }}: {{ number_format($totals['levySpecialHire'] ?? 0, 2) }}</span>
        <span><strong>{{ __('system.pages.grand_total') }}: {{ number_format($totals['totalGovernmentLevy'] ?? 0, 2) }}</strong></span>
    </div>
    <div class="summary">
        <strong>{{ __('system.pages.levy_history_parity_label') }}</strong>
        <span>{{ __('system.pages.levy_fare_short') }}: {{ number_format($totals['totalGovLevyOnFare'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.levy_service_short') }}: {{ number_format($totals['totalGovLevyOnService'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.levy_fare_plus_service') }}: {{ number_format($totals['farePlusServiceLevy'] ?? 0, 2) }}</span>
    </div>

    <div class="section-title">{{ __('system.pages.category_summary') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('system.pages.category_label') }}</th>
                <th>{{ __('system.pages.fee_base') }}</th>
                <th class="text-right">{{ __('system.pages.gov_levy_short') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categoryRows as $row)
                <tr>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['fee_base'] }}</td>
                    <td class="text-right">{{ $row['gov_levy'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">{{ __('system.pages.levy_bookings_section') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('system.pages.col_booking_id') }}</th>
                <th>{{ __('system.common.date') }}</th>
                <th>{{ __('system.common.route') }}</th>
                <th>{{ __('system.pages.vendor_involvement') }}</th>
                <th class="text-right">{{ __('system.pages.paid_amount') }}</th>
                <th class="text-right">{{ __('system.pages.commission_levy') }}</th>
                <th class="text-right">{{ __('system.pages.gov_levy_fare') }}</th>
                <th class="text-right">{{ __('system.pages.gov_levy_service') }}</th>
                <th class="text-right">{{ __('system.pages.luggage_levy') }}</th>
                <th class="text-right">{{ __('system.pages.levy_fare_plus_service') }}</th>
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
                    <td class="text-right">{{ $row['gov_levy_commission'] ?? '0.00' }}</td>
                    <td class="text-right">{{ $row['gov_levy_fare'] }}</td>
                    <td class="text-right">{{ $row['gov_levy_service'] }}</td>
                    <td class="text-right">{{ $row['gov_levy_luggage'] ?? '0.00' }}</td>
                    <td class="text-right">{{ $row['total_gov_levy'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">{{ __('system.pages.subtotal_fare_service') }}</td>
                <td class="text-right">{{ number_format($totals['levyCommission'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalGovLevyOnFare'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['totalGovLevyOnService'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['levyLuggage'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totals['farePlusServiceLevy'] ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if (!empty($specialHireRows) && count($specialHireRows) > 0)
        <div class="section-title teal">{{ __('system.pages.levy_special_hire_section', ['percent' => $pct]) }}</div>
        <table>
            <thead>
                <tr>
                    <th class="teal-header">{{ __('system.pages.col_order_code') }}</th>
                    <th class="teal-header">{{ __('system.common.date') }}</th>
                    <th class="teal-header">{{ __('system.pages.col_pickup_dropoff') }}</th>
                    <th class="teal-header">{{ __('system.pages.operator') }}</th>
                    <th class="teal-header text-right">{{ __('system.pages.platform_commission') }}</th>
                    <th class="teal-header text-right">{{ __('system.pages.gov_levy_short') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($specialHireRows as $row)
                    <tr>
                        <td>{{ $row['booking_code'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['route'] }}</td>
                        <td>{{ $row['vendor'] }}</td>
                        <td class="text-right">{{ $row['fee_base'] }}</td>
                        <td class="text-right">{{ $row['gov_levy'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">{{ __('system.pages.subtotal') }}</td>
                    <td class="text-right">{{ number_format($totals['specialHireCommissionBase'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($totals['levySpecialHire'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

</body>
</html>
