<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #111; margin: 0; padding: 12px; }
        h1 { font-size: 15px; margin: 0 0 4px; color: #1d4ed8; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 4px 3px; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #eff6ff; font-size: 8px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
        .summary { margin-top: 10px; font-size: 9px; }
        .summary span { margin-right: 14px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        @if (!empty($operator))
            · {{ $operator }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('system.pages.code') }}</th>
                @empty($operator)
                    <th>{{ __('system.pages.operator') }}</th>
                @endempty
                <th>{{ __('system.common.date') }}</th>
                <th>{{ __('system.pages.coaster') }}</th>
                <th>{{ __('system.pages.col_passenger') }}</th>
                <th>{{ __('system.pages.hire_date') }}</th>
                <th class="text-right">{{ __('system.common.total') }}</th>
                <th class="text-right">{{ __('system.pages.platform_commission') }}</th>
                <th class="text-right">{{ __('system.pages.operator_net') }}</th>
                <th>{{ __('system.pages.payment_status') }}</th>
                <th>{{ __('system.common.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['order_code'] }}</td>
                    @empty($operator)
                        <td>{{ $row['operator'] }}</td>
                    @endempty
                    <td>{{ $row['created_at'] }}</td>
                    <td>{{ $row['coaster'] }}<br><span style="color:#666;">{{ $row['plate'] }}</span></td>
                    <td>{{ $row['customer_name'] }}<br><span style="color:#666;">{{ $row['customer_phone'] }}</span></td>
                    <td>{{ $row['hire_date'] }}</td>
                    <td class="text-right">{{ $row['total_amount'] }}</td>
                    <td class="text-right">{{ $row['platform_commission'] }}</td>
                    <td class="text-right">{{ $row['operator_net'] }}</td>
                    <td>{{ $row['payment_status'] }}</td>
                    <td>{{ $row['order_status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <strong>{{ __('system.pages.section_total') }}:</strong>
        <span>{{ __('system.pages.orders') }}: {{ number_format($totals['count'] ?? 0) }}</span>
        <span>{{ __('system.common.total') }}: {{ number_format($totals['total_amount'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.platform_commission') }}: {{ number_format($totals['platform_commission'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.operator_net') }}: {{ number_format($totals['operator_net'] ?? 0, 2) }}</span>
        <span>{{ __('system.pages.paid_revenue') }}: {{ number_format($totals['paid_amount'] ?? 0, 2) }}</span>
    </div>
</body>
</html>
