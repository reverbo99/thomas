<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #111; margin: 0; padding: 14px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #0f766e; }
        h2 { font-size: 11px; margin: 0 0 10px; color: #334155; font-weight: normal; }
        .meta { font-size: 9px; color: #666; margin-bottom: 12px; }
        .summary { margin-bottom: 10px; font-size: 9px; }
        .summary span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 3px; text-align: left; vertical-align: top; }
        th { background: #ecfeff; font-size: 7px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $vendorName }}</h2>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        · {{ __('vender/history.export_filter', ['period' => $filterLabel]) }}
    </p>

    <div class="summary">
        <span><strong>{{ __('vender/history.booking_id') }}:</strong> {{ $bookingCount ?? 0 }}</span>
        <span><strong>{{ __('system.pages.section_total') }}:</strong> {{ number_format($totalAmount ?? 0, 2) }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('vender/history.booking_id') }}</th>
                <th>{{ __('vender/history.company') }}</th>
                <th>{{ __('vender/history.bus_route') }}</th>
                <th>{{ __('vender/history.bus_number') }}</th>
                <th>{{ __('vender/history.travel_date') }}</th>
                <th>{{ __('vender/history.seat_label') }}</th>
                <th>{{ __('vender/history.pickup_drop') }}</th>
                <th>{{ __('vender/history.passenger') }}</th>
                <th>{{ __('vender/history.phone') }}</th>
                <th class="text-right">{{ __('vender/history.seats_payment') }}</th>
                <th class="text-right">{{ __('vender/history.commission') }}</th>
                <th class="text-right">{{ __('vender/history.discount_label') }}</th>
                <th class="text-right">{{ __('vender/history.vat_label') }}</th>
                <th class="text-right">{{ __('vender/history.total') }}</th>
                <th>{{ __('vender/history.paid_time') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['booking_code'] ?? '—' }}</td>
                    <td>{{ $row['company_name'] ?? '—' }}</td>
                    <td>{{ $row['route'] ?? '—' }}</td>
                    <td>{{ $row['bus_number'] ?? '—' }}</td>
                    <td>{{ $row['travel_date'] ?? '—' }}</td>
                    <td>{{ $row['seat'] ?? '—' }}</td>
                    <td>{{ $row['pickup_drop'] ?? '—' }}</td>
                    <td>{{ $row['customer_name'] ?? '—' }}</td>
                    <td>{{ $row['customer_phone'] ?? '—' }}</td>
                    <td class="text-right">{{ $row['payment'] ?? '—' }}</td>
                    <td class="text-right">{{ $row['commission'] ?? '—' }}</td>
                    <td class="text-right">{{ $row['discount'] ?? '—' }}</td>
                    <td class="text-right">{{ $row['vat'] ?? '—' }}</td>
                    <td class="text-right">{{ $row['total'] ?? '—' }}</td>
                    <td>{{ $row['paid_at'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15">{{ __('vender/history.no_bookings_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($rows) > 0)
            <tfoot>
                <tr>
                    <td colspan="13" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($totalAmount ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
