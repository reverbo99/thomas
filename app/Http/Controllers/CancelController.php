<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ConstData;
use App\Models\AdminWallet;
use App\Models\Booking;
use App\Models\CancelledBookings;
use App\Models\Schedule;
use App\Models\TempWallet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CancelController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('info');
    }

    public function cancel(Request $request)
    {
        // return $request->all();


        $booking = Booking::find($request->booking_id);

        if (!$booking) {
            return back()->with('error', __('all.booking_not_found'));
        }

        if ($booking->payment_status === 'Cancel') {
            return back()->with('error', __('all.booking_already_cancelled'));
        }

        if (!in_array($booking->payment_status, ['Paid', 'Reserved'], true)) {
            return back()->with('error', __('all.booking_not_cancellable'));
        }

        if (!(new ConstData())->isCancelAllowed($booking)) {
            return back()->with('error', __('all.cancel_not_allowed'));
        }

        $amount = (new ConstData())->cancel_logic($booking->id);
        $cancel = max(0, (float) $booking->amount - $amount);

        ////////////////cancel percent//////////////

        CancelledBookings::create([
            'booking_id' => $request->booking_id,
            'amount' => $cancel,
            'campany_id' => $booking->campany_id,
        ]);

        // Also increase $cancel to adminwallet
        $adminWallet = AdminWallet::first();
        if ($adminWallet) {
            $adminWallet->balance += $cancel;
            $adminWallet->save();
        }

        

        $booking->update(['payment_status' => 'Cancel']);

        if (auth()->check()) {
            $wallet = TempWallet::firstOrNew(['user_id' => auth()->id()]);

            $wallet->amount = $amount;
            $wallet->user_key = $request->key ?? $wallet->user_key;
            $wallet->status = '0';

            $wallet->save();
        } else {
            TempWallet::create([
                'amount' => $amount,
                'user_key' => $request->key,
                'status' => '0',
            ]);
        }

        return redirect()->back()->with('success', __('all.cancel_completed_success'));
    }

    public function generateRandomString()
    {
        $length = 7;
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function filterNumber($number)
    {
        $number = (string)$number; // Convert to string for easier manipulation

        if (strpos($number, '0') === 0) {
            return '255' . substr($number, 1); // Replace leading zero with 255
        } elseif (strpos($number, '255') === 0) {
            return $number; //if start with 255 return number
        } else {
            return '255' . $number; // Prepend 255
        }
    }


    public function cancel_schedule(Request $request)
    {
        //validate
        if ($request->schedule_id == null) {
            return redirect()->back()->with('error', 'Schedule ID is required');
        }
        // fetch data
        $booking = Booking::where('schedule_id', $request->schedule_id)
            ->where('payment_status', 'Paid')
            ->get();

        if (count($booking) == 0) {
            // update schedule 
            Schedule::where('id', $request->schedule_id)->delete();
            // return
            return back()->with('success', 'Schedule canceld deleted successfully');
        }
        // get data
        foreach ($booking as $booking) {
            $email = $booking->customer_email ?? '';
            $phone = $booking->customer_phone ?? '';

            // create code
            $code = $this->generateRandomString();

            // update tempolary wallet
            if ($booking->user_id != null) {
                TempWallet::updateOrCreate(
                    [
                        'user_id' => $booking->user_id,
                    ],
                    [
                        'amount' => $booking->busFee,
                    ]
                );
            } else {
                TempWallet::create([
                    'user_key' => $code,
                    'amount' => $booking->busFee,
                ]);
            }
            // notify users

            // send email

            // send sms
            $number = $this->filterNumber($booking->customer_number);
            $sms = new SmsController();
            $text = "Habari $booking->customer_name, tunapenda kukuarifu kuwa safari yako ya basi kutoka $booking->pickup_point kwenda $booking->dropping_point tarehe $booking->travel_date imeghairiwa. Unaweza kukata tiketi nyingine bila malipo. Tumia msimbo $code kukata tiketi nyingine bila malipo.";
            $sms->sms_send($number, $text);
        }

        // update booking and schedule

        $booking->update(['payment_status' => 'Cancel']);
        $booking->save();

        Schedule::where('id', $request->schedule_id)->delete();

        return back()->with('success', 'Schedule canceld deleted successfully');
    }
}
