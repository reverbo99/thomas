<!DOCTYPE html>
<html>

<head>
    <title>Bus Ticket</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            line-height: 1.2;
            margin: 0;
            padding: 5mm;
            width: 80mm;
        }

        .ticket-container {
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

        .qr-code-container {
            text-align: center;
            /* Center the QR code */
            margin-left: 20%;
            height: 3.2cm;
        }
    </style>
</head>

<body>
    <div class="ticket-container">
        <div class="header">
            <h2>HIGHLINK ISGC</h2>
            @php
                $busOwnerAccount = null;
                $busCompany = null;
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
            @endphp
            <p>{{ $busCompany->name ?? ($data->campany->name ?? 'N/A') }}</p>
            <p>P. O. Box {{ $busOwnerAccount->box ?? 'N/A' }}</p>
            <p>{{ $busOwnerAccount->region ?? 'N/A' }},
                {{ $busOwnerAccount->country ?? 'N/A' }}</p>
            <p>Reg. No: {{ $busOwnerAccount->registration_number ?? 'N/A' }}</p>
            <p>TIN: {{ $busOwnerAccount->tin ?? 'N/A' }}</p>
            <p>VRN: {{ $busOwnerAccount->vrn ?? 'N/A' }}</p>
        </div>

        <div class="divider"></div>

        <div class="details">
            <table>
                <tr>
                    <td>Traveller Name:</td>
                    <td>{{ $data->customer_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Traveller Contact:</td>
                    <td>{{ $data->customer_phone ?? 'N/A' }}</td>
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
                    <td>
                        @if(isset($data->schedule) && $data->schedule)
                            {{ ($data->schedule->from ?? 'N/A') }} - {{ ($data->schedule->to ?? 'N/A') }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Traveller route:</td>
                    <td>
                        @if(isset($data->schedule) && $data->schedule)
                            {{ ($data->schedule->from ?? 'N/A') }} - {{ ($data->schedule->to ?? 'N/A') }}
                        @else
                            {{ $data->pickup_point ?? 'N/A' }} - {{ $data->dropping_point ?? 'N/A' }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Travel date:</td>
                    <td>{{ $data->travel_date ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Reporting time:</td>
                    <td>
                        @php
                            $departureTime = null;
                            if (isset($data->schedule) && $data->schedule) {
                                $departureTime = $data->schedule->start ?? null;
                            }
                            $reportingTimeStr = 'N/A';
                            if ($data->travel_date && $departureTime) {
                                try {
                                    $departureDt = \Carbon\Carbon::parse($data->travel_date . ' ' . $departureTime);
                                    $reportingTimeStr = $departureDt->copy()->subMinutes(30)->format('h:i A');
                                } catch (\Exception $e) {
                                    $reportingTimeStr = is_string($departureTime) ? $departureTime : 'N/A';
                                }
                            }
                        @endphp
                        {{ $data->travel_date ?? 'N/A' }} {{ $reportingTimeStr }}
                    </td>
                </tr>
                <tr>
                    <td>Departure time:</td>
                    <td>
                        @php
                            $departureTime = null;
                            if (isset($data->schedule) && $data->schedule) {
                                $departureTime = $data->schedule->start ?? null;
                            }
                            if (!$departureTime && isset($data->schedule) && $data->schedule) {
                                $departureTime = $data->schedule->start ?? null;
                            }
                            $departureTimeStr = 'N/A';
                            if ($data->travel_date && $departureTime) {
                                try {
                                    $departureDt = \Carbon\Carbon::parse($data->travel_date . ' ' . $departureTime);
                                    $departureTimeStr = $departureDt->format('h:i A');
                                } catch (\Exception $e) {
                                    $departureTimeStr = is_string($departureTime) ? $departureTime : 'N/A';
                                }
                            }
                        @endphp
                        {{ $data->travel_date ?? 'N/A' }} {{ $departureTimeStr }}
                    </td>
                </tr>
                <tr>
                    <td>Arrival date and time:</td>
                    <td>
                        @php
                            $arrivalTime = null;
                            if (isset($data->schedule) && $data->schedule) {
                                $arrivalTime = $data->schedule->end ?? null;
                            }
                            if (!$arrivalTime && isset($data->schedule) && $data->schedule) {
                                $arrivalTime = $data->schedule->end ?? null;
                            }
                            $arrivalTimeStr = 'N/A';
                            $arrivalDateStr = $data->travel_date ?? 'N/A';
                            if ($data->travel_date && $arrivalTime) {
                                try {
                                    $arrivalDt = \Carbon\Carbon::parse($data->travel_date . ' ' . $arrivalTime);
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
                        @endphp
                        {{ $arrivalDateStr }} {{ $arrivalTimeStr }}
                    </td>
                </tr>
                <tr>
                    <td>Seat number:</td>
                <td>{{ $data->seat ?? 'N/A' }}</td>
                </tr>
                @php
                    extract(booking_payment_amounts($data ?? null));
                @endphp
                @if ($breakdownLuggageFee > 0)
                <tr>
                    <td>Luggage amount:</td>
                    <td>{{ number_format($breakdownLuggageFee, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Service Amount:</td>
                    <td>
                        @php
                            echo $breakdownServiceFee > 0 ? number_format($breakdownServiceFee, 2) : 'N/A';
                        @endphp
                    </td>
                </tr>
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <h3>Insurance Details</h3>
            <table> 
                <tr>
                    <td>Policy:</td>
                    <td>Safiri salama - Domestic</td>
                </tr>
                <tr>
                    <td>Date and time of issue:</td>
                    <td>{{ $data->travel_date }}</td>
                </tr>
                <tr>
                    <td>Expire date and time:</td>
                    <td>{{ $data->insuranceDate }}</td>
                </tr> 
            </table>
        </div>

        <div class="divider"></div>

        <div class="details">
            <table>
                <tr>
                    <td>Conductor number:</td>
                    <td>{{ $data->bus->conductor ?? 'N/A' }}</td>
                </tr>
                @if ($data->vender_id)
                    <tr>
                        <td>Vendor Name:</td>
                        <td>{{ $data->vender->name }}</td>
                    </tr>
                    <tr>
                        <td>Vendor Number:</td>
                        <td>{{ $data->vender->contact }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="divider"></div>

        <div class="qr-code-container">
            {!! $data->qrcode !!}
        </div>

        <div class="divider"></div>

        <div class="footer">
            <div class="container">
                                <h6 class="text-muted">
                                    Nunua ticket mtandaoni kwa usalama wa hali ya juu wakati wowote na bila usumbufu kwa
                                    kutembelea www.hisgc.co.tz au piga <a href="tel:*149*46*36#">*149*46*36#</a> halafu
                                    fuata maelekezo ya kununua ticket au piga <a href="tel:+255755879793">+255 755 879
                                        793</a> kwa msaada zaidi. Highlink ISGC</h6>
                            </div>
        </div>
    </div>
</body>

</html>
