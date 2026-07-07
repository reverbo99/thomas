<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 16px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #1d4ed8; }
        .meta { font-size: 9px; color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 5px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-size: 9px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">{{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>{{ __('system.common.date') }}</th>
                <th>{{ __('system.pages.operator') }}</th>
                <th>{{ __('system.common.email') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.payment_method') }}</th>
                <th>{{ __('system.common.payment_number') }}</th>
                <th>{{ __('system.common.status') }}</th>
                <th>{{ __('system.pages.processed_at') }}</th>
                <th>{{ __('system.pages.admin_note') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['operator'] }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['payment_method'] }}</td>
                    <td>{{ $row['payment_number'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['processed_at'] }}</td>
                    <td>{{ $row['admin_note'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                <td class="text-right">{{ number_format($totals['amount'] ?? 0, 2) }}</td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
