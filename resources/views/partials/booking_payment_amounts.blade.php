@php
    $bpBooking = $booking ?? $data ?? null;

    $breakdownTicketFee = (float) ($bpBooking->busFee ?? 0);
    $breakdownLuggageFee = ((int) ($bpBooking->has_excess_luggage ?? 0) === 1 || (float) ($bpBooking->excess_luggage_fee ?? 0) > 0)
        ? (float) ($bpBooking->excess_luggage_fee ?? 0)
        : 0;
    $breakdownInsurance = (float) ($bpBooking->bima_amount ?? 0);
    $breakdownStoredTotal = (float) ($bpBooking->customer_paid_total ?? 0);

    $breakdownServiceFee = (float) ($bpBooking->system_service_fee ?? 0);
    if ($breakdownServiceFee <= 0) {
        $breakdownServiceFee = (float) ($bpBooking->service ?? 0)
            + (float) ($bpBooking->vender_service ?? 0)
            + (float) ($bpBooking->service_vat ?? 0);
    }

    if ($breakdownStoredTotal > 0) {
        $breakdownAmountPaid = $breakdownStoredTotal;
        $breakdownServiceFee = max(0, $breakdownStoredTotal - $breakdownTicketFee - $breakdownLuggageFee - $breakdownInsurance);
        $breakdownUseStoredTotal = true;
    } else {
        if ($breakdownServiceFee <= 0 && $breakdownTicketFee > 0) {
            $fareService = app(\App\Services\FareFormulaService::class);
            $seatCountForFee = $fareService->seatCountFromSeatString($bpBooking->seat ?? null);
            $breakdownServiceFee = $fareService->calculateTravellerServiceFee(
                $breakdownTicketFee,
                \App\Models\Setting::first(),
                $seatCountForFee
            );
        }

        $breakdownAmountPaid = $breakdownTicketFee + $breakdownLuggageFee + $breakdownInsurance + $breakdownServiceFee;
        $breakdownUseStoredTotal = false;
    }
@endphp
