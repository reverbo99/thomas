<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coaster;
use App\Models\SpecialHireOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DriverApiController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    protected function specialHireOrderToDriverArray(SpecialHireOrder $order): array
    {
        $order->loadMissing(['coaster', 'customer']);
        $payload = $order->toArray();
        $payload['hire_next_step'] = $order->customerHireNextStep();

        return $payload;
    }

    /**
     * Driver login.
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

        // Check if user is a driver
        if ($user->role !== 'driver') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Driver access only.',
            ], 403);
        }

        if ($user->status === 'disabled') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'This driver account has been disabled. Contact your operator.',
            ], 403);
        }

        $token = $user->createToken('driver-token')->plainTextToken;

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
     * Get driver profile.
     */
    public function profile()
    {
        $user = Auth::user();
        
        // Get coaster assigned to this driver
        $coaster = Coaster::where('driver_user_id', $user->id)
            ->with('pricing', 'user')
            ->first();

        $pendingHireRequests = 0;
        if ($coaster) {
            $pendingHireRequests = SpecialHireOrder::where('coaster_id', $coaster->id)
                ->where('payment_status', 'paid')
                ->whereNotIn('order_status', ['completed', 'cancelled'])
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'coaster' => $coaster,
                'pending_hire_requests' => $pendingHireRequests,
            ],
        ]);
    }

    /**
     * Update driver profile.
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
            $data['phone'] = $request->phone;
        }
        if ($request->has('password')) {
            $data['password'] = bcrypt($request->password);
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
     * Get coaster assigned to driver.
     */
    public function getCoaster()
    {
        $user = Auth::user();
        
        $coaster = Coaster::where('driver_user_id', $user->id)
            ->with('pricing', 'user')
            ->first();

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $coaster,
        ]);
    }

    /**
     * Get orders for driver's coaster.
     */
    public function getOrders(Request $request)
    {
        $user = Auth::user();
        
        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $query = SpecialHireOrder::where('coaster_id', $coaster->id)
            ->with('coaster', 'customer');

        // Filter by status
        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('hire_date', $request->date);
        }

        $orders = $query->orderBy('hire_date', 'desc')
            ->orderBy('hire_time', 'desc')
            ->paginate($request->get('per_page', 15));

        $orders->setCollection(
            $orders->getCollection()->map(fn (SpecialHireOrder $o): array => $this->specialHireOrderToDriverArray($o))
        );

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Paid special-hire orders on this driver's coaster that are not completed or cancelled.
     */
    public function hirePendingBookings(Request $request)
    {
        $user = Auth::user();

        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (! $coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $orders = SpecialHireOrder::where('coaster_id', $coaster->id)
            ->where('payment_status', 'paid')
            ->whereNotIn('order_status', ['completed', 'cancelled'])
            ->with(['coaster', 'customer'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20));

        $orders->setCollection(
            $orders->getCollection()->map(fn (SpecialHireOrder $o): array => $this->specialHireOrderToDriverArray($o))
        );

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Driver accepts hire (same effect as former operator "accept" — sets owner_accepted_at).
     */
    public function acceptHireRequest($id)
    {
        $user = Auth::user();

        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (! $coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $order = SpecialHireOrder::where('coaster_id', $coaster->id)->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if (! $order->canDriverAcceptHire()) {
            return response()->json([
                'success' => false,
                'message' => 'This hire cannot be accepted in its current state.',
            ], 400);
        }

        $order->update(['owner_accepted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Booking accepted. The customer can continue payment in the app.',
            'data' => $this->specialHireOrderToDriverArray($order->fresh()),
        ]);
    }

    /**
     * Driver declines hire before accepting (cancels booking).
     */
    public function declineHireRequest($id)
    {
        $user = Auth::user();

        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (! $coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $order = SpecialHireOrder::where('coaster_id', $coaster->id)->find($id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if (! $order->canDriverDeclineHire()) {
            return response()->json([
                'success' => false,
                'message' => 'This hire cannot be declined now.',
            ], 400);
        }

        $order->update(['order_status' => 'cancelled']);
        $order->load('coaster');
        if ($order->coaster && $order->coaster->status === 'on_hire') {
            $order->coaster->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking declined and cancelled.',
            'data' => $this->specialHireOrderToDriverArray($order->fresh()),
        ]);
    }

    /**
     * Get single order details.
     */
    public function getOrder($id)
    {
        $user = Auth::user();
        
        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $order = SpecialHireOrder::where('coaster_id', $coaster->id)
            ->with('coaster', 'customer')
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->specialHireOrderToDriverArray($order),
        ]);
    }

    /**
     * Get order history (completed orders).
     */
    public function getHistory(Request $request)
    {
        $user = Auth::user();
        
        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $orders = SpecialHireOrder::where('coaster_id', $coaster->id)
            ->whereIn('order_status', ['completed', 'cancelled'])
            ->with('coaster', 'customer')
            ->orderBy('hire_date', 'desc')
            ->orderBy('hire_time', 'desc')
            ->paginate($request->get('per_page', 15));

        $orders->setCollection(
            $orders->getCollection()->map(fn (SpecialHireOrder $o): array => $this->specialHireOrderToDriverArray($o))
        );

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get scheduled orders (upcoming orders).
     */
    public function getSchedule(Request $request)
    {
        $user = Auth::user();
        
        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $orders = SpecialHireOrder::where('coaster_id', $coaster->id)
            ->whereIn('order_status', ['confirmed', 'pending'])
            ->where('hire_date', '>=', Carbon::today())
            ->with('coaster', 'customer')
            ->orderBy('hire_date', 'asc')
            ->orderBy('hire_time', 'asc')
            ->paginate($request->get('per_page', 15));

        $orders->setCollection(
            $orders->getCollection()->map(fn (SpecialHireOrder $o): array => $this->specialHireOrderToDriverArray($o))
        );

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Update order status (driver can start/complete trips).
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $user = Auth::user();
        
        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $order = SpecialHireOrder::where('coaster_id', $coaster->id)->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:in_progress,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order->update([
            'order_status' => $request->order_status,
        ]);

        // Update coaster status
        if ($request->order_status === 'in_progress') {
            $coaster->update(['status' => 'on_hire']);
        } elseif ($request->order_status === 'completed') {
            $coaster->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $this->specialHireOrderToDriverArray($order->fresh(['coaster', 'customer'])),
        ]);
    }

    /**
     * Update coaster location (GPS tracking).
     */
    public function updateLocation(Request $request)
    {
        $user = Auth::user();
        
        $coaster = Coaster::where('driver_user_id', $user->id)->first();

        if (!$coaster) {
            return response()->json([
                'success' => false,
                'message' => 'No coaster assigned to you',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $coaster->updateLocation($request->latitude, $request->longitude);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'coaster_id' => $coaster->id,
                'latitude' => $coaster->latitude,
                'longitude' => $coaster->longitude,
                'last_updated' => $coaster->last_location_update,
            ],
        ]);
    }

    /**
     * Register (or refresh) an FCM device token for push notifications.
     */
    public function registerDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:512',
            'platform' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();

        // A physical device has one token; make sure it points at this user.
        \App\Models\DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $user->id,
                'platform' => $request->input('platform', 'android'),
                'app' => 'bushire_driver',
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered for notifications',
        ]);
    }

    /**
     * Remove an FCM device token (called on logout / disable notifications).
     */
    public function deleteDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:512',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        \App\Models\DeviceToken::where('token', $request->token)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device unregistered',
        ]);
    }

    /**
     * Logout driver.
     */
    public function logout(Request $request)
    {
        // Best-effort: drop this device's push token if the app sent it.
        if ($request->filled('device_token')) {
            \App\Models\DeviceToken::where('token', $request->device_token)
                ->where('user_id', $request->user()->id)
                ->delete();
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}



