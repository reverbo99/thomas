<!DOCTYPE html>
<html>
<head>
    <title>Vendor Transaction Receipt</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            line-height: 1.2;
            margin: 0;
            padding: 5mm;
            width: 80mm;
        }

        .receipt-container {
            width: 100%;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 2mm;
        }

        .details table {
            width: 100%;
            border-collapse: collapse;
        }

        .details table th,
        .details table td {
            padding: 1mm 0;
            text-align: left;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <h2>VENDOR TRANSACTION RECEIPT</h2>
            <p>Highlink Investigation and Security Guard Company Limited</p>
            <p>Registration No: 118308</p>
            <p>TIN: 156692506</p>
            <p>Mobile No: +255755879793</p>
        </div>

        <div class="divider"></div>

        <div class="details">
            <p>Vendor: {{ $data->user->name ?? 'N/A' }}</p>
            <p>Contact: {{ $data->user->contact ?? 'N/A' }}</p>
        </div>

        <div class="divider"></div>

        <div class="details">
            <table>
                <tr>
                    <td>Transaction ID:</td>
                    <td>{{ $data->id }}</td>
                </tr>
                <tr>
                    <td>Reference Number:</td>
                    <td>{{ $data->reference_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Amount:</td>
                    <td>{{ number_format($data->amount, 2) }} TZS</td>
                </tr>
                <tr>
                    <td>Payment Method:</td>
                    <td>{{ $data->payment_method ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Payment Details:</td>
                    <td>{{ transaction_payment_detail($data) }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>{{ $data->status ?? 'Pending' }}</td>
                </tr>
                <tr>
                    <td>Date and Time:</td>
                    <td>{{ Carbon\Carbon::parse($data->created_at)->format('Y-m-d h:i A') }}</td>
                </tr> 
            </table>
        </div>
 

        <div class="divider"></div>

        <div class="footer">
            <p>Thank you for your service.</p>
            <p>Issued on: {{ Carbon\Carbon::now()->format('Y-m-d h:i A') }}</p>
        </div>
    </div>
</body>
</html>