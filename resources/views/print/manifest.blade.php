<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Manifest</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
        }

        .manifest-header {
            margin-bottom: 6px;
        }

        .manifest-header h1 {
            font-size: 11px;
            font-weight: 700;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .servicer-row {
            font-size: 7px;
            margin: 0 0 2px;
            line-height: 1.4;
        }

        .servicer-row span {
            margin-right: 12px;
        }

        .manifest-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .manifest-table th,
        .manifest-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.25;
        }

        .manifest-table thead th {
            background-color: #f2f2f2;
            font-weight: normal;
            text-align: center;
            font-size: 7px;
        }

        .manifest-table tbody td {
            font-size: 7px;
        }

        .manifest-table tr.staff-row td {
            background-color: #dbeafe;
            font-weight: 600;
            color: #1e40af;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .no-data {
            text-align: center;
            padding: 12px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="manifest-header">
        <h1>Passenger Manifest</h1>
        @if (isset($bus))
            <div class="servicer-row">
                <span><strong>Bus:</strong> {{ $bookings[0]['bus_number'] ?? 'N/A' }}</span>
                <span><strong>Driver:</strong> {{ $bus->driver_name ?? 'N/A' }} ({{ $bus->driver_contact ?? 'N/A' }})</span>
                <span><strong>Conductor:</strong> {{ $bus->conductor_name ?? 'N/A' }} ({{ $bus->conductor ?? 'N/A' }})</span>
                <span><strong>Date:</strong> {{ $bookings[0]['travel_date'] ?? now()->format('Y-m-d') }}</span>
            </div>
        @endif
    </div>

    <table class="manifest-table" aria-describedby="passengerManifest">
        <thead>
            <tr>
                <th style="width: 2%;">#</th>
                <th style="width: 5%;">Seat</th>
                <th style="width: 7%;">Route</th>
                <th style="width: 8%;">Name</th>
                <th style="width: 2%;">Sex</th>
                <th style="width: 7%;">Phone</th>
                <th style="width: 4%;">Type</th>
                <th style="width: 3%;">Infant</th>
                <th style="width: 4%;">ID Type</th>
                <th style="width: 5%;">Id no</th>
                <th style="width: 8%;">PNR</th>
                <th style="width: 6%;">Issue date</th>
                <th style="width: 5%;">Issue by</th>
                <th style="width: 6%;">From</th>
                <th style="width: 6%;">To</th>
                <th style="width: 5%;">Base Fare</th>
                <th style="width: 4%;">Discount</th>
                <th style="width: 5%;">Paid fare</th>
                <th style="width: 5%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @if (isset($bookings) && is_array($bookings) && count($bookings) > 0)
                @foreach ($bookings as $index => $booking)
                    <tr class="{{ !empty($booking['is_staff']) ? 'staff-row' : '' }}">
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $booking['seat'] ?? '' }}</td>
                        <td>{{ $booking['route_label'] ?? '' }}</td>
                        <td>{{ strtoupper($booking['customer_name'] ?? '') }}</td>
                        <td class="text-center">{{ $booking['gender_code'] ?? '' }}</td>
                        <td>{{ $booking['customer_phone'] ?? '' }}</td>
                        <td>{{ $booking['passenger_type'] ?? 'Adult' }}</td>
                        <td class="text-center">{{ !empty($booking['infant_child']) ? 'Yes' : 'No' }}</td>
                        <td>{{ $booking['id_type'] ?? '' }}</td>
                        <td>{{ $booking['id_number'] ?? '' }}</td>
                        <td>{{ $booking['booking_code'] ?? '' }}</td>
                        <td>{{ $booking['issue_date'] ?? '' }}</td>
                        <td>{{ $booking['issue_by'] ?? '' }}</td>
                        <td>{{ $booking['pickup_point'] ?? '' }}</td>
                        <td>{{ $booking['dropping_point'] ?? '' }}</td>
                        <td class="text-right">{{ $booking['base_fare'] ?? '0' }}</td>
                        <td class="text-right">{{ $booking['manifest_discount'] ?? '0' }}</td>
                        <td class="text-right">{{ $booking['paid_fare'] ?? '0' }}</td>
                        <td>{{ $booking['remarks'] ?? '' }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="19" class="no-data">No bookings available</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
