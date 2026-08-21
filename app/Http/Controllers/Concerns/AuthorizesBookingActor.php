<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Booking;
use App\Services\BookingActorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Thin wiring for Cancel / Refund / Rebook (and similar) controllers.
 *
 * Usage:
 *   $actor = $this->bookingActor();
 *   if ($deny = $this->denyUnlessCanManageBooking($booking, $actor->resolveActorFromRequest($request))) {
 *       return $deny;
 *   }
 */
trait AuthorizesBookingActor
{
    protected function bookingActor(): BookingActorService
    {
        return app(BookingActorService::class);
    }

    protected function resolveBookingActor(?Request $request = null): string
    {
        return $this->bookingActor()->resolveActorFromRequest($request);
    }

    protected function denyUnlessCanManageBooking(Booking $booking, ?string $actor = null): ?RedirectResponse
    {
        return $this->bookingActor()->authorizeManage($booking, $actor);
    }
}
