<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\TraVfdService;
use Illuminate\Console\Command;

class FiscalizePendingTraBookings extends Command
{
    protected $signature = 'tra:fiscalize-pending {--limit=50 : Max bookings to process}';

    protected $description = 'Fiscalize paid bookings with pending TRA status (uses test mock when test_mode is on)';

    public function handle(TraVfdService $tra): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $bookings = Booking::query()
            ->where('payment_status', 'Paid')
            ->where(function ($query) {
                $query->whereNull('tra_status')
                    ->orWhere('tra_status', 'pending')
                    ->orWhere('tra_status', 'failed');
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No pending TRA bookings found.');
            return self::SUCCESS;
        }

        $success = 0;
        foreach ($bookings as $booking) {
            $ok = $tra->fiscalize($booking->refresh());
            $booking->refresh();
            $this->line(sprintf(
                'Booking %s (#%d): %s — tra_status=%s tra_vnum=%s',
                $booking->booking_code,
                $booking->id,
                $ok ? 'OK' : 'FAIL',
                $booking->tra_status ?? 'n/a',
                $booking->tra_vnum ?? 'n/a'
            ));
            if ($ok && $booking->tra_status === 'success') {
                $success++;
            }
        }

        $this->info("Fiscalized {$success}/{$bookings->count()} bookings.");

        return self::SUCCESS;
    }
}
