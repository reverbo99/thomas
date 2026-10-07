<?php

namespace App\Console\Commands;

use App\Models\Parcel;
use App\Services\ParcelFlowService;
use Illuminate\Console\Command;

/**
 * Repair path for parcels that were cancelled before commission reversal
 * existed. Any parcel that is cancelled, settled and fully paid but has no
 * commission_reversed_at marker still has its wallets overstated; this command
 * finds them and (optionally) reverses the credits.
 *
 * Idempotent: reversal is driven by the commission_reversed_at marker, so a
 * second --apply run finds nothing to do.
 */
class ReconcileCancelledParcelCommissions extends Command
{
    /**
     * @var string
     */
    protected $signature = 'parcels:reconcile-cancelled
                            {--apply : Actually apply the reversal (default is a dry run)}';

    /**
     * @var string
     */
    protected $description = 'Find cancelled settled parcels whose wallet credits were never reversed and reverse them.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $parcels = Parcel::query()
            ->where('status', ParcelFlowService::STATUS_CANCELLED)
            ->where('payment_status', ParcelFlowService::PAY_PAID)
            ->whereNotNull('settled_at')
            ->whereNull('commission_reversed_at')
            ->orderBy('id')
            ->get();

        $headers = ['ID', 'Parcel', 'Amount Paid', 'Admin', 'Owner', 'Vendor', 'Government Levy'];
        $rows = [];
        $totals = [
            'admin' => 0.0,
            'owner' => 0.0,
            'vendor' => 0.0,
            'government' => 0.0,
        ];

        foreach ($parcels as $parcel) {
            $split = ParcelFlowService::splitForParcel($parcel);
            $admin = (float) $split['admin'];
            $owner = (float) $split['owner'];
            $vendor = (float) $split['vendor'];
            $government = (float) $split['government'];

            $totals['admin'] += $admin;
            $totals['owner'] += $owner;
            $totals['vendor'] += $vendor;
            $totals['government'] += $government;

            $rows[] = [
                $parcel->id,
                $parcel->parcel_number,
                number_format((float) $parcel->amount_paid, 2, '.', ''),
                number_format($admin, 2, '.', ''),
                number_format($owner, 2, '.', ''),
                number_format($vendor, 2, '.', ''),
                number_format($government, 2, '.', ''),
            ];
        }

        $this->table($headers, $rows);
        $this->line(sprintf(
            'Totals to reverse: owner=%s admin=%s vendor=%s government=%s',
            number_format($totals['owner'], 2, '.', ''),
            number_format($totals['admin'], 2, '.', ''),
            number_format($totals['vendor'], 2, '.', ''),
            number_format($totals['government'], 2, '.', '')
        ));

        if ($parcels->isEmpty()) {
            $this->info('No cancelled settled parcels need commission reversal.');

            return self::SUCCESS;
        }

        if (!$apply) {
            $this->warn('Dry run: no changes were written. Re-run with --apply to reverse these commissions.');

            return self::SUCCESS;
        }

        $flow = app(ParcelFlowService::class);
        $reversed = 0;
        $reversedTotals = [
            'admin' => 0.0,
            'owner' => 0.0,
            'vendor' => 0.0,
            'government' => 0.0,
        ];

        foreach ($parcels as $parcel) {
            $result = $flow->reverseSettledParcelIfNeeded($parcel);
            if (!$result['reversed']) {
                continue;
            }

            $reversed++;
            $reversedTotals['admin'] += $result['admin'];
            $reversedTotals['owner'] += $result['owner'];
            $reversedTotals['vendor'] += $result['vendor'];
            $reversedTotals['government'] += $result['government'];
        }

        $this->info(sprintf(
            'Reversed %d parcel(s): owner=%s admin=%s vendor=%s government=%s',
            $reversed,
            number_format($reversedTotals['owner'], 2, '.', ''),
            number_format($reversedTotals['admin'], 2, '.', ''),
            number_format($reversedTotals['vendor'], 2, '.', ''),
            number_format($reversedTotals['government'], 2, '.', '')
        ));

        return self::SUCCESS;
    }
}
