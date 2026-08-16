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
use App\Models\ExcessLuggageEscrow;
use App\Services\ExcessLuggageService;
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
use App\Models\Parcel;
use App\Models\SpecialHireOrder;
use App\Models\SpecialHireWithdrawalRequest;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemController extends Controller
{
    private function requireAccess(string $link): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && $user->hasAccess($link), 403);
    }

    public function index()
    {
        // Ticket rows for "today's bookings" table (paid tickets only).
        $bookings = Booking::whereDate('created_at', today())
            ->where('payment_status', 'Paid')
            ->with(['bus', 'route', 'campany'])
            ->get();

        // GMV-style revenue: paid tickets (checkout + synced luggage top-ups via customer_paid_total)
        // + paid parcels + paid special hire. All figures in TZS base.
        // Special hire stays offline Campany balance (platform commission only on SH wallets).
        $todayAmount = $this->sumCombinedPaidRevenue(Carbon::today(), Carbon::today()->endOfDay());
        $todayPaidCount = $this->countCombinedPaidTransactions(Carbon::today(), Carbon::today()->endOfDay());

        $totalAmount = $this->sumCombinedPaidRevenue(null, null);
        $totalPaidCount = $this->countCombinedPaidTransactions(null, null);

        $bima = Bima::sum('amount');

        // Weekly GMV (tickets + parcels + special hire), last 7 days
        $weeklyAmounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $weeklyAmounts[] = [
                'date' => $date->format('Y-m-d'),
                'amount' => $this->sumCombinedPaidRevenue($date->copy()->startOfDay(), $date->copy()->endOfDay()),
            ];
        }

        // Monthly amounts: last 4 weeks (each point = one week)
        $weeklyAmountsMonth = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = Carbon::today()->subWeeks($i)->startOfWeek();
            $end = Carbon::today()->subWeeks($i)->endOfWeek();
            $weeklyAmountsMonth[] = [
                'date' => $start->format('M d'),
                'amount' => $this->sumCombinedPaidRevenue($start, $end),
            ];
        }

        // Yearly amounts: last 12 months
        $weeklyAmountsYear = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::today()->subMonths($i);
            $weeklyAmountsYear[] = [
                'date' => $date->format('M Y'),
                'amount' => $this->sumCombinedPaidRevenue(
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth()
                ),
            ];
        }

        // Recent activity: paid bookings, parcels, special hire + cancellations
        $recentBookings = Booking::where('payment_status', 'Paid')
            ->with(['campany', 'route'])
            ->latest('created_at')
            ->take(4)
            ->get();
        $recentParcels = $this->commissionableParcelsQuery()
            ->with(['bus.campany', 'bus.route'])
            ->latest('created_at')
            ->take(2)
            ->get();
        $recentSpecialHire = SpecialHireOrder::where('payment_status', 'paid')
            ->with('user')
            ->latest('created_at')
            ->take(2)
            ->get();
        $recentCancellations = CancelledBookings::with('booking')
            ->latest('created_at')
            ->take(2)
            ->get();
        $recentActivity = collect();
        foreach ($recentBookings as $b) {
            $recentActivity->push([
                'type' => 'booking',
                'message' => __('system.dashboard.new_booking_confirmed'),
                'detail' => 'Booking ' . ($b->booking_code ?? '') . ' for ' . ($b->campany->name ?? '') . ' – ' . ($b->route->from ?? '') . ' to ' . ($b->route->to ?? ''),
                'amount' => (float) ($b->customer_paid_total ?? $b->amount),
                'time' => $b->created_at,
            ]);
        }
        foreach ($recentParcels as $p) {
            $from = $p->bus->route->from ?? '';
            $to = $p->bus->route->to ?? '';
            $recentActivity->push([
                'type' => 'parcel',
                'message' => __('system.dashboard.parcel_paid'),
                'detail' => ($p->parcel_number ?? '') . ' – ' . ($p->bus->campany->name ?? '') .
                    ($from || $to ? " ({$from} → {$to})" : ''),
                'amount' => (float) $p->amount_paid,
                'time' => $p->created_at,
            ]);
        }
        foreach ($recentSpecialHire as $o) {
            $recentActivity->push([
                'type' => 'special_hire',
                'message' => __('system.dashboard.special_hire_paid'),
                'detail' => ($o->order_code ?? '') . ' – ' . ($o->user->name ?? '') .
                    ' (' . ($o->pickup_location ?? '') . ' → ' . ($o->dropoff_location ?? '') . ')',
                'amount' => (float) $o->total_amount,
                'time' => $o->created_at,
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
        // Recalculate from bookings so vendor rows use levy-on-full-service (not legacy payment_fees).
        $levyRate = government_levy_percent() / 100;
        $fees = (float) Booking::query()
            ->where('payment_status', 'Paid')
            ->selectRaw(
                'COALESCE(SUM(GREATEST(0,
                    COALESCE(service, 0)
                    - (? * COALESCE(NULLIF(system_service_fee, 0), COALESCE(service, 0) + COALESCE(vender_service, 0)))
                )), 0) as total',
                [$levyRate]
            )
            ->value('total');
        // System income from luggage: admin share only after escrow release.
        $luggageTotal = $this->sumReleasedLuggageAdminIncome();
        $escrowBalance = app(ExcessLuggageService::class)->totalEscrowBalance();
        $parcelCommissionPercent = (float) (Setting::first()->parcel_commission_percentage ?? 0);
        $parcelCommissionTotal = round(
            (float) $this->commissionableParcelsQuery()->sum('amount_paid') * $parcelCommissionPercent / 100,
            2
        );
        $balance = AdminWallet::sum('balance');
        $cancelledAmount = CancelledBookings::get()->sum(fn ($row) => abs((float) $row->amount));
        $specialHireCommissionTotal = (float) SpecialHireOrder::where('payment_status', 'paid')->sum('platform_commission_amount');

        return view('system.dashboard', compact(
            'bookings', 'todayAmount', 'todayPaidCount', 'totalAmount', 'totalPaidCount',
            'weeklyAmounts', 'weeklyAmountsMonth', 'weeklyAmountsYear', 'recentActivity',
            'service', 'fees', 'luggageTotal', 'escrowBalance', 'parcelCommissionTotal', 'bima', 'balance',
            'cancelledAmount', 'specialHireCommissionTotal'
        ));
    }

    /**
     * Combined GMV for admin dashboard: paid tickets + paid parcels + paid special hire (TZS).
     * Ticket gross prefers customer_paid_total (checkout total + synced luggage top-ups).
     * Top-ups are folded into customer_paid_total in ExcessLuggageService::confirmTopUpPayment
     * so they are not added again here (no double-count). Legacy paid top-ups on bookings
     * still missing customer_paid_total are added once via luggage_refund_amount.
     */
    private function sumCombinedPaidRevenue(?Carbon $from, ?Carbon $to): float
    {
        $bookingQ = Booking::query()->where('payment_status', 'Paid');
        $parcelQ = $this->commissionableParcelsQuery();
        $hireQ = SpecialHireOrder::query()->where('payment_status', 'paid');
        $legacyTopUpQ = Booking::query()
            ->where('payment_status', 'Paid')
            ->where('luggage_payment_status', 'paid')
            ->whereNull('customer_paid_total')
            ->where('luggage_refund_amount', '>', 0);

        if ($from && $to) {
            $bookingQ->whereBetween('created_at', [$from, $to]);
            $parcelQ->whereBetween('created_at', [$from, $to]);
            $hireQ->whereBetween('created_at', [$from, $to]);
            $legacyTopUpQ->whereBetween('created_at', [$from, $to]);
        }

        $tickets = (float) $bookingQ->sum(DB::raw('COALESCE(customer_paid_total, amount)'));
        $legacyTopUps = (float) $legacyTopUpQ->sum('luggage_refund_amount');
        $parcels = (float) $parcelQ->sum('amount_paid');
        $hire = (float) $hireQ->sum('total_amount');

        return round($tickets + $legacyTopUps + $parcels + $hire, 2);
    }

    private function countCombinedPaidTransactions(?Carbon $from, ?Carbon $to): int
    {
        $bookingQ = Booking::query()->where('payment_status', 'Paid');
        $parcelQ = $this->commissionableParcelsQuery();
        $hireQ = SpecialHireOrder::query()->where('payment_status', 'paid');

        if ($from && $to) {
            $bookingQ->whereBetween('created_at', [$from, $to]);
            $parcelQ->whereBetween('created_at', [$from, $to]);
            $hireQ->whereBetween('created_at', [$from, $to]);
        }

        return (int) $bookingQ->count() + (int) $parcelQ->count() + (int) $hireQ->count();
    }

    public function buses()
    {
        $this->requireAccess(Access::LINKS['BUSES']);
        $buses = bus::with('busname', 'route')->paginate(10);
        return view('system.buses', compact('buses'));
    }

    public function printBusesPdf()
    {
        $this->requireAccess(Access::LINKS['BUSES']);

        $buses = bus::with(['busname', 'route', 'campany'])
            ->orderBy('bus_number')
            ->get();

        $pdf = Pdf::loadView('print.system_bus_list', compact('buses'));
        return $pdf->download('buses_' . now()->format('Ymd_His') . '.pdf');
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

        $govLevyPercent = government_levy_percent();

        $owners = User::query()
            ->where('role', 'special_hire')
            ->withCount(['coasters', 'specialHireOrders'])
            ->withSum(['specialHireOrders as revenue_paid_sum' => fn ($q) => $q->where('payment_status', 'paid')], 'total_amount')
            ->withSum(['specialHireOrders as commission_paid_sum' => fn ($q) => $q->where('payment_status', 'paid')], 'platform_commission_amount')
            ->orderBy('name')
            ->get()
            ->each(function ($owner) use ($govLevyPercent) {
                $owner->gov_levy_sum = round(((float) ($owner->revenue_paid_sum ?? 0)) * $govLevyPercent / 100, 2);
            });

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
            'gov_levy' => round((float) SpecialHireOrder::where('user_id', $ownerId)->where('payment_status', 'paid')->sum('total_amount') * government_levy_percent() / 100, 2),
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

    public function specialHireReportPdf(Request $request)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $tab = $this->resolveSpecialHireReportTab($request);
        $payload = $this->buildSpecialHireIndexReportPayload($tab);

        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.special_hire_report_empty'));
        }

        $pdf = Pdf::loadView($payload['view'], $payload['pdfData']);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('special_hire_' . $tab . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function specialHireReportCsv(Request $request)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $tab = $this->resolveSpecialHireReportTab($request);
        $payload = $this->buildSpecialHireIndexReportPayload($tab);

        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.special_hire_report_empty'));
        }

        return $this->streamSpecialHireCsv(
            'special_hire_' . $tab . '_' . now()->format('Ymd_His') . '.csv',
            $payload['headers'],
            $payload['rows']
        );
    }

    public function specialHireOwnerReportPdf(int $user)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $payload = $this->buildSpecialHireOwnerOrdersReportPayload($user);

        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.special_hire_report_empty'));
        }

        $pdf = Pdf::loadView('print.special_hire_orders', $payload['pdfData']);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('special_hire_orders_' . $user . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function specialHireOwnerReportCsv(int $user)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $payload = $this->buildSpecialHireOwnerOrdersReportPayload($user);

        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.special_hire_report_empty'));
        }

        return $this->streamSpecialHireCsv(
            'special_hire_orders_' . $user . '_' . now()->format('Ymd_His') . '.csv',
            $payload['headers'],
            $payload['rows']
        );
    }

    private function resolveSpecialHireReportTab(Request $request): string
    {
        $tab = $request->query('tab', 'accounts');

        return in_array($tab, ['accounts', 'withdrawals', 'orders'], true) ? $tab : 'accounts';
    }

    private function buildSpecialHireIndexReportPayload(string $tab): array
    {
        if ($tab === 'withdrawals') {
            $withdrawals = SpecialHireWithdrawalRequest::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->get();

            $rows = $withdrawals->map(fn ($wr) => $this->mapSpecialHireWithdrawalRow($wr));

            return [
                'view' => 'print.special_hire_withdrawals',
                'headers' => array_keys($rows->first() ?? $this->emptySpecialHireWithdrawalRow()),
                'rows' => $rows,
                'pdfData' => [
                    'title' => __('system.pages.special_hire_withdrawals_report'),
                    'rows' => $rows->values()->all(),
                    'totals' => [
                        'amount' => $withdrawals->sum(fn ($wr) => (float) $wr->amount),
                    ],
                ],
            ];
        }

        if ($tab === 'orders') {
            $orders = SpecialHireOrder::query()
                ->with(['coaster', 'user'])
                ->orderByDesc('created_at')
                ->get();

            $rows = $orders->map(fn ($order) => $this->mapSpecialHireOrderRow($order));

            return [
                'view' => 'print.special_hire_orders',
                'headers' => array_keys($rows->first() ?? $this->emptySpecialHireOrderRow()),
                'rows' => $rows,
                'pdfData' => $this->specialHireOrdersPdfData(__('system.pages.special_hire_orders_report'), $orders, $rows),
            ];
        }

        $owners = User::query()
            ->where('role', 'special_hire')
            ->withCount(['coasters', 'specialHireOrders'])
            ->orderBy('name')
            ->get();

        $rows = $owners->map(fn ($owner) => $this->mapSpecialHireAccountRow($owner));

        return [
            'view' => 'print.special_hire_accounts',
            'headers' => array_keys($rows->first() ?? $this->emptySpecialHireAccountRow()),
            'rows' => $rows,
            'pdfData' => [
                'title' => __('system.pages.special_hire_accounts_report'),
                'rows' => $rows->values()->all(),
                'totals' => [
                    'coasters' => $owners->sum('coasters_count'),
                    'orders' => $owners->sum('special_hire_orders_count'),
                ],
            ],
        ];
    }

    private function buildSpecialHireOwnerOrdersReportPayload(int $user): array
    {
        $owner = User::query()->where('role', 'special_hire')->findOrFail($user);

        $orders = SpecialHireOrder::query()
            ->where('user_id', $owner->id)
            ->with(['coaster', 'user'])
            ->orderByDesc('created_at')
            ->get();

        $rows = $orders->map(fn ($order) => $this->mapSpecialHireOrderRow($order));

        return [
            'headers' => array_keys($rows->first() ?? $this->emptySpecialHireOrderRow()),
            'rows' => $rows,
            'pdfData' => $this->specialHireOrdersPdfData(
                __('system.pages.special_hire_orders_report') . ' — ' . $owner->name,
                $orders,
                $rows,
                $owner
            ),
        ];
    }

    private function specialHireOrdersPdfData(string $title, $orders, $rows, ?User $owner = null): array
    {
        return [
            'title' => $title,
            'operator' => $owner?->name,
            'rows' => $rows->values()->all(),
            'totals' => [
                'count' => $orders->count(),
                'total_amount' => $orders->sum(fn ($order) => (float) $order->total_amount),
                'platform_commission' => $orders->sum(fn ($order) => (float) ($order->platform_commission_amount ?? 0)),
                'operator_net' => $orders->sum(fn ($order) => $order->operatorNetAmount()),
                'paid_amount' => $orders->where('payment_status', 'paid')->sum(fn ($order) => (float) $order->total_amount),
            ],
        ];
    }

    private function mapSpecialHireAccountRow(User $owner): array
    {
        return [
            'name' => $owner->name,
            'email' => $owner->email,
            'contact' => $owner->contact ?? $owner->phone ?? '—',
            'coasters' => (string) ($owner->coasters_count ?? 0),
            'orders' => (string) ($owner->special_hire_orders_count ?? 0),
            'platform_percent' => number_format((float) ($owner->special_hire_platform_percent ?? 0), 2),
        ];
    }

    private function emptySpecialHireAccountRow(): array
    {
        return [
            'name' => '',
            'email' => '',
            'contact' => '',
            'coasters' => '',
            'orders' => '',
            'platform_percent' => '',
        ];
    }

    private function mapSpecialHireWithdrawalRow(SpecialHireWithdrawalRequest $wr): array
    {
        return [
            'date' => $wr->created_at?->format('Y-m-d H:i') ?? '—',
            'operator' => $wr->user?->name ?? '—',
            'email' => $wr->user?->email ?? '—',
            'amount' => number_format((float) $wr->amount, 2),
            'payment_method' => $wr->payment_method ?? '—',
            'payment_number' => $wr->payment_number ?? '—',
            'status' => $wr->status ?? '—',
            'processed_at' => $wr->processed_at?->format('Y-m-d H:i') ?? '—',
            'admin_note' => $wr->admin_note ?? '',
        ];
    }

    private function emptySpecialHireWithdrawalRow(): array
    {
        return [
            'date' => '',
            'operator' => '',
            'email' => '',
            'amount' => '',
            'payment_method' => '',
            'payment_number' => '',
            'status' => '',
            'processed_at' => '',
            'admin_note' => '',
        ];
    }

    private function mapSpecialHireOrderRow(SpecialHireOrder $order): array
    {
        return [
            'order_code' => $order->order_code,
            'operator' => $order->user?->name ?? '—',
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? '—',
            'coaster' => $order->coaster?->name ?? '—',
            'plate' => $order->coaster?->plate_number ?? '—',
            'customer_name' => $order->customer_name ?? '—',
            'customer_phone' => $order->customer_phone ?? '—',
            'pickup' => $order->pickup_location ?? '—',
            'dropoff' => $order->dropoff_location ?? '—',
            'hire_date' => $order->hire_date?->format('Y-m-d') ?? '—',
            'passengers' => (string) ($order->passengers_count ?? 0),
            'distance_km' => number_format((float) ($order->distance_km ?? 0), 2),
            'total_amount' => number_format((float) $order->total_amount, 2),
            'platform_commission' => number_format((float) ($order->platform_commission_amount ?? 0), 2),
            'operator_net' => number_format($order->operatorNetAmount(), 2),
            'payment_status' => $order->payment_status ?? '—',
            'order_status' => $order->order_status ?? '—',
            'payment_method' => $order->payment_method ?? '—',
        ];
    }

    private function emptySpecialHireOrderRow(): array
    {
        return [
            'order_code' => '',
            'operator' => '',
            'created_at' => '',
            'coaster' => '',
            'plate' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'pickup' => '',
            'dropoff' => '',
            'hire_date' => '',
            'passengers' => '',
            'distance_km' => '',
            'total_amount' => '',
            'platform_commission' => '',
            'operator_net' => '',
            'payment_status' => '',
            'order_status' => '',
            'payment_method' => '',
        ];
    }

    private function streamSpecialHireCsv(string $filename, array $headers, $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, array_values($row instanceof \Illuminate\Support\Collection ? $row->all() : (array) $row));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    public function toggleSpecialHireStatus(Request $request, int $user)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $request->validate([
            'status' => 'required|in:accept,disabled',
        ]);

        $owner = User::query()->where('role', 'special_hire')->findOrFail($user);
        $owner->update(['status' => $request->status]);

        $label = $request->status === 'accept' ? 'enabled' : 'disabled';

        return redirect()
            ->back()
            ->with('success', "Special hire account {$owner->name} has been {$label}.");
    }

    /**
     * Passenger manifest capture for one special-hire order (name, phone, gender,
     * infant flag) — persisted to the existing passenger_seats JSON column.
     */
    public function specialHireOrderPassengersEdit(int $order)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $hireOrder = SpecialHireOrder::with(['user', 'coaster'])->findOrFail($order);
        $passengers = is_array($hireOrder->passenger_seats) ? $hireOrder->passenger_seats : [];

        return view('system.special_hire_order_passengers', compact('hireOrder', 'passengers'));
    }

    public function specialHireOrderPassengersUpdate(Request $request, int $order)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $hireOrder = SpecialHireOrder::findOrFail($order);

        $request->validate([
            'passengers' => 'array',
            'passengers.*.name' => 'nullable|string|max:150',
            'passengers.*.phone' => 'nullable|string|max:30',
            'passengers.*.gender' => 'nullable|in:male,female',
            'passengers.*.is_infant' => 'nullable|boolean',
        ]);

        $passengers = collect($request->input('passengers', []))
            ->filter(fn ($row) => trim((string) ($row['name'] ?? '')) !== '')
            ->map(fn ($row) => [
                'name' => trim($row['name']),
                'phone' => trim((string) ($row['phone'] ?? '')),
                'gender' => $row['gender'] ?? null,
                'is_infant' => (bool) ($row['is_infant'] ?? false),
            ])
            ->values()
            ->all();

        $hireOrder->update(['passenger_seats' => $passengers]);

        return redirect()
            ->route('system.special_hire.order.passengers.edit', $order)
            ->with('success', __('system.messages.passengers_saved'));
    }

    public function specialHireOrderManifestPdf(int $order)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $hireOrder = SpecialHireOrder::with(['user', 'coaster.driver'])->findOrFail($order);
        $passengers = is_array($hireOrder->passenger_seats) ? $hireOrder->passenger_seats : [];

        $pdf = Pdf::loadView('print.special_hire_manifest', compact('hireOrder', 'passengers'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('special_hire_manifest_' . $hireOrder->order_code . '.pdf');
    }

    public function specialHireOrderCustomerReceiptPdf(int $order)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $hireOrder = SpecialHireOrder::with(['user', 'coaster'])->findOrFail($order);
        if (($hireOrder->payment_status ?? '') !== 'paid') {
            return redirect()->back()->with('error', __('system.pages.receipt_requires_paid'));
        }

        $pdf = Pdf::loadView('print.special_hire_customer_receipt', compact('hireOrder'));

        return $pdf->download('special_hire_receipt_' . $hireOrder->order_code . '.pdf');
    }

    public function specialHireOrderCommissionReceiptPdf(int $order)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $hireOrder = SpecialHireOrder::with(['user', 'coaster'])->findOrFail($order);
        if (($hireOrder->payment_status ?? '') !== 'paid') {
            return redirect()->back()->with('error', __('system.pages.receipt_requires_paid'));
        }

        $pdf = Pdf::loadView('print.special_hire_commission_receipt', compact('hireOrder'));

        return $pdf->download('special_hire_commission_' . $hireOrder->order_code . '.pdf');
    }

    /**
     * Reassign a special-hire order to a different coaster (and, if that
     * coaster belongs to a different operator, a different owner). This is a
     * logistics reassignment only — passengers, pricing, commission and
     * payment records are left untouched, since the payment was already
     * agreed/settled against the original quote. Blocked once the trip is
     * completed or cancelled, since there is nothing left to reassign.
     */
    public function specialHireOrderTransferEdit(int $order)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $hireOrder = SpecialHireOrder::with(['user', 'coaster'])->findOrFail($order);

        if (in_array($hireOrder->order_status, ['completed', 'cancelled'], true)) {
            return redirect()->route('system.special_hire.show', $hireOrder->user_id)
                ->with('error', __('system.pages.special_hire_transfer_not_allowed'));
        }

        $coasters = Coaster::with('user')
            ->where('id', '!=', $hireOrder->coaster_id)
            ->orderByDesc('status')
            ->orderBy('name')
            ->get();

        return view('system.special_hire_order_transfer', compact('hireOrder', 'coasters'));
    }

    public function specialHireOrderTransferUpdate(Request $request, int $order)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SPECIAL_HIRE']), 403);

        $hireOrder = SpecialHireOrder::findOrFail($order);

        if (in_array($hireOrder->order_status, ['completed', 'cancelled'], true)) {
            return redirect()->route('system.special_hire.show', $hireOrder->user_id)
                ->with('error', __('system.pages.special_hire_transfer_not_allowed'));
        }

        $request->validate([
            'new_coaster_id' => 'required|exists:coasters,id|different:' . $hireOrder->coaster_id,
        ]);

        $newCoaster = Coaster::findOrFail($request->new_coaster_id);

        $hireOrder->update([
            'coaster_id' => $newCoaster->id,
            'user_id' => $newCoaster->user_id,
        ]);

        // If ownership moved to a different operator, land back on that
        // operator's page; otherwise stay on the original operator's page.
        return redirect()->route('system.special_hire.show', $newCoaster->user_id)
            ->with('success', __('system.pages.special_hire_transfer_success', [
                'coaster' => $newCoaster->name,
            ]));
    }

    public function pay_request(Request $request)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);

        $queries = $this->buildPaymentRequestQueries($request);
        $pendingTransactions = $queries['pendingQuery']->orderByDesc('created_at')->get();
        $allTransactions = $queries['allQuery']->orderByDesc('created_at')->get();
        $allTransactionsTotal = $allTransactions->sum('amount');
        $dateFilter = $queries['dateFilter'];

        return view('system.transaction', array_merge(
            compact('pendingTransactions', 'allTransactions', 'allTransactionsTotal'),
            [
                'period' => $dateFilter['period'],
                'startDate' => $dateFilter['startDate'],
                'endDate' => $dateFilter['endDate'],
            ]
        ));
    }

    public function paymentRequestReportPdf(Request $request)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);

        $payload = $this->buildPaymentRequestExportPayload($request);
        if ($payload['pendingRows']->isEmpty() && $payload['allRows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.no_transactions_filter'));
        }

        $pdf = Pdf::loadView('print.payment_request', $payload['pdfData']);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('payment_requests_' . now()->format('Ymd_His') . '.pdf');
    }

    public function paymentRequestReportCsv(Request $request)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);

        $payload = $this->buildPaymentRequestExportPayload($request);
        if ($payload['csvRows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.no_transactions_filter'));
        }

        return $this->streamSpecialHireCsv(
            'payment_requests_' . now()->format('Ymd_His') . '.csv',
            $payload['headers'],
            $payload['csvRows']
        );
    }

    private function buildPaymentRequestQueries(Request $request): array
    {
        $pendingQuery = Transaction::whereRaw('LOWER(status) = ?', ['pending'])
            ->with(['campany', 'user.VenderAccount']);
        $dateFilter = apply_booking_history_date_filter($pendingQuery, $request);

        $allQuery = Transaction::with(['campany', 'user.VenderAccount']);
        apply_booking_history_date_filter($allQuery, $request);

        return [
            'pendingQuery' => $pendingQuery,
            'allQuery' => $allQuery,
            'dateFilter' => $dateFilter,
        ];
    }

    private function buildPaymentRequestExportPayload(Request $request): array
    {
        $queries = $this->buildPaymentRequestQueries($request);
        $pending = $queries['pendingQuery']->orderByDesc('created_at')->get();
        $all = $queries['allQuery']->orderByDesc('created_at')->get();
        $dateFilter = $queries['dateFilter'];

        $pendingRows = $pending->map(fn ($transaction) => $this->mapPaymentRequestRow($transaction, 'pending'));
        $allRows = $all->map(fn ($transaction) => $this->mapPaymentRequestRow($transaction, 'all'));
        $csvRows = $pendingRows->concat($allRows);

        return [
            'headers' => array_keys($csvRows->first() ?? $this->emptyPaymentRequestRow()),
            'csvRows' => $csvRows,
            'pendingRows' => $pendingRows,
            'allRows' => $allRows,
            'pdfData' => [
                'title' => __('system.transactions.report_title'),
                'period' => $dateFilter['period'] ?? '',
                'startDate' => $dateFilter['startDate'],
                'endDate' => $dateFilter['endDate'],
                'pendingRows' => $pendingRows->values()->all(),
                'allRows' => $allRows->values()->all(),
                'pendingTotal' => (float) $pending->sum('amount'),
                'allTotal' => (float) $all->sum('amount'),
            ],
        ];
    }

    private function mapPaymentRequestRow(Transaction $transaction, string $section): array
    {
        return [
            'section' => $section === 'pending'
                ? __('system.transactions.requested_transactions')
                : __('system.transactions.all_transactions'),
            'company' => $transaction->campany ? $transaction->campany->name : __('system.common.vender_label'),
            'user' => $transaction->user ? $transaction->user->name : __('system.common.unknown'),
            'payment_method' => $transaction->payment_method ?? '—',
            'payment_number' => transaction_payment_detail($transaction),
            'amount' => number_format((float) $transaction->amount, 2),
            'status' => $transaction->status,
            'reference_number' => $transaction->reference_number ?? '—',
            'date' => optional($transaction->created_at)->format('Y-m-d H:i') ?? '—',
        ];
    }

    private function emptyPaymentRequestRow(): array
    {
        return [
            'section' => '',
            'company' => '',
            'user' => '',
            'payment_method' => '',
            'payment_number' => '',
            'amount' => '',
            'status' => '',
            'reference_number' => '',
            'date' => '',
        ];
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
        $pendingTransactions = Transaction::whereRaw('LOWER(status) = ?', ['pending'])
            ->with(['campany', 'user.VenderAccount'])
            ->get();

        // Initialize query for all transactions
        $query = Transaction::with(['campany', 'user.VenderAccount']);

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

    public function cancel(Request $request, $transaction, $campany = null, $vender = null)
    {
        $this->requireAccess(Access::LINKS['PAYMENT_REQUEST']);
        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);

        $transaction = Transaction::findOrFail($transaction);

        if ($campany != 0 && (int) $transaction->campany_id !== (int) $campany) {
            return redirect()->back()->with('error', __('system.messages.invalid_company_transaction'));
        }

        $companyId = (int) ($transaction->campany_id ?? 0);
        $isPending = strtolower((string) $transaction->status) === 'pending';

        try {
            \DB::beginTransaction();

            // If transaction was pending, refund the amount back to company balance
            if ($isPending && $companyId > 0) {
                $balance = balance::where('campany_id', $companyId)->first();
                if ($balance) {
                    $balance->amount += $transaction->amount;
                    $balance->save();
                }
            }

            $transaction->cancel_reason = $request->cancel_reason;
            $transaction->status = 'Cancelled';
            $transaction->save();

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();

            return redirect()->back()->with('error', __('system.messages.transaction_cancel_failed'));
        }

        return redirect()->back()->with('success', __('system.messages.transaction_cancelled'));
    }

    public function campany()
    {
        $this->requireAccess(Access::LINKS['BUS_OPERATORS']);

        $campanies = Campany::all();
        return view('system.campany', compact('campanies'));
    }

    public function printCampaniesPdf()
    {
        $this->requireAccess(Access::LINKS['BUS_OPERATORS']);

        $campanies = Campany::with(['user', 'balance'])->orderBy('name')->get();

        $pdf = Pdf::loadView('print.campany_list', compact('campanies'));
        return $pdf->download('bus_operators_' . now()->format('Ymd_His') . '.pdf');
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
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(COALESCE(customer_paid_total, busFee, amount)) as total')
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
        $totalBookingsRevenue = Booking::where('campany_id', $campany->id)->where('payment_status', 'Paid')->sum(DB::raw('COALESCE(customer_paid_total, busFee, amount)'));
        $totalLuggageRevenue = (float) Booking::where('campany_id', $campany->id)
            ->where('payment_status', 'Paid')
            ->where('excess_luggage_fee', '>', 0)
            ->sum('excess_luggage_fee');
        $totalParcelRevenue = (float) $this->commissionableParcelsQuery()
            ->whereHas('bus', fn ($q) => $q->where('campany_id', $campany->id))
            ->sum('amount_paid');
        $parcelCommissionPercent = (float) (Setting::first()->parcel_commission_percentage ?? 0);
        $totalParcelCommission = round($totalParcelRevenue * $parcelCommissionPercent / 100, 2);
        $totalOperatorRevenue = round($totalBookingsRevenue + $totalParcelRevenue, 2);

        return view('system.campany_dashboard', compact(
            'campany', 'bookings', 'bookingsChart', 'schedules',
            'systemBalances', 'paymentFees', 'transactions',
            'totalCommission', 'totalServiceFees', 'totalBookingsRevenue',
            'totalLuggageRevenue', 'totalParcelRevenue', 'totalParcelCommission', 'totalOperatorRevenue'
        ));
    }

    public function system_payments()
    {
        $this->requireAccess(Access::LINKS['SYSTEM_INCOME']);
        $balances = SystemBalance::with('campany')->orderByDesc('created_at')->get();
        $pays = PaymentFees::with('campany')->orderByDesc('created_at')->get();
        $luggageBookings = $this->paidLuggageBookingsQuery()->with('campany')->orderByDesc('created_at')->get();
        $cancellations = CancelledBookings::with(['campany', 'booking'])->orderByDesc('created_at')->get();
        $parcels = $this->commissionableParcelsQuery()->with('bus.campany')->orderByDesc('created_at')->get();
        $specialHireOrders = $this->paidSpecialHireCommissionQuery()->with('user')->orderByDesc('created_at')->get();

        $bookingsByCode = $this->bookingsForPaymentFeeCodes($pays->pluck('booking_id')->all());
        $pays = $pays->map(function ($payment) use ($bookingsByCode) {
            $payment->display_amount = $this->paymentFeeDisplayAmount($payment, $bookingsByCode);

            return $payment;
        });

        $parcelCommissionPercent = (float) (Setting::first()->parcel_commission_percentage ?? 0);
        $parcels = $parcels->map(function ($parcel) use ($parcelCommissionPercent) {
            $parcel->commission_amount = round((float) $parcel->amount_paid * $parcelCommissionPercent / 100, 2);

            return $parcel;
        });

        return view('system.payments', compact('balances', 'pays', 'luggageBookings', 'cancellations', 'parcels', 'specialHireOrders'));
    }

    /**
     * Parcels eligible for commission income — only ClickPesa/settlement-confirmed paid rows.
     */
    private function commissionableParcelsQuery()
    {
        return Parcel::query()
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'paid');
    }

    private function paidSpecialHireCommissionQuery()
    {
        return SpecialHireOrder::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('platform_commission_amount');
    }

    public function systemIncomeReportPdf(Request $request)
    {
        $this->requireAccess(Access::LINKS['SYSTEM_INCOME']);

        $payload = $this->buildSystemIncomeExportPayload($request);
        if ($payload['csvRows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.no_data_found'));
        }

        $pdf = Pdf::loadView('print.system_income', $payload['pdfData']);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('system_income_' . now()->format('Ymd_His') . '.pdf');
    }

    public function systemIncomeReportCsv(Request $request)
    {
        $this->requireAccess(Access::LINKS['SYSTEM_INCOME']);

        $payload = $this->buildSystemIncomeExportPayload($request);
        if ($payload['csvRows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.no_data_found'));
        }

        return $this->streamSpecialHireCsv(
            'system_income_' . now()->format('Ymd_His') . '.csv',
            $payload['headers'],
            $payload['csvRows']
        );
    }

    private function buildSystemIncomeExportPayload(Request $request): array
    {
        $balances = $this->buildSystemIncomeLedgerQuery(SystemBalance::query(), $request)->get();
        $pays = $this->buildSystemIncomeLedgerQuery(PaymentFees::query(), $request)->get();
        $luggageQuery = $this->paidLuggageBookingsQuery()->with('campany')->orderByDesc('created_at');
        $this->applySystemIncomeDateFilter($luggageQuery, $request);
        $luggageBookings = $luggageQuery->get();
        $cancellationsQuery = CancelledBookings::query()->with('campany', 'booking')->orderByDesc('created_at');
        $this->applySystemIncomeDateFilter($cancellationsQuery, $request);
        $cancellations = $cancellationsQuery->get();
        $parcelQuery = $this->commissionableParcelsQuery()->with('bus.campany')->orderByDesc('created_at');
        $this->applySystemIncomeDateFilter($parcelQuery, $request);
        $parcels = $parcelQuery->get();
        $specialHireQuery = $this->paidSpecialHireCommissionQuery()->with('user')->orderByDesc('created_at');
        $this->applySystemIncomeDateFilter($specialHireQuery, $request);
        $specialHireOrders = $specialHireQuery->get();
        $dateFilter = $this->resolveSystemIncomeDateFilter($request);

        $bookingsByCode = $this->bookingsForPaymentFeeCodes($pays->pluck('booking_id')->all());

        $commissionRows = $balances->values()->map(function ($record, $index) {
            return $this->mapSystemIncomeRow(
                __('system.pages.commission'),
                $index + 1,
                $record->campany->name ?? '—',
                $record->booking_id ?? 'N/A',
                (float) $record->balance,
                $record->created_at
            );
        });

        $serviceFeeRows = $pays->values()->map(function ($record, $index) use ($bookingsByCode) {
            return $this->mapSystemIncomeRow(
                __('system.pages.service_fees'),
                $index + 1,
                $record->campany->name ?? '—',
                $record->booking_id ?? 'N/A',
                $this->paymentFeeDisplayAmount($record, $bookingsByCode),
                $record->created_at
            );
        });

        $luggageRows = $luggageBookings->values()->map(function ($booking, $index) {
            return $this->mapSystemIncomeRow(
                __('system.pages.luggage_fees'),
                $index + 1,
                $booking->campany->name ?? '—',
                $booking->booking_code ?? 'N/A',
                booking_released_luggage_admin_fee($booking),
                $booking->created_at
            );
        });

        $cancellationRows = $cancellations->values()->map(function ($record, $index) {
            return $this->mapSystemIncomeRow(
                __('system.pages.cancellation_fees'),
                $index + 1,
                $record->campany->name ?? '—',
                optional($record->booking)->booking_code ?? 'N/A',
                (float) $record->amount,
                $record->created_at
            );
        });

        $parcelCommissionPercent = (float) (Setting::first()->parcel_commission_percentage ?? 0);
        $parcelRows = $parcels->values()->map(function ($parcel, $index) use ($parcelCommissionPercent) {
            $commission = round((float) $parcel->amount_paid * $parcelCommissionPercent / 100, 2);

            return $this->mapSystemIncomeRow(
                __('system.pages.parcel_commission_fees'),
                $index + 1,
                $parcel->bus->campany->name ?? '—',
                $parcel->parcel_number ?? 'N/A',
                $commission,
                $parcel->created_at
            );
        });

        $specialHireRows = $specialHireOrders->values()->map(function ($order, $index) {
            return $this->mapSystemIncomeRow(
                __('system.pages.special_hire_commission_fees'),
                $index + 1,
                $order->user->name ?? '—',
                $order->order_code ?? 'N/A',
                (float) $order->platform_commission_amount,
                $order->created_at
            );
        });

        $commissionTotal = (float) $balances->sum('balance');
        $serviceFeeTotal = (float) $pays->sum(fn ($record) => $this->paymentFeeDisplayAmount($record, $bookingsByCode));
        $luggageTotal = (float) $luggageBookings->sum(fn ($booking) => booking_released_luggage_admin_fee($booking));
        $cancellationTotal = (float) $cancellations->sum('amount');
        $parcelTotal = (float) $parcels->sum(fn ($parcel) => round((float) $parcel->amount_paid * $parcelCommissionPercent / 100, 2));
        $specialHireTotal = (float) $specialHireOrders->sum('platform_commission_amount');
        $combinedTotal = $commissionTotal + $serviceFeeTotal + $luggageTotal
            + $cancellationTotal + $parcelTotal + $specialHireTotal;
        $csvRows = $commissionRows->concat($serviceFeeRows)->concat($luggageRows)
            ->concat($cancellationRows)->concat($parcelRows)->concat($specialHireRows);

        return [
            'headers' => array_keys($csvRows->first() ?? $this->emptySystemIncomeRow()),
            'csvRows' => $csvRows,
            'pdfData' => [
                'title' => __('system.pages.payments_title'),
                'period' => $dateFilter['period'],
                'startDate' => $dateFilter['startDate'],
                'endDate' => $dateFilter['endDate'],
                'commissionRows' => $commissionRows->values()->all(),
                'serviceFeeRows' => $serviceFeeRows->values()->all(),
                'luggageRows' => $luggageRows->values()->all(),
                'cancellationRows' => $cancellationRows->values()->all(),
                'parcelRows' => $parcelRows->values()->all(),
                'specialHireRows' => $specialHireRows->values()->all(),
                'commissionTotal' => $commissionTotal,
                'serviceFeeTotal' => $serviceFeeTotal,
                'luggageTotal' => $luggageTotal,
                'cancellationTotal' => $cancellationTotal,
                'parcelTotal' => $parcelTotal,
                'specialHireTotal' => $specialHireTotal,
                'combinedTotal' => $combinedTotal,
            ],
        ];
    }

    private function paidLuggageBookingsQuery()
    {
        return Booking::query()
            ->with(['campany', 'excessLuggageEscrow'])
            ->where('payment_status', 'Paid')
            ->where('excess_luggage_fee', '>', 0);
    }

    private function sumReleasedLuggageAdminIncome(): float
    {
        $fromEscrow = (float) ExcessLuggageEscrow::query()
            ->whereIn('status', [
                ExcessLuggageEscrow::STATUS_RELEASED,
                ExcessLuggageEscrow::STATUS_SURPLUS_HELD,
                ExcessLuggageEscrow::STATUS_REFUNDED,
            ])
            ->sum('admin_share');

        $escrowBookingIds = ExcessLuggageEscrow::query()->pluck('booking_id');
        $legacy = (float) Booking::query()
            ->where('payment_status', 'Paid')
            ->where('excess_luggage_fee', '>', 0)
            ->when($escrowBookingIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $escrowBookingIds))
            ->get()
            ->sum(fn ($booking) => system_luggage_fee($booking));

        return round($fromEscrow + $legacy, 2);
    }

    /**
     * @param  array<int, string|null>  $bookingCodes
     * @return \Illuminate\Support\Collection<string, \App\Models\Booking>
     */
    private function bookingsForPaymentFeeCodes(array $bookingCodes)
    {
        $codes = array_values(array_filter(array_unique($bookingCodes)));
        if ($codes === []) {
            return collect();
        }

        return Booking::query()
            ->whereIn('booking_code', $codes)
            ->get(['booking_code', 'system_service_fee', 'service', 'vender_service'])
            ->keyBy('booking_code');
    }

    /**
     * System-retained service fee for a payment_fees row.
     * Prefer booking recalculation so legacy vendor levy-on-pool rows are corrected.
     *
     * @param  \Illuminate\Support\Collection<string, \App\Models\Booking>  $bookingsByCode
     */
    private function paymentFeeDisplayAmount($payment, $bookingsByCode): float
    {
        $booking = $bookingsByCode->get($payment->booking_id);
        if ($booking) {
            return booking_system_retained_service_fee($booking);
        }

        return (float) $payment->amount;
    }

    private function buildSystemIncomeLedgerQuery($query, Request $request)
    {
        $query->with('campany')->orderByDesc('created_at');
        $this->applySystemIncomeDateFilter($query, $request);

        return $query;
    }

    private function resolveSystemIncomeDateFilter(Request $request): array
    {
        $period = $request->query('period');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate && $endDate) {
            $period = 'custom';
        } elseif (! $period || $period === 'all') {
            $period = null;
        }

        return [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    private function applySystemIncomeDateFilter($query, Request $request, string $dateColumn = 'created_at'): void
    {
        $period = $request->query('period');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween($dateColumn, [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

            return;
        }

        if (! $period || $period === 'all') {
            return;
        }

        switch ($period) {
            case 'day':
            case 'today':
                $query->whereDate($dateColumn, today());
                break;
            case 'week':
                $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year);
                break;
            case 'year':
                $query->whereYear($dateColumn, now()->year);
                break;
        }
    }

    private function mapSystemIncomeRow(
        string $incomeType,
        int $no,
        string $company,
        string $bookingCode,
        float $amount,
        $createdAt
    ): array {
        return [
            'income_type' => $incomeType,
            'no' => $no,
            'company' => $company,
            'booking_code' => $bookingCode,
            'amount' => number_format($amount, 2),
            'date' => optional($createdAt)->format('Y-m-d H:i') ?? '—',
        ];
    }

    private function emptySystemIncomeRow(): array
    {
        return [
            'income_type' => '',
            'no' => '',
            'company' => '',
            'booking_code' => '',
            'amount' => '',
            'date' => '',
        ];
    }

    public function governmentLevyReport(Request $request)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SYSTEM_INCOME']), 403);

        $period = $request->query('period', 'month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = $this->buildGovernmentLevyBookingsQuery($request);
        $bookings = (clone $query)->latest()->paginate(50)->withQueryString();
        $totals = $this->computeGovernmentLevyCategoryTotals($request);

        $specialHireOrders = $this->buildGovernmentLevySpecialHireQuery($request)
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $hasGovernmentLevyColumn = Schema::hasColumn('bookings', 'government_levy');
        $hasSystemServiceFeeColumn = Schema::hasColumn('bookings', 'system_service_fee');

        return view('system.government_levy', compact(
            'bookings',
            'specialHireOrders',
            'period',
            'startDate',
            'endDate',
            'hasGovernmentLevyColumn',
            'hasSystemServiceFeeColumn'
        ) + $totals);
    }

    public function governmentLevyReportPdf(Request $request)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SYSTEM_INCOME']), 403);

        $payload = $this->buildGovernmentLevyExportPayload($request);
        if (
            ($payload['pdfData']['totals']['totalGovernmentLevy'] ?? 0) <= 0
            && empty($payload['pdfData']['rows'])
            && empty($payload['pdfData']['specialHireRows'])
        ) {
            return redirect()->back()->with('error', __('system.pages.no_paid_bookings_filter'));
        }

        $pdf = Pdf::loadView('print.government_levy', $payload['pdfData']);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('government_levy_' . now()->format('Ymd_His') . '.pdf');
    }

    public function governmentLevyReportCsv(Request $request)
    {
        abort_unless(Auth::user()->hasAccess(Access::LINKS['SYSTEM_INCOME']), 403);

        $payload = $this->buildGovernmentLevyExportPayload($request);
        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.no_paid_bookings_filter'));
        }

        return $this->streamSpecialHireCsv(
            'government_levy_' . now()->format('Ymd_His') . '.csv',
            $payload['headers'],
            $payload['rows']
        );
    }

    private function buildGovernmentLevyBookingsQuery(Request $request)
    {
        $query = Booking::query()
            ->where('payment_status', 'Paid')
            ->with(['campany', 'route', 'vender', 'governmentLeviesOnService']);

        $this->applyGovernmentLevyPeriodFilter($query, $request);

        return $query;
    }

    private function buildGovernmentLevySpecialHireQuery(Request $request)
    {
        $query = SpecialHireOrder::query()
            ->where('payment_status', 'paid')
            ->whereNotNull('platform_commission_amount')
            ->with('user');

        $this->applyGovernmentLevyPeriodFilter($query, $request);

        return $query;
    }

    private function buildGovernmentLevyCancellationsQuery(Request $request)
    {
        $query = CancelledBookings::query()->with(['campany', 'booking']);
        $this->applyGovernmentLevyPeriodFilter($query, $request);

        return $query;
    }

    private function buildGovernmentLevyParcelsQuery(Request $request)
    {
        $query = $this->commissionableParcelsQuery()->with('bus.campany');
        $this->applyGovernmentLevyPeriodFilter($query, $request);

        return $query;
    }

    private function applyGovernmentLevyPeriodFilter($query, Request $request): void
    {
        $period = $request->query('period', 'month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

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
        // period=all (or unknown) → no date filter
    }

    /**
     * Six levy categories + fare/service reconciliation totals for booking history parity.
     */
    private function computeGovernmentLevyCategoryTotals(Request $request): array
    {
        $bookings = $this->buildGovernmentLevyBookingsQuery($request)
            ->get([
                'id', 'booking_code', 'amount', 'customer_paid_total', 'vat', 'busFee',
                'fee', 'vender_fee', 'service', 'vender_service', 'system_service_fee',
                'government_levy', 'excess_luggage_fee', 'has_excess_luggage', 'created_at',
            ]);

        $levyCommission = (float) $bookings->sum(fn ($b) => booking_government_levy_on_commission($b));
        $levyService = (float) $bookings->sum(fn ($b) => booking_government_levy_on_service($b));
        $levyLuggage = (float) $bookings->sum(fn ($b) => booking_government_levy_on_luggage($b));
        $levyFare = (float) $bookings->sum(fn ($b) => booking_government_levy_on_fare($b));
        $farePlusService = (float) $bookings->sum(fn ($b) => booking_total_government_levy($b));

        $cancellations = $this->buildGovernmentLevyCancellationsQuery($request)->get();
        $levyCancellation = (float) $cancellations->sum(
            fn ($row) => government_levy_on_amount(abs((float) $row->amount))
        );

        $parcels = $this->buildGovernmentLevyParcelsQuery($request)->get();
        $levyParcel = (float) $parcels->sum(
            fn ($parcel) => government_levy_on_amount((float) $parcel->amount_paid)
        );

        $specialHireOrders = $this->buildGovernmentLevySpecialHireQuery($request)->get();
        $specialHireCommissionBase = (float) $specialHireOrders->sum('platform_commission_amount');
        $levySpecialHire = (float) $specialHireOrders->sum(
            fn ($order) => government_levy_on_amount((float) ($order->platform_commission_amount ?? 0))
        );

        $totalGovernmentLevy = round(
            $levyCommission + $levyService + $levyLuggage + $levyCancellation + $levyParcel + $levySpecialHire,
            2
        );

        return [
            'totalPaidAmount' => (float) $bookings->sum(fn ($b) => (float) ($b->customer_paid_total ?? $b->amount ?? 0)),
            'totalVat' => (float) $bookings->sum('vat'),
            'totalGovLevyOnFare' => $levyFare,
            'totalGovLevyOnService' => $levyService,
            'farePlusServiceLevy' => $farePlusService,
            'levyCommission' => round($levyCommission, 2),
            'levyService' => round($levyService, 2),
            'levyLuggage' => round($levyLuggage, 2),
            'levyCancellation' => round($levyCancellation, 2),
            'levyParcel' => round($levyParcel, 2),
            'levySpecialHire' => round($levySpecialHire, 2),
            'specialHireCommissionBase' => round($specialHireCommissionBase, 2),
            'specialHireLevyTotal' => round($levySpecialHire, 2),
            'cancellationCount' => $cancellations->count(),
            'parcelCount' => $parcels->count(),
            'luggageBookingCount' => $bookings->filter(fn ($b) => booking_luggage_fee($b) > 0)->count(),
            'totalGovernmentLevy' => $totalGovernmentLevy,
            'levyPercent' => government_levy_percent(),
        ];
    }

    private function buildGovernmentLevyExportPayload(Request $request): array
    {
        $totals = $this->computeGovernmentLevyCategoryTotals($request);
        $levyPercent = government_levy_percent();

        $bookings = $this->buildGovernmentLevyBookingsQuery($request)->latest()->get();
        $bookingDetailRows = $bookings->map(fn ($booking) => $this->mapGovernmentLevyRow($booking));

        $cancellations = $this->buildGovernmentLevyCancellationsQuery($request)->latest()->get();
        $parcels = $this->buildGovernmentLevyParcelsQuery($request)->latest()->get();
        $specialHireOrders = $this->buildGovernmentLevySpecialHireQuery($request)->latest()->get();
        $specialHireDetailRows = $specialHireOrders->map(fn ($order) => $this->mapGovernmentLevySpecialHireRow($order));

        $categoryRows = collect([
            [
                'category' => __('system.pages.levy_cat_commission'),
                'reference' => '—',
                'date' => '—',
                'detail' => 'fee + vender_fee',
                'fee_base' => number_format((float) $bookings->sum(fn ($b) => booking_gross_commission($b)), 2),
                'gov_levy' => number_format($totals['levyCommission'], 2),
            ],
            [
                'category' => __('system.pages.levy_cat_service'),
                'reference' => '—',
                'date' => '—',
                'detail' => 'system_service_fee (full)',
                'fee_base' => number_format((float) $bookings->sum(fn ($b) => booking_gross_service_fee($b)), 2),
                'gov_levy' => number_format($totals['levyService'], 2),
            ],
            [
                'category' => __('system.pages.levy_cat_luggage'),
                'reference' => '—',
                'date' => '—',
                'detail' => 'excess_luggage_fee',
                'fee_base' => number_format((float) $bookings->sum(fn ($b) => booking_luggage_fee($b)), 2),
                'gov_levy' => number_format($totals['levyLuggage'], 2),
            ],
            [
                'category' => __('system.pages.levy_cat_cancellation'),
                'reference' => '—',
                'date' => '—',
                'detail' => 'cancelled_bookings.amount',
                'fee_base' => number_format((float) $cancellations->sum(fn ($r) => abs((float) $r->amount)), 2),
                'gov_levy' => number_format($totals['levyCancellation'], 2),
            ],
            [
                'category' => __('system.pages.levy_cat_parcel'),
                'reference' => '—',
                'date' => '—',
                'detail' => 'parcels.amount_paid',
                'fee_base' => number_format((float) $parcels->sum('amount_paid'), 2),
                'gov_levy' => number_format($totals['levyParcel'], 2),
            ],
            [
                'category' => __('system.pages.levy_cat_special_hire'),
                'reference' => '—',
                'date' => '—',
                'detail' => 'platform_commission_amount',
                'fee_base' => number_format($totals['specialHireCommissionBase'], 2),
                'gov_levy' => number_format($totals['levySpecialHire'], 2),
            ],
            [
                'category' => __('system.pages.total_gov_levy'),
                'reference' => '—',
                'date' => '—',
                'detail' => 'six categories',
                'fee_base' => '',
                'gov_levy' => number_format($totals['totalGovernmentLevy'], 2),
            ],
        ]);

        $bookingCsvRows = $bookings->map(function ($booking) {
            return [
                'category' => 'booking',
                'reference' => $booking->booking_code ?? 'N/A',
                'date' => optional($booking->created_at)->format('Y-m-d H:i') ?? '—',
                'detail' => 'fare=' . number_format(booking_government_levy_on_fare($booking), 2)
                    . '; service=' . number_format(booking_government_levy_on_service($booking), 2)
                    . '; commission=' . number_format(booking_government_levy_on_commission($booking), 2)
                    . '; luggage=' . number_format(booking_government_levy_on_luggage($booking), 2),
                'fee_base' => number_format(booking_gross_service_fee($booking), 2),
                'gov_levy' => number_format(booking_total_government_levy($booking), 2),
            ];
        });

        $cancellationCsvRows = $cancellations->map(function ($row) {
            $base = abs((float) $row->amount);

            return [
                'category' => 'cancellation',
                'reference' => optional($row->booking)->booking_code ?? 'N/A',
                'date' => optional($row->created_at)->format('Y-m-d H:i') ?? '—',
                'detail' => optional($row->campany)->name ?? '—',
                'fee_base' => number_format($base, 2),
                'gov_levy' => number_format(government_levy_on_amount($base), 2),
            ];
        });

        $parcelCsvRows = $parcels->map(function ($parcel) {
            $base = (float) $parcel->amount_paid;

            return [
                'category' => 'parcel',
                'reference' => $parcel->parcel_number ?? 'N/A',
                'date' => optional($parcel->created_at)->format('Y-m-d H:i') ?? '—',
                'detail' => optional(optional($parcel->bus)->campany)->name ?? '—',
                'fee_base' => number_format($base, 2),
                'gov_levy' => number_format(government_levy_on_amount($base), 2),
            ];
        });

        $specialHireCsvRows = $specialHireOrders->map(function ($order) {
            $commission = (float) ($order->platform_commission_amount ?? 0);

            return [
                'category' => 'special_hire',
                'reference' => $order->order_code ?? 'N/A',
                'date' => optional($order->created_at)->format('Y-m-d H:i') ?? '—',
                'detail' => $order->user->name ?? '—',
                'fee_base' => number_format($commission, 2),
                'gov_levy' => number_format(government_levy_on_amount($commission), 2),
            ];
        });

        $csvRows = $categoryRows
            ->concat($bookingCsvRows)
            ->concat($cancellationCsvRows)
            ->concat($parcelCsvRows)
            ->concat($specialHireCsvRows);

        return [
            'headers' => array_keys($csvRows->first() ?? $this->emptyGovernmentLevyRow()),
            'rows' => $csvRows,
            'pdfData' => [
                'title' => __('system.pages.levy_title'),
                'period' => $request->query('period', 'month'),
                'startDate' => $request->query('start_date'),
                'endDate' => $request->query('end_date'),
                'levyPercent' => $levyPercent,
                'rows' => $bookingDetailRows->values()->all(),
                'specialHireRows' => $specialHireDetailRows->values()->all(),
                'categoryRows' => $categoryRows->values()->all(),
                'totals' => $totals,
            ],
        ];
    }

    private function emptyGovernmentLevyRow(): array
    {
        return [
            'category' => '',
            'reference' => '',
            'date' => '',
            'detail' => '',
            'fee_base' => '',
            'gov_levy' => '',
        ];
    }

    private function mapGovernmentLevyRow(Booking $booking): array
    {
        $govLevyOnFare = booking_government_levy_on_fare($booking);
        $govLevyOnService = booking_government_levy_on_service($booking);
        $totalGovLevy = booking_total_government_levy($booking);
        $commissionLevy = booking_government_levy_on_commission($booking);
        $luggageLevy = booking_government_levy_on_luggage($booking);
        $paidAmount = (float) ($booking->customer_paid_total ?? $booking->amount ?? 0);

        return [
            'booking_code' => $booking->booking_code ?? 'N/A',
            'date' => optional($booking->created_at)->format('Y-m-d H:i') ?? '—',
            'route' => ($booking->route->from ?? 'N/A') . ' - ' . ($booking->route->to ?? 'N/A'),
            'vendor' => ($booking->vender_id ?? 0) > 0 ? 'Involved' : 'Not Involved',
            'paid_amount' => number_format($paidAmount, 2),
            'vat' => number_format((float) ($booking->vat ?? 0), 2),
            'gov_levy_fare' => number_format($govLevyOnFare, 2),
            'gov_levy_service' => number_format($govLevyOnService, 2),
            'gov_levy_commission' => number_format($commissionLevy, 2),
            'gov_levy_luggage' => number_format($luggageLevy, 2),
            'total_gov_levy' => number_format($totalGovLevy, 2),
            'fee_base' => number_format(booking_gross_service_fee($booking), 2),
            'gov_levy' => number_format($totalGovLevy, 2),
        ];
    }

    private function mapGovernmentLevySpecialHireRow(SpecialHireOrder $order): array
    {
        $commission = (float) ($order->platform_commission_amount ?? 0);
        $shLevy = government_levy_on_amount($commission);

        return [
            'booking_code' => $order->order_code ?? 'N/A',
            'date' => optional($order->created_at)->format('Y-m-d H:i') ?? '—',
            'route' => ($order->pickup_location ?? 'N/A') . ' - ' . ($order->dropoff_location ?? 'N/A'),
            'vendor' => $order->user->name ?? '—',
            'paid_amount' => number_format((float) ($order->total_amount ?? 0), 2),
            'fee_base' => number_format($commission, 2),
            'gov_levy' => number_format($shLevy, 2),
            'total_gov_levy' => number_format($shLevy, 2),
        ];
    }

    public function history(Request $request)
    {
        $this->requireAccess(Access::LINKS['BOOKING_HISTORY']);

        // Warn (but don't redirect) when a custom range is missing its dates —
        // redirecting back to system.history?period=custom re-triggers this guard
        // and loops forever (ERR_TOO_MANY_REDIRECTS). The date filter helper applies
        // no filter when the custom dates are absent.
        if ($request->get('period') === 'custom' && (! $request->filled('start_date') || ! $request->filled('end_date'))) {
            session()->flash('error', __('system.pages.custom_range_requires_dates'));
        }

        $query = Booking::with(['campany', 'schedule', 'user', 'route', 'vender', 'bus.route', 'campany.busOwnerAccount', 'governmentLeviesOnService']);
        $dateFilter = apply_booking_history_date_filter($query, $request);
        $period = $dateFilter['period'];
        $startDate = $dateFilter['startDate'];
        $endDate = $dateFilter['endDate'];

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $period = 'custom';
        }

        $this->applyHistoryChannelFilter($query, $request);
        $this->applyHistoryColumnFilters($query, $request);

        $bookings = $query->where('payment_status', 'Paid')->latest()->get();

        $totalPayment = $bookings->sum(fn ($b) => (float) ($b->customer_paid_total ?? 0));
        $totalDiscount = $bookings->sum('discount_amount');
        $totalVAT = $bookings->sum('vat');
        $totalGovLevy = $bookings->sum(fn ($b) => booking_total_government_levy($b));
        $grandTotal = $bookings->sum(fn ($b) => round((float) ($b->busFee ?? 0)));

        return view('system.history', compact('bookings', 'totalPayment', 'totalDiscount', 'totalVAT', 'totalGovLevy', 'grandTotal', 'period', 'startDate', 'endDate'))
            ->with('channelFilter', $request->get('channel'));
    }

    public function printManifestAll(Request $request)
    {
        $this->requireAccess(Access::LINKS['BOOKING_HISTORY']);

        $bookings = $this->buildHistoryBookingsQuery($request)->orderBy('seat')->get();

        if ($bookings->isEmpty()) {
            return redirect()->back()->with('error', __('vender/history.no_booking_data_manifest'));
        }

        $sections = [];
        foreach ($bookings->groupBy(fn ($booking) => optional($booking->bus)->bus_number ?? '') as $busNumber => $busBookings) {
            if ($busNumber === '') {
                continue;
            }

            $bus = bus::where('bus_number', $busNumber)->first();
            if (!$bus) {
                continue;
            }

            $data = $busBookings
                ->sortBy('seat')
                ->map(fn ($booking) => booking_to_report_row($booking))
                ->values()
                ->all();

            if (!empty($data)) {
                $sections[] = ['bus' => $bus, 'bookings' => $data];
            }
        }

        if (empty($sections)) {
            return redirect()->back()->with('error', __('vender/history.no_booking_data_manifest'));
        }

        $pdf = Pdf::loadView('print.manifest_all', compact('sections'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('manifest_all_' . now()->format('Ymd_His') . '.pdf');
    }

    private function buildHistoryBookingsQuery(Request $request)
    {
        $query = Booking::with(['campany', 'schedule', 'user', 'route', 'vender', 'bus.route', 'campany.busOwnerAccount', 'governmentLeviesOnService']);
        apply_booking_history_date_filter($query, $request);
        $this->applyHistoryChannelFilter($query, $request);
        $this->applyHistoryColumnFilters($query, $request);

        return $query->where('payment_status', 'Paid');
    }

    private function applyHistoryChannelFilter($query, Request $request): void
    {
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
    }

    /**
     * Filter booking history by bus name, plate number, departure date/time,
     * driver name and conductor name. Each is optional and applied only when
     * present so they compose with the date-range and channel filters.
     */
    private function applyHistoryColumnFilters($query, Request $request): void
    {
        apply_booking_history_column_filters($query, $request);
    }

    public function print(Request $request)
    {
        $this->requireAccess(Access::LINKS['BOOKING_HISTORY']);

        $data = $this->resolvePrintBookingRows($request);
        if ($data === null) {
            return redirect()->back()->with('error', __('system.messages.no_booking_income_data'));
        }

        return $this->generatePDF($data);
    }

    public function printService(Request $request)
    {
        $this->requireAccess(Access::LINKS['BOOKING_HISTORY']);

        $data = $this->resolvePrintBookingRows($request);
        if ($data === null) {
            return redirect()->back()->with('error', __('system.messages.no_booking_income_data'));
        }

        $pdf = Pdf::loadView('print.service_list', ['bookings' => $data]);
        return $pdf->download('service-fees-' . now()->format('Ymd_His') . '.pdf');
    }

    public function printCommission(Request $request)
    {
        $this->requireAccess(Access::LINKS['BOOKING_HISTORY']);

        $data = $this->resolvePrintBookingRows($request);
        if ($data === null) {
            return redirect()->back()->with('error', __('system.messages.no_booking_income_data'));
        }

        $pdf = Pdf::loadView('print.commission_list', ['bookings' => $data]);
        return $pdf->download('commission-' . now()->format('Ymd_His') . '.pdf');
    }

    private function resolvePrintBookingRows(Request $request): ?array
    {
        if ($request->filled('booking_ids')) {
            $bookings = $this->bookingsFromPrintRequest($request);
            if ($bookings->isEmpty()) {
                return null;
            }

            return $bookings
                ->map(fn ($booking) => booking_to_report_row($booking))
                ->values()
                ->all();
        }

        if (!$request->filled('data')) {
            return null;
        }

        $data = json_decode($request->data, true);
        if ($data === null || !is_array($data) || empty($data)) {
            return null;
        }

        return $data;
    }

    private function bookingsFromPrintRequest(Request $request)
    {
        $ids = is_array($request->booking_ids)
            ? $request->booking_ids
            : (array) json_decode($request->booking_ids, true);
        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return collect();
        }

        return Booking::with(['campany', 'schedule', 'user', 'route', 'vender', 'bus.route', 'campany.busOwnerAccount', 'governmentLeviesOnService'])
            ->whereIn('id', $ids)
            ->where('payment_status', 'Paid')
            ->orderBy('created_at', 'desc')
            ->get();
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

    public function printVendersPdf()
    {
        $this->requireAccess(Access::LINKS['VENDORS']);

        $venders = User::where('role', 'vender')
            ->with(['VenderBalances', 'VenderAccount'])
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('print.vender_list', compact('venders'));
        return $pdf->download('vendors_' . now()->format('Ymd_His') . '.pdf');
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
        $discounts = Discount::orderByDesc('id')->get();

        return view('system.discount', compact('discounts'));
    }


    public function add_coupon(Request $request)
    {
        $code = $request->code;
        $used = $request->used;

        if (empty($code) || empty($used)) {
            return back()->with('error', __('system.messages.fill_all_inputs'));
        }

        $appliesTicket = $request->boolean('applies_to_ticket');
        $appliesLuggage = $request->boolean('applies_to_luggage');
        $appliesParcel = $request->boolean('applies_to_parcel');
        $appliesSpecialHire = $request->boolean('applies_to_special_hire');

        // Default legacy behaviour: ticket-only when nothing selected.
        if (!$appliesTicket && !$appliesLuggage && !$appliesParcel && !$appliesSpecialHire) {
            $appliesTicket = true;
        }

        $data = Discount::create([
            'code' => $code,
            'used' => $used,
            'percentage' => $request->percentage,
            'applies_to_ticket' => $appliesTicket,
            'applies_to_luggage' => $appliesLuggage,
            'applies_to_parcel' => $appliesParcel,
            'applies_to_special_hire' => $appliesSpecialHire,
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

    public function bus_route(Request $request)
    {
        $this->requireAccess(Access::LINKS['BUS_SCHEDULE']);

        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');

        $scope = in_array($request->input('scope'), ['upcoming', 'past', 'all'], true)
            ? $request->input('scope')
            : 'upcoming';
        $sort = $request->input('sort') === 'desc' ? 'desc' : 'asc';
        $companyId = $request->filled('campany_id') ? (int) $request->input('campany_id') : null;
        $search = trim((string) $request->input('search'));
        $startDate = $this->normalizeDateInput($request->input('start_date'));
        $endDate = $this->normalizeDateInput($request->input('end_date'));

        $query = Schedule::with(['bus.campany', 'bus.route', 'route'])
            ->whereHas('bus');

        if ($scope === 'upcoming') {
            $query->where(function ($q) use ($today, $currentTime) {
                $q->where('schedule_date', '>', $today)
                    ->orWhere(function ($inner) use ($today, $currentTime) {
                        $inner->where('schedule_date', $today)->where('start', '>', $currentTime);
                    });
            });
        } elseif ($scope === 'past') {
            $query->where(function ($q) use ($today, $currentTime) {
                $q->where('schedule_date', '<', $today)
                    ->orWhere(function ($inner) use ($today, $currentTime) {
                        $inner->where('schedule_date', $today)->where('start', '<=', $currentTime);
                    });
            });
        }

        if ($companyId) {
            $query->whereHas('bus', function ($q) use ($companyId) {
                $q->where('campany_id', $companyId);
            });
        }

        if ($startDate) {
            $query->where('schedule_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('schedule_date', '<=', $endDate);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('from', 'like', "%{$search}%")
                    ->orWhere('to', 'like', "%{$search}%")
                    ->orWhereHas('bus', function ($bus) use ($search) {
                        $bus->where('bus_number', 'like', "%{$search}%")
                            ->orWhereHas('campany', function ($campany) use ($search) {
                                $campany->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $busCount = (clone $query)->distinct()->count('bus_id');
        $todayCount = (clone $query)->where('schedule_date', $today)->count();

        $schedules = $query->orderBy('schedule_date', $sort)
            ->orderBy('start', $sort)
            ->orderBy('id', $sort)
            ->paginate(20)
            ->withQueryString();

        $seatMaps = schedule_seat_maps($schedules->getCollection());
        foreach ($schedules as $schedule) {
            $schedule->booked_seat_map = $seatMaps[$schedule->id] ?? [];
        }

        $companies = Campany::orderBy('name')->get(['id', 'name', 'status']);

        return view('system.bus_route', [
            'schedules' => $schedules,
            'companies' => $companies,
            'scope' => $scope,
            'sort' => $sort,
            'companyId' => $companyId,
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'busCount' => $busCount,
            'todayCount' => $todayCount,
            'todayDate' => $today,
            'currentTime' => $currentTime,
        ]);
    }

    private function normalizeDateInput($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
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
                'parcel_commission_percentage' => 0,
                'excess_luggage_fee_per_kg' => 0,
                'parcel_fee_per_kg' => 0,
                'enable_customer_sms_notifications' => true,
                'enable_customer_email_notifications' => true,
                'enable_conductor_sms_notifications' => true,
                'enable_conductor_email_notifications' => true,
                'test_mode' => false,
                'enforce_2fa' => true,
                'enforce_customer_email_verification' => true,
            ]);
        }

        $mailConfig = [
            'host' => (string) config('mail.mailers.smtp.host', ''),
            'port' => (string) config('mail.mailers.smtp.port', ''),
            'encryption' => (string) config('mail.mailers.smtp.encryption', ''),
            'username' => (string) config('mail.mailers.smtp.username', ''),
            'from_address' => (string) config('mail.from.address', ''),
            'from_name' => (string) config('mail.from.name', ''),
        ];

        return view('system.setting', compact('settings', 'mailConfig'));
    }

    public function setting_update(Request $request)
    {
        $request->validate([
            'excess_luggage_fee_per_kg' => ['required', 'numeric', 'min:0'],
            'parcel_fee_per_kg' => ['required', 'numeric', 'min:0'],
            'sms_driver' => ['nullable', Rule::in(SmsManager::DRIVERS)],
            // Alphanumeric sender ids are capped at 11 characters by the networks.
            'sms_sender_id' => ['nullable', 'string', 'max:11'],
            'at_username' => ['nullable', 'string', 'max:100'],
            'at_api_key' => ['nullable', 'string', 'max:255'],
            'cotz_username' => ['nullable', 'string', 'max:100'],
            'cotz_password' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = Setting::first();

        if (!$settings) {
            $settings = Setting::create([
                'international' => 0,
                'local' => 0,
                'service' => 0,
                'service_percentage' => 0,
                'parcel_commission_percentage' => 0,
                'excess_luggage_fee_per_kg' => 0,
                'parcel_fee_per_kg' => 0,
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
            'parcel_commission_percentage' => $request->parcel_commission_percentage,
            'excess_luggage_fee_per_kg' => $request->excess_luggage_fee_per_kg,
            'parcel_fee_per_kg' => $request->parcel_fee_per_kg,
            'enable_customer_sms_notifications' => $request->boolean('enable_customer_sms_notifications'),
            'enable_customer_email_notifications' => $request->boolean('enable_customer_email_notifications'),
            'enable_conductor_sms_notifications' => $request->boolean('enable_conductor_sms_notifications'),
            'enable_conductor_email_notifications' => $request->boolean('enable_conductor_email_notifications'),
            'test_mode' => $request->boolean('test_mode'),
            'enforce_2fa' => $request->boolean('enforce_2fa'),
            'enforce_customer_email_verification' => $request->boolean('enforce_customer_email_verification'),
            'sms_driver' => in_array($request->input('sms_driver'), SmsManager::DRIVERS, true)
                ? $request->input('sms_driver')
                : 'smscotz',
            'sms_sender_id' => trim((string) $request->input('sms_sender_id', '')),
            'at_username' => trim((string) $request->input('at_username', '')),
            'at_sandbox' => $request->boolean('at_sandbox'),
            'cotz_username' => trim((string) $request->input('cotz_username', '')),
        ]);

        // Secrets are write-only in the form: a blank box means "keep what is
        // already stored", so we never round-trip them through the browser.
        $secrets = [];
        foreach (['at_api_key', 'cotz_password'] as $field) {
            $value = trim((string) $request->input($field, ''));
            if ($value !== '') {
                $secrets[$field] = $value;
            }
        }
        if ($secrets) {
            $settings->update($secrets);
        }

        SmsManager::flushConfig();

        return back()->with('success', __('system.messages.settings_updated'));
    }

    /**
     * Fire a one-off SMS through the currently configured gateway so the admin
     * can confirm the credentials before relying on them.
     */
    public function sms_test(Request $request)
    {
        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:20'],
            'test_message' => ['nullable', 'string', 'max:300'],
        ]);

        SmsManager::flushConfig();

        $manager = app(SmsManager::class);
        $config = $manager->config();
        $message = $data['test_message'] ?: __('system.settings.sms_test_default_message');

        $result = $manager->send($data['test_phone'], $message);

        if ($result->success) {
            return back()->with('success', __('system.messages.sms_test_sent', [
                'driver' => $config['driver'],
                'id' => $result->messageId ?: '-',
            ]));
        }

        return back()->withErrors([
            'test_phone' => __('system.messages.sms_test_failed', [
                'driver' => $config['driver'],
                'error' => $result->error ?: 'unknown error',
            ]),
        ])->withInput();
    }

    /**
     * Send a one-off email via the configured SMTP mailer so admins can verify
     * delivery (verification codes, booking notices, etc.).
     */
    public function email_test(Request $request)
    {
        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
            'test_email_subject' => ['nullable', 'string', 'max:200'],
            'test_email_message' => ['nullable', 'string', 'max:5000'],
        ]);

        $subject = trim((string) ($data['test_email_subject'] ?? ''))
            ?: __('system.settings.email_test_default_subject');
        $body = trim((string) ($data['test_email_message'] ?? ''))
            ?: __('system.settings.email_test_default_message');

        try {
            Mail::raw($body, function ($message) use ($data, $subject) {
                $message->to($data['test_email'])->subject($subject);
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'test_email' => __('system.messages.email_test_failed', [
                    'error' => $e->getMessage(),
                ]),
            ])->withInput();
        }

        return back()->with('success', __('system.messages.email_test_sent', [
            'email' => $data['test_email'],
        ]));
    }

    public function refunds()
    {
        $this->requireAccess(Access::LINKS['REFUNDS']);
        $refunds = Refund::orderByDesc('id')->get();

        return view('system.refunds', compact('refunds'));
    }

    public function refundsReportPdf()
    {
        $this->requireAccess(Access::LINKS['REFUNDS']);

        $payload = $this->buildRefundsExportPayload();
        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.refunds.no_refunds'));
        }

        $pdf = Pdf::loadView('print.refunds', $payload['pdfData']);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('refunds_' . now()->format('Ymd_His') . '.pdf');
    }

    public function refundsReportCsv()
    {
        $this->requireAccess(Access::LINKS['REFUNDS']);

        $payload = $this->buildRefundsExportPayload();
        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.refunds.no_refunds'));
        }

        return $this->streamSpecialHireCsv(
            'refunds_' . now()->format('Ymd_His') . '.csv',
            $payload['headers'],
            $payload['rows']
        );
    }

    private function buildRefundsExportPayload(): array
    {
        $refunds = Refund::orderByDesc('id')->get();
        $rows = $refunds->map(fn ($refund) => $this->mapRefundRow($refund));

        return [
            'headers' => array_keys($rows->first() ?? $this->emptyRefundRow()),
            'rows' => $rows,
            'pdfData' => [
                'title' => __('system.refunds.report_title'),
                'rows' => $rows->values()->all(),
                'totals' => [
                    'count' => $refunds->count(),
                    'pendingCount' => $refunds->where('status', 'Pending')->count(),
                    'approvedCount' => $refunds->where('status', 'Approved')->count(),
                    'rejectedCount' => $refunds->where('status', 'Rejected')->count(),
                    'pendingAmount' => (float) $refunds->where('status', 'Pending')->sum('amount'),
                    'approvedAmount' => (float) $refunds->where('status', 'Approved')->sum('amount'),
                    'totalAmount' => (float) $refunds->sum('amount'),
                ],
            ],
        ];
    }

    private function mapRefundRow(Refund $refund): array
    {
        return [
            'booking_code' => $refund->booking_code ?? '—',
            'amount' => number_format((float) $refund->amount, 2),
            'status' => $refund->status,
            'phone' => $refund->phone ?? '—',
            'fullname' => $refund->fullname ?? '—',
            'date' => optional($refund->created_at)->format('Y-m-d H:i') ?? '—',
        ];
    }

    private function emptyRefundRow(): array
    {
        return [
            'booking_code' => '',
            'amount' => '',
            'status' => '',
            'phone' => '',
            'fullname' => '',
            'date' => '',
        ];
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
