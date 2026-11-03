<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ __('system.pages.customer_receipt') }} — {{ $hireOrder->order_code }}</title>
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
            color: #000;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 1.5mm;
        }

        .header h2 {
            margin: 0 0 1mm;
            font-size: 13px;
            text-transform: uppercase;
        }

        .header p {
            margin: 0.3mm 0;
        }

        .details table {
            width: 100%;
            border-collapse: collapse;
        }

        .details table th,
        .details table td {
            padding: 0.3mm 0;
            text-align: left;
            vertical-align: top;
        }

        .details table td.value {
            text-align: right;
        }

        .details h3 {
            margin: 1mm 0;
            font-size: 10px;
            text-transform: uppercase;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 1mm 0;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 1mm;
        }

        .qr-cell {
            width: 100%;
            text-align: center;
            vertical-align: top;
            padding: 0.5mm;
        }

        .qr-label {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 0.5mm;
        }

        .qr-cell img {
            width: 24mm;
            height: 24mm;
            display: block;
            margin: 0 auto;
        }

        .footer h6 {
            font-size: 7px;
            margin: 0;
            font-weight: normal;
        }
    </style>
</head>

<body>
    @php
        $receiptQr = receipt_qr_png_image((string) $hireOrder->order_code, 'Receipt QR', 68, 4);
        $hireDate = $hireOrder->hire_date ? $hireOrder->hire_date->format('d M Y') : 'N/A';
        $returnDate = $hireOrder->return_date ? $hireOrder->return_date->format('d M Y') : null;
    @endphp

    <div class="header">
        <h2>{{ __('system.pages.customer_receipt') }}</h2>
        <p style="font-weight: bold; font-size: 12px;">{{ __('all.highlink_isgc') }}</p>
        <p>Printed on: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="divider"></div>

    <div class="details">
        <table>
            <tr>
                <td>Order number:</td>
                <td>{{ $hireOrder->order_code }}</td>
            </tr>
            <tr>
                <td>Customer:</td>
                <td>{{ $hireOrder->customer_name }}</td>
            </tr>
            <tr>
                <td>Phone:</td>
                <td>{{ $hireOrder->customer_phone }}</td>
            </tr>
            @if($hireOrder->customer_email)
            <tr>
                <td>Email:</td>
                <td>{{ $hireOrder->customer_email }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="divider"></div>

    <div class="details">
        <h3>{{ __('system.pages.hire_summary') }}</h3>
        <table>
            <tr>
                <td>Operator:</td>
                <td>{{ $hireOrder->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Coaster:</td>
                <td>{{ $hireOrder->coaster->name ?? 'N/A' }} ({{ $hireOrder->coaster->plate_number ?? 'N/A' }})</td>
            </tr>
            <tr>
                <td>Route:</td>
                <td>{{ $hireOrder->pickup_location }} - {{ $hireOrder->dropoff_location }}</td>
            </tr>
            <tr>
                <td>Hire date:</td>
                <td>{{ $hireDate }} {{ $hireOrder->hire_time }}</td>
            </tr>
            @if($returnDate)
            <tr>
                <td>Return date:</td>
                <td>{{ $returnDate }} {{ $hireOrder->return_time }}</td>
            </tr>
            @endif
            <tr>
                <td>Seats:</td>
                <td>{{ $hireOrder->passengers_count }}</td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <div class="details">
        <h3>{{ __('system.common.amount') }}</h3>
        <table>
            <tr>
                <td>Base fare:</td>
                <td class="value">{{ number_format((float) $hireOrder->base_price, 2) }}</td>
            </tr>
            <tr>
                <td>Distance ({{ $hireOrder->distance_km }} km):</td>
                <td class="value">{{ number_format((float) $hireOrder->km_amount, 2) }}</td>
            </tr>
            @if((float) $hireOrder->surcharge_amount > 0)
            <tr>
                <td>Surcharge ({{ $hireOrder->surcharge_percent }}%):</td>
                <td class="value">{{ number_format((float) $hireOrder->surcharge_amount, 2) }}</td>
            </tr>
            @endif
            @if($hireOrder->deposit_amount !== null)
            <tr>
                <td>Paid:</td>
                <td class="value">{{ number_format((float) $hireOrder->deposit_amount, 2) }}</td>
            </tr>
            @endif
            @if((float) $hireOrder->balance_amount > 0)
            <tr>
                <td>Balance:</td>
                <td class="value">{{ number_format((float) $hireOrder->balance_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td>Payment method:</td>
                <td class="value">{{ $hireOrder->payment_method ?? 'N/A' }}</td>
            </tr>
            <tr class="total-row">
                <td>Total paid:</td>
                <td class="value">{{ number_format((float) $hireOrder->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="qr-cell">
                <div class="qr-label">Receipt QR</div>
                @if($receiptQr)
                    {!! $receiptQr !!}
                @endif
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer">
        <h6>
            Asante kwa kuchagua {{ __('all.highlink_isgc') }}. Kwa msaada piga
            +255 755 879 793 au tembelea www.hisgc.co.tz
        </h6>
    </div>
</body>

</html>
