<!DOCTYPE html>
<html>
<head>
    <title>{{ __('system.pages.print_buses_title') }}</title>
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
        <h1>{{ __('system.pages.print_buses_title') }}</h1>
        <p>{{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('system.common.no') }}</th>
                <th>{{ __('system.common.company') }}</th>
                <th>{{ __('system.pages.bus_details') }}</th>
                <th>{{ __('system.pages.plate_number') }}</th>
                <th>{{ __('system.common.route') }}</th>
                <th>{{ __('all.seats') }}</th>
                <th>{{ __('system.pages.conductor') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($buses as $key => $bus)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $bus->campany->name ?? 'N/A' }}</td>
                    <td>{{ $bus->busname->name ?? 'N/A' }}</td>
                    <td>{{ $bus->bus_number ?? 'N/A' }}</td>
                    <td>{{ $bus->route->from ?? 'N/A' }} to {{ $bus->route->to ?? 'N/A' }}</td>
                    <td>{{ $bus->total_seats ?? 'N/A' }}</td>
                    <td>{{ $bus->conductor ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">{{ __('system.pages.no_buses') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('all.highlink_isgc') }}
    </div>
</body>
</html>
