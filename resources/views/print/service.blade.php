<!DOCTYPE html>
<html>

<head>
    <title>Service Fee Receipt</title>
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
        }

        .receipt-container {
            width: 100%;
            page-break-inside: avoid;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 1.5mm;
        }

        .header h2 {
            margin: 0 0 1mm;
            font-size: 13px;
        }

        .header p {
            margin: 0.3mm 0;
        }

        .company-name {
            font-weight: bold;
            font-size: 15px;
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

        .details h3 {
            margin: 1mm 0;
            font-size: 10px;
        }

        .details .total-row td {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 1mm 0;
        }

        .qr-row {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
            margin: 1mm 0;
        }

        .qr-cell {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0.5mm;
        }

        .qr-cell-single {
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

        .qr-cell img,
        .qr-cell-single img {
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
        /* ---------------------------------------------------------------
         * All values are computed here (never inside a <td>) and every
         * access is null-safe: $data may be a full Booking model or a
         * partial stdClass payload from the history screens.
         * --------------------------------------------------------------- */
        $busCompany = null;
        $busOwnerAccount = null;

        if (isset($data->bus) && $data->bus) {
            if (isset($data->bus->campany) && $data->bus->campany) {
                $busCompany = $data->bus->campany;
                $busOwnerAccount = $busCompany->busOwnerAccount ?? null;
            } elseif (isset($data->bus->campany_id) && $data->bus->campany_id) {
                $busCompany = \App\Models\Campany::with('busOwnerAccount')->find($data->bus->campany_id);
                $busOwnerAccount = $busCompany->busOwnerAccount ?? null;
            }
        }

        if (!$busOwnerAccount && isset($data->campany)) {
            if (is_object($data->campany) && isset($data->campany->busOwnerAccount)) {
                $busCompany = $data->campany;
                $busOwnerAccount = $data->campany->busOwnerAccount;
            } elseif (is_object($data->campany) && isset($data->campany->id)) {
                $busCompany = \App\Models\Campany::with('busOwnerAccount')->find($data->campany->id);
                $busOwnerAccount = $busCompany->busOwnerAccount ?? null;
            }
        }

        $companyName = $busCompany->name ?? (data_get($data, 'campany.name') ?? 'N/A');

        // Date of issue: deterministic reprint date from created_at.
        $issuedLabel = receipt_issued_label($data);

        /* ---- Travel dates / times (same computation as print/ticket) ---- */
        $travelDateRaw = data_get($data, 'travel_date');
        try {
            $travelDateFormatted = $travelDateRaw ? \Carbon\Carbon::parse($travelDateRaw)->format('d M Y') : 'N/A';
        } catch (\Throwable $e) {
            $travelDateFormatted = 'N/A';
        }

        $departureTime = data_get($data, 'schedule.start');
        $arrivalTime = data_get($data, 'schedule.end');

        $reportingTimeStr = 'N/A';
        $departureTimeStr = 'N/A';
        $arrivalTimeStr = 'N/A';
        $arrivalDateStr = $travelDateFormatted;

        if ($travelDateRaw && $departureTime) {
            try {
                $departureDt = \Carbon\Carbon::parse($travelDateRaw . ' ' . $departureTime);
                $reportingTimeStr = $departureDt->copy()->subMinutes(30)->format('h:i A');
                $departureTimeStr = $departureDt->format('h:i A');
            } catch (\Throwable $e) {
                $reportingTimeStr = is_string($departureTime) ? $departureTime : 'N/A';
                $departureTimeStr = $reportingTimeStr;
            }
        }

        if ($travelDateRaw && $arrivalTime) {
            try {
                $arrivalDt = \Carbon\Carbon::parse($travelDateRaw . ' ' . $arrivalTime);
                $arrivalTimeStr = $arrivalDt->format('h:i A');
                if ($departureTime && $arrivalDt->format('H:i') < \Carbon\Carbon::parse($departureTime)->format('H:i')) {
                    $arrivalDt->addDay();
                    $arrivalTimeStr = $arrivalDt->format('h:i A');
                    $arrivalDateStr = $arrivalDt->format('d M Y');
                }
            } catch (\Throwable $e) {
                $arrivalTimeStr = is_string($arrivalTime) ? $arrivalTime : 'N/A';
            }
        }

        /* ---- Routes ---------------------------------------------------- */
        $busRouteFrom = data_get($data, 'bus.route.from') ?: (data_get($data, 'schedule.from') ?: 'N/A');
        $busRouteTo = data_get($data, 'bus.route.to') ?: (data_get($data, 'schedule.to') ?: 'N/A');
        $travellerRouteFrom = data_get($data, 'pickup_point') ?: (data_get($data, 'schedule.from') ?: 'N/A');
        $travellerRouteTo = data_get($data, 'dropping_point') ?: (data_get($data, 'schedule.to') ?: 'N/A');

        /* ---- Traveller ------------------------------------------------- */
        $travellerName = data_get($data, 'customer_name') ?: (data_get($data, 'user.name') ?: 'N/A');
        $travellerContact = data_get($data, 'customer_phone');
        if (empty($travellerContact) || $travellerContact === 'N/A') {
            $travellerContact = data_get($data, 'payment_number');
        }
        if (empty($travellerContact) || $travellerContact === 'N/A') {
            $travellerContact = data_get($data, 'user.contact');
        }
        $travellerContact = $travellerContact ?: 'N/A';

        $receiptNo = data_get($data, 'booking_code') ?: 'N/A';
        $seatNumber = data_get($data, 'seat') ?: 'N/A';

        /* ---- Money ----------------------------------------------------- */
        $amounts = booking_payment_amounts($data);
        $busFare = (float) $amounts['breakdownTicketFee'];
        $insuranceAmount = (float) $amounts['breakdownInsurance'];
        $luggageAmount = (float) $amounts['breakdownLuggageFee'];
        $totalPaid = (float) $amounts['breakdownAmountPaid'];
        // Keep the printed service line on the booked service fee, not the
        // residual of customer_paid_total (which may include luggage deposit).
        $serviceFeeAmount = booking_service_fee($data);
        // booking_government_levy_on_service() touches the relation when no gross
        // service fee is known; only call it for models/known fees so a partial
        // stdClass payload from system/history cannot blow the view up.
        $grossServiceFee = booking_gross_service_fee($data);
        $govLevyOnService = 0.0;
        if ($grossServiceFee > 0 || $data instanceof \Illuminate\Database\Eloquent\Model) {
            $govLevyOnService = booking_government_levy_on_service($data);
        }
        $govLevyPercentLabel = rtrim(rtrim(number_format(government_levy_percent(), 2, '.', ''), '0'), '.');
        $discountAmount = max(0.0, (float) data_get($data, 'discount_amount', 0));

        $showServiceFee = empty($hideServiceFee);
        $showLuggage = empty($hideLuggageAmount) && $luggageAmount > 0;

        // Total of the rows actually printed. The full variant reconciles with
        // customer_paid_total (bus fare + service fee + luggage + insurance);
        // the bus-owner variant prints a subtotal without the hidden service fee.
        $shownTotal = $busFare
            + ($showServiceFee ? $serviceFeeAmount : 0.0)
            + $insuranceAmount
            + ($showLuggage ? $luggageAmount : 0.0)
            - $discountAmount;

        /* ---- Payment --------------------------------------------------- */
        $paymentMethod = data_get($data, 'payment_method');
        $paymentStatus = data_get($data, 'payment_status');
        $transactionRef = data_get($data, 'transaction_ref_id');
        $showPaymentMethod = !empty($paymentMethod) && $paymentMethod !== 'N/A';
        $showPaymentStatus = !empty($paymentStatus) && $paymentStatus !== 'N/A';
        $showTransactionRef = !empty($transactionRef) && $transactionRef !== 'N/A';

        /* ---- TRA ------------------------------------------------------- */
        $hasTraData = !empty(data_get($data, 'tra_status'))
            || !empty(data_get($data, 'tra_rct_num'))
            || !empty(data_get($data, 'tra_vnum'))
            || !empty(data_get($data, 'tra_z_num'))
            || !empty(data_get($data, 'tra_qr_url'));

        $traQrCode = $traQrCode ?? null;
        $serviceQrCode = $serviceQrCode ?? null;
    @endphp

    <div class="receipt-container">
        <div class="header">
            <h2>SERVICE FEE RECEIPT</h2>
            <p class="company-name">{{ $companyName }}</p>
            <p>Registration number: {{ $busOwnerAccount->registration_number ?? 'N/A' }}</p>
            <p>P. O. Box {{ $busOwnerAccount->box ?? 'N/A' }}</p>
            <p>{{ $busOwnerAccount->city ?? ($busOwnerAccount->town ?? 'N/A') }}</p>
            <p>TIN: {{ $busOwnerAccount->tin ?? 'N/A' }}</p>
            <p>VRN: {{ $busOwnerAccount->vrn ?? 'N/A' }}</p>
        </div>

        <div class="divider"></div>

        <div class="details">
            <table>
                <tr>
                    <td>Date of issue:</td>
                    <td>{{ $issuedLabel }}</td>
                </tr>
                <tr>
                    <td>Receipt No.:</td>
                    <td>{{ $receiptNo }}</td>
                </tr>
                <tr>
                    <td>Traveller name:</td>
                    <td>{{ $travellerName }}</td>
                </tr>
                <tr>
                    <td>Traveller contact:</td>
                    <td>{{ $travellerContact }}</td>
                </tr>
                <tr>
                    <td>Booking number:</td>
                    <td>{{ $receiptNo }}</td>
                </tr>
                <tr>
                    <td>Bus number:</td>
                    <td>{{ data_get($data, 'bus.bus_number') ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Bus route:</td>
                    <td>{{ $busRouteFrom }} - {{ $busRouteTo }}</td>
                </tr>
                <tr>
                    <td>Traveller route:</td>
                    <td>{{ $travellerRouteFrom }} - {{ $travellerRouteTo }}</td>
                </tr>
                <tr>
                    <td>Travel date:</td>
                    <td>{{ $travelDateFormatted }}</td>
                </tr>
                <tr>
                    <td>Reporting date and time:</td>
                    <td>{{ $travelDateFormatted }} {{ $reportingTimeStr }}</td>
                </tr>
                <tr>
                    <td>Departure date and time:</td>
                    <td>{{ $travelDateFormatted }} {{ $departureTimeStr }}</td>
                </tr>
                <tr>
                    <td>Arrival date and time:</td>
                    <td>{{ $arrivalDateStr }} {{ $arrivalTimeStr }}</td>
                </tr>
                <tr>
                    <td>Seat number:</td>
                    <td>{{ $seatNumber }}</td>
                </tr>
                <tr>
                    <td>Conductor number:</td>
                    <td>{{ data_get($data, 'bus.conductor') ?: 'N/A' }}</td>
                </tr>
                @if (!empty(data_get($data, 'vender.name')))
                    <tr>
                        <td>Vendor name:</td>
                        <td>{{ data_get($data, 'vender.name') }}</td>
                    </tr>
                    <tr>
                        <td>Vendor contact:</td>
                        <td>{{ data_get($data, 'vender.contact') ?: 'N/A' }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <h3>Service Fee Details</h3>
            <table>
                <tr>
                    <td>Bus fare:</td>
                    <td>{{ number_format($busFare, 2) }}</td>
                </tr>
                @if ($showServiceFee)
                    <tr>
                        <td>Service fee:</td>
                        <td>{{ number_format($serviceFeeAmount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Govt service levy ({{ $govLevyPercentLabel }}%):</td>
                        <td>{{ number_format($govLevyOnService, 2) }}</td>
                    </tr>
                @endif
                @if ($insuranceAmount > 0)
                    <tr>
                        <td>Insurance amount:</td>
                        <td>{{ number_format($insuranceAmount, 2) }}</td>
                    </tr>
                @endif
                @if ($showLuggage)
                    <tr>
                        <td>Luggage amount:</td>
                        <td>{{ number_format($luggageAmount, 2) }}</td>
                    </tr>
                @endif
                @if ($discountAmount > 0)
                    <tr>
                        <td>Discount:</td>
                        <td>-{{ number_format($discountAmount, 2) }}</td>
                    </tr>
                @endif
                @if ($showServiceFee)
                    <tr class="total-row">
                        <td>Total paid:</td>
                        <td>{{ number_format($totalPaid, 2) }}</td>
                    </tr>
                @else
                    <tr class="total-row">
                        <td>Subtotal (excl. service):</td>
                        <td>{{ number_format($shownTotal, 2) }}</td>
                    </tr>
                @endif
                @if ($showPaymentMethod)
                    <tr>
                        <td>Payment method:</td>
                        <td>{{ $paymentMethod }}</td>
                    </tr>
                @endif
                @if ($showPaymentStatus)
                    <tr>
                        <td>Payment status:</td>
                        <td>{{ $paymentStatus }}</td>
                    </tr>
                @endif
                @if ($showTransactionRef)
                    <tr>
                        <td>Transaction ref:</td>
                        <td>{{ $transactionRef }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @if ($hasTraData)
            <div class="divider"></div>
            <div class="details">
                <h3>TRA Verification</h3>
                <table>
                    <tr>
                        <td>TRA Status:</td>
                        <td>{{ data_get($data, 'tra_status') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA Receipt No:</td>
                        <td>{{ data_get($data, 'tra_rct_num') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA VNUM:</td>
                        <td>{{ data_get($data, 'tra_vnum') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>TRA Z Number:</td>
                        <td>{{ data_get($data, 'tra_z_num') ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="divider"></div>

        <table class="qr-row">
            <tr>
                @if (!empty($traQrCode))
                    <td class="qr-cell">
                        <div class="qr-label">TRA Verification</div>
                        {!! $traQrCode !!}
                    </td>
                    <td class="qr-cell">
                        <div class="qr-label">Service QR</div>
                        {!! $serviceQrCode !!}
                    </td>
                @else
                    <td class="qr-cell-single">
                        <div class="qr-label">Service QR</div>
                        {!! $serviceQrCode !!}
                    </td>
                @endif
            </tr>
        </table>

        <div class="divider"></div>

        <div class="footer">
            <div class="container">
                <h6 class="text-muted">
                    Nunua ticket mtandaoni kwa usalama wa hali ya juu wakati wowote na bila usumbufu kwa
                    kutembelea www.hisgc.co.tz au piga <a href="tel:*149*46*36#">*149*46*36#</a> halafu
                    fuata maelekezo ya kununua ticket au piga <a href="tel:+255755879793">+255 755 879
                        793</a> kwa msaada zaidi. Highlink ISGC
                </h6>
            </div>
        </div>
    </div>
</body>

</html>
