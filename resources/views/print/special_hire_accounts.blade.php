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
                <th>{{ __('system.common.name') }}</th>
                <th>{{ __('system.common.email') }}</th>
                <th>{{ __('system.common.contact') }}</th>
                <th class="text-right">{{ __('system.pages.coasters') }}</th>
                <th class="text-right">{{ __('system.pages.orders') }}</th>
                <th class="text-right">{{ __('system.pages.platform_commission') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td>{{ $row['contact'] }}</td>
                    <td class="text-right">{{ $row['coasters'] }}</td>
                    <td class="text-right">{{ $row['orders'] }}</td>
                    <td class="text-right">{{ $row['platform_percent'] }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                <td class="text-right">{{ number_format($totals['coasters'] ?? 0) }}</td>
                <td class="text-right">{{ number_format($totals['orders'] ?? 0) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
