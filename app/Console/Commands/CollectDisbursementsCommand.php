<?php

namespace App\Console\Commands;

use App\Services\DisbursementService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CollectDisbursementsCommand extends Command
{
    protected $signature = 'disbursements:collect
                            {date? : Batch date (YYYY-MM-DD), defaults to today}
                            {--force : Re-collect a batch that was already collected}';

    protected $description = 'Create the daily disbursement batch and collect every outbound refund/payout that is owed.';

    public function handle(DisbursementService $service): int
    {
        $date = $this->argument('date');

        try {
            $day = $date ? Carbon::parse($date) : Carbon::today();
        } catch (\Throwable $e) {
            $this->error('Invalid date: ' . $date);

            return self::FAILURE;
        }

        $batch = $service->collectForDate($day, (bool) $this->option('force'));

        $this->info(sprintf(
            'Disbursement batch %s (%s): %d item(s), %s %s total.',
            $batch->reference,
            $batch->batch_date?->toDateString(),
            $batch->total_items,
            $batch->currency,
            number_format((float) $batch->total_amount, 2)
        ));

        return self::SUCCESS;
    }
}
