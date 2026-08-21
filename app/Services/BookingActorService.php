<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Shared authorization for booking lifecycle actions (cancel / rebook / refund)
 * across customer, vender, guest, and bus-owner actors.
 *
 * Controllers typically:
 *   $actor = $actors->resolveActorFromRequest($request);
 *   if ($deny = $actors->authorizeManage($booking, $actor)) {
 *       return $deny;
 *   }
 * or:
 *   $actors->assertCanManage($booking, $actor);
 */
class BookingActorService
{
    public const ACTOR_CUSTOMER = 'customer';
    public const ACTOR_VENDER = 'vender';
    public const ACTOR_GUEST = 'guest';
    public const ACTOR_BUS_OWNER = 'bus_owner';

    public const ACTORS = [
        self::ACTOR_CUSTOMER,
        self::ACTOR_VENDER,
        self::ACTOR_GUEST,
        self::ACTOR_BUS_OWNER,
    ];

    public const SESSION_GUEST_LOOKUP_IDS = 'guest_booking_lookup_ids';
    public const SESSION_REBOOK_ACTOR = 'rebook_actor';

    /**
     * Resolve actor from optional request `actor` param, else auth role / guest.
     */
    public function resolveActorFromRequest(?Request $request = null): string
    {
        $request = $request ?? request();

        $explicit = $request->input('actor');
        if (is_string($explicit) && in_array($explicit, self::ACTORS, true)) {
            return $explicit;
        }

        $user = Auth::user();
        if ($user) {
            return match ($user->role) {
                'customer' => self::ACTOR_CUSTOMER,
                'vender' => self::ACTOR_VENDER,
                'bus_campany', 'local_bus_owner' => self::ACTOR_BUS_OWNER,
                default => self::ACTOR_GUEST,
            };
        }

        return self::ACTOR_GUEST;
    }

    /**
     * Whether the given actor may manage this booking.
     */
    public function canManage(Booking $booking, string $actor): bool
    {
        return match ($actor) {
            self::ACTOR_CUSTOMER => $this->canManageAsCustomer($booking),
            self::ACTOR_VENDER => $this->canManageAsVender($booking),
            self::ACTOR_GUEST => $this->canManageAsGuest($booking),
            self::ACTOR_BUS_OWNER => $this->canManageAsBusOwner($booking),
            default => false,
        };
    }

    /**
     * Throw when the actor cannot manage the booking (for non-HTTP callers).
     *
     * @throws RuntimeException
     */
    public function assertCanManage(Booking $booking, string $actor): void
    {
        if (!$this->canManage($booking, $actor)) {
            throw new RuntimeException(__('all.booking_not_found'));
        }
    }

    /**
     * Redirect-friendly deny: returns a redirect when unauthorized, else null.
     */
    public function authorizeManage(Booking $booking, ?string $actor = null): ?RedirectResponse
    {
        $actor = $actor ?? $this->resolveActorFromRequest();

        if ($this->canManage($booking, $actor)) {
            return null;
        }

        return back()->with('error', __('all.booking_not_found'));
    }

    public function canManageAsCustomer(Booking $booking): bool
    {
        $user = Auth::user();

        return $user
            && $user->role === 'customer'
            && (int) $booking->user_id === (int) $user->id;
    }

    public function canManageAsVender(Booking $booking): bool
    {
        $user = Auth::user();

        return $user
            && $user->role === 'vender'
            && $booking->vender_id !== null
            && (int) $booking->vender_id === (int) $user->id;
    }

    /**
     * Guest must have looked up the booking (session allow-list set by BookingController).
     */
    public function canManageAsGuest(Booking $booking): bool
    {
        $allowedIds = session(self::SESSION_GUEST_LOOKUP_IDS, []);
        if (!is_array($allowedIds)) {
            return false;
        }

        $allowed = array_map('intval', $allowedIds);

        return in_array((int) $booking->id, $allowed, true);
    }

    /**
     * Bus owner / local bus owner: booking belongs to Auth::user()->campany.
     */
    public function canManageAsBusOwner(Booking $booking): bool
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['bus_campany', 'local_bus_owner'], true)) {
            return false;
        }

        $companyId = $user->campany?->id;
        if (!$companyId) {
            return false;
        }

        return (int) $booking->campany_id === (int) $companyId;
    }

    /**
     * Named route to continue rebook search after Session::put('rebook').
     */
    public function rebookSearchRoute(string $actor): string
    {
        return match ($actor) {
            self::ACTOR_VENDER => 'vender.route',
            self::ACTOR_CUSTOMER => 'customer.mybooking.search',
            default => 'by_route',
        };
    }

    /**
     * Named route after a successful rebook_data update.
     */
    public function rebookSuccessRoute(string $actor): string
    {
        return match ($actor) {
            self::ACTOR_VENDER => 'vender.history',
            self::ACTOR_CUSTOMER => 'customer.mybooking',
            default => 'info',
        };
    }
}
