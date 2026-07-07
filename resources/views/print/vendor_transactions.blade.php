<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 14px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #0f766e; }
        h2 { font-size: 11px; margin: 0 0 10px; color: #334155; font-weight: normal; }
        .meta { font-size: 9px; color: #666; margin-bottom: 12px; }
        .summary { margin-bottom: 10px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; text-align: left; vertical-align: top; }
        th { background: #ecfeff; font-size: 8px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <h2>{{ $vendorName }}</h2>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        · {{ __('assistance/transaction.export_filter', ['period' => $filterLabel]) }}
    </p>

    <div class="summary">
        <strong>{{ __('system.pages.section_total') }}:</strong>
        {{ number_format($totalAmount ?? 0, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('assistance/transaction.vender') }}</th>
                <th>{{ __('assistance/transaction.payment_method') }}</th>
                <th>{{ __('assistance/transaction.payment_details') }}</th>
                <th>{{ __('assistance/transaction.date') }}</th>
                <th class="text-right">{{ __('assistance/transaction.amount') }}</th>
                <th>{{ __('assistance/transaction.reference_no') }}</th>
                <th>{{ __('assistance/transaction.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['vendor'] ?? '—' }}</td>
                    <td>{{ $row['payment_method'] ?? '—' }}</td>
                    <td>{{ $row['payment_details'] ?? '—' }}</td>
                    <td>{{ $row['date'] ?? '—' }}</td>
                    <td class="text-right">{{ $row['amount'] ?? '—' }}</td>
                    <td>{{ $row['reference_number'] ?? '—' }}</td>
                    <td>{{ $row['status'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('assistance/transaction.no_transactions_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($rows) > 0)
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($totalAmount ?? 0, 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
