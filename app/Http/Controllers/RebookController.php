<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesBookingActor;
use App\Models\Booking;
use App\Services\BookingActorService;
use Illuminate\Http\Request;
use Session;
use \Carbon\Carbon;

class RebookController extends Controller
{
    use AuthorizesBookingActor;

    public function rebook(Request $request)
    {
        $booking = Booking::find($request->input('order_id'));

        if (!$booking) {
            return back()->with('error', __('all.booking_not_found'));
        }

        $actor = $this->resolveBookingActor($request);
        if ($deny = $this->denyUnlessCanManageBooking($booking, $actor)) {
            return $deny;
        }

        $now = Carbon::parse(now())->format('Y-m-d');

        if ($booking->travel_date <= $now) {
            return back()->with('error', __('all.rebooking_out_of_date'));
        }

        Session::put('rebook', $booking);
        Session::put(BookingActorService::SESSION_REBOOK_ACTOR, $actor);

        $searchRoute = $this->bookingActor()->rebookSearchRoute($actor);

        return redirect()->route($searchRoute)->with('warning', __('all.finish_booking_before_logout'));
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
        $actor = session(BookingActorService::SESSION_REBOOK_ACTOR, BookingActorService::ACTOR_CUSTOMER);

        $rebook->update($bookingData);

        Session::forget('rebook');
        Session::forget(BookingActorService::SESSION_REBOOK_ACTOR);

        $successRoute = $this->bookingActor()->rebookSuccessRoute($actor);

        return redirect()->route($successRoute)->with('success', __('all.rebooking_completed_success'));
    }
}
