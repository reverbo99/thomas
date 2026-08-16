<?php

namespace App\Console\Commands;

use App\Services\TransactionalDataPurgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PurgeTransactionalDataCommand extends Command
{
    public const CONFIRM_PHRASE = 'PURGE TRANSACTIONAL DATA';

    /**
     * @var string
     */
    protected $signature = 'data:purge-transactional {--force : Skip interactive confirmation}';

    /**
     * @var string
     */
    protected $description = 'Delete all bookings, parcels, excess luggage, and payout transactions; reset wallet balances.';

    public function handle(TransactionalDataPurgeService $purgeService): int
    {
        if (! $this->option('force')) {
            $typed = $this->ask('This cannot be undone. Type "'.self::CONFIRM_PHRASE.'" to confirm');

            if ($typed !== self::CONFIRM_PHRASE) {
                $this->error('Confirmation phrase did not match. Aborted.');

                return self::FAILURE;
            }
        }

        $this->warn('Purging transactional data…');

        $summary = $purgeService->purge();

        $this->newLine();
        $this->info('Deleted rows:');

        $deleteRows = [];
        foreach ($summary['deleted'] as $table => $count) {
            $deleteRows[] = [$table, (string) $count];
        }
        $this->table(['Table', 'Rows deleted'], $deleteRows);

        $this->newLine();
        $this->info('Reset rows:');

        $resetRows = [];
        foreach ($summary['reset'] as $target => $count) {
            $resetRows[] = [$target, (string) $count];
        }
        $this->table(['Target', 'Rows updated'], $resetRows);

        Log::warning('Transactional data purged', [
            'user_id' => Auth::id(),
            'summary' => $summary,
        ]);

        $this->newLine();
        $this->info('Transactional data purge completed successfully.');

        return self::SUCCESS;
    }
}
