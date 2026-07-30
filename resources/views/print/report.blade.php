<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Report</title>
    <!-- Bootstrap CSS for consistent styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20mm;
            background-color: #fff;
            color: #333;
        }

        .report-container {
            max-width: 100%;
            background: #fff;
            padding: 20px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .report-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #007bff;
            margin: 0;
        }

        .report-header p {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 5px 0 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .table thead th {
            background-color: #007bff;
            color: #fff;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #0056b3;
        }

        .table tbody tr {
            transition: background-color 0.2s;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table tbody td {
            padding: 10px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        .text-xs {
            font-size: 0.8rem;
            margin: 0;
        }

        .text-sm {
            font-size: 0.9rem;
        }

        .font-weight-bold {
            font-weight: 600;
        }

        .text-secondary {
            color: #6c757d;
        }

        .amount {
            color: #28a745;
            font-weight: 700;
        }

        .booking-code {
            color: #343a40;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-style: italic;
        }

        @media print {
            body {
                padding: 10mm;
            }

            .report-container {
                padding: 0;
            }

            .table thead th {
                background-color: #0056b3;
                color: #fff;
            }

            .table tbody tr:nth-child(even) {
                background-color: #f8f9fa;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="report-container">
        <!-- Report Header -->
        <div class="report-header">
            <h1>Booking Report</h1>
            <p>Generated on {{ now()->format('F j, Y, g:i A') }}</p>
            <p>HIGHLINK ISGC</p>
        </div>

        <!-- Report Table -->
        <div class="table-responsive">
            <table class="table" aria-describedby="bookingReportTable">
                <thead>
                    <tr>
                        <th>SN</th>
                        <th scope="col">Booking ID</th>
                        <th scope="col">Bus/Route</th>
                        <th scope="col">Travel Details</th>
                        <th scope="col">Passenger</th>
                        <th scope="col">Fees</th>
                        <th scope="col">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($bookings) && is_array($bookings) && count($bookings) > 0)
                        @foreach ($bookings as $index => $booking)
                            <tr>
                                <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                                <td>
                                    <p class="text-xs booking-code">{{ $booking['booking_code'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-secondary">Confirmed</p>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <p class="text-sm font-weight-bold mb-0">{{ $booking['company_name'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-xs text-secondary mb-0">
                                            {{ $booking['route_from'] ?? 'N/A' }} to {{ $booking['route_to'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-xs text-secondary mb-0">{{ $booking['bus_number'] ?? 'N/A' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <p class="text-xs font-weight-bold mb-0">{{ $booking['travel_date'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-xs text-secondary mb-0">Seat: {{ $booking['seat'] ?? 'N/A' }}</p>
                                        <p class="text-xs text-secondary mb-0">Pickup:
                                            {{ $booking['pickup_point'] ?? 'N/A' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <p class="text-xs font-weight-bold mb-0">
                                            {{ $booking['customer_name'] ?? 'N/A' }}</p>
                                        <p class="text-xs text-secondary mb-0">
                                            {{ $booking['customer_phone'] ?? 'N/A' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <p class="text-xs mb-0"><span class="font-weight-bold">bus fare:</span> {{ $booking['bus_fee'] ?? ($booking['amount'] ?? 'N/A') }}</p>
                                        @if ((float) ($booking['luggage_fee'] ?? 0) > 0)
                                        <p class="text-xs mb-0"><span class="font-weight-bold">luggage:</span> {{ $booking['luggage_fee'] }}</p>
                                        @endif
                                        <p class="text-xs mb-0"><span class="font-weight-bold">service fee:</span> {{ $booking['service_fee'] ?? 'N/A' }}</p>
                                        <p class="text-xs mb-0"><span class="font-weight-bold">commission:</span> {{ $booking['commision'] ?? 'N/A' }}</p>
                                        <p class="text-xs mb-0"><span class="font-weight-bold">gov. levy:</span> {{ $booking['gov_levy'] ?? '0' }}</p>
                                        @if ((float) ($booking['gov_levy_service'] ?? 0) > 0)
                                        <p class="text-xs mb-0"><span class="font-weight-bold">gov. levy (service):</span> {{ $booking['gov_levy_service'] }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <p class="text-xs mb-0 font-weight-bold" style="color: rgb(43, 163, 43);">Total fee: {{ $booking['total'] ?? 'N/A' }}</p>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="no-data">No bookings available</td>
                        </tr>
                    @endif
                </tbody>
                @if (isset($bookings) && is_array($bookings) && count($bookings) > 0)
                    @php
                        $sumBusFare = collect($bookings)->sum(fn ($row) => (float) ($row['bus_fee'] ?? 0));
                        $sumLuggage = collect($bookings)->sum(fn ($row) => (float) ($row['luggage_fee'] ?? 0));
                        $sumService = collect($bookings)->sum(fn ($row) => (float) ($row['service_fee'] ?? 0));
                        $sumCommission = collect($bookings)->sum(fn ($row) => (float) ($row['commision'] ?? 0));
                        $sumGovLevy = collect($bookings)->sum(fn ($row) => (float) ($row['gov_levy'] ?? 0));
                        $sumTotal = collect($bookings)->sum(fn ($row) => (float) ($row['total'] ?? 0));
                    @endphp
                    <tfoot>
                        <tr>
                            <td colspan="5" class="font-weight-bold">Totals</td>
                            <td>
                                <p class="text-xs mb-0"><span class="font-weight-bold">bus fare:</span> {{ number_format($sumBusFare, 2) }}</p>
                                @if ($sumLuggage > 0)
                                    <p class="text-xs mb-0"><span class="font-weight-bold">luggage:</span> {{ number_format($sumLuggage, 2) }}</p>
                                @endif
                                <p class="text-xs mb-0"><span class="font-weight-bold">service fee:</span> {{ number_format($sumService, 2) }}</p>
                                <p class="text-xs mb-0"><span class="font-weight-bold">commission:</span> {{ number_format($sumCommission, 2) }}</p>
                                <p class="text-xs mb-0"><span class="font-weight-bold">gov. levy:</span> {{ number_format($sumGovLevy, 2) }}</p>
                            </td>
                            <td>
                                <p class="text-xs mb-0 font-weight-bold" style="color: rgb(43, 163, 43);">Total fee: {{ number_format($sumTotal, 2) }}</p>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</body>

</html>
