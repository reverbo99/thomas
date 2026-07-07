<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 14px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #2563eb; }
        h2 { font-size: 12px; margin: 18px 0 8px; color: #1e40af; }
        .meta { font-size: 9px; color: #666; margin-bottom: 12px; }
        .summary { margin-bottom: 10px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-size: 8px; text-transform: uppercase; }
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

    <h2>{{ __('system.transactions.requested_transactions') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('system.common.company') }}</th>
                <th>{{ __('system.common.user') }}</th>
                <th>{{ __('system.common.payment_method') }}</th>
                <th>{{ __('system.common.payment_details') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.status') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pendingRows as $row)
                <tr>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['user'] }}</td>
                    <td>{{ $row['payment_method'] }}</td>
                    <td>{{ $row['payment_number'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('system.common.dt_empty_pending_tx') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($pendingRows) > 0)
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($pendingTotal ?? 0, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h2>{{ __('system.transactions.all_transactions') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('system.common.company') }}</th>
                <th>{{ __('system.common.user') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.reference_no') }}</th>
                <th>{{ __('system.common.status') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($allRows as $row)
                <tr>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['user'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['reference_number'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('system.common.dt_empty_tx') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($allRows) > 0)
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($allTotal ?? 0, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
