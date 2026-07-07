<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('customer/myticket.ticket_report') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
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
        }
    </style>
</head>

<body>
    <div class="report-container">
        <div class="report-header">
            <h1>{{ __('customer/myticket.ticket_report') }}</h1>
            <p>{{ __('customer/myticket.report_for') }}: {{ $customerName ?? 'Customer' }}</p>
            <p>{{ __('customer/myticket.generated_on') }} {{ ($generatedAt ?? now())->format('F j, Y, g:i A') }}</p>
            <p>HIGHLINK ISGC</p>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('customer/myticket.no') }}</th>
                        <th>{{ __('customer/myticket.booking_id') }}</th>
                        <th>{{ __('customer/myticket.bus_name') }}</th>
                        <th>{{ __('customer/myticket.departure_date') }}</th>
                        <th>{{ __('customer/myticket.passenger') }}</th>
                        <th>{{ __('customer/busroot.price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($bookings))
                        @foreach ($bookings as $index => $booking)
                            <tr>
                                <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
                                <td>
                                    <p class="text-xs booking-code">{{ $booking['booking_code'] ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-sm font-weight-bold mb-0">{{ $booking['company_name'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-secondary mb-0">
                                        {{ $booking['route_from'] ?? 'N/A' }} → {{ $booking['route_to'] ?? 'N/A' }}
                                    </p>
                                    <p class="text-xs text-secondary mb-0">{{ $booking['bus_number'] ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $booking['travel_date'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-secondary mb-0">{{ __('customer/myticket.seat') }}: {{ $booking['seat'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-secondary mb-0">{{ $booking['pickup_point'] ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs font-weight-bold mb-0">{{ $booking['customer_name'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-secondary mb-0">{{ $booking['customer_phone'] ?? 'N/A' }}</p>
                                </td>
                                <td>
                                    <p class="text-xs mb-0"><span class="font-weight-bold">{{ __('customer/myticket.bus_fare') }}:</span> {{ $booking['bus_fee'] ?? ($booking['amount'] ?? 'N/A') }}</p>
                                    @if ((float) ($booking['luggage_fee'] ?? 0) > 0)
                                        <p class="text-xs mb-0"><span class="font-weight-bold">{{ __('customer/myticket.luggage') }}:</span> {{ $booking['luggage_fee'] }}</p>
                                    @endif
                                    @if ((float) ($booking['service_fee'] ?? 0) > 0)
                                        <p class="text-xs mb-0"><span class="font-weight-bold">{{ __('customer/myticket.service_fee') }}:</span> {{ $booking['service_fee'] }}</p>
                                    @endif
                                    <p class="text-xs mb-0 font-weight-bold" style="color: rgb(43, 163, 43);">{{ __('customer/myticket.total_paid') }}: {{ $booking['total'] ?? 'N/A' }}</p>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="no-data">{{ __('customer/myticket.no_booking_found') }}</td>
                        </tr>
                    @endif
                </tbody>
                @if (!empty($bookings))
                    @php
                        $sumBusFare = collect($bookings)->sum(fn ($row) => (float) ($row['bus_fee'] ?? 0));
                        $sumLuggage = collect($bookings)->sum(fn ($row) => (float) ($row['luggage_fee'] ?? 0));
                        $sumService = collect($bookings)->sum(fn ($row) => (float) ($row['service_fee'] ?? 0));
                        $sumTotal = collect($bookings)->sum(fn ($row) => (float) ($row['total'] ?? 0));
                    @endphp
                    <tfoot>
                        <tr>
                            <td colspan="5" class="font-weight-bold">{{ __('customer/myticket.totals') }}</td>
                            <td>
                                <p class="text-xs mb-0"><span class="font-weight-bold">{{ __('customer/myticket.bus_fare') }}:</span> {{ number_format($sumBusFare, 2) }}</p>
                                @if ($sumLuggage > 0)
                                    <p class="text-xs mb-0"><span class="font-weight-bold">{{ __('customer/myticket.luggage') }}:</span> {{ number_format($sumLuggage, 2) }}</p>
                                @endif
                                @if ($sumService > 0)
                                    <p class="text-xs mb-0"><span class="font-weight-bold">{{ __('customer/myticket.service_fee') }}:</span> {{ number_format($sumService, 2) }}</p>
                                @endif
                                <p class="text-xs mb-0 font-weight-bold" style="color: rgb(43, 163, 43);">{{ __('customer/myticket.total_paid') }}: {{ number_format($sumTotal, 2) }}</p>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</body>

</html>
