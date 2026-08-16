<!DOCTYPE html>
<html>

<head>
    <title>Parcel Receipt</title>
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
        $feePerKg = (float) (\App\Models\Setting::first()->parcel_fee_per_kg ?? 0);
        $isCollection = ($parcel->parcel_instructions ?? '') === 'collection';
        $instructionsLabel = $parcel->parcel_instructions === 'delivery' ? 'Delivery' : ($isCollection ? 'Collection' : 'N/A');
        $addressLabel = $isCollection ? 'Receiver collection address:' : 'Receiver delivery address:';
        $receiptFrom = optional(optional($parcel->bus)->route)->from
            ?? optional(optional($parcel->bus)->schedule)->from;
        $receiptTo = optional(optional($parcel->bus)->route)->to
            ?? optional(optional($parcel->bus)->schedule)->to;
    @endphp

    <div class="receipt-container">
        <div class="header">
            <h2>PARCEL RECEIPT</h2>
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
                    <td>{{ $parcel->parcel_number ?? 'N/A' }}</td>
                </tr>
                @if($receiptFrom || $receiptTo)
                <tr>
                    <td>From → To:</td>
                    <td>{{ $receiptFrom ?: '—' }} → {{ $receiptTo ?: '—' }}</td>
                </tr>
                @endif
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
            <h3>Sender Details</h3>
            <table>
                <tr>
                    <td>Sender name:</td>
                    <td>{{ $parcel->sender_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Sender contact:</td>
                    <td>{{ $parcel->sender_contact ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Parcel instructions:</td>
                    <td>{{ $instructionsLabel }}</td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <h3>Receiver Details</h3>
            <table>
                <tr>
                    <td>Receiver name:</td>
                    <td>{{ $parcel->receiver_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Receiver contact 1:</td>
                    <td>{{ $parcel->receiver_contact_1 ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Receiver contact 2:</td>
                    <td>{{ $parcel->receiver_contact_2 ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>{{ $addressLabel }}</td>
                    <td>{{ $parcel->receiver_delivery_address ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Parcel tracking number:</td>
                    <td>{{ $parcel->parcel_number ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <h3>Parcel Details</h3>
            <p style="margin: 0 0 1mm; font-weight: bold;">Measurements</p>
            <table>
                <tr>
                    <td>Actual weight:</td>
                    <td>{{ $parcel->weight !== null ? number_format((float) $parcel->weight, 2) . ' kg' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Actual length:</td>
                    <td>{{ $parcel->length !== null ? number_format((float) $parcel->length, 2) . ' cm' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Actual height:</td>
                    <td>{{ $parcel->height !== null ? number_format((float) $parcel->height, 2) . ' cm' : 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Actual width:</td>
                    <td>{{ $parcel->width !== null ? number_format((float) $parcel->width, 2) . ' cm' : 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <table>
                <tr>
                    <td>Parcel transport fee per kg:</td>
                    <td>{{ number_format($feePerKg, 2) }}</td>
                </tr>
                <tr>
                    <td>Parcel transport fee paid:</td>
                    <td>{{ number_format((float) ($parcel->amount_paid ?? 0), 2) }}</td>
                </tr>
            </table>
        </div>

        @if(!empty($parcel->tra_status) || !empty($parcel->tra_rct_num) || !empty($parcel->tra_vnum) || !empty($parcel->tra_z_num))
            <div class="divider"></div>
            <div class="details">
                <h3>TRA Verification</h3>
                <table>
                    <tr>
                        <td>TRA status:</td>
                        <td>{{ $parcel->tra_status ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA Receipt No:</td>
                        <td>{{ $parcel->tra_rct_num ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA VNUM:</td>
                        <td>{{ $parcel->tra_vnum ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA Z Number:</td>
                        <td>{{ $parcel->tra_z_num ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA Amount:</td>
                        <td>{{ number_format((float) ($parcel->amount_paid ?? 0), 2) }} TZS</td>
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
