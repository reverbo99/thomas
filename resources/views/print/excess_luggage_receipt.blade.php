<!DOCTYPE html>
<html>

<head>
    <title>Excess Luggage Receipt</title>
    <style>
        @page {
            size: 80mm 200mm;
            margin: 2mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
            line-height: 1.15;
            margin: 0;
            padding: 1mm;
            width: 76mm;
        }

        .receipt-container {
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 1.5mm;
        }

        .header h2 {
            margin: 0 0 1mm;
            font-size: 12px;
        }

        .header p {
            margin: 0.3mm 0;
        }

        .details table {
            width: 100%;
            border-collapse: collapse;
        }

        .details table td {
            padding: 0.3mm 0;
            text-align: left;
            vertical-align: top;
        }

        .details h3 {
            margin: 1mm 0;
            font-size: 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 1mm 0;
        }

        .qr-row {
            width: 100%;
            border-collapse: collapse;
            margin: 1mm 0;
        }

        .qr-cell-single {
            width: 100%;
            text-align: center;
            vertical-align: top;
            padding: 0.5mm;
        }

        .qr-cell-single img {
            width: 24mm;
            height: 24mm;
            display: block;
            margin: 0 auto;
        }

        .footer {
            text-align: center;
            margin-top: 1.5mm;
        }

        .footer p {
            font-size: 7px;
            margin: 0;
        }
    </style>
</head>

<body>
    @php
        $estimatedWeight = $booking->estimated_weight;
        $actualWeight = $booking->actual_weight;
        $weightDiff = ($estimatedWeight !== null && $actualWeight !== null)
            ? round((float) $actualWeight - (float) $estimatedWeight, 2)
            : null;
        $weightDiffLabel = $weightDiff === null
            ? 'N/A'
            : ($weightDiff > 0
                ? 'Over estimate by ' . $weightDiff . ' kg'
                : ($weightDiff < 0
                    ? 'Under estimate by ' . abs($weightDiff) . ' kg'
                    : 'Exactly as estimated'));
    @endphp

    <div class="receipt-container">
        <div class="header">
            <h2>EXCESS LUGGAGE RECEIPT</h2>
            <p style="font-weight: bold; font-size: 11px;">{{ $busCompany->name ?? 'N/A' }}</p>
            <p>Reg. No: {{ $busOwnerAccount->registration_number ?? 'N/A' }}</p>
            <p>P. O. Box {{ $busOwnerAccount->box ?? 'N/A' }}</p>
            <p>{{ $busOwnerAccount->city ?? $busOwnerAccount->town ?? 'N/A' }}</p>
        </div>

        <div class="divider"></div>

        <div class="details">
            <table>
                <tr>
                    <td>Receipt No.</td>
                    <td>{{ $booking->booking_code ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>TIN:</td>
                    <td>{{ $busOwnerAccount->tin ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>VRN:</td>
                    <td>{{ $busOwnerAccount->vrn ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <table>
                <tr>
                    <td>Traveller name:</td>
                    <td>{{ $booking->customer_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Booking number:</td>
                    <td>{{ $booking->booking_code ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <h3>Luggage Details</h3>
            <p style="margin: 0 0 1mm; font-weight: bold;">Measurements</p>
            <table>
                <tr>
                    <td>Length:</td>
                    <td>{{ $booking->actual_length !== null ? number_format((float) $booking->actual_length, 2) . ' cm' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Height:</td>
                    <td>{{ $booking->actual_height !== null ? number_format((float) $booking->actual_height, 2) . ' cm' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Width:</td>
                    <td>{{ $booking->actual_width !== null ? number_format((float) $booking->actual_width, 2) . ' cm' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Actual weight</td>
                    <td>{{ $actualWeight !== null ? number_format((float) $actualWeight, 2) . ' kg' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Less Estimated weight</td>
                    <td>{{ $estimatedWeight !== null ? number_format((float) $estimatedWeight, 2) . ' kg' : 'N/A' }}</td>
                </tr>
            </table>
            <p style="margin: 1.5mm 0 0;">Over estimated/Under estimated amount:</p>
            <p style="margin: 0 0 1.5mm; font-weight: bold;">{{ $weightDiffLabel }}</p>

            <p style="margin: 1.5mm 0 0;">Refund/Payment:</p>
            <p style="margin: 0 0 1.5mm; font-weight: bold;">
                {{ $booking->luggage_refund_amount !== null ? number_format((float) $booking->luggage_refund_amount, 2) : 'N/A' }}
            </p>

            <table>
                <tr>
                    <td>Excess luggage fee paid:</td>
                    <td>{{ number_format((float) ($booking->excess_luggage_fee ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>{{ !empty($status) ? strtoupper(str_replace('_', ' ', $status)) : 'DECLARED' }}</td>
                </tr>
                @if(!empty(optional($booking->bus)->bus_number))
                <tr>
                    <td>Assigned bus:</td>
                    <td>{{ $booking->bus->bus_number }}</td>
                </tr>
                @endif
                @php
                    $receiptFrom = optional($booking->route)->from ?? optional(optional($booking->bus)->route)->from
                        ?? optional($booking->schedule)->from;
                    $receiptTo = optional($booking->route)->to ?? optional(optional($booking->bus)->route)->to
                        ?? optional($booking->schedule)->to;
                @endphp
                @if($receiptFrom || $receiptTo)
                <tr>
                    <td>From → To:</td>
                    <td>{{ $receiptFrom ?: '—' }} → {{ $receiptTo ?: '—' }}</td>
                </tr>
                @endif
            </table>
        </div>

        @if(!empty($booking->tra_status) || !empty($booking->tra_rct_num) || !empty($booking->tra_vnum) || !empty($booking->tra_z_num))
            <div class="divider"></div>
            <div class="details">
                <h3>TRA Verification</h3>
                <table>
                    <tr>
                        <td>TRA status:</td>
                        <td>{{ $booking->tra_status ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA Receipt No:</td>
                        <td>{{ $booking->tra_rct_num ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA VNUM:</td>
                        <td>{{ $booking->tra_vnum ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA Z Number:</td>
                        <td>{{ $booking->tra_z_num ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        @endif

        @if($traQrCode)
            <div class="divider"></div>
            <table class="qr-row">
                <tr>
                    <td class="qr-cell-single">
                        {!! $traQrCode !!}
                    </td>
                </tr>
            </table>
        @endif

        <div class="divider"></div>

        <div class="footer">
            <p>Thank you for your support. For any queries, please do not hesitate to contact us or our support team on</p>
            <p style="font-weight: bold;">0755 879 793 or support@hisgc.co.tz</p>
        </div>
    </div>
</body>

</html>
