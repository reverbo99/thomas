<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesBookingActor;
use App\Models\Booking;
use App\Models\Refund;
use App\Models\RefundPercentage;
use App\Services\BookingActorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    use AuthorizesBookingActor;

    /**
     * Normalize phone to digits only, 255XXXXXXXXX for comparison.
     */
    private function normalizePhone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (strpos($digits, '0') === 0 && strlen($digits) <= 10) {
            $digits = '255' . substr($digits, 1);
        } elseif (strlen($digits) >= 9 && substr($digits, 0, 3) !== '255') {
            $digits = '255' . $digits;
        }
        return $digits;
    }

    public function get_booking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'fullname'   => 'required|string|max:255',
        ], [
            'booking_id.required' => 'Booking is required.',
            'booking_id.exists'   => 'Booking not found.',
            'fullname.required'  => 'Please enter your full name.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $mobile = trim((string) $request->mobile_number);
        $bank   = trim((string) $request->bank_number);

        if ($mobile === '' && $bank === '') {
            return back()->with('error', 'Please enter a valid mobile number or bank account number.')->withInput();
        }

        if ($mobile !== '' && !preg_match('/^[0-9]{9,15}$/', preg_replace('/\D/', '', $mobile))) {
            return back()->with('error', 'Mobile number must be 9–15 digits.')->withInput();
        }

        if ($bank !== '' && !preg_match('/^[0-9]{5,25}$/', preg_replace('/\D/', '', $bank))) {
            return back()->with('error', 'Bank account number must be 5–25 digits.')->withInput();
        }

        $booking = Booking::where('id', $request->booking_id)
            ->whereIn('payment_status', ['Paid', 'Refund Rejected'])
            ->first();

        if (!$booking) {
            return back()->with('error', 'Booking not available for refund or does not exist.');
        }

        // Optional actor: vender who owns the booking may bypass phone match.
        // Guest / public refund keeps phone match as today.
        $actor = $this->resolveBookingActor($request);
        $venderOwns = $actor === BookingActorService::ACTOR_VENDER
            && $this->bookingActor()->canManage($booking, BookingActorService::ACTOR_VENDER);

        $existingPending = Refund::where('booking_code', $booking->booking_code)
            ->where('status', 'Pending')
            ->exists();

        if ($existingPending) {
            return back()->with('error', 'A refund request is already pending for this booking.')->withInput();
        }

        $constData = new ConstData();

        if (!$constData->isRefundAllowed($booking)) {
            return back()->with('error', 'Refund is only available more than 6 hours before departure.')->withInput();
        }

        // Validate mobile matches booking phone unless authenticated vender owns the ticket
        if (!$venderOwns && $mobile !== '') {
            $normalizedInput = $this->normalizePhone($mobile);
            $normalizedBooking = $this->normalizePhone($booking->customer_phone);
            if ($normalizedBooking !== '' && $normalizedInput !== $normalizedBooking) {
                return back()->with('error', 'Mobile number must match the phone number used for this booking (' . $booking->customer_phone . ').')->withInput();
            }
        }

        $amount = $constData->refund_logic($booking->id);

        if ($amount <= 0) {
            return back()->with('error', 'Refund is not available for this booking at this time.')->withInput();
        }

        $percentage = (float) ($booking->busFee ?? 0) - (float) $amount;

        // post request
        $data = Refund::create([
            'booking_code' => $booking->booking_code,
            'amount' => $amount, // Access busFee from the retrieved booking model
            'status' => 'Pending',
            'phone' => $mobile !== '' ? $request->mobile_number : $request->bank_number,
            'fullname' => $request->fullname,
        ]); 

        $booking->update([
            'payment_status' => 'Refund Pending',
            'refund_id' => $data->id,
        ]);

        RefundPercentage::create([
            'booking_code' => $booking->booking_code,
            'amount' => $percentage,
        ]);

        return back()->with('success','Refund request sent successfully');


    }
}
