<!DOCTYPE html>
<html>
<head>
    <title>{{ __('system.vendors.print_list_title') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        h1 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('system.vendors.print_list_title') }}</h1>
        <p>{{ __('system.vendors.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('system.common.no') }}</th>
                <th>{{ __('system.common.name') }}</th>
                <th>{{ __('system.common.contact') }}</th>
                <th>{{ __('system.common.balance') }}</th>
                <th>{{ __('system.common.work_center') }}</th>
                <th>{{ __('system.common.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($venders as $key => $vendor)
                @php
                    $statusLabel = match ($vendor->status) {
                        'accept' => __('system.vendors.status_accept'),
                        'cancel' => __('system.vendors.status_cancel'),
                        default => __('system.common.pending'),
                    };
                @endphp
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $vendor->name }}</td>
                    <td>{{ $vendor->contact ?? '—' }}</td>
                    <td>{{ number_format($vendor->VenderBalances->amount ?? 0, 2) }} TZS</td>
                    <td>{{ $vendor->VenderAccount->work ?? __('system.common.na') }}</td>
                    <td>{{ $statusLabel }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">{{ __('system.vendors.no_vendors') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('all.highlink_isgc') }}
    </div>
</body>
</html>
