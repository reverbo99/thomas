<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ClickPesaController;
use App\Http\Controllers\SmsController;
use App\Models\Coaster;
use App\Models\Setting;
use App\Models\SpecialHireOrder;
use App\Models\SpecialHirePaymentIntent;
use App\Models\SpecialHirePricing;
use App\Models\User;
use App\Services\SpecialHireOrderPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CustomerApiController extends Controller
{
    /**
     * Customer registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'status' => 'accept',
        ]);

        $token = $user->createToken('customer-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Customer login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();

        // Check if user is a customer
        if ($user->role !== 'customer') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Customer access only.',
            ], 403);
        }

        $token = $user->createToken('customer-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Get customer profile.
     */
    public function profile()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Update customer profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [];
        if ($request->has('name')) {
            $data['name'] = $request->name;
        }
        if ($request->has('phone')) {
            // Write to whichever column exists (phone or contact)
            if (Schema::hasColumn('users', 'phone')) {
                $data['phone'] = $request->phone;
            }
            if (Schema::hasColumn('users', 'contact')) {
                $data['contact'] = $request->phone;
            }
        }
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
        ]);
    }

    /**
     * Get bookable coasters with availability status (list + optional map).
     *
     * Lat/lng are optional: operators count "Active Coasters" by status=available
     * without requiring a GPS ping. Hiding null coordinates emptied the customer
     * list for newly created / not-yet-tracked vehicles.
     */
    public function getCoasters(Request $request)
    {
        // Bookable fleet: available + currently on hire (shown as busy).
        // Maintenance is not bookable and is excluded.
        $query = Coaster::with(['pricing', 'driver'])
            ->whereIn('status', ['available', 'on_hire']);

        // If date and time provided, check availability
        if ($request->has('date') && $request->has('time')) {
            $date = $request->date;
            $time = $request->time;

            $query->with(['orders' => function ($q) use ($date, $time) {
                $q->where('hire_date', $date)
                    ->where('hire_time', '<=', $time)
                    ->whereIn('order_status', ['confirmed', 'in_progress']);
            }]);
        }

        $coasters = $query->get()->map(function ($coaster) use ($request) {
            $isAvailable = true;
            $availabilityStatus = 'available'; // green

            // Check if coaster has active orders at requested time
            if ($request->has('date') && $request->has('time')) {
                if ($coaster->orders->count() > 0) {
                    $isAvailable = false;
                    $availabilityStatus = 'busy'; // red
                }
            } else {
                // Check current status
                if ($coaster->status === 'on_hire') {
                    $isAvailable = false;
                    $availabilityStatus = 'busy'; // red
                }
            }

            $hasLocation = $coaster->latitude !== null && $coaster->longitude !== null;

            $data = [
                'id' => $coaster->id,
                'name' => $coaster->name,
                'plate_number' => $coaster->plate_number,
                'capacity' => $coaster->capacity,
                'model' => $coaster->model,
                'color' => $coaster->color,
                'features' => $coaster->features,
                'image_url' => $coaster->image_url,
                'latitude' => $coaster->latitude,
                'longitude' => $coaster->longitude,
                'has_location' => $hasLocation,
                'is_available' => $isAvailable,
                'availability_status' => $availabilityStatus,
                'status' => $coaster->status,
                'pricing' => $coaster->pricing,
            ];

            // Add driver information if available
            if ($coaster->driver) {
                $data['driver'] = [
                    'id' => $coaster->driver->id,
                    'name' => $coaster->driver->name,
                    'phone' => $coaster->driver->phone ?? $coaster->driver_contact,
                    'email' => $coaster->driver->email,
                ];
            } else if ($coaster->driver_name) {
                // Fallback to legacy driver fields if no driver account
                $data['driver'] = [
                    'name' => $coaster->driver_name,
                    'phone' => $coaster->driver_contact,
                ];
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $coasters,
        ]);
    }

    /**
     * Get single coaster details.
     */
    public function getCoaster($id)
    {
        $coaster = Coaster::with(['pricing', 'driver'])->find($id);

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'Coaster not found',
            ], 404);
        }

        $isAvailable = $coaster->status === 'available';
        $hasLocation = $coaster->latitude !== null && $coaster->longitude !== null;
        $data = [
            'id' => $coaster->id,
            'name' => $coaster->name,
            'plate_number' => $coaster->plate_number,
            'capacity' => $coaster->capacity,
            'model' => $coaster->model,
            'color' => $coaster->color,
            'features' => $coaster->features,
            'image_url' => $coaster->image_url,
            'latitude' => $coaster->latitude,
            'longitude' => $coaster->longitude,
            'has_location' => $hasLocation,
            'is_available' => $isAvailable,
            'availability_status' => $isAvailable ? 'available' : 'busy',
            'status' => $coaster->status,
            'pricing' => $coaster->pricing,
        ];

        // Add driver information if available
        if ($coaster->driver) {
            $data['driver'] = [
                'id' => $coaster->driver->id,
                'name' => $coaster->driver->name,
                'phone' => $coaster->driver->phone ?? $coaster->driver_contact,
                'email' => $coaster->driver->email,
            ];
        } else if ($coaster->driver_name) {
            // Fallback to legacy driver fields if no driver account
            $data['driver'] = [
                'name' => $coaster->driver_name,
                'phone' => $coaster->driver_contact,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Calculate price for a trip.
     */
    public function calculatePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coaster_id' => 'required|exists:coasters,id',
            'pickup_latitude' => 'nullable|numeric',
            'pickup_longitude' => 'nullable|numeric',
            'dropoff_latitude' => 'nullable|numeric',
            'dropoff_longitude' => 'nullable|numeric',
            'distance_km' => 'nullable|numeric|min:0',
            'routed_distance_km' => 'nullable|numeric|min:0|max:8000',
            'distance_mode' => 'nullable|in:straight,route',
            'hire_date' => 'required|date',
            'hire_time' => 'required',
            'return_date' => 'nullable|date|after_or_equal:hire_date',
            'return_time' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $coaster = Coaster::with('pricing')->find($request->coaster_id);

        if (!$coaster->pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing not set for this coaster',
            ], 400);
        }

        $winStart = Carbon::parse($request->hire_date)->startOfDay();
        $winEnd = Carbon::parse($request->input('return_date', $request->hire_date))->startOfDay();
        if ($coaster->hasHireScheduleConflict($winStart, $winEnd)) {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is not available for the selected hire dates.',
            ], 422);
        }

        $haversineKm = null;
        if ($request->pickup_latitude !== null && $request->dropoff_latitude !== null
            && $request->pickup_longitude !== null && $request->dropoff_longitude !== null) {
            $haversineKm = SpecialHirePricing::calculateDistance(
                $request->pickup_latitude,
                $request->pickup_longitude,
                $request->dropoff_latitude,
                $request->dropoff_longitude
            );
        }

        // Prefer explicit client/OSM `distance_km` when provided. Optional
        // `distance_mode=route` + `routed_distance_km` is a validated alternate.
        // Haversine from coordinates is the last fallback.
        $distanceKm = null;
        if ($request->filled('distance_km')) {
            $distanceKm = (float) $request->distance_km;
        } elseif ($request->input('distance_mode') === 'route'
            && $request->filled('routed_distance_km')
            && $haversineKm !== null) {
            $routed = (float) $request->routed_distance_km;
            $minOk = max($haversineKm * 0.72, 0.05);
            $maxOk = max($haversineKm * 14, $haversineKm + 250);
            if ($routed >= $minOk && $routed <= $maxOk) {
                $distanceKm = $routed;
            } else {
                $distanceKm = $haversineKm;
            }
        } elseif ($haversineKm !== null) {
            $distanceKm = $haversineKm;
        }

        if (!$distanceKm) {
            return response()->json([
                'success' => false,
                'message' => 'Distance is required. Provide either coordinates or distance_km.',
            ], 400);
        }

        // Calculate price
        $priceData = $coaster->pricing->calculatePrice(
            $distanceKm,
            $request->hire_date,
            $request->hire_time
        );

        $owner = User::query()->find($coaster->user_id);
        $commission = SpecialHireOrder::previewPlatformCommission(
            (float) $priceData['total_amount'],
            $owner ? (float) ($owner->special_hire_platform_percent ?? 0) : null
        );

        return response()->json([
            'success' => true,
            'data' => array_merge($priceData, $commission),
        ]);
    }

    /**
     * Start ClickPesa for a hire WITHOUT creating SpecialHireOrder.
     * Order is created only after sync confirms payment (preparePayment → syncIntentPayment).
     */
    public function preparePayment(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'coaster_id' => 'required|exists:coasters,id',
            'pickup_location' => 'required|string|max:255',
            'pickup_latitude' => 'nullable|numeric',
            'pickup_longitude' => 'nullable|numeric',
            'dropoff_location' => 'required|string|max:255',
            'dropoff_latitude' => 'nullable|numeric',
            'dropoff_longitude' => 'nullable|numeric',
            'hire_date' => 'required|date|after_or_equal:today',
            'hire_time' => 'required',
            'return_date' => 'nullable|date|after_or_equal:hire_date',
            'return_time' => 'nullable',
            'passengers_count' => 'required|integer|min:1',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'total_amount' => 'required|numeric|min:0',
            'distance_km' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $coaster = Coaster::with('pricing')->find($request->coaster_id);
        if (! $coaster) {
            return response()->json(['success' => false, 'message' => 'Coaster not found'], 404);
        }
        if (! $coaster->pricing) {
            return response()->json(['success' => false, 'message' => 'Pricing not set for this coaster'], 400);
        }

        $winStart = Carbon::parse($request->hire_date)->startOfDay();
        $winEnd = Carbon::parse($request->input('return_date', $request->hire_date))->startOfDay();
        if ($coaster->hasHireScheduleConflict($winStart, $winEnd)) {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is not available for the selected hire dates.',
            ], 422);
        }

        $amount = round((float) $request->total_amount, 2);
        $payload = [
            'coaster_id' => (int) $request->coaster_id,
            'pickup_location' => $request->pickup_location,
            'pickup_latitude' => $request->pickup_latitude,
            'pickup_longitude' => $request->pickup_longitude,
            'dropoff_location' => $request->dropoff_location,
            'dropoff_latitude' => $request->dropoff_latitude,
            'dropoff_longitude' => $request->dropoff_longitude,
            'hire_date' => $request->hire_date,
            'hire_time' => $request->hire_time,
            'return_date' => $request->return_date,
            'return_time' => $request->return_time,
            'passengers_count' => (int) $request->passengers_count,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'distance_km' => (float) $request->distance_km,
            'total_amount' => $amount,
            'customer_phone' => $request->phone,
        ];

        $intent = SpecialHirePaymentIntent::create([
            'customer_user_id' => $user->id,
            'coaster_id' => $coaster->id,
            'payload' => $payload,
            'amount' => $amount,
            'phone' => $request->phone,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(45),
        ]);

        if ((bool) (Setting::query()->value('test_mode') ?? false)) {
            $ref = 'TESTSH'.$intent->id.'T'.now()->format('YmdHis');
            $intent->update(['clickpesa_ref' => $ref]);

            try {
                $order = app(SpecialHireOrderPaymentService::class)
                    ->finalizePaidIntent($intent, (object) [
                        'status' => 'success',
                        'amount' => $amount,
                        'reference' => $ref,
                        'payment_method' => 'test_mode',
                    ], $ref);
            } catch (\Throwable $e) {
                $intent->update(['status' => 'expired']);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            $orderPayload = $order->toArray();
            $orderPayload['hire_next_step'] = $order->customerHireNextStep();

            return response()->json([
                'success' => true,
                'message' => 'Test mode is enabled. Payment approved automatically and hire saved.',
                'data' => [
                    'intent_id' => $intent->id,
                    'order_reference' => $ref,
                    'amount' => $amount,
                    'test_mode' => true,
                    'booking' => $orderPayload,
                ],
            ]);
        }

        $parts = explode(' ', trim($user->name), 2);
        $out = app(SpecialHireOrderPaymentService::class)->initiateIntentUssd(
            $intent,
            $request->phone,
            $parts[0] ?? 'Customer',
            $parts[1] ?? '',
            $user->email ?? ''
        );

        if (! $out['ok']) {
            $intent->update(['status' => 'expired']);

            return response()->json([
                'success' => false,
                'message' => $out['error'] ?? 'Payment start failed',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request sent to phone. Approve on your handset. Hire is saved only after payment succeeds.',
            'data' => [
                'intent_id' => $intent->id,
                'order_reference' => $out['order_reference'] ?? null,
                'amount' => $amount,
                'clickpesa' => $out['response'] ?? null,
                'expires_at' => optional($intent->expires_at)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Poll ClickPesa for a payment intent. On success, create SpecialHireOrder (paid) and return it.
     */
    public function syncIntentPayment(Request $request, int $intentId)
    {
        $user = Auth::user();
        $intent = SpecialHirePaymentIntent::where('customer_user_id', $user->id)->find($intentId);
        if (! $intent) {
            return response()->json(['success' => false, 'message' => 'Payment session not found'], 404);
        }

        if ($intent->status === 'consumed' && $intent->special_hire_order_id) {
            $order = SpecialHireOrder::with('coaster')->find($intent->special_hire_order_id);
            if ($order) {
                $payload = $order->toArray();
                $payload['hire_next_step'] = $order->customerHireNextStep();

                return response()->json(['success' => true, 'data' => $payload]);
            }
        }

        if ($intent->isExpired() || $intent->status === 'expired') {
            $intent->update(['status' => 'expired']);

            return response()->json([
                'success' => false,
                'message' => 'Payment session expired. Start payment again.',
            ], 400);
        }

        $ref = $request->input('reference') ?: $intent->clickpesa_ref;
        if (! $ref) {
            return response()->json(['success' => false, 'message' => 'No payment reference to check'], 422);
        }

        $cp = new ClickPesaController();
        $verify = $cp->verifyTransaction($ref);
        $statusStr = is_object($verify) && isset($verify->status) ? (string) $verify->status : '';
        $lower = strtolower(trim($statusStr));
        $isPaid = $lower === 'success' || ClickPesaController::clickPesaPaidStatus($statusStr);
        if (! $isPaid) {
            $message = is_string($verify) ? $verify : 'Payment not completed yet';
            if (is_object($verify) && isset($verify->status)) {
                if ($lower === 'api_error' && isset($verify->message)) {
                    $message = 'Could not verify with ClickPesa yet. Try again shortly. '
                        .(is_string($verify->message) ? $verify->message : '');
                } elseif (in_array($lower, ['pending', 'initiated', 'processing'], true)) {
                    $message = 'Payment is still processing. Approve on your phone if prompted, then tap Sync again.';
                }
            }

            return response()->json([
                'success' => false,
                'message' => trim($message) !== '' ? trim($message) : 'Payment not completed yet',
                'data' => ['intent_id' => $intent->id],
            ], 400);
        }

        try {
            $order = app(SpecialHireOrderPaymentService::class)
                ->finalizePaidIntent($intent, $verify, $ref);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $payload = $order->toArray();
        $payload['hire_next_step'] = $order->customerHireNextStep();

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed. Hire saved.',
            'data' => $payload,
        ]);
    }

    /**
     * Create booking request.
     */
    public function createBooking(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'coaster_id' => 'required|exists:coasters,id',
            'pickup_location' => 'required|string|max:255',
            'pickup_latitude' => 'nullable|numeric',
            'pickup_longitude' => 'nullable|numeric',
            'dropoff_location' => 'required|string|max:255',
            'dropoff_latitude' => 'nullable|numeric',
            'dropoff_longitude' => 'nullable|numeric',
            'hire_date' => 'required|date|after_or_equal:today',
            'hire_time' => 'required',
            'return_date' => 'nullable|date|after_or_equal:hire_date',
            'return_time' => 'nullable',
            'passengers_count' => 'required|integer|min:1',
            'purpose' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'contact' => 'nullable|string|max:20',
            'total_amount' => 'required|numeric|min:0',
            'distance_km' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Ensure we have a customer phone (DB column is non-nullable)
        $customerPhone = $request->phone
            ?: $request->contact
            ?: ($user->phone ?? null)
            ?: ($user->contact ?? null);
        if (!$customerPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is required. Update your profile or include phone in the booking request.',
            ], 422);
        }

        // Find coaster with pricing
        $coaster = Coaster::with('pricing')->find($request->coaster_id);

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'Coaster not found',
            ], 404);
        }

        if (!$coaster->pricing) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing not set for this coaster',
            ], 400);
        }

        $winStart = Carbon::parse($request->hire_date)->startOfDay();
        $winEnd = Carbon::parse($request->input('return_date', $request->hire_date))->startOfDay();
        if ($coaster->hasHireScheduleConflict($winStart, $winEnd)) {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is not available for the selected hire dates.',
            ], 422);
        }

        // Use the amount and distance provided by the customer app
        // The customer app calculates: distance × price_per_km + surcharges
        $distanceKm = $request->distance_km;
        $totalAmount = $request->total_amount;
        
        // Calculate price breakdown for record-keeping
        $priceData = $coaster->pricing->calculatePrice(
            $distanceKm,
            $request->hire_date,
            $request->hire_time
        );

        // Create order
        $breakdown = $priceData['breakdown'] ?? $priceData;

        $owner = User::query()->find($coaster->user_id);
        $platformPct = $owner ? (float) ($owner->special_hire_platform_percent ?? 0) : 0.0;

        // Full hire amount collected at booking (ClickPesa deposit); no remaining balance.
        $depositAmount = round((float) $totalAmount, 2);
        $balanceAmount = 0;

        $order = SpecialHireOrder::create([
            'user_id' => $coaster->user_id, // Admin/Owner
            'customer_user_id' => $user->id, // Customer
            'coaster_id' => $coaster->id,
            'customer_name' => $user->name,
            'customer_phone' => $customerPhone,
            'customer_email' => $user->email,
            'pickup_location' => $request->pickup_location,
            'pickup_latitude' => $request->pickup_latitude,
            'pickup_longitude' => $request->pickup_longitude,
            'dropoff_location' => $request->dropoff_location,
            'dropoff_latitude' => $request->dropoff_latitude,
            'dropoff_longitude' => $request->dropoff_longitude,
            'hire_date' => $request->hire_date,
            'hire_time' => $request->hire_time,
            'return_date' => $request->return_date,
            'return_time' => $request->return_time,
            'passengers_count' => $request->passengers_count,
            'purpose' => $request->purpose,
            'notes' => $request->notes,
            'distance_km' => $distanceKm,
            'base_price' => 0, // No base price
            'price_per_km' => $coaster->pricing->price_per_km,
            'km_amount' => $breakdown['km_amount'],
            'surcharge_percent' => $breakdown['surcharge_percent'],
            'surcharge_amount' => $breakdown['surcharge_amount'],
            'total_amount' => $totalAmount, // Use amount from customer app
            'deposit_amount' => $depositAmount,
            'balance_amount' => $balanceAmount,
            'platform_commission_percent' => $platformPct,
            'order_status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Guarantee pay_deposit is available for confirm-booking ClickPesa push.
        $order->ensureUpfrontDepositPayment();
        $order = $order->fresh();

        $coaster->load('driver');
        $driver = $coaster->driver;
        if ($driver) {
            $phone = $driver->contact ?: $driver->phone;
            if ($phone) {
                $msg = 'HISGC: New special hire '.$order->order_code.' on '.($coaster->name ?? 'your vehicle').'. Open the Driver app to Accept or Decline.';
                app(SmsController::class)->sms_send($phone, $msg);
            }

            // Realtime push to the driver app (works even when it's closed).
            try {
                app(\App\Services\FcmService::class)->sendToUser(
                    $driver,
                    'New hire request',
                    'New special hire '.$order->order_code.' on '.($coaster->name ?? 'your vehicle').'. Tap to Accept or Decline.',
                    [
                        'type' => 'hire_request',
                        'order_id' => (string) $order->id,
                        'order_code' => (string) $order->order_code,
                    ],
                    'bushire_driver'
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('FCM driver notify failed: '.$e->getMessage());
            }
        }

        $payload = $order->load('coaster')->toArray();
        $payload['hire_next_step'] = $order->customerHireNextStep();

        return response()->json([
            'success' => true,
            'message' => 'Booking request created successfully',
            'data' => $payload,
        ], 201);
    }

    /**
     * Get customer's bookings.
     */
    public function getBookings(Request $request)
    {
        $user = Auth::user();

        $query = SpecialHireOrder::where('customer_user_id', $user->id)
            ->with('coaster');

        // Filter by status
        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->orderBy('hire_date', 'desc')
            ->orderBy('hire_time', 'desc')
            ->paginate($request->get('per_page', 15));

        $orders->setCollection(
            $orders->getCollection()->map(static function (SpecialHireOrder $order): array {
                $payload = $order->toArray();
                $payload['hire_next_step'] = $order->customerHireNextStep();

                return $payload;
            })
        );

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get single booking details.
     */
    public function getBooking($id)
    {
        $user = Auth::user();

        $order = SpecialHireOrder::where('customer_user_id', $user->id)
            ->with('coaster')
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        $payload = $order->toArray();
        $payload['hire_next_step'] = $order->customerHireNextStep();

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * Customer: start ClickPesa USSD for the hire deposit (full amount at booking).
     *
     * Heals legacy orders that stored the full amount on balance (wait_owner)
     * so confirm-booking pay-push works without waiting for the driver.
     */
    public function specialHirePayDeposit(Request $request, int $id)
    {
        $user = Auth::user();
        $order = SpecialHireOrder::where('customer_user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $order->ensureUpfrontDepositPayment();
        $order = $order->fresh();

        if ($order->customerHireNextStep() !== 'pay_deposit') {
            return response()->json([
                'success' => false,
                'message' => 'Deposit is not required at this step',
                'data' => [
                    'hire_next_step' => $order->customerHireNextStep(),
                    'deposit_amount' => $order->deposit_amount,
                    'balance_amount' => $order->balance_amount,
                ],
            ], 400);
        }

        $request->validate(['phone' => 'required|string|max:20']);

        $parts = explode(' ', trim($user->name), 2);

        $svc = app(SpecialHireOrderPaymentService::class);
        $out = $svc->initiateUssd(
            $order,
            'deposit',
            $request->phone,
            $parts[0] ?? 'Customer',
            $parts[1] ?? '',
            $user->email ?? ''
        );

        if (!$out['ok']) {
            return response()->json(['success' => false, 'message' => $out['error'] ?? 'Payment start failed'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request sent to phone. Approve on your handset, then tap sync.',
            'data' => [
                'order_reference' => $out['order_reference'] ?? null,
                'clickpesa' => $out['response'] ?? null,
                'hire_next_step' => $order->customerHireNextStep(),
            ],
        ]);
    }

    /**
     * Customer: submit passenger seat names when hire_next_step is enter_passengers
     * (after deposit when balance is 0, or after balance paid when split payment).
     * Must match passengers_count.
     */
    public function specialHirePassengers(Request $request, int $id)
    {
        $user = Auth::user();
        $order = SpecialHireOrder::where('customer_user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }
        if ($order->customerHireNextStep() !== 'enter_passengers') {
            return response()->json(['success' => false, 'message' => 'Passenger list is not editable now'], 400);
        }

        $request->validate([
            'seat_names' => 'required|array|min:1',
            'seat_names.*' => 'required|string|max:120',
        ]);
        if (count($request->seat_names) !== (int) $order->passengers_count) {
            return response()->json([
                'success' => false,
                'message' => 'Provide exactly ' . $order->passengers_count . ' passenger names.',
            ], 422);
        }

        $order->update(['passenger_seats' => array_values($request->seat_names)]);
        $order = $order->fresh('coaster');
        $order->markCompletedIfHireFlowDone();
        $order = $order->fresh('coaster');

        $payload = $order->toArray();
        $payload['hire_next_step'] = $order->customerHireNextStep();

        return response()->json(['success' => true, 'data' => $payload]);
    }

    /**
     * Customer: start ClickPesa for the hire balance (after operator acceptance).
     */
    public function specialHirePayBalance(Request $request, int $id)
    {
        $user = Auth::user();
        $order = SpecialHireOrder::where('customer_user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }
        if ($order->customerHireNextStep() !== 'pay_balance') {
            return response()->json(['success' => false, 'message' => 'Balance payment is not available yet'], 400);
        }

        $request->validate(['phone' => 'required|string|max:20']);

        $parts = explode(' ', trim($user->name), 2);
        $svc = app(SpecialHireOrderPaymentService::class);
        $out = $svc->initiateUssd(
            $order,
            'balance',
            $request->phone,
            $parts[0] ?? 'Customer',
            $parts[1] ?? '',
            $user->email ?? ''
        );

        if (!$out['ok']) {
            return response()->json(['success' => false, 'message' => $out['error'] ?? 'Payment start failed'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment request sent to phone.',
            'data' => [
                'order_reference' => $out['order_reference'] ?? null,
                'clickpesa' => $out['response'] ?? null,
            ],
        ]);
    }

    /**
     * Poll ClickPesa and update order when payment succeeded (mobile-friendly).
     */
    public function specialHireSyncPayment(Request $request, int $id)
    {
        $user = Auth::user();
        $order = SpecialHireOrder::where('customer_user_id', $user->id)->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $ref = $request->input('reference');
        if (!$ref) {
            $step = $order->customerHireNextStep();
            $depositRequired = (float) ($order->deposit_amount ?? 0) > 0;

            if ($depositRequired) {
                if ($step === 'pay_deposit' || ($step === 'wait_owner' && ! $order->deposit_paid_at)) {
                    $ref = $order->clickpesa_deposit_ref;
                } elseif ($step === 'pay_balance' || ($order->deposit_paid_at && ! $order->balance_paid_at)) {
                    $ref = $order->clickpesa_balance_ref;
                }
            } elseif ($step === 'pay_balance' || ! $order->balance_paid_at) {
                $ref = $order->clickpesa_balance_ref;
            }
        }
        if (!$ref) {
            return response()->json(['success' => false, 'message' => 'No payment reference to check'], 422);
        }

        $cp = new ClickPesaController();
        $verify = $cp->verifyTransaction($ref);
        $statusStr = is_object($verify) && isset($verify->status) ? (string) $verify->status : '';
        $lower = strtolower(trim($statusStr));
        $isPaid = $lower === 'success' || ClickPesaController::clickPesaPaidStatus($statusStr);
        if (!$isPaid) {
            $message = is_string($verify) ? $verify : 'Payment not completed yet';
            if (is_object($verify) && isset($verify->status)) {
                if ($lower === 'api_error' && isset($verify->message)) {
                    $message = 'Could not verify with ClickPesa yet. Try again shortly. '
                        . (is_string($verify->message) ? $verify->message : '');
                } elseif (in_array($lower, ['pending', 'initiated', 'processing'], true)) {
                    $message = 'Payment is still processing. Approve on your phone if prompted, then tap Sync again.';
                }
            }

            return response()->json([
                'success' => false,
                'message' => trim($message) !== '' ? trim($message) : 'Payment not completed yet',
                'data' => ['hire_next_step' => $order->customerHireNextStep()],
            ], 400);
        }

        $san = preg_replace('/[^a-zA-Z0-9]/', '', (string) $ref);
        $type = SpecialHireOrderPaymentService::resolveTypeFromReference($order, $san);
        if (!$type) {
            return response()->json(['success' => false, 'message' => 'Reference does not match this booking'], 422);
        }

        try {
            app(SpecialHireOrderPaymentService::class)->confirmFromVerifiedReference($order, $type, $verify, $ref);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $fresh = $order->fresh('coaster');
        $payload = $fresh->toArray();
        $payload['hire_next_step'] = $fresh->customerHireNextStep();

        return response()->json(['success' => true, 'data' => $payload]);
    }

    /**
     * Cancel booking.
     */
    public function cancelBooking($id)
    {
        $user = Auth::user();

        $order = SpecialHireOrder::where('customer_user_id', $user->id)->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        if ($order->order_status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed booking',
            ], 400);
        }

        if ($order->order_status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking already cancelled',
            ], 400);
        }

        if ($order->balance_paid_at) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel after full payment',
            ], 400);
        }

        $order->update([
            'order_status' => 'cancelled',
        ]);

        // Update coaster status if it was in progress
        if ($order->coaster && $order->coaster->status === 'on_hire') {
            $order->coaster->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $order->fresh('coaster'),
        ]);
    }

    /**
     * Track booking (get coaster location).
     */
    public function trackBooking($id)
    {
        $user = Auth::user();

        $order = SpecialHireOrder::where('customer_user_id', $user->id)
            ->with('coaster')
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'order_status' => $order->order_status,
                'coaster' => [
                    'id' => $order->coaster->id,
                    'name' => $order->coaster->name,
                    'plate_number' => $order->coaster->plate_number,
                    'latitude' => $order->coaster->latitude,
                    'longitude' => $order->coaster->longitude,
                    'last_location_update' => $order->coaster->last_location_update,
                ],
            ],
        ]);
    }

    /**
     * Logout customer.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}


