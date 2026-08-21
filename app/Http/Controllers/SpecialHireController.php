<?php

namespace App\Http\Controllers;

use App\Models\Coaster;
use App\Models\SpecialHireOrder;
use App\Models\SpecialHirePricing;
use App\Models\SpecialHireWithdrawalRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SpecialHireController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        // Get statistics
        $totalCoasters = Coaster::byUser($user->id)->count();
        $availableCoasters = Coaster::byUser($user->id)->available()->count();
        $totalOrders = SpecialHireOrder::byUser($user->id)->count();
        $pendingOrders = SpecialHireOrder::byUser($user->id)->pending()->count();
        $todayOrders = SpecialHireOrder::byUser($user->id)->today()->count();
        
        // Revenue calculations
        $totalRevenue = SpecialHireOrder::byUser($user->id)->paid()->sum('total_amount');
        $todayRevenue = SpecialHireOrder::byUser($user->id)->paid()->today()->sum('total_amount');
        $monthRevenue = SpecialHireOrder::byUser($user->id)
            ->paid()
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('total_amount');

        // Recent orders
        $recentOrders = SpecialHireOrder::byUser($user->id)
            ->with('coaster')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Upcoming orders (today and future)
        $upcomingOrders = SpecialHireOrder::byUser($user->id)
            ->with('coaster')
            ->whereDate('hire_date', '>=', Carbon::today())
            ->whereIn('order_status', ['pending', 'confirmed'])
            ->orderBy('hire_date', 'asc')
            ->take(5)
            ->get();

        // Coasters with locations for map
        $coastersWithLocation = Coaster::byUser($user->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $withdrawalRequestsOpen = SpecialHireWithdrawalRequest::query()
            ->forUser($user->id)
            ->awaitingAction()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $withdrawalRequestsExecuted = SpecialHireWithdrawalRequest::query()
            ->forUser($user->id)
            ->executed()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $withdrawalActionNeededCount = SpecialHireWithdrawalRequest::query()
            ->forUser($user->id)
            ->awaitingAction()
            ->count();

        return view('special_hire.dashboard', compact(
            'totalCoasters',
            'availableCoasters',
            'totalOrders',
            'pendingOrders',
            'todayOrders',
            'totalRevenue',
            'todayRevenue',
            'monthRevenue',
            'recentOrders',
            'upcomingOrders',
            'coastersWithLocation',
            'withdrawalRequestsOpen',
            'withdrawalRequestsExecuted',
            'withdrawalActionNeededCount'
        ));
    }

    /**
     * Display coasters list.
     */
    public function coasters()
    {
        $coasters = Coaster::byUser(Auth::id())
            ->with('pricing')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('special_hire.coasters.index', compact('coasters'));
    }

    /**
     * Show create coaster form.
     */
    public function createCoaster()
    {
        return view('special_hire.coasters.create');
    }

    /**
     * Store new coaster.
     */
    public function storeCoaster(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:coasters,plate_number',
            'capacity' => 'required|integer|min:1|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'driver_contact' => 'nullable|string|max:20',
            'driver_email' => 'nullable|email|unique:users,email|required_with:driver_password',
            'driver_password' => 'nullable|string|min:6|required_with:driver_email',
            'features' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'min_km' => 'required|integer|min:1',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            try {
                $imagePath = Coaster::storeCoasterImageFile($request->file('image'));
            } catch (\Throwable $e) {
                Log::error('Special hire coaster image upload failed', [
                    'user_id' => Auth::id(),
                    'error' => $e->getMessage(),
                ]);

                return back()
                    ->withInput()
                    ->with('error', 'Could not save the vehicle photo. Ensure the server can write to storage/app/public and that the public/storage link exists (php artisan storage:link). '
                        . (config('app.debug') ? $e->getMessage() : 'Contact support if this continues.'));
            }
        }

        $driver = null;
        if ($request->filled('driver_email')) {
            $driverName = $request->driver_name ?: "{$request->name} Driver";
            $driver = User::create([
                'name' => $driverName,
                'email' => $request->driver_email,
                'contact' => $request->driver_contact,
                'password' => Hash::make($request->driver_password),
                'role' => 'driver',
            ]);
        }

        // Create coaster
        $coaster = Coaster::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'plate_number' => $request->plate_number,
            'capacity' => $request->capacity,
            'model' => $request->model,
            'color' => $request->color,
            'driver_user_id' => $driver?->id,
            'driver_name' => $driver?->name ?? $request->driver_name,
            'driver_contact' => $driver?->contact ?? $request->driver_contact,
            'features' => $request->features,
            'image' => $imagePath,
            'status' => 'available',
        ]);

        // Create pricing
        SpecialHirePricing::create([
            'coaster_id' => $coaster->id,
            'base_price' => $request->base_price,
            'price_per_km' => $request->price_per_km,
            'min_km' => $request->min_km,
            'weekend_surcharge_percent' => $request->weekend_surcharge_percent ?? 15,
            'night_surcharge_percent' => $request->night_surcharge_percent ?? 20,
        ]);

        return redirect()->route('special_hire.coasters')
            ->with('success', 'Coaster added successfully!');
    }

    /**
     * Show edit coaster form.
     */
    public function editCoaster($id)
    {
        $coaster = Coaster::byUser(Auth::id())
            ->with('pricing')
            ->findOrFail($id);

        return view('special_hire.coasters.edit', compact('coaster'));
    }

    /**
     * Update coaster.
     */
    public function updateCoaster(Request $request, $id)
    {
        $coaster = Coaster::byUser(Auth::id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:coasters,plate_number,' . $id,
            'capacity' => 'required|integer|min:1|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'status' => 'required|in:available,on_hire,maintenance',
            'driver_name' => 'nullable|string|max:100',
            'driver_contact' => 'nullable|string|max:20',
            'features' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:2048',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'min_km' => 'required|integer|min:1',
        ]);

        $data = [
            'name' => $request->name,
            'plate_number' => $request->plate_number,
            'capacity' => $request->capacity,
            'model' => $request->model,
            'color' => $request->color,
            'status' => $request->status,
            'driver_name' => $request->driver_name,
            'driver_contact' => $request->driver_contact,
            'features' => $request->features,
        ];

        if ($request->hasFile('image')) {
            try {
                $newPath = Coaster::storeCoasterImageFile($request->file('image'));
                if ($coaster->image) {
                    Storage::disk(Coaster::IMAGE_DISK)->delete($coaster->image);
                }
                $data['image'] = $newPath;
            } catch (\Throwable $e) {
                Log::error('Special hire coaster image upload failed (update)', [
                    'user_id' => Auth::id(),
                    'coaster_id' => $coaster->id,
                    'error' => $e->getMessage(),
                ]);

                return back()
                    ->withInput()
                    ->with('error', 'Could not save the new vehicle photo. Check server storage permissions and the public/storage symlink (php artisan storage:link). '
                        . (config('app.debug') ? $e->getMessage() : 'Contact support if this continues.'));
            }
        }

        $coaster->update($data);

        // Update or create pricing
        $coaster->pricing()->updateOrCreate(
            ['coaster_id' => $coaster->id],
            [
                'base_price' => $request->base_price,
                'price_per_km' => $request->price_per_km,
                'min_km' => $request->min_km,
                'weekend_surcharge_percent' => $request->weekend_surcharge_percent ?? 15,
                'night_surcharge_percent' => $request->night_surcharge_percent ?? 20,
            ]
        );

        return redirect()->route('special_hire.coasters')
            ->with('success', 'Coaster updated successfully!');
    }

    /**
     * Delete coaster.
     */
    public function deleteCoaster($id)
    {
        $coaster = Coaster::byUser(Auth::id())->findOrFail($id);

        // Delete image
        if ($coaster->image) {
            Storage::disk('public')->delete($coaster->image);
        }

        $coaster->delete();

        return redirect()->route('special_hire.coasters')
            ->with('success', 'Coaster deleted successfully!');
    }

    /**
     * Display live tracking page.
     */
    public function tracking()
    {
        $coasters = Coaster::byUser(Auth::id())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return view('special_hire.tracking', compact('coasters'));
    }

    /**
     * Display orders list.
     */
    public function orders(Request $request)
    {
        $query = SpecialHireOrder::byUser(Auth::id())->with('coaster');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('hire_date', $request->date);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('special_hire.orders.index', compact('orders'));
    }

    /**
     * Show create order form.
     */
    public function createOrder()
    {
        $coasters = Coaster::byUser(Auth::id())->available()->get();

        return view('special_hire.orders.create', compact('coasters'));
    }

    /**
     * Store new order.
     */
    public function storeOrder(Request $request)
    {
        $request->validate([
            'coaster_id' => 'required|exists:coasters,id',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:100',
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
            'distance_km' => 'required|numeric|min:0',
        ]);

        // Verify coaster belongs to user
        $coaster = Coaster::byUser(Auth::id())->findOrFail($request->coaster_id);
        
        // Get pricing
        $pricing = $coaster->pricing;
        if (!$pricing) {
            return back()->with('error', 'Pricing not set for this coaster.');
        }

        $winStart = Carbon::parse($request->hire_date)->startOfDay();
        $winEnd = Carbon::parse($request->input('return_date', $request->hire_date))->startOfDay();
        if ($coaster->hasHireScheduleConflict($winStart, $winEnd)) {
            return back()->with('error', 'This vehicle is not available for the selected hire dates.');
        }

        // Calculate price
        $priceData = $pricing->calculatePrice(
            $request->distance_km,
            $request->hire_date,
            $request->hire_time
        );

        $totalAmt = (float) $priceData['total_amount'];
        $dep = round($totalAmt * 0.10, 2);
        $bal = round($totalAmt - $dep, 2);
        $ownerUser = Auth::user();

        // Create order
        $order = SpecialHireOrder::create([
            'user_id' => Auth::id(),
            'customer_user_id' => null,
            'coaster_id' => $coaster->id,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
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
            'distance_km' => $priceData['billable_km'],
            'base_price' => $priceData['base_price'],
            'price_per_km' => $priceData['price_per_km'],
            'km_amount' => $priceData['km_amount'],
            'surcharge_percent' => $priceData['surcharge_percent'],
            'surcharge_amount' => $priceData['surcharge_amount'],
            'total_amount' => $priceData['total_amount'],
            'deposit_amount' => $dep,
            'balance_amount' => $bal,
            'platform_commission_percent' => (float) ($ownerUser->special_hire_platform_percent ?? 0),
            'order_status' => 'pending',
            'payment_status' => 'pending',
        ]);

        return redirect()->route('special_hire.orders.show', $order->id)
            ->with('success', 'Order created successfully!');
    }

    /**
     * Show order details.
     */
    public function showOrder($id)
    {
        $order = SpecialHireOrder::byUser(Auth::id())
            ->with('coaster')
            ->findOrFail($id);

        return view('special_hire.orders.show', compact('order'));
    }

    /**
     * Update order status.
     */
    public function updateOrder(Request $request, $id)
    {
        $order = SpecialHireOrder::byUser(Auth::id())->findOrFail($id);

        $request->validate([
            'order_status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid,refunded',
            'payment_method' => 'nullable|string|max:50',
            'quick_advance' => 'nullable|boolean',
        ]);

        if ($request->boolean('quick_advance') && $request->filled('order_status')) {
            $target = $request->order_status;
            if (!in_array($target, $order->allowedNextOrderStatuses(), true)) {
                return back()->with('error', 'That step is not available for this booking right now.');
            }
        }

        $updateData = [];

        if ($request->filled('order_status')) {
            $updateData['order_status'] = $request->order_status;

            // Update coaster status based on order status
            if ($order->coaster) {
                if ($request->order_status === 'in_progress') {
                    $order->coaster->update(['status' => 'on_hire']);
                } elseif (in_array($request->order_status, ['completed', 'cancelled'], true)) {
                    $order->coaster->update(['status' => 'available']);
                }
            }
        }

        if ($request->filled('payment_status')) {
            $updateData['payment_status'] = $request->payment_status;
        }

        if ($request->filled('payment_method')) {
            $updateData['payment_method'] = $request->payment_method;
        }

        $order->update($updateData);

        return back()->with('success', 'Order updated successfully!');
    }

    /**
     * Display pricing management page.
     */
    public function pricing()
    {
        $coasters = Coaster::byUser(Auth::id())
            ->with('pricing')
            ->get();

        return view('special_hire.pricing', compact('coasters'));
    }

    /**
     * Update pricing.
     */
    public function storePricing(Request $request)
    {
        $request->validate([
            'coaster_id' => 'required|exists:coasters,id',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'min_km' => 'required|integer|min:1',
            'weekend_surcharge_percent' => 'required|numeric|min:0|max:100',
            'night_surcharge_percent' => 'required|numeric|min:0|max:100',
        ]);

        // Verify coaster belongs to user
        $coaster = Coaster::byUser(Auth::id())->findOrFail($request->coaster_id);

        SpecialHirePricing::updateOrCreate(
            ['coaster_id' => $coaster->id],
            [
                'base_price' => $request->base_price,
                'price_per_km' => $request->price_per_km,
                'min_km' => $request->min_km,
                'weekend_surcharge_percent' => $request->weekend_surcharge_percent,
                'night_surcharge_percent' => $request->night_surcharge_percent,
            ]
        );

        return back()->with('success', 'Pricing updated successfully!');
    }

    /**
     * Display earnings page.
     */
    public function earnings(Request $request)
    {
        $user = Auth::user();
        $period = $request->get('period', 'month');

        // Get date range based on period
        switch ($period) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today();
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            default: // month
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
        }

        // Get earnings data
        $totalEarnings = SpecialHireOrder::byUser($user->id)
            ->paid()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        $totalOrders = SpecialHireOrder::byUser($user->id)
            ->paid()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalDistance = SpecialHireOrder::byUser($user->id)
            ->paid()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('distance_km');

        // Get orders for the period
        $orders = SpecialHireOrder::byUser($user->id)
            ->with('coaster')
            ->paid()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Earnings by coaster
        $earningsByCoaster = SpecialHireOrder::byUser($user->id)
            ->paid()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('coaster_id, SUM(total_amount) as total, COUNT(*) as trips')
            ->groupBy('coaster_id')
            ->with('coaster')
            ->get();

        $lifetimePaidEarnings = (float) SpecialHireOrder::byUser($user->id)->paid()->sum('total_amount');
        $withdrawalReserved = SpecialHireWithdrawalRequest::reservedAmountForUser($user->id);
        $withdrawalPaidOut = SpecialHireWithdrawalRequest::paidOutAmountForUser($user->id);
        $withdrawableBalance = SpecialHireWithdrawalRequest::withdrawableForUser($user->id);
        $withdrawalRequestsOpen = SpecialHireWithdrawalRequest::query()
            ->forUser($user->id)
            ->awaitingAction()
            ->orderByDesc('created_at')
            ->get();

        $withdrawalRequestsExecuted = SpecialHireWithdrawalRequest::query()
            ->forUser($user->id)
            ->executed()
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return view('special_hire.earnings', compact(
            'period',
            'startDate',
            'endDate',
            'totalEarnings',
            'totalOrders',
            'totalDistance',
            'orders',
            'earningsByCoaster',
            'lifetimePaidEarnings',
            'withdrawalReserved',
            'withdrawalPaidOut',
            'withdrawableBalance',
            'withdrawalRequestsOpen',
            'withdrawalRequestsExecuted'
        ));
    }

    /**
     * Submit a payout request to system admin (from earnings page).
     */
    public function storeWithdrawalRequest(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:100',
            'payment_number' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $available = SpecialHireWithdrawalRequest::withdrawableForUser($user->id);
        if ((float) $request->amount > $available) {
            return redirect()
                ->route('special_hire.earnings', ['period' => $request->get('period', 'month')])
                ->with('error', 'That amount exceeds what you can request. Available: Tsh ' . number_format($available, 0) . '.');
        }

        SpecialHireWithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_number' => $request->payment_number,
            'notes' => $request->notes,
            'status' => SpecialHireWithdrawalRequest::STATUS_PENDING,
        ]);

        return redirect()
            ->route('special_hire.earnings', ['period' => $request->get('period', 'month')])
            ->with('success', 'Withdrawal request sent. Admin will review it.');
    }

    /**
     * Display profile page.
     */
    public function profile()
    {
        $user = Auth::user();
        return view('special_hire.profile', compact('user'));
    }

    /**
     * Update profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'contact' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'contact' => $request->contact,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $user->update($updateData);

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Calculate price (AJAX endpoint).
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'coaster_id' => 'required|exists:coasters,id',
            'distance_km' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'hire_time' => 'required',
        ]);

        $coaster = Coaster::byUser(Auth::id())->with('pricing')->findOrFail($request->coaster_id);

        if (!$coaster->pricing) {
            return response()->json(['error' => 'Pricing not set'], 400);
        }

        $priceData = $coaster->pricing->calculatePrice(
            $request->distance_km,
            $request->hire_date,
            $request->hire_time
        );

        $commission = SpecialHireOrder::previewPlatformCommission(
            (float) $priceData['total_amount'],
            (float) (Auth::user()->special_hire_platform_percent ?? 0)
        );

        return response()->json([
            'success' => true,
            'data' => array_merge($priceData, $commission),
        ]);
    }

    /**
     * Web: accept hire after customer 10% deposit (same rules as API).
     */
    public function acceptHireBooking(Request $request, $id)
    {
        return back()->with(
            'error',
            'Hire bookings are accepted or declined by the assigned driver in the driver app. You can still cancel or mark a refund when appropriate.'
        );
    }

    /**
     * Operator: cancel an open hire booking.
     */
    public function cancelHireOrder(Request $request, $id)
    {
        $order = SpecialHireOrder::byUser(Auth::id())->findOrFail($id);

        if ($order->order_status === 'cancelled') {
            return back()->with('error', 'Already cancelled.');
        }
        if ($order->order_status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed booking.');
        }

        $order->update(['order_status' => 'cancelled']);
        if ($order->coaster && $order->coaster->status === 'on_hire') {
            $order->coaster->update(['status' => 'available']);
        }

        return back()->with('success', 'Booking cancelled.');
    }

    /**
     * Operator: mark payment as refunded (e.g. after manual reversal).
     */
    public function refundHirePayment(Request $request, $id)
    {
        $order = SpecialHireOrder::byUser(Auth::id())->findOrFail($id);

        if (! in_array($order->payment_status, ['paid', 'refund_pending'], true)) {
            return back()->with('error', 'Refund is only available when payment status is Paid or Refund pending.');
        }

        $order->update([
            'payment_status' => 'refunded',
            'order_status' => 'cancelled',
        ]);
        if ($order->coaster && $order->coaster->status === 'on_hire') {
            $order->coaster->update(['status' => 'available']);
        }

        return back()->with('success', 'Payment marked as refunded and booking cancelled.');
    }

    public function createDriver()
    {
        $coasters = Coaster::byUser(Auth::id())->orderBy('name')->get();
        // Only coasters without a linked driver account are selectable.
        $assignableCoasters = $coasters->whereNull('driver_user_id')->values();

        return view('special_hire.drivers.create', compact('coasters', 'assignableCoasters'));
    }

    public function storeDriver(Request $request)
    {
        $request->validate([
            'coaster_id' => 'required|exists:coasters,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        $coaster = Coaster::byUser(Auth::id())->findOrFail($request->coaster_id);
        if ($coaster->driver_user_id) {
            return back()->withInput()->with('error', 'This coaster already has a driver assigned. Unassign the current driver first.');
        }

        $driver = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'contact' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'driver',
            'status' => 'accept',
        ]);

        $coaster->update([
            'driver_user_id' => $driver->id,
            'driver_name' => $driver->name,
            'driver_contact' => $driver->phone,
        ]);

        return redirect()->route('special_hire.coasters')->with('success', 'Driver account created and linked to the selected coaster.');
    }

    /**
     * Show form to set a new password for a driver on this operator's fleet.
     */
    public function resetDriverPasswordForm(int $driver)
    {
        $driverUser = User::where('role', 'driver')->findOrFail($driver);
        if (! Coaster::byUser(Auth::id())->where('driver_user_id', $driverUser->id)->exists()) {
            abort(403);
        }

        return view('special_hire.drivers.reset_password', ['driver' => $driverUser]);
    }

    /**
     * Save a new password for a fleet driver (operator-initiated reset).
     */
    public function resetDriverPassword(Request $request, int $driver)
    {
        $driverUser = User::where('role', 'driver')->findOrFail($driver);
        if (! Coaster::byUser(Auth::id())->where('driver_user_id', $driverUser->id)->exists()) {
            abort(403);
        }

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $driverUser->update([
            'password' => Hash::make($request->password),
        ]);
        $driverUser->tokens()->delete();

        return redirect()
            ->route('special_hire.coasters')
            ->with('success', 'New password saved for ' . $driverUser->name . '. They must sign in with this password from now on.');
    }
}

