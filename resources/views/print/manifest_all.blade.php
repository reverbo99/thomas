<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('system.pages.print_all_manifest') }}</title>
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
            font-size: 8px;
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
        }

        .manifest-section {
            page-break-after: always;
        }

        .manifest-section:last-child {
            page-break-after: auto;
        }

        .manifest-header {
            margin-bottom: 6px;
        }

        .manifest-header h1 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .servicer-row {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            margin: 0 0 2px;
            line-height: 1.45;
        }

        .servicer-row span {
            margin-right: 12px;
        }

        .manifest-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-family: Arial, Helvetica, sans-serif;
        }

        .manifest-table th,
        .manifest-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            line-height: 1.25;
            font-family: Arial, Helvetica, sans-serif;
        }

        .manifest-table thead th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            font-size: 7.5px;
        }

        .manifest-table tbody td {
            font-size: 8px;
        }

        .manifest-table tr.staff-row td {
            background-color: #dbeafe;
            font-weight: 600;
            color: #1e40af;
        }

        .manifest-table tr.infant-row td {
            background-color: #fef3c7;
            font-weight: 600;
            color: #92400e;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    @foreach ($sections as $section)
        @php
            $bus = $section['bus'];
            $bookings = $section['bookings'];
            $metaRow = collect($bookings)->first(fn ($row) => empty($row['is_staff'])) ?? ($bookings[0] ?? []);
            $companyName = $metaRow['company_name']
                ?? optional(optional($bus)->campany)->name
                ?? 'N/A';
            $routeLabel = $metaRow['route_label'] ?? 'N/A';
            $departureAt = $metaRow['departure_datetime'] ?? ($metaRow['travel_date'] ?? now()->format('Y-m-d'));
            $arrivalAt = $metaRow['arrival_datetime'] ?? ($metaRow['travel_date'] ?? 'N/A');
            $busNumber = $metaRow['bus_number'] ?? ($bus->bus_number ?? 'N/A');
        @endphp
        <div class="manifest-section">
            <div class="manifest-header">
                <h1>{{ $companyName }}, {{ __('vender/history.passenger_list') }}</h1>
                <div class="servicer-row">
                    <span><strong>{{ __('vender/history.manifest_route') }}:</strong> {{ $routeLabel }}</span>
                    <span><strong>{{ __('vender/history.manifest_bus') }}:</strong> {{ $busNumber }}</span>
                    <span><strong>{{ __('vender/history.manifest_driver') }}:</strong> {{ $bus->driver_name ?? 'N/A' }} ({{ $bus->driver_contact ?? 'N/A' }})</span>
                    <span><strong>{{ __('vender/history.manifest_conductor') }}:</strong> {{ $bus->conductor_name ?? 'N/A' }} ({{ $bus->conductor ?? 'N/A' }})</span>
                </div>
                <div class="servicer-row">
                    <span><strong>{{ __('vender/history.departure_datetime') }}:</strong> {{ $departureAt }}</span>
                    <span><strong>{{ __('vender/history.arrival_datetime') }}:</strong> {{ $arrivalAt }}</span>
                </div>
            </div>

            <table class="manifest-table">
                <thead>
                    <tr>
                        <th style="width: 2%;">#</th>
                        <th style="width: 5%;">{{ __('vender/history.manifest_seat') }}</th>
                        <th style="width: 9%;">{{ __('vender/history.manifest_name') }}</th>
                        <th style="width: 2%;">{{ __('vender/history.manifest_sex') }}</th>
                        <th style="width: 7%;">{{ __('vender/history.manifest_phone') }}</th>
                        <th style="width: 4%;">{{ __('vender/history.manifest_type') }}</th>
                        <th style="width: 4%;">{{ __('vender/history.manifest_infant') }}</th>
                        <th style="width: 4%;">{{ __('vender/history.manifest_id_type') }}</th>
                        <th style="width: 5%;">{{ __('vender/history.manifest_id_no') }}</th>
                        <th style="width: 8%;">{{ __('vender/history.manifest_pnr') }}</th>
                        <th style="width: 6%;">{{ __('vender/history.manifest_issue_date') }}</th>
                        <th style="width: 5%;">{{ __('vender/history.manifest_issue_by') }}</th>
                        <th style="width: 7%;">{{ __('vender/history.pickup_point') }}</th>
                        <th style="width: 7%;">{{ __('vender/history.dropping_point') }}</th>
                        <th style="width: 5%;">{{ __('vender/history.manifest_base_fare') }}</th>
                        <th style="width: 4%;">{{ __('vender/history.manifest_discount') }}</th>
                        <th style="width: 5%;">{{ __('vender/history.manifest_paid_fare') }}</th>
                        <th style="width: 5%;">{{ __('vender/history.manifest_remarks') }}</th>
                    </tr>
                </thead>
                <tbody>
@foreach ($bookings as $index => $booking)
                    @php
                        $infantCount = !empty($booking['is_infant_companion'])
                            ? 1
                            : (int) ($booking['infant_child'] ?? 0);
                        $rowClass = !empty($booking['is_staff'])
                            ? 'staff-row'
                            : (($infantCount > 0 || !empty($booking['is_infant_companion'])) ? 'infant-row' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $booking['seat'] ?? '' }}</td>
                        <td>{{ strtoupper($booking['customer_name'] ?? '') }}</td>
                        <td class="text-center">{{ $booking['gender_code'] ?? '' }}</td>
                        <td>{{ $booking['customer_phone'] ?? '' }}</td>
                        <td>{{ $booking['passenger_type'] ?? 'Adult' }}</td>
                        <td class="text-center">{{ $infantCount }}</td>
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
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
