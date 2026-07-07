<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('system.pages.print_commission') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 16px; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #2563eb; }
        .meta { font-size: 10px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #eff6ff; font-size: 10px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ __('system.pages.print_commission') }}</h1>
    <p class="meta">{{ __('all.highlink_isgc') }} · {{ now()->format('d M Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('system.pages.col_booking_id') }}</th>
                <th>{{ __('system.pages.col_passenger') }}</th>
                <th>{{ __('system.pages.col_bus_route') }}</th>
                <th class="text-right">{{ __('system.pages.commission') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bookings as $index => $booking)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $booking['booking_code'] ?? 'N/A' }}</td>
                    <td>{{ $booking['customer_name'] ?? 'N/A' }}</td>
                    <td>{{ $booking['company_name'] ?? 'N/A' }} · {{ $booking['route_from'] ?? 'N/A' }} - {{ $booking['route_to'] ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format((float) ($booking['commision'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">{{ __('system.pages.no_bookings_history') }}</td></tr>
            @endforelse
        </tbody>
        @if (!empty($bookings))
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format(collect($bookings)->sum(fn ($row) => (float) ($row['commision'] ?? 0)), 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
