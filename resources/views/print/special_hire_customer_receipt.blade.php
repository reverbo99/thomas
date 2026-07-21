<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('system.pages.customer_receipt') }} — {{ $hireOrder->order_code }}</title>
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
    <h1>{{ __('system.pages.customer_receipt') }}</h1>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        · {{ __('system.pages.order') }} <strong>{{ $hireOrder->order_code }}</strong>
    </p>

    <h2>{{ __('system.pages.customer') }}</h2>
    <table>
        <tr><th>{{ __('system.pages.customer') }}</th><td>{{ $hireOrder->customer_name }}</td></tr>
        <tr><th>{{ __('system.pages.passenger_phone') }}</th><td>{{ $hireOrder->customer_phone }}</td></tr>
        @if($hireOrder->customer_email)
            <tr><th>Email</th><td>{{ $hireOrder->customer_email }}</td></tr>
        @endif
    </table>

    <h2>{{ __('system.pages.hire_summary') }}</h2>
    <table>
        <tr><th>{{ __('system.pages.operator') }}</th><td>{{ $hireOrder->user->name ?? 'N/A' }}</td></tr>
        <tr><th>{{ __('system.pages.coaster') }}</th><td>{{ $hireOrder->coaster->name ?? 'N/A' }} ({{ $hireOrder->coaster->plate_number ?? 'N/A' }})</td></tr>
        <tr><th>{{ __('system.pages.col_travel_details') }}</th><td>{{ $hireOrder->pickup_location }} → {{ $hireOrder->dropoff_location }}</td></tr>
        <tr><th>{{ __('system.pages.hire_date') }}</th><td>{{ $hireOrder->hire_date?->format('Y-m-d') }} {{ $hireOrder->hire_time }}</td></tr>
        @if($hireOrder->return_date)
            <tr><th>{{ __('system.common.date') }} ({{ __('system.pages.col_travel_details') }})</th><td>{{ $hireOrder->return_date?->format('Y-m-d') }} {{ $hireOrder->return_time }}</td></tr>
        @endif
        <tr><th>{{ __('all.seats') }}</th><td>{{ $hireOrder->passengers_count }}</td></tr>
    </table>

    <h2>{{ __('system.common.amount') }}</h2>
    <table>
        <tr><th>{{ __('system.pages.base_fare') }}</th><td class="text-right">{{ number_format((float) $hireOrder->base_price, 2) }}</td></tr>
        <tr><th>{{ __('system.pages.distance_charge') }} ({{ $hireOrder->distance_km }} km)</th><td class="text-right">{{ number_format((float) $hireOrder->km_amount, 2) }}</td></tr>
        @if((float) $hireOrder->surcharge_amount > 0)
            <tr><th>{{ __('system.pages.surcharge') }} ({{ $hireOrder->surcharge_percent }}%)</th><td class="text-right">{{ number_format((float) $hireOrder->surcharge_amount, 2) }}</td></tr>
        @endif
        @if($hireOrder->deposit_amount !== null)
            <tr><th>{{ __('system.pages.deposit_paid') }}</th><td class="text-right">{{ number_format((float) $hireOrder->deposit_amount, 2) }}</td></tr>
        @endif
        @if($hireOrder->balance_amount !== null)
            <tr><th>{{ __('system.pages.balance_paid') }}</th><td class="text-right">{{ number_format((float) $hireOrder->balance_amount, 2) }}</td></tr>
        @endif
        <tr><th>{{ __('system.pages.payment_method') }}</th><td>{{ $hireOrder->payment_method ?? 'N/A' }}</td></tr>
        <tfoot>
            <tr><td>{{ __('system.pages.total_paid') }}</td><td class="text-right">{{ number_format((float) $hireOrder->total_amount, 2) }}</td></tr>
        </tfoot>
    </table>
</body>
</html>
