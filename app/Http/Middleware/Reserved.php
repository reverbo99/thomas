<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Reserved
{
    /**
     * Expire unpaid seat holds without hard-deleting them.
     *
     * Previous behaviour hard-deleted `Reserved`/`resaved` rows on every request
     * once within 6 hours of departure (or when schedule start was missing and
     * defaulted to midnight). That wiped holds before bus owners or customers
     * could see/pay them. Align with `bookings:check-expired-resaved`: mark Fail
     * when `resaved_until` (or 24h from create) has passed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bookings = Booking::query()
            ->whereIn('payment_status', ['Reserved', 'resaved'])
            ->get(['id', 'payment_status', 'resaved_until', 'created_at']);

        $now = Carbon::now();

        foreach ($bookings as $booking) {
            if ($this->shouldExpireHold($booking, $now)) {
                $booking->update(['payment_status' => 'Fail']);
            }
        }

        return $next($request);
    }

    private function shouldExpireHold(Booking $booking, Carbon $now): bool
    {
        if (! empty($booking->resaved_until)) {
            try {
                return $now->greaterThan(Carbon::parse($booking->resaved_until));
            } catch (\Exception $e) {
                // fall through to created_at window
            }
        }

        try {
            return $booking->created_at
                && $now->greaterThan(Carbon::parse($booking->created_at)->addDay());
        } catch (\Exception $e) {
            return false;
        }
    }
}
