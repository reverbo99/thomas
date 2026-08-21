<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesBookingActor;
use App\Models\Booking;
use App\Models\Schedule;
use App\Services\BookingActorService;
use App\Services\BookingTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Transfer booking form for vender / customer / guest (scheduled mode only).
 * Bus-owner keeps AdminController + BookingController@transferBooking.
 */
class TransferFormController extends Controller
{
    use AuthorizesBookingActor;

    public function __construct(private readonly BookingTransferService $transfers)
    {
    }

    public function show(Request $request, $booking_id = null)
    {
        $actor = $this->resolveActor($request);
        $booking_id = $booking_id ?? $request->input('booking_id');

        $options = $this->actorListOptions($actor);
        $bookings = $this->transfers->listTransferableBookings($actor, $options);

        $selectedBooking = null;
        $companionBookings = collect();

        if ($booking_id) {
            $selectedBooking = Booking::with('bus.busname', 'bus.campany', 'route_name', 'schedule')->find($booking_id);
            if (!$selectedBooking) {
                return redirect()->route($this->formRoute($actor))
                    ->with('error', __('vender/transfer.booking_not_found'));
            }

            if ($deny = $this->denyUnlessCanManageBooking($selectedBooking, $actor)) {
                return $deny;
            }

            if (!in_array($selectedBooking->payment_status, BookingTransferService::TRANSFERABLE_STATUSES, true)) {
                return redirect()->route($this->formRoute($actor))
                    ->with('error', __('vender/transfer.booking_not_transferable'));
            }

            if ($actor !== BookingActorService::ACTOR_CUSTOMER && $actor !== BookingActorService::ACTOR_GUEST) {
                $companionBookings = $this->transfers->listCompanionBookings($selectedBooking);
            }

            $companyId = (int) ($selectedBooking->bus->campany_id ?? $selectedBooking->campany_id);
            $buses = $this->transfers->listDestinationBuses($actor, [
                'company_id' => $companyId,
                'allow_emergency' => false,
                'source_booking_id' => (int) $selectedBooking->id,
            ]);
        } else {
            $buses = collect();
        }

        $otherCompanies = collect();
        $allowEmergency = false;
        $formAction = route($this->storeRoute($actor));
        $formSelectBase = url($this->formUrlBase($actor));
        $ajaxBusesRoute = route($this->ajaxBusesRoute($actor));
        $ajaxSchedulesRoute = route($this->ajaxSchedulesRoute($actor));
        $ajaxAmountsRoute = route($this->ajaxAmountsRoute($actor));
        $layout = $this->layout($actor);

        return view('shared.transfer_booking', compact(
            'bookings',
            'buses',
            'otherCompanies',
            'selectedBooking',
            'companionBookings',
            'allowEmergency',
            'formAction',
            'formSelectBase',
            'ajaxBusesRoute',
            'ajaxSchedulesRoute',
            'ajaxAmountsRoute',
            'layout',
            'actor'
        ));
    }

    public function store(Request $request)
    {
        $actor = $this->resolveActor($request);

        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'new_bus_id' => 'required|exists:buses,id',
            'new_schedule_id' => 'required|exists:schedules,id',
            'new_travel_date' => 'required|date',
            'new_pickup_point' => 'required|string',
            'new_dropping_point' => 'required|string',
            'companion_ids' => 'nullable|array',
            'companion_ids.*' => 'integer|exists:bookings,id',
        ]);

        try {
            $sourceBooking = Booking::with('bus')->findOrFail($request->booking_id);

            if ($deny = $this->denyUnlessCanManageBooking($sourceBooking, $actor)) {
                return $deny;
            }

            $companionIds = $request->input('companion_ids', []);
            if (in_array($actor, [BookingActorService::ACTOR_CUSTOMER, BookingActorService::ACTOR_GUEST], true)) {
                $companionIds = [];
            }

            $options = [
                'allow_emergency' => false,
                'actor' => $actor,
                'company_id' => (int) ($sourceBooking->bus->campany_id ?? $sourceBooking->campany_id),
            ];
            if ($actor === BookingActorService::ACTOR_VENDER) {
                $options['vender_id'] = (int) Auth::id();
            }
            if ($actor === BookingActorService::ACTOR_CUSTOMER) {
                $options['user_id'] = (int) Auth::id();
            }

            $result = $this->transfers->transfer(
                $sourceBooking,
                [
                    'bus_id' => (int) $request->new_bus_id,
                    'schedule_id' => (int) $request->new_schedule_id,
                    'travel_date' => (string) $request->new_travel_date,
                    'pickup_point' => (string) $request->new_pickup_point,
                    'dropping_point' => (string) $request->new_dropping_point,
                    'transfer_mode' => BookingTransferService::MODE_SCHEDULED,
                ],
                $companionIds,
                $options
            );

            $transferred = (int) ($result['transferred'] ?? 0);

            return redirect()->route($this->formRoute($actor))->with(
                'success',
                $transferred > 1
                    ? __('vender/transfer.transfer_success_bulk', ['count' => $transferred])
                    : __('vender/transfer.transfer_success')
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Actor booking transfer failed: ' . $e->getMessage());

            return back()->with('error', __('vender/transfer.transfer_failed', ['error' => $e->getMessage()]));
        }
    }

    public function getTransferBuses(Request $request)
    {
        $actor = $this->resolveActor($request);
        $sourceId = (int) $request->input('booking_id');
        $source = Booking::with('bus')->find($sourceId);
        if (!$source || !$this->bookingActor()->canManage($source, $actor)) {
            return response()->json([], 403);
        }

        $companyId = (int) ($source->bus->campany_id ?? $source->campany_id);
        $scheduleId = (int) $request->input('schedule_id');
        $preferredBusId = $scheduleId
            ? (int) (Schedule::where('id', $scheduleId)->value('bus_id') ?? 0)
            : null;

        $buses = $this->transfers->listDestinationBuses($actor, [
            'company_id' => $companyId,
            'allow_emergency' => false,
            'source_booking_id' => $sourceId,
            'schedule_id' => $scheduleId ?: null,
        ]);

        return response()->json(
            $buses->map(fn ($b) => $this->transfers->formatDestinationBus($b, $preferredBusId ?: null))->values()
        );
    }

    public function getFilteredSchedules(Request $request)
    {
        $actor = $this->resolveActor($request);
        $sourceId = (int) $request->input('booking_id');
        $source = $sourceId ? Booking::with('bus')->find($sourceId) : null;
        if ($source && !$this->bookingActor()->canManage($source, $actor)) {
            return response()->json([], 403);
        }

        $companyId = $source
            ? (int) ($source->bus->campany_id ?? $source->campany_id)
            : null;

        $query = Schedule::query()->with(['bus.campany', 'bus.busname']);
        if ($request->filled('bus_id')) {
            $query->where('bus_id', (int) $request->input('bus_id'));
        } elseif ($companyId) {
            $query->whereHas('bus', fn ($q) => $q->where('campany_id', $companyId));
        } else {
            return response()->json([]);
        }

        return response()->json(
            $query->orderBy('start')->get()->map(fn ($s) => $this->transfers->formatTransferSchedule($s))->values()
        );
    }

    public function calculateTransferAmounts(Request $request)
    {
        $actor = $this->resolveActor($request);
        $source = Booking::with('bus')->find($request->input('booking_id'));
        if (!$source || !$this->bookingActor()->canManage($source, $actor)) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        try {
            $newBus = \App\Models\bus::with(['campany', 'route', 'busname'])->findOrFail((int) $request->input('new_bus_id'));
            $preview = $this->transfers->previewPricing(
                $source,
                $newBus,
                (string) $request->input('new_pickup_point', $source->pickup_point),
                (string) $request->input('new_dropping_point', $source->dropping_point),
                false,
                (int) ($source->bus->campany_id ?? $source->campany_id)
            );

            return response()->json($preview);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    private function resolveActor(Request $request): string
    {
        $explicit = $request->route('transfer_actor') ?? $request->input('actor');

        if (is_string($explicit) && in_array($explicit, BookingActorService::ACTORS, true)) {
            return $explicit;
        }

        return $this->resolveBookingActor($request);
    }

    private function actorListOptions(string $actor): array
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => ['vender_id' => (int) Auth::id()],
            BookingActorService::ACTOR_CUSTOMER => ['user_id' => (int) Auth::id()],
            BookingActorService::ACTOR_GUEST => [
                'booking_ids' => array_map('intval', (array) session(BookingActorService::SESSION_GUEST_LOOKUP_IDS, [])),
            ],
            default => [],
        };
    }

    private function formRoute(string $actor): string
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => 'vender.booking.transfer.form',
            BookingActorService::ACTOR_CUSTOMER => 'customer.booking.transfer.form',
            default => 'guest.booking.transfer.form',
        };
    }

    private function storeRoute(string $actor): string
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => 'vender.booking.transfer',
            BookingActorService::ACTOR_CUSTOMER => 'customer.booking.transfer',
            default => 'guest.booking.transfer',
        };
    }

    private function formUrlBase(string $actor): string
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => '/vender/booking/transfer',
            BookingActorService::ACTOR_CUSTOMER => '/customer/booking/transfer',
            default => '/guest/booking/transfer',
        };
    }

    private function ajaxBusesRoute(string $actor): string
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => 'vender.get.transfer.buses',
            BookingActorService::ACTOR_CUSTOMER => 'customer.get.transfer.buses',
            default => 'guest.get.transfer.buses',
        };
    }

    private function ajaxSchedulesRoute(string $actor): string
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => 'vender.get.filtered.schedules',
            BookingActorService::ACTOR_CUSTOMER => 'customer.get.filtered.schedules',
            default => 'guest.get.filtered.schedules',
        };
    }

    private function ajaxAmountsRoute(string $actor): string
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => 'vender.calculate.transfer.amounts',
            BookingActorService::ACTOR_CUSTOMER => 'customer.calculate.transfer.amounts',
            default => 'guest.calculate.transfer.amounts',
        };
    }

    private function layout(string $actor): string
    {
        return match ($actor) {
            BookingActorService::ACTOR_VENDER => 'vender.app',
            BookingActorService::ACTOR_CUSTOMER => 'customer.app',
            default => 'test.layouts.marketing',
        };
    }
}
