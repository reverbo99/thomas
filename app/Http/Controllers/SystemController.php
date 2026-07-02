<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\bus;
use App\Models\Bima;
use App\Models\City;
use App\Models\User;
use App\Models\balance;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\Campany;
use App\Models\Discount;
use App\Models\AdminWallet;
use App\Models\PaymentFees;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\SystemBalance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Models\AdminTransaction;
use App\Models\Schedule;
use PhpParser\Builder\Function_;
use PhpParser\Node\Expr\FuncCall;
use App\Http\Controllers\Pdf\Report;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SmsController;
use App\Models\Refund;
use App\Models\RefundPercentage;
use App\Models\Access;
use App\Models\CancelledBookings;
use App\Models\Coaster;
use App\Models\SpecialHireOrder;
use App\Models\SpecialHireWithdrawalRequest;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    private function requireAccess(string $link): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && $user->hasAccess($link), 403);
    }

    public function index()
    {
        // All dashboard figures from PAID bookings only
        $bookings = Booking::whereDate('created_at', today())
            ->where('payment_status', 'Paid')
            ->with(['bus', 'route', 'campany'])
            ->get();

        $todayAmount = Booking::whereDate('created_at', today())->where('payment_status', 'Paid')->sum('amount');
        $todayPaidCount = Booking::whereDate('created_at', today())->where('payment_status', 'Paid')->count();

        $totalAmount = Booking::where('payment_status', 'Paid')->sum('amount');
        $totalPaidCount = Booking::where('payment_status', 'Paid')->count();

        $bima = Bima::sum('amount');

        // Weekly amounts: only PAID bookings (last 7 days)
        $weeklyAmounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $amount = Booking::whereDate('created_at', $date)->where('payment_status', 'Paid')->sum('amount');
            $weeklyAmounts[] = [
                'date' => $date->format('Y-m-d'),
                'amount' => $amount,
            ];
        }

        // Monthly amounts: last 4 weeks (each point = one week)
        $weeklyAmountsMonth = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = Carbon::today()->subWeeks($i)->startOfWeek();
            $end = Carbon::today()->subWeeks($i)->endOfWeek();
            $amount = Booking::where('payment_status', 'Paid')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');
            $weeklyAmountsMonth[] = [
                'date' => $start->format('M d'),
                'amount' => $amount,
            ];
        }

        // Yearly amounts: last 12 months
        $weeklyAmountsYear = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::today()->subMonths($i);
            $amount = Booking::where('payment_status', 'Paid')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
            $weeklyAmountsYear[] = [
                'date' => $date->format('M Y'),
                'amount' => $amount,
            ];
        }

        // Recent activity: last 10 paid bookings + recent cancellations (real data)
        $recentBookings = Booking::where('payment_status', 'Paid')
            ->with(['campany', 'route'])
            ->latest('created_at')
            ->take(5)
            ->get();
        $recentCancellations = CancelledBookings::with('booking')
            ->latest('created_at')
            ->take(3)
            ->get();
        $recentActivity = collect();
        foreach ($recentBookings as $b) {
            $recentActivity->push([
                'type' => 'booking',
                'message' => __('system.dashboard.new_booking_confirmed'),
                'detail' => 'Booking ' . ($b->booking_code ?? '') . ' for ' . ($b->campany->name ?? '') . ' – ' . ($b->route->from ?? '') . ' to ' . ($b->route->to ?? ''),
                'amount' => $b->amount,
                'time' => $b->created_at,
            ]);
        }
        foreach ($recentCancellations as $c) {
            $recentActivity->push([
                'type' => 'cancelled',
                'message' => __('system.dashboard.booking_cancelled'),
                'detail' => (optional($c->booking)->booking_code ?? 'N/A') . ' – ' . (optional($c->booking)->customer_name ?? 'N/A'),
                'amount' => $c->amount,
                'time' => $c->created_at,
            ]);
        }
        $recentActivity = $recentActivity->sortByDesc('time')->take(8)->values();

        $service = SystemBalance::sum('balance');
        $fees = PaymentFees::sum('amount');
        $balance = AdminWallet::sum('balance');
        $cancelledAmount = CancelledBookings::get()->sum(fn ($row) => abs((float) $row->amount));

        return view('system.dashboard', compact(
            'bookings', 'todayAmount', 'todayPaidCount', 'totalAmount', 'totalPaidCount',
            'weeklyAmounts', 'weeklyAmountsMonth', 'weeklyAmountsYear', 'recentActivity',
            'service', 'fees', 'bima', 'balance', 'cancelledAmount'
        ));
    }

    public function buses()
    {
        $this->requireAccess(Access::LINKS['BUSES']);
        $buses = bus::with('busname', 'route')->paginate(10);
        return view('system.buses', compact('buses'));
    }

    /**
     * Pick a special hire business account before viewing coasters, orders, drivers, withdrawals.
     */
    public function specialHireIndex(Request $request)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $tab = $request->query('tab', 'accounts');
        if (!in_array($tab, ['accounts', 'withdrawals'], true)) {
            $tab = 'accounts';
        }

        $withdrawalActionNeededCount = SpecialHireWithdrawalRequest::query()
            ->awaitingAction()
            ->count();

        $owners = User::query()
            ->where('role', 'special_hire')
            ->withCount(['coasters', 'specialHireOrders'])
            ->orderBy('name')
            ->get();

        $withdrawalRequestsOpen = collect();
        $withdrawalRequestsExecuted = collect();
        if ($tab === 'withdrawals') {
            $withdrawalRequestsOpen = SpecialHireWithdrawalRequest::query()
                ->with('user')
                ->awaitingAction()
                ->orderByDesc('created_at')
                ->limit(150)
                ->get();
            $withdrawalRequestsExecuted = SpecialHireWithdrawalRequest::query()
                ->with('user')
                ->executed()
                ->orderByDesc('created_at')
                ->limit(250)
                ->get();
        }

        return view('system.special_hire_index', compact(
            'owners',
            'tab',
            'withdrawalActionNeededCount',
            'withdrawalRequestsOpen',
            'withdrawalRequestsExecuted'
        ));
    }

    /**
     * Special hire detail for one business owner (user_id on coasters / orders).
     */
    public function specialHireShow(int $user)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $selectedOwner = User::query()
            ->where('role', 'special_hire')
            ->findOrFail($user);

        $ownerId = $selectedOwner->id;

        $coasters = Coaster::query()
            ->where('user_id', $ownerId)
            ->with(['user', 'driver', 'pricing'])
            ->orderByDesc('created_at')
            ->get();

        $coastersByDriverId = Coaster::query()
            ->where('user_id', $ownerId)
            ->whereNotNull('driver_user_id')
            ->with('user')
            ->orderBy('name')
            ->get()
            ->groupBy('driver_user_id');

        $driverIds = $coasters->pluck('driver_user_id')->filter()->unique()->values();
        $drivers = User::query()
            ->where('role', 'driver')
            ->whereIn('id', $driverIds)
            ->orderBy('name')
            ->get();

        $orders = SpecialHireOrder::query()
            ->where('user_id', $ownerId)
            ->with(['coaster', 'user', 'customer'])
            ->orderByDesc('created_at')
            ->limit(150)
            ->get();

        $stats = [
            'coasters' => $coasters->count(),
            'drivers' => $drivers->count(),
            'orders' => SpecialHireOrder::where('user_id', $ownerId)->count(),
            'revenue_paid' => (float) SpecialHireOrder::where('user_id', $ownerId)->where('payment_status', 'paid')->sum('total_amount'),
            'revenue_pending' => (float) SpecialHireOrder::where('user_id', $ownerId)->where('payment_status', 'pending')->sum('total_amount'),
        ];

        $ordersByStatus = SpecialHireOrder::query()
            ->where('user_id', $ownerId)
            ->selectRaw('order_status, COUNT(*) as cnt')
            ->groupBy('order_status')
            ->pluck('cnt', 'order_status');

        $paymentsByStatus = SpecialHireOrder::query()
            ->where('user_id', $ownerId)
            ->selectRaw('payment_status, COUNT(*) as cnt')
            ->groupBy('payment_status')
            ->pluck('cnt', 'payment_status');

        $withdrawalRequestsOpen = SpecialHireWithdrawalRequest::query()
            ->where('user_id', $ownerId)
            ->with('user')
            ->awaitingAction()
            ->orderByDesc('created_at')
            ->get();

        $withdrawalRequestsExecuted = SpecialHireWithdrawalRequest::query()
            ->where('user_id', $ownerId)
            ->with('user')
            ->executed()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('system.special_hire_show', compact(
            'selectedOwner',
            'coasters',
            'coastersByDriverId',
            'drivers',
            'orders',
            'stats',
            'ordersByStatus',
            'paymentsByStatus',
            'withdrawalRequestsOpen',
            'withdrawalRequestsExecuted'
        ));
    }

    public function updateSpecialHireWithdrawal(Request $request, int $id)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $request->validate([
            'status' => 'required|in:approved,rejected,paid',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $withdrawal = SpecialHireWithdrawalRequest::with('user')->findOrFail($id);

        if (!$withdrawal->user || !$withdrawal->user->isSpecialHire()) {
            abort(404);
        }

        $withdrawal->update([
            'status' => $request->status,
            'admin_note' => $request->admin_note,
            'processed_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', __('system.messages.withdrawal_marked', ['status' => $request->status]));
    }

    /**
     * System admin: set platform commission % taken on each paid special hire trip for this owner.
     */
    public function updateSpecialHireOwnerPlatformPercent(Request $request, int $user)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $request->validate([
            'special_hire_platform_percent' => 'required|numeric|min:0|max:100',
        ]);

        $owner = User::query()->where('role', 'special_hire')->findOrFail($user);
        $owner->update([
            'special_hire_platform_percent' => $request->special_hire_platform_percent,
        ]);

        return redirect()
            ->back()
            ->with('success', __('system.messages.platform_percent_saved'));
    }

    public function pay_request(Request $request)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);
        // Fetch pending transactions
        $pendingTransactions = Transaction::whereIn('status', ['Pending'])
            ->with(['campany', 'user'])
            ->get();

        // Fetch all transactions (default: no filter)
        $allTransactions = Transaction::with(['campany', 'user'])->get();

        // Pass modal state from query parameter
        return view('system.transaction', compact('pendingTransactions', 'allTransactions'));
    }

    public function filter(Request $request)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);
        // Validate request
        $request->validate([
            'filter' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:filter,custom|date',
            'end_date' => 'required_if:filter,custom|date|after_or_equal:start_date',
        ]);

        // Fetch pending transactions (unchanged by filter)
        $pendingTransactions = Transaction::whereIn('status', ['Pending'])
            ->with(['campany', 'user'])
            ->get();

        // Initialize query for all transactions
        $query = Transaction::with(['campany', 'user']);

        // Apply filter
        $filter = $request->input('filter');
        if ($filter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filter === 'week') {
            $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]);
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter === 'year') {
            $query->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter === 'custom') {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('start_date')),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        }

        $allTransactions = $query->get();

        // Redirect back to index with filtered data
        return view('system.transaction', compact('pendingTransactions', 'allTransactions'));
    }

    public function completes(Request $request, $transaction, $campany = null)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);
        $transaction = Transaction::findOrFail($transaction);
        $transaction->status = 'Completed';
        $transaction->save();

        return redirect()->route('pay.request')->with('success', __('system.messages.transaction_completed'));
    }

    public function cancels(Request $request, $transaction, $campany = null)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);
        $transaction = Transaction::findOrFail($transaction);
        $transaction->status = 'Cancelled';
        $transaction->save();

        return redirect()->route('pay.request')->with('success', __('system.messages.transaction_cancelled'));
    }

    public function complete(Request $request, $transaction, $campany = null, $vender = null, $reference_number = null)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);
        $transaction = Transaction::findOrFail($transaction);

        // Validate company only when this is a company (not vender) transaction
        if ($campany != 0 && (int) $transaction->campany_id !== (int) $campany) {
            return redirect()->back()->with('error', __('system.messages.invalid_company_transaction'));
        }
        if ($campany != 0) {
            $transaction->status = 'Completed';
            $transaction->reference_number = $request->reference_number;
            $transaction->save();
            
            // Amount was already deducted from balance when request was created (pending state)
            // So we don't need to deduct again when approved - just mark as completed
            // The balance already reflects the pending amount being removed

            return redirect()->back()->with('success', __('system.messages.transaction_completed_back'));
        } else if ($vender != 0) {
            $transaction->status = 'Completed';
            $transaction->reference_number = $request->reference_number;
            $transaction->save();
            $user = User::find($vender);
            $balance = $user->VenderBalances;
            if ($balance) {
                $balance->amount -= $transaction->amount;
                $balance->save();
            }
            return redirect()->back()->with('success', __('system.messages.transaction_completed_back'));
        } else {
            return back()->with('error', __('system.messages.invalid_transaction'));
        }
    }

    public function cancel($transaction, $campany = null, $vender = null)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);
        $transaction = Transaction::findOrFail($transaction);

        if ($campany != 0 && (int) $transaction->campany_id !== (int) $campany) {
            return redirect()->back()->with('error', __('system.messages.invalid_company_transaction'));
        }

        // If transaction was pending, refund the amount back to balance
        if ($transaction->status === 'Pending' && $campany != 0) {
            $balance = balance::where('campany_id', $campany)->first();
            if ($balance) {
                $balance->amount += $transaction->amount;
                $balance->save();
            }
        }

        $transaction->status = 'Cancelled';
        $transaction->save();

        return redirect()->back()->with('success', __('system.messages.transaction_cancelled'));
    }

    public function campany()
    {
        $this->requireAccess(Access::LINKS['BUS_OPERATORS']);

        $campanies = Campany::all();
        return view('system.campany', compact('campanies'));
    }

    public function campany_status(Request $request)
    {
        $this->requireAccess(Access::LINKS['BUS_OPERATORS']);
        $percent = $request->percentage ?? 0;
        $amount = $request->commission_amount ?? 0;
        $status = $request->status;
        $campany_id = $request->campany_id;

        $campany = Campany::find($campany_id);

        $campany->status = $status;
        $campany->percentage = $percent;
        $campany->commission_amount = $amount;
        $campany->save();

        return back()->with('success', __('system.messages.company_edit_success'));
    }

    public function campanyShow($id)
    {
        $this->requireAccess(Access::LINKS['BUS_OPERATORS']);
        $campany = Campany::with(['user', 'balance', 'busOwnerAccount', 'bus' => function ($q) {
            $q->withCount('routes');
        }])->findOrFail($id);

        $busIds = $campany->bus->pluck('id')->toArray();

        $bookings = Booking::where('campany_id', $campany->id)
            ->where('payment_status', 'Paid')
            ->with(['bus', 'schedule'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $bookingsChart = Booking::where('campany_id', $campany->id)
            ->where('payment_status', 'Paid')
            ->where('created_at', '>=', Carbon::now()->subDays(14))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount + COALESCE(fee,0) + COALESCE(service,0)) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $schedules = Schedule::whereIn('bus_id', $busIds)
            ->with(['bus', 'route'])
            ->orderByDesc('schedule_date')
            ->limit(30)
            ->get();

        $systemBalances = SystemBalance::where('campany_id', $campany->id)->orderByDesc('created_at')->limit(15)->get();
        $paymentFees = PaymentFees::where('campany_id', $campany->id)->orderByDesc('created_at')->limit(15)->get();
        $transactions = Transaction::where('campany_id', $campany->id)->with('user')->orderByDesc('created_at')->limit(20)->get();

        $totalCommission = SystemBalance::where('campany_id', $campany->id)->sum('balance');
        $totalServiceFees = PaymentFees::where('campany_id', $campany->id)->sum('amount');
        $totalBookingsRevenue = Booking::where('campany_id', $campany->id)->where('payment_status', 'Paid')->sum('amount');

        return view('system.campany_dashboard', compact(
            'campany', 'bookings', 'bookingsChart', 'schedules',
            'systemBalances', 'paymentFees', 'transactions',
            'totalCommission', 'totalServiceFees', 'totalBookingsRevenue'
        ));
    }

    public function system_payments()
    {
        $this->requireAccess(Access::LINKS['SYSTEM_INCOME']);
        $balances = SystemBalance::with('campany')->orderByDesc('created_at')->get();
        $pays = PaymentFees::with('campany')->orderByDesc('created_at')->get();
        $levies = \App\Models\GovernmentLevy::with('campany')->orderByDesc('created_at')->get();

        return view('system.payments', compact('balances', 'pays', 'levies'));
    }

    public function governmentLevyReport(Request $request)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SYSTEM_INCOME']), 403);

        $period = $request->query('period', 'month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Booking::query()
            ->where('payment_status', 'Paid')
            ->with(['campany', 'route', 'vender', 'governmentLeviesOnService']);

        if ($period === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($period === 'year') {
            $query->whereYear('created_at', now()->year);
        } elseif ($period === 'custom' && $startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        $summaryQuery = clone $query;
        $tableQuery = clone $query;

        $hasGovernmentLevyColumn = Schema::hasColumn('bookings', 'government_levy');
        $hasSystemServiceFeeColumn = Schema::hasColumn('bookings', 'system_service_fee');

        $bookings = $tableQuery->latest()->paginate(50)->withQueryString();
        $totalPaidAmount = (float) $summaryQuery->sum('amount');
        $totalVat = (float) $summaryQuery->sum('vat');
        $totalGovLevyOnFare = $hasGovernmentLevyColumn ? (float) $summaryQuery->sum('government_levy') : 0.0;
        $bookingCodes = (clone $summaryQuery)->pluck('booking_code');
        $totalGovLevyOnService = $bookingCodes->isEmpty()
            ? 0.0
            : (float) \App\Models\GovernmentLevy::whereIn('booking_id', $bookingCodes)->sum('amount');
        $totalGovernmentLevy = $totalGovLevyOnFare + $totalGovLevyOnService;
        $totalSystemServiceFee = $hasSystemServiceFeeColumn ? (float) $summaryQuery->sum('system_service_fee') : 0.0;

        return view('system.government_levy', compact(
            'bookings',
            'period',
            'startDate',
            'endDate',
            'totalPaidAmount',
            'totalVat',
            'totalGovLevyOnFare',
            'totalGovLevyOnService',
            'totalGovernmentLevy',
            'totalSystemServiceFee',
            'hasGovernmentLevyColumn',
            'hasSystemServiceFeeColumn'
        ));
    }

    public function history(Request $request)
    {
        $this->requireAccess(Access::LINKS['BOOKING_HISTORY']);
        $query = Booking::with(['campany', 'schedule', 'user', 'route', 'vender', 'bus.route', 'campany.busOwnerAccount', 'governmentLeviesOnService']);
        // Apply period filter from sidebar dropdown
        if ($request->has('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        if ($request->filled('channel') && in_array($request->channel, ['online', 'in_person', 'phone'], true)) {
            $channel = $request->channel;
            $query->where(function ($q) use ($channel) {
                $q->where('booking_channel', $channel);
                if ($channel === 'in_person') {
                    $q->orWhere(function ($sub) {
                        $sub->whereNull('booking_channel')->whereNotNull('vender_id');
                    });
                } elseif ($channel === 'online') {
                    $q->orWhere(function ($sub) {
                        $sub->whereNull('booking_channel')->whereNull('vender_id');
                    });
                }
            });
        }

        $bookings = $query->where('payment_status', 'Paid')->latest()->get();

        $totalPayment = $bookings->sum(fn ($b) => ($b->amount ?? 0) + ($b->vat ?? 0));
        $totalDiscount = $bookings->sum('discount_amount');
        $totalVAT = $bookings->sum('vat');
        $grandTotal = $bookings->sum(fn ($b) => round(($b->fee ?? 0) + ($b->vender_fee ?? 0) + ($b->amount ?? 0) + ($b->vat ?? 0) + ($b->fee_vat ?? 0)));

        return view('system.history', compact('bookings', 'totalPayment', 'totalDiscount', 'totalVAT', 'grandTotal'))
            ->with('channelFilter', $request->get('channel'));
    }

    public function print(Request $request)
    {
        $this->requireAccess(Access::LINKS['BOOKING_HISTORY']);
        $data = $request->data;
        
        // Validate that data exists
        if (empty($data)) {
            return redirect()->back()->with('error', __('system.messages.no_income_data'));
        }
        
        $data = json_decode($data, true);
        
        // Validate JSON decode was successful
        if ($data === null || !is_array($data)) {
            return redirect()->back()->with('error', __('system.messages.invalid_income_format'));
        }
        
        // Validate that data array is not empty
        if (empty($data)) {
            return redirect()->back()->with('error', __('system.messages.no_booking_income_data'));
        }

        return $this->generatePDF($data);
    }

    public function generatePDF($data)
    {
        // Ensure data is an array before passing to view
        if (!is_array($data)) {
            $data = [];
        }
        
        $pdf = Pdf::loadView('print.report', ['bookings' => $data]);

        return $pdf->download('income-' . now() . '.pdf');
    }

    public function vender()
    {
        $this->requireAccess(Access::LINKS['VENDORS']);
        $venders = User::where('role', 'vender')->get();
        return view('system.vender', compact('venders'));
    }

    public function vender_status(Request $request)
    {
        $this->requireAccess(Access::LINKS['VENDORS']);
        $vender_id = $request->vender_id;
        $status = $request->status;

        $vender = User::find($vender_id);
        $vender->status = $status;
        $vender->save();

        return back()->with('success', __('system.messages.changes_successful'));
    }
    
    public function vender_percent(Request $request)
    {
        $this->requireAccess(Access::LINKS['VENDORS']);
        $request->validate([
            'percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'vender_id' => ['required', 'exists:users,id'],
        ]);
        $user = user::find($request->vender_id);
        $user->VenderAccount->update(['percentage' => $request->percent]);
        return back()->with('success', __('system.messages.account_updated'));
    }

    public function profile()
    {
        return view('system.profile');
    }

    public function update_profile(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
            'contact' => ['nullable', 'string', 'max:20'],
            'payment_number' => ['nullable', 'string', 'max:50'], // Adjust max length as needed
            'password' => ['nullable', 'string', 'min:8'], // Requires password_confirmation field
        ]);

        try {
            // Get the authenticated user
            $user = Auth::user();

            // Update user fields
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->contact = $validated['contact'];

            // Update password only if provided
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }

            // Save user
            $user->save();


            return back()->with('success', __('system.messages.profile_updated'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('system.messages.profile_update_failed', ['error' => $e->getMessage()])])->withInput();
        }
    }

    public function cities()
    {
        $cities = City::orderBy('name')->get();
        return view('system.cities', compact('cities'));
    }

    public function store_city(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            // Create a new city
            if (City::where('name', $request->name)->exists()) {
                return back()->with('error', __('system.messages.city_exists'));
            }
            City::create([
                'name' => $request->name,
            ]);

            return back()->with('success', __('system.messages.city_created'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('system.messages.city_create_failed', ['error' => $e->getMessage()])])->withInput();
        }
    }


    public function discount()
    {
        // Retrieve all discounts with a count of associated bookings where payment_status is 'Paid'
        $discounts = Discount::withCount(['booking' => function ($query) {
            $query->where('payment_status', 'Paid');
        }])->get();

        return view('system.discount', compact('discounts'));
    }


    public function add_coupon(Request $request)
    {
        $code = $request->code;
        $used = $request->used;

        if (empty($code) || empty($used)) {
            return back()->with('error', __('system.messages.fill_all_inputs'));
        }

        $data = Discount::create([
            'code' => $code,
            'used' => $used,
            'percentage' => $request->percentage
        ]);

        // Get eligible phone numbers for the coupon
        $phone = Booking::where('distance', '>=', 100) // Exclude trips < 100km
            ->whereRaw('created_at <= DATE_SUB(travel_date, INTERVAL 24 HOUR)') // Tickets bought ≥ 24 hours before travel
            ->groupBy('customer_phone')
            ->select('customer_phone', \DB::raw('count(*) as total'))
            ->orderBy('total', 'desc')
            ->limit($used)
            ->get();

        $sms = new SmsController();
        $smsSent = 0;
        foreach ($phone as $item) {
            if ($sms->sms_send($item->customer_phone, "Dear customer, we are pleased to inform you that we have created a discount coupon for you. Use code: $code to enjoy a discount of $request->percentage% on your next booking. Thank you for choosing our service!") !== false) {
                $smsSent++;
            }
        }

        if ($data) {
            return back()->with('success', __('system.messages.discount_created') . ($smsSent < count($phone) ? __('system.messages.discount_sms_partial') : ''));
        } else {
            return back()->with('error', __('system.messages.discount_failed'));
        }
    }

    public function bus_route()
    {
        $this->requireAccess(Access::LINKS['BUS_SCHEDULE']);
        // Show only upcoming schedules from now (same logic as admin Bus Schedule)
        $today = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');

        $schedules = Schedule::with(['bus.campany', 'bus.route', 'route'])
            ->whereHas('bus.campany', function ($query) {
                $query->where('status', 1);
            })
            ->where(function ($query) use ($today, $currentTime) {
                $query->where('schedule_date', '>', $today)
                    ->orWhere(function ($q) use ($today, $currentTime) {
                        $q->where('schedule_date', $today)->where('start', '>', $currentTime);
                    });
            })
            ->orderBy('schedule_date', 'asc')
            ->orderBy('start', 'asc')
            ->get();

        // Build $cars so the view stays unchanged: each row is the bus with its schedule set to this upcoming schedule
        $cars = $schedules->map(function (Schedule $schedule) {
            $bus = $schedule->bus;
            $bus->setRelation('schedule', $schedule);
            return $bus;
        });

        return view('system.bus_route', compact('cars'));
    }


    public function balance()
    {
        $data = AdminTransaction::all();
        return view('system.balance', compact('data'));
    }

    public function print_recipt2(Request $request)
    {
        $data = json_decode($request->data);

        $pdf = Pdf::loadView('print.admin', ['data' => $data]);

        $pdf->setPaper([0, 0, 4 * 72, 7 * 72], 'portrait');

        return $pdf->stream('admin-' . now() . '.pdf');
    }

    public function update_balance(Request $request)
    {
        $request->validate([
            'trans_ref_id' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_number' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
        ]);

        // Fetch the first admin wallet record
        $wallet = AdminWallet::first();

        // Check if wallet exists and has sufficient balance
        if (!$wallet || $wallet->balance < $request->amount) {
            return back()->with('error', __('system.messages.insufficient_balance'));
        }

        try {
            // Create a new admin transaction
            AdminTransaction::create([
                'trans_ref_id' => $request->trans_ref_id,
                'amount' => $request->amount,
                'payment_number' => $request->payment_number,
                'payment_method' => $request->payment_method,
            ]);

            // Decrement the balance
            $wallet->decrement('balance', $request->amount);

            return back()->with('success', __('system.messages.balance_updated'));
        } catch (\Exception $e) {
            return back()->with('error', __('system.messages.balance_update_failed', ['error' => $e->getMessage()]));
        }
    }

    public function busOwner($id)
    {
        $user = User::find($id);

        return view('system.view_bus_owner', compact('user'));
    }

    public function update_profile_bus(Request $request)
    {
        try {
            // Get the authenticated user
            $user = User::find($request->id);

            // Update user fields
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->contact = $request->input('contact');

            // Update password only if provided
            if (!empty($request->input('password'))) {
                $user->password = bcrypt($request->input('password'));
            }

            // Save user
            $user->save();

            // Update or create company details
            if ($user->campany) {
                $user->campany->update([
                    'name' => $request->input('campany_name'),
                ]);
            } elseif ($request->input('campany_name')) {
                // Create a new company record if it doesn't exist and name is provided
                $user->campany()->create([
                    'name' => $request->input('campany_name'),
                ]);
            }

            // Update or create bus owner account details (used on printed tickets)
            if ($user->campany) {
                $existingAccount = $user->campany->busOwnerAccount;
                $accountData = [
                    'registration_number' => $request->input('registration_number'),
                    'tin' => $request->input('tin'),
                    'vrn' => $request->input('vrn'),
                    'office_number' => $request->input('office_number'),
                    'box' => $request->input('box'),
                    'street' => $request->input('street'),
                    'town' => $request->input('town'),
                    'city' => $request->input('city'),
                    'region' => $request->input('region'),
                    'whatsapp_number' => $request->input('whatsapp_number'),
                ];

                if ($existingAccount) {
                    $accountData['bank_name'] = $request->has('bank_name')
                        ? $request->input('bank_name')
                        : $existingAccount->bank_name;
                    $accountData['bank_number'] = $request->has('account_number')
                        ? $request->input('account_number')
                        : $existingAccount->bank_number;
                    $existingAccount->update($accountData);
                } else {
                    $accountData['bank_name'] = $request->input('bank_name');
                    $accountData['bank_number'] = $request->input('account_number');
                    $user->campany->busOwnerAccount()->create($accountData);
                }
            }

            return back()->with('success', __('system.messages.profile_updated'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => __('system.messages.profile_update_failed', ['error' => $e->getMessage()])])->withInput();
        }
    }
    
    public function setting()
    {
        $settings = Setting::first();

        if (!$settings) {
            $settings = Setting::create([
                'international' => 0,
                'local' => 0,
                'service' => 0,
                'service_percentage' => 0,
                'enable_customer_sms_notifications' => true,
                'enable_customer_email_notifications' => true,
                'enable_conductor_sms_notifications' => true,
                'enable_conductor_email_notifications' => true,
                'test_mode' => false,
                'enforce_2fa' => true,
                'enforce_customer_email_verification' => true,
            ]);
        }

        return view('system.setting', compact('settings'));
    }
    
    public function setting_update(Request $request)
    {
        $settings = Setting::first();

        if (!$settings) {
            $settings = Setting::create([
                'international' => 0,
                'local' => 0,
                'service' => 0,
                'service_percentage' => 0,
                'test_mode' => false,
                'enforce_2fa' => true,
                'enforce_customer_email_verification' => true,
            ]);
        }

        $settings->update([
            'local' => $request->local,
            'international' => $request->international,
            'insurance_company' => $request->insurance_company,
            'insurance_policy_local' => $request->insurance_policy_local,
            'insurance_policy_foreign' => $request->insurance_policy_foreign,
            'service' => $request->service,
            'service_percentage' => $request->service_percentage,
            'enable_customer_sms_notifications' => $request->boolean('enable_customer_sms_notifications'),
            'enable_customer_email_notifications' => $request->boolean('enable_customer_email_notifications'),
            'enable_conductor_sms_notifications' => $request->boolean('enable_conductor_sms_notifications'),
            'enable_conductor_email_notifications' => $request->boolean('enable_conductor_email_notifications'),
            'test_mode' => $request->boolean('test_mode'),
            'enforce_2fa' => $request->boolean('enforce_2fa'),
            'enforce_customer_email_verification' => $request->boolean('enforce_customer_email_verification'),
        ]);

        return back()->with('success', __('system.messages.settings_updated'));
    }

    public function refunds()
    {
        $this->requireAccess(Access::LINKS['REFUNDS']);
        $refunds = Refund::all();
        return view('system.refunds', compact('refunds'));
    }

    public function approveRefund($id)
    {
        $this->requireAccess(Access::LINKS['REFUNDS']);
        $refund = Refund::findOrFail($id);
        $refund->status = 'Approved';
        $refund->save();

        $booking = Booking::where('booking_code', $refund->booking_code)->first();

        if ($booking) {
            $booking->update([
                'payment_status' => 'Refund',
                'refund_id' => $refund->id,
            ]);
        }

        if ($booking && $booking->campany_id) {
            $campany = Campany::with('balance')->find($booking->campany_id);

            if ($campany && $campany->balance) {
                $campany->balance->decrement('amount', $refund->amount);
                $campany->save();
            }
        }

        return back()->with('success', __('system.messages.refund_approved'));
    }

    public function rejectRefund($id)
    {
        $this->requireAccess(Access::LINKS['REFUNDS']);
        $refund = Refund::findOrFail($id);
        $refund->status = 'Rejected';
        $refund->save();

        $booking = Booking::where('booking_code', $refund->booking_code)->first();

        if ($booking) {
            $booking->update([
                'payment_status' => 'Refund Rejected',
            ]);
        }

        RefundPercentage::where('booking_code', $refund->booking_code)->delete();

        return back()->with('error', __('system.messages.refund_rejected'));
    }

    public function cancelled_bookings(Request $request)
    {
        $this->requireAccess(Access::LINKS['INSURANCE']);
        // Get cancelled bookings with related data
        $cancelledBookings = CancelledBookings::with([
            'booking' => function($query) {
                $query->with(['bus.busname', 'campany', 'route']);
            }
        ])
        ->when($request->has('filter'), function($query) use ($request) {
            switch($request->filter) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', Carbon::now()->year);
                    break;
            }
        })
        ->orderBy('created_at', 'desc')
        ->get();

        // Calculate summary statistics
        $totalCancelled = CancelledBookings::count();
        $totalAmount = CancelledBookings::get()->sum(fn ($row) => abs((float) $row->amount));
        $todayCancelled = CancelledBookings::whereDate('created_at', Carbon::today())->count();
        $todayAmount = CancelledBookings::whereDate('created_at', Carbon::today())
            ->get()
            ->sum(fn ($row) => abs((float) $row->amount));

        return view('system.cancelled_bookings', compact(
            'cancelledBookings', 
            'totalCancelled', 
            'totalAmount', 
            'todayCancelled', 
            'todayAmount'
        ));
    }

    /**
     * Run database migrations (for production when CLI is not available).
     * Only accessible by admin. Use with care.
     *
     * URL examples:
     *   /admin/migrate                          — run all migrations
     *   /admin/migrate/2026_03_12_013541_add_expires_at_to_discount_table.php
     *   /admin/migrate/migration--2026_03_12_013541_add_expires_at_to_discount_table.php
     */
    public function runMigrations(Request $request, $migration = null)
    {
        $path = null;

        if ($migration) {
            // Allow "migration--filename.php" or plain "filename.php"
            $filename = preg_replace('/^migration--/i', '', $migration);
            // Only allow safe migration filenames (digits, underscores, letters, .php)
            if (!preg_match('/^[a-z0-9_]+\.php$/i', $filename)) {
                $output = 'Invalid migration filename. Use only: 2026_03_12_013541_add_expires_at_to_discount_table.php';
                $exitCode = 1;
                $success = false;
            } else {
                $fullPath = database_path('migrations/' . $filename);
                if (!is_file($fullPath)) {
                    $output = 'Migration file not found: ' . $filename;
                    $exitCode = 1;
                    $success = false;
                } else {
                    $path = 'database/migrations/' . $filename;
                }
            }
        }

        if (!isset($exitCode)) {
            $params = ['--force' => true];
            if ($path) {
                $params['--path'] = $path;
            }
            $exitCode = Artisan::call('migrate', $params);
            $output = trim(Artisan::output());
            $success = $exitCode === 0;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'exit_code' => $exitCode,
                'output' => $output,
            ], $success ? 200 : 500);
        }

        return view('system.migrate_result', [
            'success' => $success,
            'exit_code' => $exitCode,
            'output' => $output,
        ]);
    }
}
