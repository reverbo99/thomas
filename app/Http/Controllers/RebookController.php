<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Session;
use \Carbon\Carbon;

class RebookController extends Controller
{
    public function rebook(Request $request)
    {
        $booking = Booking::find($request->input('order_id'));

        if (!$booking) {
            return back()->with('error', __('all.booking_not_found'));
        }

        if (auth()->check() && auth()->user()->role === 'customer' && (int) $booking->user_id !== (int) auth()->id()) {
            return back()->with('error', __('all.booking_not_found'));
        }

        $now = Carbon::parse(now())->format('Y-m-d');
    
        if($booking->travel_date <= $now)
        {
            return back()->with('error', __('all.rebooking_out_of_date'));
        }

        Session::put('rebook', $booking);

        return redirect()->route('customer.mybooking.search')->with('warning', __('all.finish_booking_before_logout'));
    }

    public function rebook_data($data)
    {

        //return $data;

        $bookingData = [  
            'bus_id' => $data['bus_id'],
            'route_id' => $data['route_id'],
            'pickup_point' => $data['pickup_point'],
            'dropping_point' => $data['dropping_point'],
            'travel_date' => $data['travel_date'],
            'seat' => $data['seats'],
            'payment_status' => 'Paid',
            'distance' => $data['route_distance']
        ];

        $rebook = session('rebook');
        $rebook->update($bookingData);
        
        Session::forget('rebook');

        return redirect()->route('customer.mybooking')->with('success', __('all.rebooking_completed_success'));
    }
}
