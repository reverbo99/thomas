<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 16px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #d97706; }
        .meta { font-size: 9px; color: #666; margin-bottom: 14px; }
        .summary { margin-bottom: 12px; font-size: 9px; }
        .summary span { display: inline-block; margin: 0 12px 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 5px; text-align: left; vertical-align: top; }
        th { background: #fffbeb; font-size: 9px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">{{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}</p>

    <div class="summary">
        <strong>{{ __('system.pages.section_total') }}:</strong>
        <span>{{ __('system.refunds.total_requests') }}: {{ $totals['count'] ?? 0 }}</span>
        <span>{{ __('system.common.pending') }}: {{ $totals['pendingCount'] ?? 0 }} ({{ number_format($totals['pendingAmount'] ?? 0, 2) }})</span>
        <span>{{ __('system.common.approved') }}: {{ $totals['approvedCount'] ?? 0 }} ({{ number_format($totals['approvedAmount'] ?? 0, 2) }})</span>
        <span>{{ __('system.common.rejected') }}: {{ $totals['rejectedCount'] ?? 0 }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('system.pages.booking_id') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.status') }}</th>
                <th>{{ __('system.pages.phone') }}</th>
                <th>{{ __('system.pages.full_name') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['booking_code'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['phone'] }}</td>
                    <td>{{ $row['fullname'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="text-right">{{ __('system.pages.section_total') }}</td>
                <td class="text-right">{{ number_format($totals['totalAmount'] ?? 0, 2) }}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
