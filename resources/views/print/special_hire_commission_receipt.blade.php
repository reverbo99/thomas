<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('system.pages.commission_receipt') }} — {{ $hireOrder->order_code }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; margin: 0; padding: 0; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #1d4ed8; text-transform: uppercase; }
        .meta { font-size: 9px; color: #666; margin-bottom: 16px; }
        h2 { font-size: 12px; margin: 16px 0 8px; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-size: 9px; text-transform: uppercase; width: 45%; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ __('system.pages.commission_receipt') }}</h1>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        · {{ __('system.pages.order') }} <strong>{{ $hireOrder->order_code }}</strong>
    </p>

    <h2>{{ __('system.pages.receipt_for') }} {{ $hireOrder->user->name ?? 'N/A' }}</h2>
    <table>
        <tr><th>{{ __('system.pages.operator') }}</th><td>{{ $hireOrder->user->name ?? 'N/A' }}</td></tr>
        <tr><th>{{ __('system.pages.coaster') }}</th><td>{{ $hireOrder->coaster->name ?? 'N/A' }} ({{ $hireOrder->coaster->plate_number ?? 'N/A' }})</td></tr>
        <tr><th>{{ __('system.pages.customer') }}</th><td>{{ $hireOrder->customer_name }}</td></tr>
        <tr><th>{{ __('system.pages.hire_date') }}</th><td>{{ $hireOrder->hire_date?->format('Y-m-d') }} {{ $hireOrder->hire_time }}</td></tr>
    </table>

    <h2>{{ __('system.common.amount') }}</h2>
    <table>
        <tr><th>{{ __('system.pages.total_paid') }}</th><td class="text-right">{{ number_format((float) $hireOrder->total_amount, 2) }}</td></tr>
        <tr><th>{{ __('system.pages.commission_percent') }}</th><td class="text-right">{{ number_format((float) $hireOrder->platform_commission_percent, 2) }}%</td></tr>
        <tr><th>{{ __('system.pages.commission_amount') }}</th><td class="text-right">{{ number_format((float) $hireOrder->platform_commission_amount, 2) }}</td></tr>
        <tfoot>
            <tr>
                <td>{{ __('system.pages.operator_receives') }}</td>
                <td class="text-right">{{ number_format((float) $hireOrder->total_amount - (float) $hireOrder->platform_commission_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
