<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ __('system.pages.commission_receipt') }} — {{ $hireOrder->order_code }}</title>
    <style>
        @page {
            size: 80mm 180mm;
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
        $receiptQr = receipt_qr_png_image((string) $hireOrder->order_code, 'Commission QR', 68, 4);
        $hireDate = $hireOrder->hire_date ? $hireOrder->hire_date->format('d M Y') : 'N/A';
        $total = (float) $hireOrder->total_amount;
        $commission = (float) $hireOrder->platform_commission_amount;
        $operatorReceives = max(0, $total - $commission);
    @endphp

    <div class="header">
        <h2>{{ __('system.pages.commission_receipt') }}</h2>
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
                <td>Operator:</td>
                <td>{{ $hireOrder->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Coaster:</td>
                <td>{{ $hireOrder->coaster->name ?? 'N/A' }} ({{ $hireOrder->coaster->plate_number ?? 'N/A' }})</td>
            </tr>
            <tr>
                <td>Customer:</td>
                <td>{{ $hireOrder->customer_name }}</td>
            </tr>
            <tr>
                <td>Hire date:</td>
                <td>{{ $hireDate }} {{ $hireOrder->hire_time }}</td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <div class="details">
        <h3>{{ __('system.common.amount') }}</h3>
        <table>
            <tr>
                <td>Total paid:</td>
                <td class="value">{{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td>Commission percent:</td>
                <td class="value">{{ number_format((float) $hireOrder->platform_commission_percent, 2) }}%</td>
            </tr>
            <tr>
                <td>Commission amount:</td>
                <td class="value">{{ number_format($commission, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>Operator receives:</td>
                <td class="value">{{ number_format($operatorReceives, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="qr-cell">
                <div class="qr-label">Commission QR</div>
                @if($receiptQr)
                    {!! $receiptQr !!}
                @endif
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer">
        <h6>
            {{ __('all.highlink_isgc') }} — commission statement. Kwa msaada piga
            +255 755 879 793.
        </h6>
    </div>
</body>

</html>
