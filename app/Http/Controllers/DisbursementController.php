<?php

namespace App\Http\Controllers;

use App\Models\Access;
use App\Models\Disbursement;
use App\Models\DisbursementItem;
use App\Models\Setting;
use App\Services\DisbursementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DisbursementController extends Controller
{
    public function __construct(private DisbursementService $service)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $selectedDate = $this->selectedDate($request);

        try {
            // Make sure today's tab exists as soon as the page is opened.
            if ($selectedDate->isToday()) {
                $this->service->ensureTodayBatch();
            }

            $dates = Disbursement::query()
                ->orderByDesc('batch_date')
                ->limit(60)
                ->get();

            $batch = Disbursement::query()
                ->whereDate('batch_date', $selectedDate->toDateString())
                ->with(['items' => fn ($q) => $q->orderBy('id')])
                ->first();

            if (! $batch && $selectedDate->isToday()) {
                $batch = $this->service->ensureTodayBatch();
                $batch->load('items');
            }

            if ($batch) {
                $this->service->refreshBatch($batch);
                $batch = $batch->fresh(['items']);
            }

            $items = $batch
                ? $batch->items()->orderBy('id')->paginate(50)->appends(['date' => $selectedDate->toDateString()])
                : null;

            return view('system.disbursements', [
                'dates' => $dates,
                'batch' => $batch,
                'items' => $items,
                'selectedDate' => $selectedDate,
                'gatewayConfigured' => app(\App\Services\ClickPesaPayoutService::class)->isConfigured(),
                'dryRun' => $this->service->effectiveDryRun(),
                'enabled' => $this->service->isEnabled(),
                'live' => $this->service->isLive(),
                'runTime' => $this->service->runTime(),
                'lastRunAt' => $this->service->lastRunAt(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('system.disbursements', [
                'dates' => collect(),
                'batch' => null,
                'items' => null,
                'selectedDate' => $selectedDate,
                'gatewayConfigured' => false,
                'dryRun' => true,
                'enabled' => false,
                'live' => false,
                'runTime' => '00:00',
                'lastRunAt' => null,
                'setupError' => $e->getMessage(),
            ]);
        }
    }

    public function collectNow(Request $request)
    {
        $this->authorizeAccess();

        $date = $this->selectedDate($request);

        try {
            $batch = $this->service->collectForDate($date, true);
            $message = __('system.disbursements.messages.collected', [
                'date' => $batch->batch_date?->format('d M Y'),
                'count' => $batch->total_items,
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('system.disbursements', ['date' => $date->toDateString()])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('system.disbursements', ['date' => $date->toDateString()])
            ->with('success', $message);
    }

    public function runNow(Request $request)
    {
        $this->authorizeAccess();

        try {
            $result = $this->service->process(true, 3);
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        if ($result['processed'] === 0) {
            return back()->with('error', __('system.disbursements.messages.nothing_to_process'));
        }

        return back()->with('success', __('system.disbursements.messages.processed', [
            'count' => $result['processed'],
        ]));
    }

    public function syncNow()
    {
        $this->authorizeAccess();

        $synced = $this->service->syncProcessingItems(50);

        return back()->with('success', __('system.disbursements.messages.synced', ['count' => $synced]));
    }

    public function retry(DisbursementItem $item)
    {
        $this->authorizeAccess();
        $this->service->retryItem($item);

        return back()->with('success', __('system.disbursements.messages.retried'));
    }

    public function skip(Request $request, DisbursementItem $item)
    {
        $this->authorizeAccess();

        $reason = trim((string) $request->input('reason', ''));
        $this->service->skipItem($item, $reason);

        return back()->with('success', __('system.disbursements.messages.skipped'));
    }

    public function markPaid(Request $request, DisbursementItem $item)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'manual_reference' => 'nullable|string|max:100',
        ]);

        $this->service->markPaidManually($item, $data['manual_reference'] ?? null, Auth::user());

        return back()->with('success', __('system.disbursements.messages.marked_paid'));
    }

    public function updateItem(Request $request, DisbursementItem $item)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'beneficiary_name' => 'nullable|string|max:150',
            'payout_channel' => 'required|in:mobile_money,bank',
            'payout_number' => 'required|string|max:100',
            'bank_name' => 'nullable|string|max:150',
            'bank_bic' => 'nullable|string|max:30',
        ]);

        $this->service->updateItemDetails($item, $data);

        return back()->with('success', __('system.disbursements.messages.details_saved'));
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'disbursement_run_time' => 'required|date_format:H:i',
            'disbursement_enabled' => 'nullable|boolean',
            'disbursement_live' => 'nullable|boolean',
        ]);

        if (! Schema::hasTable('settings')) {
            return back()->with('error', __('system.disbursements.messages.settings_unavailable'));
        }

        try {
            $settings = Setting::query()->first() ?: new Setting();
            $settings->disbursement_enabled = $request->boolean('disbursement_enabled');
            $settings->disbursement_run_time = $data['disbursement_run_time'];
            $settings->disbursement_live = $request->boolean('disbursement_live');
            $settings->save();
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('system.disbursements.messages.settings_saved'));
    }

    public function exportCsv(Request $request)
    {
        $this->authorizeAccess();

        $date = $this->selectedDate($request);
        $batch = Disbursement::query()
            ->whereDate('batch_date', $date->toDateString())
            ->with(['items' => fn ($q) => $q->orderBy('id')])
            ->first();

        $filename = 'disbursements_' . $date->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($batch) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'batch_date',
                'reference',
                'source',
                'booking_reference',
                'beneficiary',
                'channel',
                'payout_number',
                'bank_name',
                'bank_bic',
                'amount',
                'currency',
                'status',
                'gateway_status',
                'attempts',
                'last_error',
            ]);

            foreach ($batch?->items ?? [] as $item) {
                fputcsv($handle, [
                    $batch?->batch_date?->toDateString(),
                    $item->reference,
                    $item->sourceLabel(),
                    $item->booking_code ?: $item->source_reference,
                    $item->beneficiary_name,
                    $item->channelLabel(),
                    $item->payout_number,
                    $item->bank_name,
                    $item->bank_bic,
                    number_format((float) $item->amount, 2, '.', ''),
                    $item->currency,
                    $item->status,
                    $item->gateway_status,
                    $item->attempts,
                    $item->last_error,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function selectedDate(Request $request): Carbon
    {
        $date = (string) $request->input('date', $request->query('date', ''));

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Carbon::parse($date)->startOfDay();
        }

        return Carbon::today();
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();

        abort_unless($user && $user->isActive(), 403);

        $allowed = $user->hasAccess(Access::LINKS['DISBURSEMENTS'])
            || $user->hasAccess(Access::LINKS['REFUNDS'])
            || $user->hasAccess(Access::LINKS['PAYMENT_REQUEST'])
            || $user->hasAccess(Access::LINKS['SPECIAL_HIRE']);

        abort_unless($allowed, 403);
    }
}
