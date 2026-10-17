<?php

namespace App\Console\Commands;

use App\Services\DisbursementService;
use Illuminate\Console\Command;

class ProcessDisbursementsCommand extends Command
{
    protected $signature = 'disbursements:process
                            {--force : Ignore the configured run time and enabled flag}
                            {--limit= : Maximum payout creates in this run (ClickPesa allows 1 per 60s)}';

    protected $description = 'Send the next due disbursement payout(s) and sync in-flight ClickPesa payouts.';

    public function handle(DisbursementService $service): int
    {
        try {
            $limit = $this->option('limit');
            $result = $service->process(
                (bool) $this->option('force'),
                $limit !== null ? (int) $limit : null
            );

            if ($result['reason'] === 'locked') {
                $this->warn('Another disbursement run is already in progress.');

                return self::SUCCESS;
            }

            if ($result['processed'] > 0) {
                $service->markRun();
            }

            $this->info(sprintf(
                'Disbursements: %d paid/queued (batches: %d, reason: %s).',
                $result['processed'],
                $result['batches'],
                $result['reason'] ?: 'ok'
            ));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Disbursement process failed: ' . $e->getMessage());

            report($e);

            return self::FAILURE;
        }
    }
}
