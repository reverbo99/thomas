<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Parcel Manifest</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>{{ __('vender/parcels.manifest_title') }}</h1>
    <p>{{ $company->name ?? 'All companies' }} · {{ now()->format('Y-m-d H:i') }}</p>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tracking</th>
                <th>Bus</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Weight</th>
                <th>Instr.</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parcels as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->parcel_number }}</td>
                    <td>{{ $p->bus->bus_number ?? '—' }}</td>
                    <td>{{ $p->sender_name }}</td>
                    <td>{{ $p->receiver_name }}</td>
                    <td>{{ $p->weight ?? '—' }}</td>
                    <td>{{ $p->parcel_instructions }}</td>
                    <td>{{ $p->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
