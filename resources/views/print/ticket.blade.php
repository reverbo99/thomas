<!DOCTYPE html>
<html>

<head>
    <title>Bus Ticket</title>
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

        .ticket-container {
            width: 100%;
            page-break-inside: avoid;
        }

        .ticket-page-break {
            page-break-after: always;
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
        $seatList = booking_seat_list($data->seat ?? '');
        $seatCount = count($seatList);
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

        $travelDateRaw = $data->travel_date ?? null;
        $travelDateFormatted = $travelDateRaw ? \Carbon\Carbon::parse($travelDateRaw)->format('d M Y') : 'N/A';
        $insuranceDateFormatted = isset($data->insuranceDate) && $data->insuranceDate ? \Carbon\Carbon::parse($data->insuranceDate)->format('d M Y') : 'N/A';
        $travelDate = $travelDateRaw;
        $departureTime = null;
        $arrivalTime = null;

        if (isset($data->schedule) && $data->schedule) {
            $departureTime = $data->schedule->start ?? null;
            $arrivalTime = $data->schedule->end ?? null;
        }

        $reportingTimeStr = 'N/A';
        $departureTimeStr = 'N/A';
        $arrivalTimeStr = 'N/A';
        $arrivalDateStr = $travelDateFormatted;

        if ($travelDate && $departureTime) {
            try {
                $departureDt = \Carbon\Carbon::parse($travelDate . ' ' . $departureTime);
                $reportingTimeStr = $departureDt->copy()->subMinutes(30)->format('h:i A');
                $departureTimeStr = $departureDt->format('h:i A');
            } catch (\Exception $e) {
                $reportingTimeStr = is_string($departureTime) ? $departureTime : 'N/A';
                $departureTimeStr = $reportingTimeStr;
            }
        }

        if ($travelDate && $arrivalTime) {
            try {
                $arrivalDt = \Carbon\Carbon::parse($travelDate . ' ' . $arrivalTime);
                $arrivalTimeStr = $arrivalDt->format('h:i A');
                if ($departureTime && $arrivalDt->format('H:i') < \Carbon\Carbon::parse($departureTime)->format('H:i')) {
                    $arrivalDt->addDay();
                    $arrivalTimeStr = $arrivalDt->format('h:i A');
                    $arrivalDateStr = $arrivalDt->format('d M Y');
                }
            } catch (\Exception $e) {
                $arrivalTimeStr = is_string($arrivalTime) ? $arrivalTime : 'N/A';
            }
        }

        $contact = null;
        if (isset($data->customer_phone) && !empty($data->customer_phone) && $data->customer_phone != 'N/A') {
            $contact = $data->customer_phone;
        }
        if (!$contact && isset($data->payment_number) && !empty($data->payment_number) && $data->payment_number != 'N/A') {
            $contact = $data->payment_number;
        }
        if (!$contact && isset($data->user) && $data->user && isset($data->user->contact) && !empty($data->user->contact)) {
            $contact = $data->user->contact;
        }
    @endphp

        @foreach ($seatList as $seatIndex => $printSeat)
        @php
            extract(booking_per_seat_payment_amounts($data, $seatIndex, $seatCount));
            $printPassengerName = booking_passenger_name_for_seat($data, $seatIndex, $printSeat);
            $printPassengerPhone = booking_passenger_phone_for_seat($data, $seatIndex, $printSeat);
            $seatQrPayload = trim(($data->booking_code ?? 'N/A') . '|' . $printSeat, '|');
            // PNG + fixed img size keeps TRA and Ticket QRs identical (HTML table QRs scale with data length)
            $seatQrPng = DNS2D::getBarcodePNG($seatQrPayload, 'QRCODE', 4, 4, [0, 0, 0]);
            $seatQrCode = $seatQrPng
                ? '<img src="data:image/png;base64,' . $seatQrPng . '" alt="Ticket QR" width="68" height="68">'
                : '';
            $hasTraQr = !empty($data->tra_qr_url);
            $traQrCode = null;
            if ($hasTraQr) {
                $traQrPng = DNS2D::getBarcodePNG($data->tra_qr_url, 'QRCODE', 4, 4, [0, 0, 0]);
                $traQrCode = $traQrPng
                    ? '<img src="data:image/png;base64,' . $traQrPng . '" alt="TRA QR" width="68" height="68">'
                    : null;
                $hasTraQr = !empty($traQrCode);
            }
        @endphp

        <div class="ticket-container{{ $seatIndex < ($seatCount - 1) ? ' ticket-page-break' : '' }}">
            <div class="header">
                <h2>BUS TICKET</h2>
                @if ($seatCount > 1)
                    <p style="font-size: 11px;">Seat {{ $seatIndex + 1 }} of {{ $seatCount }}</p>
                @endif
                <p style="font-weight: bold; font-size: 15px;">{{ $busCompany->name ?? ($data->campany->name ?? 'N/A') }}</p>
                <p>Registration number: {{ $busOwnerAccount->registration_number ?? 'N/A' }}</p>
                <p>P. O. Box {{ $busOwnerAccount->box ?? 'N/A' }}</p>
                <p>{{ $busOwnerAccount->city ?? $busOwnerAccount->town ?? 'N/A' }}</p>
            </div>

            <div class="divider"></div>

            <div class="details">
                <table>
                    <tr>
                        <td>Passenger name:</td>
                        <td>{{ $printPassengerName }}</td>
                    </tr>
                    <tr>
                        <td>Passenger contact:</td>
                        <td>{{ $printPassengerPhone !== 'N/A' ? $printPassengerPhone : ($contact ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td>Booking number:</td>
                        <td>{{ $data->booking_code ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Bus number:</td>
                        <td>{{ $data->bus->bus_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Bus route:</td>
                        <td>{{ optional(optional($data->bus)->route)->from ?? optional($data->schedule)->from ?? 'N/A' }} - {{ optional(optional($data->bus)->route)->to ?? optional($data->schedule)->to ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Passenger route:</td>
                        <td>{{ $data->pickup_point ?? optional($data->schedule)->from ?? 'N/A' }} - {{ $data->dropping_point ?? optional($data->schedule)->to ?? 'N/A' }}</td>
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
                        <td>{{ $printSeat }}</td>
                    </tr>
                    <tr>
                        <td>Bus fare:</td>
                        <td>{{ number_format($breakdownTicketFee, 2) }}</td>
                    </tr>
                </table>
            </div>

            <div class="divider"></div>

            @if ($data->bima == 1)
            <div class="details">
                <h3>Insurance Details</h3>
                @php
                    $insAmountForType = (float) $breakdownInsurance;
                    $insSetting = \App\Models\Setting::first();
                    $localRate = (float) ($insSetting->local ?? 0);
                    $foreignRate = (float) ($insSetting->international ?? 0);

                    $insDays = 1;
                    if (!empty($data->travel_date) && !empty($data->insuranceDate)) {
                        try {
                            $travelDt = \Carbon\Carbon::parse($data->travel_date);
                            $insExpiryDt = \Carbon\Carbon::parse($data->insuranceDate);
                            $insDays = max(1, abs($travelDt->diffInDays($insExpiryDt)) + 1);
                        } catch (\Exception $e) {
                            $insDays = 1;
                        }
                    }

                    $perDayPaid = $insDays > 0 ? $insAmountForType / $insDays : $insAmountForType;
                    $isForeignInsurance = false;
                    if ($foreignRate > 0 || $localRate > 0) {
                        $isForeignInsurance = abs($perDayPaid - $foreignRate) < abs($perDayPaid - $localRate);
                    }

                    $insuranceTypeLabel = $isForeignInsurance ? 'Foreign' : 'Local';
                    $insuranceCompanyLabel = $insSetting->insurance_company ?? 'G.A Insurance';
                    $insurancePolicyLabel = $isForeignInsurance
                        ? ($insSetting->insurance_policy_foreign ?? 'Safiri salama - Foreign')
                        : ($insSetting->insurance_policy_local ?? 'Safiri salama - Domestic');
                @endphp
                <table>
                    <tr>
                        <td>Insurance company:</td>
                        <td>{{ $insuranceCompanyLabel }}</td>
                    </tr>
                    <tr>
                        <td>Insurance type:</td>
                        <td>{{ $insuranceTypeLabel }}</td>
                    </tr>
                    <tr>
                        <td>Policy:</td>
                        <td>{{ $insurancePolicyLabel }}</td>
                    </tr>
                    <tr>
                        <td>Date and time of issue:</td>
                        <td>{{ $travelDateFormatted }}</td>
                    </tr>
                    <tr>
                        <td>Expire date and time:</td>
                        <td>{{ $insuranceDateFormatted }}</td>
                    </tr>
                    <tr>
                        <td>Amount paid for insurance:</td>
                        <td>{{ number_format($breakdownInsurance, 2) }}</td>
                    </tr>
                </table>
            </div>

            <div class="divider"></div>
            @endif

            <div class="details">
                <table>
                    <tr>
                        <td>Conductor number:</td>
                        <td>{{ $data->bus->conductor ?? 'N/A' }}</td>
                    </tr>
                    @if ($data->vender_id)
                        <tr>
                            <td>Vendor name:</td>
                            <td>{{ $data->vender->name }}</td>
                        </tr>
                        <tr>
                            <td>Vendor contact number:</td>
                            <td>{{ $data->vender->contact }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            @if ($data->has_excess_luggage ?? false)
                @php
                    $ticketLuggageFee = (float) ($data->excess_luggage_fee ?? 0);
                    $ticketEstimatedWeight = $data->estimated_weight ?? null;
                    $ticketActualWeight = $data->actual_weight ?? null;
                    $ticketFeePerKg = excess_luggage_fee_per_kg();
                @endphp
                <div class="divider"></div>
                <div class="details">
                    <table>
                        <tr>
                            <td>Excess luggage:</td>
                            <td>Yes</td>
                        </tr>
                        @if ($ticketEstimatedWeight !== null || $ticketActualWeight !== null)
                        <tr>
                            <td>Weight:</td>
                            <td>{{ $ticketActualWeight !== null ? number_format((float) $ticketActualWeight, 2) . ' kg' : number_format((float) $ticketEstimatedWeight, 2) . ' kg (est.)' }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>Excess luggage fee:</td>
                            <td>{{ number_format($ticketLuggageFee, 2) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            @if(!empty($data->tra_status) || !empty($data->tra_rct_num) || !empty($data->tra_vnum) || !empty($data->tra_qr_url))
                <div class="divider"></div>
                <div class="details">
                    <h3>TRA Verification</h3>
                    <table>
                        <tr>
                            <td>TRA Status:</td>
                            <td>{{ $data->tra_status ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>TRA Receipt No:</td>
                            <td>{{ $data->tra_rct_num ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>TRA VNUM:</td>
                            <td>{{ $data->tra_vnum ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>TRA Z Number:</td>
                            <td>{{ $data->tra_z_num ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            <div class="divider"></div>

            <table class="qr-row">
                <tr>
                    @if ($hasTraQr)
                        <td class="qr-cell">
                            <div class="qr-label">TRA Verification</div>
                            {!! $traQrCode !!}
                        </td>
                        <td class="qr-cell">
                            <div class="qr-label">Ticket QR</div>
                            {!! $seatQrCode !!}
                        </td>
                    @else
                        <td class="qr-cell-single">
                            <div class="qr-label">Ticket QR</div>
                            {!! $seatQrCode !!}
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
    @endforeach
</body>

</html>
