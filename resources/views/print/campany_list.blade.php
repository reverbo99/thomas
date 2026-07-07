<!DOCTYPE html>
<html>
<head>
    <title>{{ __('system.operators.print_list_title') }}</title>
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
        <h1>{{ __('system.operators.print_list_title') }}</h1>
        <p>{{ __('system.operators.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('system.common.no') }}</th>
                <th>{{ __('system.common.company') }}</th>
                <th>{{ __('system.common.owner') }}</th>
                <th>{{ __('system.common.contact') }}</th>
                <th>{{ __('system.common.balance') }}</th>
                <th>%</th>
                <th>{{ __('system.common.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($campanies as $key => $campany)
                @php
                    $contact = preg_replace('/\s+/', '', $campany->user->contact ?? '');
                    $contact = preg_replace('/^\+?255/', '', $contact);
                    $contact = ltrim($contact, '0');
                    $formattedContact = $contact !== '' ? '255' . $contact : '—';

                    $statusLabel = $campany->status == 1
                        ? __('system.common.active')
                        : ($campany->status == 2 ? __('system.common.disabled') : __('system.common.pending'));

                    $commission = ($campany->percentage ?? 0) . '%';
                    if (!empty($campany->commission_amount)) {
                        $commission .= ' / ' . number_format($campany->commission_amount, 2) . ' TZS';
                    }
                @endphp
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $campany->name }}</td>
                    <td>{{ $campany->user->name ?? 'N/A' }}</td>
                    <td>{{ $formattedContact }}</td>
                    <td>{{ number_format($campany->balance->amount ?? 0, 2) }} TZS</td>
                    <td>{{ $commission }}</td>
                    <td>{{ $statusLabel }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('system.operators.no_companies') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('all.highlink_isgc') }}
    </div>
</body>
</html>
