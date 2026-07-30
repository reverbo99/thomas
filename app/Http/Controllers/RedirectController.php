<?php

namespace App\Http\Controllers;

use App\Mail\SendEmail;
use App\Models\Booking;
use App\Models\Roundtrip;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RedirectController extends Controller
{
    public function showBookingStatus($bookingId)
    {
        $booking = Booking::with('bus.route', 'schedule', 'campany.busOwnerAccount', 'campany.user')
            ->where('id', $bookingId)
            ->orwhere('transaction_ref_id', $bookingId)
            ->first();

        if (!$booking) {
            abort(404, 'Booking not found.');
        }

        return view('bookings.status', compact('booking'));
    }
    public function _redirect($transactionRefId)
    {
        $sessionB1 = session('booking1');
        $sessionB2 = session('booking2');
        if ($sessionB1 && $sessionB2) {
            $b1 = $sessionB1 instanceof Booking ? Booking::find($sessionB1->id) : Booking::find($sessionB1);
            $b2 = $sessionB2 instanceof Booking ? Booking::find($sessionB2->id) : Booking::find($sessionB2);
            if ($b1 && $b2 && $b1->payment_status === 'Paid' && $b2->payment_status === 'Paid') {
                return $this->showRoundTripBookingStatus($b1, $b2);
            }
        }

        // Round-trip gateways share one transaction_ref across both legs.
        $paidSiblings = Booking::with('bus.route', 'schedule', 'campany.busOwnerAccount', 'campany.user')
            ->where('transaction_ref_id', $transactionRefId)
            ->where('payment_status', 'Paid')
            ->orderBy('id')
            ->get();
        if ($paidSiblings->count() >= 2) {
            return $this->showRoundTripBookingStatus($paidSiblings[0], $paidSiblings[1]);
        }

        $data = Booking::with('bus.route', 'schedule', 'campany.busOwnerAccount', 'campany.user')
            ->where('transaction_ref_id', $transactionRefId)
            ->orWhere('id', $transactionRefId)
            ->first();

        if (!$data) {
            return view('payments.failed', ['data' => null]);
        }

        // Payment provider may redirect the user before the server callback has run.
        // Show "processing" and auto-refresh instead of "failed" so we don't show
        // "Payment Failed" when payment actually succeeded.
        if ($data->payment_status != "Paid") {
            $isPendingByRef = (string) $data->transaction_ref_id === (string) $transactionRefId
                || (string) $data->id === (string) $transactionRefId;
            if ($isPendingByRef) {
                // Still waiting: if a second unpaid/resaved sibling exists, keep processing.
                return view('payments.processing', ['data' => $data]);
            }
            return view('payments.failed', compact('data'));
        }

        // Payment is Paid - show success, send notifications
        // --- TRA INTEGRATION ---
        try {
            \Illuminate\Support\Facades\Log::info('TRA Fiscalization Starting (Single Booking Payment)', [
                'booking_id' => $data->id,
                'booking_code' => $data->booking_code,
                'payment_method' => $data->payment_method ?? 'unknown',
                'amount' => $data->amount,
            ]);
            
            $tra = new \App\Services\TraVfdService();
            $fiscalized = $tra->fiscalize($data);
            
            // Refresh data to get TRA tokens if updated
            $data->refresh();
            
            if ($fiscalized) {
                \Illuminate\Support\Facades\Log::info('TRA Fiscalization Successful (Single Booking Payment)', [
                    'booking_id' => $data->id,
                    'booking_code' => $data->booking_code,
                    'tra_status' => $data->tra_status,
                    'tra_vnum' => $data->tra_vnum ?? 'N/A',
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('TRA Fiscalization Returned False (Single Booking Payment)', [
                    'booking_id' => $data->id,
                    'booking_code' => $data->booking_code,
                    'tra_status' => $data->tra_status ?? 'N/A',
                    'tra_error' => $data->tra_error ?? 'N/A',
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TRA Fiscalization Failed (Single Booking Payment): " . $e->getMessage(), [
                'booking_id' => $data->id,
                'booking_code' => $data->booking_code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        // -----------------------

        $settings = Setting::first();
        $sendCustomerSms = $settings ? (bool) $settings->enable_customer_sms_notifications : true;
        $sendCustomerEmail = $settings ? (bool) $settings->enable_customer_email_notifications : true;
        $sendConductorSms = $settings ? (bool) $settings->enable_conductor_sms_notifications : true;
        $sendConductorEmail = $settings ? (bool) $settings->enable_conductor_email_notifications : true;

        $departureTime = $data->schedule->start ?? 'N/A';
        $reportTime = 'N/A';
        if ($departureTime !== 'N/A') {
            try {
                $reportTime = \Carbon\Carbon::parse($data->travel_date . ' ' . $departureTime)->subMinutes(30)->format('h:i A');
            } catch (\Exception $e) {
                $reportTime = $departureTime;
            }
        }
        $customerMessage = "Dear {$data->customer_name}, Karibu {$data->campany->name}, Utasafiri na basi namba {$data->bus->bus_number} Linalotoka {$data->pickup_point} Kwenda {$data->dropping_point} Tarehe {$data->travel_date}. tafadhali wasili " . ($data->pickup_point ?? 'kituoni') . " angalau mapema saa {$reportTime} tayari kwa safari. Namba ya kiti chako ni {$data->seat} na namba yako ya safari ni {$data->booking_code}. Kwa mawasiliano piga +255755879793. HIGHLINK ISGC inakutakia safari njema.";
        $conductorMessage = "Dear conductor, Kiti {$data->seat} katika basi namba {$data->bus->bus_number} kimeuzwa kwa {$data->customer_name} kwa safari ya kutoka {$data->pickup_point} kwenda {$data->dropping_point} tarehe {$data->travel_date} namba ya safari yake ni {$data->booking_code} wasiliana naye kwa namba {$data->customer_phone} HIGHLINK ISGC inawatakia safari njema";

        if ($sendCustomerSms && !empty($data->customer_phone)) {
            $sms = new SmsController();
            $sms->sms_send($data->customer_phone, $customerMessage);
        }

        if ($sendConductorSms && !empty($data->bus->conductor)) {
            $sms = isset($sms) ? $sms : new SmsController();
            $sms->sms_send($data->bus->conductor, $conductorMessage);
        }

        if ($sendCustomerEmail && !empty($data->customer_email)) {
            try {
                Mail::to($data->customer_email)->send(new SendEmail($customerMessage));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send customer email: " . $e->getMessage(), [
                    'booking_id' => $data->id ?? null,
                    'customer_email' => $data->customer_email,
                ]);
            }
        }

        $conductorEmail = $data->bus && $data->bus->campany && $data->bus->campany->user
            ? $data->bus->campany->user->email
            : ($data->campany && $data->campany->user ? $data->campany->user->email : null);

        if ($sendConductorEmail && !empty($conductorEmail)) {
            try {
                Mail::to($conductorEmail)->send(new SendEmail($conductorMessage));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send conductor email: " . $e->getMessage(), [
                    'booking_id' => $data->id ?? null,
                    'conductor_email' => $conductorEmail,
                ]);
            }
        }

        // After ClickPesa (or other) payment success, send customers to My Ticket with message
        if (auth()->check() && auth()->user()->role === 'customer') {
            return redirect()->route('customer.mybooking')->with('success', __('all.payment_successful') ?: 'Payment successful');
        }

        return view('payments.success', compact('data'));
    }

    // public function _round($booking1, $booking2)
    // {
    //     $bookingone = Booking::with('bus.route', 'campany.busOwnerAccount')
    //         ->orwhere('id', $booking1)
    //         ->first();
    //     $bookingtwo = Booking::with('bus.route', 'campany.busOwnerAccount')
    //         ->orwhere('id', $booking2)
    //         ->first();

    //     return ['bookingone' => $bookingone, 'bookingtwo' => $bookingtwo];
    // }

    public function showRoundTripBookingStatus($booking1Id, $booking2Id)
    {
        //$bookings = $this->_round($booking1Id, $booking2Id);

        $bookingone = $booking1Id;
        $bookingtwo = $booking2Id;

        if (!$bookingone || !$bookingtwo) {
            abort(404, 'One or both bookings not found.');
        }

        // Eager-load schedule so second leg shows correct from/to (schedule route, not main route)
        if ($bookingone instanceof Booking) {
            $bookingone->load(['schedule', 'bus.route', 'bus.busname', 'campany', 'vender']);
        }
        if ($bookingtwo instanceof Booking) {
            $bookingtwo->load(['schedule', 'bus.route', 'bus.busname', 'campany', 'vender']);
        }

        // --- TRA INTEGRATION ---
        try {
            $tra = new \App\Services\TraVfdService();
            
            // Fiscalize first leg booking
            if($bookingone instanceof Booking && $bookingone->payment_status == 'Paid') {
                \Illuminate\Support\Facades\Log::info('TRA Fiscalization Starting (Round Trip - First Leg)', [
                    'booking_id' => $bookingone->id,
                    'booking_code' => $bookingone->booking_code,
                    'payment_method' => $bookingone->payment_method ?? 'unknown',
                    'amount' => $bookingone->amount,
                ]);
                
                $fiscalized1 = $tra->fiscalize($bookingone);
                $bookingone->refresh();
                
                if ($fiscalized1) {
                    \Illuminate\Support\Facades\Log::info('TRA Fiscalization Successful (Round Trip - First Leg)', [
                        'booking_id' => $bookingone->id,
                        'booking_code' => $bookingone->booking_code,
                        'tra_status' => $bookingone->tra_status,
                        'tra_vnum' => $bookingone->tra_vnum ?? 'N/A',
                    ]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('TRA Fiscalization Returned False (Round Trip - First Leg)', [
                        'booking_id' => $bookingone->id,
                        'booking_code' => $bookingone->booking_code,
                        'tra_status' => $bookingone->tra_status ?? 'N/A',
                        'tra_error' => $bookingone->tra_error ?? 'N/A',
                    ]);
                }
            }
            
            // Fiscalize second leg booking
            if($bookingtwo instanceof Booking && $bookingtwo->payment_status == 'Paid') {
                \Illuminate\Support\Facades\Log::info('TRA Fiscalization Starting (Round Trip - Second Leg)', [
                    'booking_id' => $bookingtwo->id,
                    'booking_code' => $bookingtwo->booking_code,
                    'payment_method' => $bookingtwo->payment_method ?? 'unknown',
                    'amount' => $bookingtwo->amount,
                ]);
                
                $fiscalized2 = $tra->fiscalize($bookingtwo);
                $bookingtwo->refresh();
                
                if ($fiscalized2) {
                    \Illuminate\Support\Facades\Log::info('TRA Fiscalization Successful (Round Trip - Second Leg)', [
                        'booking_id' => $bookingtwo->id,
                        'booking_code' => $bookingtwo->booking_code,
                        'tra_status' => $bookingtwo->tra_status,
                        'tra_vnum' => $bookingtwo->tra_vnum ?? 'N/A',
                    ]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('TRA Fiscalization Returned False (Round Trip - Second Leg)', [
                        'booking_id' => $bookingtwo->id,
                        'booking_code' => $bookingtwo->booking_code,
                        'tra_status' => $bookingtwo->tra_status ?? 'N/A',
                        'tra_error' => $bookingtwo->tra_error ?? 'N/A',
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("TRA Fiscalization Failed (RoundTrip): " . $e->getMessage(), [
                'booking1_id' => $bookingone instanceof Booking ? $bookingone->id : 'N/A',
                'booking2_id' => $bookingtwo instanceof Booking ? $bookingtwo->id : 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        // -----------------------

        return view('bookings.roundtrip_status', compact('bookingone', 'bookingtwo'));
    }
}
