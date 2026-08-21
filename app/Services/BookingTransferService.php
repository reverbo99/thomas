<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\bus;
use App\Models\route;
use App\Models\Schedule;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shared booking transfer: move paid/reserved/resaved tickets to another bus/schedule.
 *
 * Actors:
 * - bus_owner: existing behaviour including emergency / cross-company
 * - vender / customer / guest: scheduled mode only; destination bus same company as source bus
 */
class BookingTransferService
{
    public const ACTOR_BUS_OWNER = 'bus_owner';
    public const ACTOR_VENDER = 'vender';
    public const ACTOR_CUSTOMER = 'customer';
    public const ACTOR_GUEST = 'guest';

    public const MODE_SCHEDULED = 'scheduled';
    public const MODE_EMERGENCY = 'emergency';

    public const TRANSFERABLE_STATUSES = ['Paid', 'Reserved', 'resaved'];

    public function __construct(private readonly FareFormulaService $formulaService)
    {
    }

    /**
     * Transfer a source booking (and optional companions) to a destination trip.
     *
     * @param  array{
     *     bus_id:int,
     *     schedule_id?:int|null,
     *     travel_date:string,
     *     pickup_point:string,
     *     dropping_point:string,
     *     transfer_mode?:string,
     *     emergency_start?:string|null,
     *     emergency_end?:string|null
     * }  $destination
     * @param  array<int>  $companionIds
     * @param  array{
     *     allow_emergency?:bool,
     *     company_id?:int|null,
     *     actor?:string,
     *     vender_id?:int|null,
     *     user_id?:int|null,
     *     seat_map?:array<int|string, string>,
     *     passengers?:array<int|string, array<string, mixed>>
     * }  $options
     * @return array{transferred:int, booking_ids:array<int>}
     *
     * @throws \RuntimeException
     */
    public function transfer(Booking $sourceBooking, array $destination, array $companionIds = [], array $options = []): array
    {
        $actor = (string) ($options['actor'] ?? self::ACTOR_BUS_OWNER);
        $allowEmergency = (bool) ($options['allow_emergency'] ?? false);
        $companyId = isset($options['company_id']) ? (int) $options['company_id'] : null;
        $mode = (string) ($destination['transfer_mode'] ?? self::MODE_SCHEDULED);
        $emergency = $mode === self::MODE_EMERGENCY;
        $seatMap = $this->normalizeSeatMap($options['seat_map'] ?? []);
        $passengerOverrides = is_array($options['passengers'] ?? null) ? $options['passengers'] : [];

        if ($emergency && !$allowEmergency) {
            throw new \RuntimeException(__('vender/transfer.new_bus_company_mismatch'));
        }

        if ($actor !== self::ACTOR_BUS_OWNER && $emergency) {
            throw new \RuntimeException(__('vender/transfer.new_bus_company_mismatch'));
        }

        // Customer transfers never include companions.
        if ($actor === self::ACTOR_CUSTOMER) {
            $companionIds = [];
        }

        return DB::transaction(function () use (
            $sourceBooking,
            $destination,
            $companionIds,
            $actor,
            $companyId,
            $emergency,
            $options,
            $seatMap,
            $passengerOverrides
        ) {
            $sourceBooking = Booking::whereKey($sourceBooking->id)->lockForUpdate()->first();
            if (!$sourceBooking) {
                throw new \RuntimeException(__('vender/transfer.booking_not_found'));
            }

            $this->assertSourceAuthorized($sourceBooking, $actor, $companyId, $options);

            if (!in_array($sourceBooking->payment_status, self::TRANSFERABLE_STATUSES, true)) {
                throw new \RuntimeException(__('vender/transfer.booking_not_transferable'));
            }

            $newBus = bus::with(['route', 'campany'])->whereKey($destination['bus_id'])->lockForUpdate()->first();
            if (!$newBus || !$newBus->campany) {
                throw new \RuntimeException(__('vender/transfer.new_bus_not_found'));
            }

            $this->assertDestinationBusAllowed($sourceBooking, $newBus, $actor, $companyId, $emergency);

            $crossCompany = $companyId !== null
                ? (int) $newBus->campany_id !== (int) $companyId
                : (int) $newBus->campany_id !== (int) ($sourceBooking->bus?->campany_id ?? $sourceBooking->campany_id);

            $newSchedule = $this->resolveDestinationSchedule(
                $destination,
                $newBus,
                $sourceBooking,
                $emergency
            );

            $keepOriginalFare = $emergency || $crossCompany;
            $keepOriginalCompany = $emergency && $crossCompany;

            $bookingIds = collect([$sourceBooking->id])
                ->merge($companionIds)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if (!empty($seatMap)) {
                $this->assertSeatMapValid($bookingIds->all(), $seatMap);
            }

            $occupiedSeats = Booking::query()
                ->whereNotIn('id', $bookingIds->all())
                ->where('bus_id', $newBus->id)
                ->where('travel_date', $newSchedule->schedule_date)
                ->whereIn('payment_status', self::TRANSFERABLE_STATUSES)
                ->lockForUpdate()
                ->pluck('seat')
                ->flatMap(fn ($seats) => explode(',', (string) $seats))
                ->map(fn ($seat) => trim($seat))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $claimedSeats = [];
            $transferredIds = [];
            foreach ($bookingIds as $bookingId) {
                $booking = Booking::whereKey($bookingId)->lockForUpdate()->first();
                if (!$booking) {
                    continue;
                }
                if (!$this->companionEligible($booking, $sourceBooking, $actor, $companyId, $options)) {
                    continue;
                }

                $originalSeats = array_values(array_filter(array_map('trim', explode(',', (string) $booking->seat))));
                $targetSeats = isset($seatMap[$bookingId])
                    ? array_values(array_filter(array_map('trim', explode(',', (string) $seatMap[$bookingId]))))
                    : $originalSeats;

                if (empty($targetSeats)) {
                    throw new \RuntimeException(__('vender/transfer.seat_map_invalid'));
                }

                if (isset($seatMap[$bookingId]) && count($targetSeats) !== count($originalSeats)) {
                    throw new \RuntimeException(__('vender/transfer.seat_count_mismatch'));
                }

                if (!empty(array_intersect($targetSeats, $occupiedSeats))
                    || !empty(array_intersect($targetSeats, $claimedSeats))) {
                    throw new \RuntimeException(__('vender/transfer.target_seats_unavailable'));
                }

                $claimedSeats = array_merge($claimedSeats, $targetSeats);

                $pricing = $keepOriginalFare
                    ? $this->keepOriginalPricing($booking)
                    : $this->buildTransferPricing(
                        $booking,
                        $newBus,
                        (string) $destination['pickup_point'],
                        (string) $destination['dropping_point']
                    );

                $updates = [
                    'bus_id' => $newBus->id,
                    'schedule_id' => $newSchedule->id,
                    'route_id' => $newBus->route?->id ?? $booking->route_id,
                    'campany_id' => $keepOriginalCompany ? $booking->campany_id : $newBus->campany->id,
                    'travel_date' => $newSchedule->schedule_date,
                    'pickup_point' => $destination['pickup_point'],
                    'dropping_point' => $destination['dropping_point'],
                    'seat' => implode(',', $targetSeats),
                    'amount' => $pricing['amount'],
                    'busFee' => $pricing['busFee'],
                    'discount_amount' => $pricing['discount_amount'],
                    'distance' => $pricing['distance'],
                    'bima_amount' => $pricing['bima_amount'],
                    'vat' => $pricing['vat'],
                    'fee' => $pricing['fee'],
                    'service' => $pricing['service'],
                    'vender_fee' => $pricing['vender_fee'],
                    'vender_service' => $pricing['vender_service'],
                    'payment_status' => $booking->payment_status,
                    'booking_code' => $this->generateRandomCode(),
                ];

                $passengerPatch = $this->passengerOverridePatch(
                    $passengerOverrides[$bookingId] ?? ($passengerOverrides[(string) $bookingId] ?? null)
                );
                if (!empty($passengerPatch)) {
                    $updates = array_merge($updates, $passengerPatch);
                }

                $booking->update($updates);
                $transferredIds[] = (int) $booking->id;
            }

            if (count($transferredIds) === 0) {
                throw new \RuntimeException(__('vender/transfer.booking_not_found'));
            }

            return [
                'transferred' => count($transferredIds),
                'booking_ids' => $transferredIds,
            ];
        });
    }

    /**
     * Bookings the given actor may transfer.
     *
     * @param  array{
     *     company_id?:int|null,
     *     vender_id?:int|null,
     *     user_id?:int|null,
     *     booking_ids?:array<int>
     * }  $options
     */
    public function transferableBookingsQuery(string $actor, array $options = []): Builder
    {
        $query = Booking::query()
            ->whereIn('payment_status', self::TRANSFERABLE_STATUSES)
            ->with(['bus.busname', 'route_name', 'schedule']);

        return match ($actor) {
            self::ACTOR_BUS_OWNER => $query->whereHas('bus', function ($q) use ($options) {
                $q->where('campany_id', (int) ($options['company_id'] ?? 0));
            }),
            self::ACTOR_VENDER => $query->where('vender_id', (int) ($options['vender_id'] ?? 0)),
            self::ACTOR_CUSTOMER => $query->where('user_id', (int) ($options['user_id'] ?? 0)),
            self::ACTOR_GUEST => $query->when(
                !empty($options['booking_ids']),
                fn (Builder $q) => $q->whereIn('id', $options['booking_ids']),
                fn (Builder $q) => $q->whereRaw('1 = 0')
            ),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @param  array{
     *     company_id?:int|null,
     *     vender_id?:int|null,
     *     user_id?:int|null,
     *     booking_ids?:array<int>
     * }  $options
     */
    public function listTransferableBookings(string $actor, array $options = []): Collection
    {
        return $this->transferableBookingsQuery($actor, $options)
            ->orderByDesc('travel_date')
            ->get();
    }

    /**
     * Destination buses scoped by actor / emergency / destination company.
     *
     * @param  array{
     *     company_id?:int|null,
     *     source_company_id?:int|null,
     *     dest_company_id?:int|null,
     *     emergency?:bool,
     *     allow_emergency?:bool,
     *     schedule_id?:int|null
     * }  $options
     */
    public function destinationBusesQuery(string $actor, array $options = []): Builder
    {
        $emergency = (bool) ($options['emergency'] ?? false)
            && (bool) ($options['allow_emergency'] ?? ($actor === self::ACTOR_BUS_OWNER));
        $companyId = isset($options['company_id']) ? (int) $options['company_id'] : null;
        $sourceCompanyId = isset($options['source_company_id'])
            ? (int) $options['source_company_id']
            : $companyId;
        $destCompanyId = isset($options['dest_company_id']) && $options['dest_company_id'] !== ''
            ? (int) $options['dest_company_id']
            : null;

        $query = bus::with(['busname', 'campany', 'route']);

        if ($actor === self::ACTOR_BUS_OWNER && $emergency) {
            if ($destCompanyId) {
                $query->where('campany_id', $destCompanyId);
            }
        } else {
            $scopeCompany = $sourceCompanyId ?? $companyId;
            $query->where('campany_id', (int) $scopeCompany);
        }

        return $query;
    }

    /**
     * @param  array{
     *     company_id?:int|null,
     *     source_company_id?:int|null,
     *     dest_company_id?:int|null,
     *     emergency?:bool,
     *     allow_emergency?:bool,
     *     schedule_id?:int|null
     * }  $options
     */
    public function listDestinationBuses(string $actor, array $options = []): Collection
    {
        $query = $this->destinationBusesQuery($actor, $options);
        $buses = $query->orderBy('bus_number')->get();

        $scheduleId = $options['schedule_id'] ?? null;
        if ($scheduleId) {
            $schedule = Schedule::with('bus.campany')->find($scheduleId);
            if ($schedule?->bus && !$buses->contains('id', $schedule->bus_id)) {
                $buses = $buses->prepend($schedule->bus);
            }
        }

        return $buses->unique('id')->values();
    }

    /**
     * Companion passengers on the same trip as the source booking (bus + date [+ schedule]).
     */
    public function listCompanionBookings(Booking $source): Collection
    {
        $query = Booking::query()
            ->where('id', '!=', $source->id)
            ->where('bus_id', $source->bus_id)
            ->where('travel_date', $source->travel_date)
            ->whereIn('payment_status', self::TRANSFERABLE_STATUSES);

        if ($source->schedule_id) {
            $query->where('schedule_id', $source->schedule_id);
        }

        return $query->orderBy('seat')->get();
    }

    /**
     * Preview pricing for UI (mirrors transfer fare rules).
     *
     * @return array<string, mixed>
     */
    public function previewPricing(
        Booking $originalBooking,
        bus $newBus,
        string $pickupPoint,
        string $droppingPoint,
        bool $emergency = false,
        ?int $actorCompanyId = null
    ): array {
        $crossCompany = $actorCompanyId !== null
            ? (int) ($newBus->campany?->id ?? 0) !== (int) $actorCompanyId
            : (int) ($newBus->campany_id ?? 0) !== (int) ($originalBooking->campany_id ?? 0);

        $keepOriginalFare = $emergency || $crossCompany;
        $companyName = $newBus->campany?->name ?? '';

        if ($keepOriginalFare) {
            $pricing = $this->keepOriginalPricing($originalBooking);

            return [
                'new_amount' => $pricing['amount'],
                'new_busFee' => $pricing['busFee'],
                'new_discount_amount' => $pricing['discount_amount'],
                'new_distance' => $pricing['distance'],
                'new_bima_amount' => $pricing['bima_amount'],
                'new_vat' => $pricing['vat'],
                'new_fee' => $pricing['fee'],
                'new_service' => $pricing['service'],
                'new_vender_fee' => $pricing['vender_fee'],
                'new_vender_service' => $pricing['vender_service'],
                'new_campany_id' => $crossCompany
                    ? $originalBooking->campany_id
                    : ($newBus->campany?->id ?? $originalBooking->campany_id),
                'new_route_id' => $newBus->route?->id ?? $originalBooking->route_id,
                'company_name' => $companyName,
                'company_id' => $newBus->campany_id,
                'keep_original_fare' => true,
            ];
        }

        $pricing = $this->buildTransferPricing($originalBooking, $newBus, $pickupPoint, $droppingPoint);

        return [
            'new_amount' => $pricing['amount'],
            'new_busFee' => $pricing['busFee'],
            'new_discount_amount' => $pricing['discount_amount'],
            'new_distance' => $pricing['distance'],
            'new_bima_amount' => $pricing['bima_amount'],
            'new_vat' => $pricing['vat'],
            'new_fee' => $pricing['fee'],
            'new_service' => $pricing['service'],
            'new_vender_fee' => $pricing['vender_fee'],
            'new_vender_service' => $pricing['vender_service'],
            'new_campany_id' => $newBus->campany->id,
            'new_route_id' => $newBus->route?->id ?? $originalBooking->route_id,
            'company_name' => $companyName,
            'company_id' => $newBus->campany_id,
            'keep_original_fare' => false,
        ];
    }

    public function formatTransferSchedule(Schedule $schedule): array
    {
        $bus = $schedule->bus;
        $companyName = $bus?->campany?->name ?? $bus?->busname?->name ?? '';

        return [
            'id' => $schedule->id,
            'from' => $schedule->from,
            'to' => $schedule->to,
            'schedule_date' => $schedule->schedule_date,
            'start' => $schedule->start,
            'end' => $schedule->end,
            'bus_id' => $schedule->bus_id,
            'bus_number' => $bus->bus_number ?? '',
            'company_id' => $bus->campany_id ?? null,
            'company_name' => $companyName,
            'driver_name' => $bus->driver_name ?? '',
            'conductor_name' => $bus->conductor_name ?? '',
            'route_id' => $schedule->route_id,
        ];
    }

    public function formatDestinationBus(bus $bus, ?int $preferredBusId = null): array
    {
        $companyName = $bus->campany->name ?? $bus->busname->name ?? '';

        return [
            'id' => $bus->id,
            'bus_number' => $bus->bus_number,
            'name' => $bus->busname->name ?? $companyName,
            'company_id' => $bus->campany_id,
            'company_name' => $companyName,
            'driver_name' => $bus->driver_name,
            'conductor_name' => $bus->conductor_name,
            'route_id' => $bus->route?->id ?? null,
            'total_seats' => (int) ($bus->total_seats ?? 0),
            'is_schedule_bus' => $preferredBusId && (int) $bus->id === (int) $preferredBusId,
        ];
    }

    /**
     * Company routes available for transfer filters (via buses owned by company).
     */
    public function listCompanyRoutes(int $companyId): Collection
    {
        return route::query()
            ->whereHas('bus', fn ($q) => $q->where('campany_id', $companyId))
            ->orderBy('from')
            ->orderBy('to')
            ->get(['id', 'from', 'to', 'bus_id']);
    }

    /**
     * Source buses that have transferable bookings on a date (+ optional route).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listSourceBuses(int $companyId, string $travelDate, ?int $routeId = null): Collection
    {
        $query = Booking::query()
            ->whereIn('payment_status', self::TRANSFERABLE_STATUSES)
            ->whereDate('travel_date', $travelDate)
            ->whereHas('bus', fn ($q) => $q->where('campany_id', $companyId))
            ->with(['bus.busname', 'bus.campany', 'bus.route']);

        if ($routeId) {
            $query->where(function ($q) use ($routeId) {
                $q->where('route_id', $routeId)
                    ->orWhereHas('bus.route', fn ($rq) => $rq->where('routes.id', $routeId));
            });
        }

        return $query->get()
            ->pluck('bus')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($bus) => $this->formatDestinationBus($bus));
    }

    /**
     * Occupied transferable seats for a source trip.
     *
     * @return array{total_seats:int, seats:array<int, array<string, mixed>>, bookings:array<int, array<string, mixed>>}
     */
    public function sourceOccupiedSeats(int $companyId, int $busId, string $travelDate, ?int $scheduleId = null): array
    {
        $bus = bus::with(['busname', 'campany'])->find($busId);
        if (!$bus || (int) $bus->campany_id !== $companyId) {
            throw new \RuntimeException(__('vender/transfer.booking_not_found_or_unauthorized'));
        }

        $query = Booking::query()
            ->where('bus_id', $busId)
            ->whereDate('travel_date', $travelDate)
            ->whereIn('payment_status', self::TRANSFERABLE_STATUSES)
            ->where('campany_id', $companyId);

        if ($scheduleId) {
            $query->where('schedule_id', $scheduleId);
        }

        $bookings = $query->orderBy('seat')->get();
        $seatRows = [];
        $bookingRows = [];

        foreach ($bookings as $booking) {
            $seats = array_values(array_filter(array_map('trim', explode(',', (string) $booking->seat))));
            $bookingRows[] = [
                'id' => (int) $booking->id,
                'booking_code' => $booking->booking_code,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'customer_email' => $booking->customer_email,
                'gender' => $booking->gender,
                'seat' => $booking->seat,
                'seats' => $seats,
                'seat_count' => count($seats),
                'pickup_point' => $booking->pickup_point,
                'dropping_point' => $booking->dropping_point,
                'amount' => $booking->amount,
                'payment_status' => $booking->payment_status,
                'schedule_id' => $booking->schedule_id,
                'route_id' => $booking->route_id,
                'travel_date' => $booking->travel_date,
            ];
            foreach ($seats as $seat) {
                $seatRows[] = [
                    'seat' => $seat,
                    'booking_id' => (int) $booking->id,
                    'booking_code' => $booking->booking_code,
                    'customer_name' => $booking->customer_name,
                    'payment_status' => $booking->payment_status,
                ];
            }
        }

        return [
            'total_seats' => (int) ($bus->total_seats ?? 0),
            'bus' => $this->formatDestinationBus($bus),
            'seats' => $seatRows,
            'bookings' => $bookingRows,
        ];
    }

    /**
     * Destination seat availability for a bus/date.
     *
     * @return array{total_seats:int, occupied:array<int, string>, available:array<int, string>}
     */
    public function destinationSeatAvailability(int $busId, string $travelDate, ?int $scheduleId = null, array $excludeBookingIds = []): array
    {
        $bus = bus::find($busId);
        if (!$bus) {
            throw new \RuntimeException(__('vender/transfer.new_bus_not_found'));
        }

        $total = max(0, (int) ($bus->total_seats ?? 0));
        $query = Booking::query()
            ->where('bus_id', $busId)
            ->whereDate('travel_date', $travelDate)
            ->whereIn('payment_status', self::TRANSFERABLE_STATUSES);

        if ($scheduleId) {
            $query->where(function ($q) use ($scheduleId) {
                $q->where('schedule_id', $scheduleId)->orWhereNull('schedule_id');
            });
        }

        if (!empty($excludeBookingIds)) {
            $query->whereNotIn('id', array_map('intval', $excludeBookingIds));
        }

        $occupied = $query->pluck('seat')
            ->flatMap(fn ($seats) => explode(',', (string) $seats))
            ->map(fn ($seat) => trim($seat))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $allSeats = [];
        for ($i = 1; $i <= $total; $i++) {
            $allSeats[] = (string) $i;
        }

        // If bus has no total_seats, still expose occupied labels for UI.
        if ($total === 0 && !empty($occupied)) {
            $allSeats = $occupied;
        }

        $available = array_values(array_diff($allSeats, $occupied));

        return [
            'total_seats' => $total,
            'occupied' => array_values($occupied),
            'available' => $available,
            'all_seats' => $allSeats,
        ];
    }

    /** @param  array<int|string, mixed>  $seatMap */
    private function normalizeSeatMap(array $seatMap): array
    {
        $normalized = [];
        foreach ($seatMap as $bookingId => $seats) {
            $id = (int) $bookingId;
            if ($id <= 0) {
                continue;
            }
            if (is_array($seats)) {
                $seats = implode(',', $seats);
            }
            $parts = array_values(array_filter(array_map('trim', explode(',', (string) $seats))));
            if (empty($parts)) {
                continue;
            }
            $normalized[$id] = implode(',', $parts);
        }

        return $normalized;
    }

    /**
     * @param  array<int>  $bookingIds
     * @param  array<int, string>  $seatMap
     */
    private function assertSeatMapValid(array $bookingIds, array $seatMap): void
    {
        $missing = array_diff($bookingIds, array_keys($seatMap));
        if (!empty($missing)) {
            throw new \RuntimeException(__('vender/transfer.seat_map_required'));
        }

        $allSeats = [];
        foreach ($seatMap as $seats) {
            foreach (array_filter(array_map('trim', explode(',', (string) $seats))) as $seat) {
                if (in_array($seat, $allSeats, true)) {
                    throw new \RuntimeException(__('vender/transfer.seat_map_duplicate'));
                }
                $allSeats[] = $seat;
            }
        }
    }

    /** @param  array<string, mixed>|null  $override */
    private function passengerOverridePatch(?array $override): array
    {
        if (empty($override) || !is_array($override)) {
            return [];
        }

        $patch = [];
        foreach (['customer_name', 'customer_phone', 'customer_email', 'gender'] as $field) {
            if (!array_key_exists($field, $override)) {
                continue;
            }
            $value = is_string($override[$field]) ? trim($override[$field]) : $override[$field];
            if ($value === null || $value === '') {
                continue;
            }
            $patch[$field] = $value;
        }

        return $patch;
    }

    private function assertSourceAuthorized(
        Booking $sourceBooking,
        string $actor,
        ?int $companyId,
        array $options
    ): void {
        match ($actor) {
            self::ACTOR_BUS_OWNER => (function () use ($sourceBooking, $companyId) {
                if (!$companyId || (int) $sourceBooking->campany_id !== (int) $companyId) {
                    throw new \RuntimeException(__('vender/transfer.booking_company_mismatch'));
                }
            })(),
            self::ACTOR_VENDER => (function () use ($sourceBooking, $options) {
                $venderId = (int) ($options['vender_id'] ?? 0);
                if (!$venderId || (int) $sourceBooking->vender_id !== $venderId) {
                    throw new \RuntimeException(__('vender/transfer.booking_not_found_or_unauthorized'));
                }
            })(),
            self::ACTOR_CUSTOMER => (function () use ($sourceBooking, $options) {
                $userId = (int) ($options['user_id'] ?? 0);
                if (!$userId || (int) $sourceBooking->user_id !== $userId) {
                    throw new \RuntimeException(__('vender/transfer.booking_not_found_or_unauthorized'));
                }
            })(),
            self::ACTOR_GUEST => null, // Caller must already have verified allow-list.
            default => throw new \RuntimeException(__('vender/transfer.booking_not_found_or_unauthorized')),
        };
    }

    private function assertDestinationBusAllowed(
        Booking $sourceBooking,
        bus $newBus,
        string $actor,
        ?int $companyId,
        bool $emergency
    ): void {
        if ($actor === self::ACTOR_BUS_OWNER) {
            $crossCompany = $companyId !== null && (int) $newBus->campany_id !== (int) $companyId;
            if (!$emergency && $crossCompany) {
                throw new \RuntimeException(__('vender/transfer.new_bus_company_mismatch'));
            }

            return;
        }

        // vender / customer / guest: same company as source booking's bus only
        $sourceBooking->loadMissing('bus');
        $sourceCompanyId = (int) ($sourceBooking->bus?->campany_id ?? $sourceBooking->campany_id);
        if ((int) $newBus->campany_id !== $sourceCompanyId) {
            throw new \RuntimeException(__('vender/transfer.new_bus_company_mismatch'));
        }
    }

    private function companionEligible(
        Booking $booking,
        Booking $sourceBooking,
        string $actor,
        ?int $companyId,
        array $options
    ): bool {
        if (!in_array($booking->payment_status, self::TRANSFERABLE_STATUSES, true)) {
            return false;
        }
        if ((int) $booking->bus_id !== (int) $sourceBooking->bus_id
            || (string) $booking->travel_date !== (string) $sourceBooking->travel_date) {
            return false;
        }

        return match ($actor) {
            self::ACTOR_BUS_OWNER => $companyId !== null && (int) $booking->campany_id === (int) $companyId,
            self::ACTOR_VENDER => (int) $booking->vender_id === (int) ($options['vender_id'] ?? 0),
            self::ACTOR_CUSTOMER => (int) $booking->id === (int) $sourceBooking->id,
            self::ACTOR_GUEST => true,
            default => false,
        };
    }

    /**
     * @param  array{
     *     schedule_id?:int|null,
     *     travel_date:string,
     *     emergency_start?:string|null,
     *     emergency_end?:string|null
     * }  $destination
     */
    public function resolveDestinationSchedule(
        array $destination,
        bus $newBus,
        Booking $sourceBooking,
        bool $emergency
    ): Schedule {
        $sourceBooking->loadMissing(['schedule', 'route_name']);
        $scheduleId = $destination['schedule_id'] ?? null;

        if (!empty($scheduleId)) {
            $schedule = Schedule::whereKey($scheduleId)->lockForUpdate()->first();
            if (!$schedule) {
                throw new \RuntimeException(__('vender/transfer.new_schedule_not_found'));
            }
            if ((int) $schedule->bus_id === (int) $newBus->id) {
                if ((string) $schedule->schedule_date !== (string) $destination['travel_date'] && !$emergency) {
                    throw new \RuntimeException(__('vender/transfer.invalid_schedule_for_bus_date'));
                }

                return $schedule;
            }
            if (!$emergency) {
                throw new \RuntimeException(__('vender/transfer.invalid_schedule_for_bus_date'));
            }

            return $this->findOrCreateEmergencySchedule($newBus, $schedule, $sourceBooking);
        }

        if (!$emergency) {
            throw new \RuntimeException(__('vender/transfer.select_new_schedule'));
        }

        $original = $sourceBooking->schedule;
        $template = $original ? $original->replicate() : new Schedule();
        $template->from = $original?->from ?: ($sourceBooking->route_name->from ?? '');
        $template->to = $original?->to ?: ($sourceBooking->route_name->to ?? '');
        $template->schedule_date = $destination['travel_date'];
        $template->start = ($destination['emergency_start'] ?? null) ?: ($original?->start ?? '06:00:00');
        $template->end = ($destination['emergency_end'] ?? null) ?: ($original?->end ?? '18:00:00');
        $template->route_id = $original?->route_id ?? $sourceBooking->route_id;

        return $this->findOrCreateEmergencySchedule($newBus, $template, $sourceBooking);
    }

    public function findOrCreateEmergencySchedule(bus $newBus, Schedule $template, Booking $source): Schedule
    {
        $existing = Schedule::where('bus_id', $newBus->id)
            ->whereDate('schedule_date', $template->schedule_date)
            ->where('start', $template->start)
            ->lockForUpdate()
            ->first();
        if ($existing) {
            return $existing;
        }

        return Schedule::create([
            'bus_id' => $newBus->id,
            'route_id' => $newBus->route?->id ?? $template->route_id ?? $source->route_id,
            'from' => $template->from ?: ($source->route_name->from ?? ''),
            'to' => $template->to ?: ($source->route_name->to ?? ''),
            'schedule_date' => $template->schedule_date,
            'start' => $template->start,
            'end' => $template->end,
        ]);
    }

    /** @return array<string, float> */
    public function buildTransferPricing(Booking $booking, bus $newBus, string $pickupPoint, string $droppingPoint): array
    {
        $seatCount = $this->formulaService->seatCountFromSeatString($booking->seat);

        $baseFare = max(0, (float) ($newBus->route->price ?? 0) * $seatCount);
        $discountAmount = max(0, (float) ($booking->discount_amount ?? 0));
        $discountAmount = min($discountAmount, $baseFare);

        $discountedFare = max(0, $baseFare - $discountAmount);
        $setting = Setting::first();
        $fee = $this->formulaService->calculateTravellerServiceFee($discountedFare, $setting, $seatCount);
        $distance = RouteDistanceService::resolveForBooking(
            null,
            $pickupPoint,
            $droppingPoint,
            (float) ($newBus->route->distance ?? 0)
        );

        return [
            'amount' => round($baseFare, 2),
            'busFee' => round($baseFare, 2),
            'discount_amount' => round($discountAmount, 2),
            'distance' => round($distance, 2),
            'bima_amount' => round((float) ($booking->bima_amount ?? 0), 2),
            'vat' => round($baseFare * 0.005, 2),
            'fee' => round($fee, 2),
            'service' => round((float) ($booking->service ?? 0), 2),
            'vender_fee' => round((float) ($booking->vender_fee ?? 0), 2),
            'vender_service' => round((float) ($booking->vender_service ?? 0), 2),
        ];
    }

    /** @return array<string, float> */
    public function keepOriginalPricing(Booking $booking): array
    {
        return [
            'amount' => round((float) ($booking->amount ?? 0), 2),
            'busFee' => round((float) ($booking->busFee ?? $booking->amount ?? 0), 2),
            'discount_amount' => round((float) ($booking->discount_amount ?? 0), 2),
            'distance' => round((float) ($booking->distance ?? 0), 2),
            'bima_amount' => round((float) ($booking->bima_amount ?? 0), 2),
            'vat' => round((float) ($booking->vat ?? 0), 2),
            'fee' => round((float) ($booking->fee ?? 0), 2),
            'service' => round((float) ($booking->service ?? 0), 2),
            'vender_fee' => round((float) ($booking->vender_fee ?? 0), 2),
            'vender_service' => round((float) ($booking->vender_service ?? 0), 2),
        ];
    }

    public function generateRandomCode(): string
    {
        do {
            $letters = '';
            for ($i = 0; $i < 2; $i++) {
                $letters .= chr(rand(65, 90));
            }
            $numbers = '';
            for ($i = 0; $i < 8; $i++) {
                $numbers .= rand(0, 9);
            }
            $code = $letters . $numbers;
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
