<?php

namespace App\Jobs;

use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued single-message send.
 *
 * Note the job itself never retries: SmsManager already swallows failures and
 * records them in sms_logs, and a retry would double-deliver a message the
 * gateway may well have accepted.
 */
class SmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(
        public string $destination,
        public string $message,
        public ?string $driver = null,
    ) {
    }

    public function handle(SmsManager $manager): void
    {
        $manager->send($this->destination, $this->message, $this->driver);
    }
}
