<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bookings weighed before weigh-in wrote the verified fee kept the declared
     * fee in bookings.excess_luggage_fee until refund approval / top-up.
     */
    public function up(): void
    {
        if (!Schema::hasTable('excess_luggage_escrow') || !Schema::hasTable('bookings')) {
            return;
        }

        $rows = DB::table('bookings')
            ->join('excess_luggage_escrow', 'excess_luggage_escrow.booking_id', '=', 'bookings.id')
            ->whereNotNull('bookings.luggage_weighed_at')
            ->where('excess_luggage_escrow.status', '!=', 'cancelled')
            ->where('excess_luggage_escrow.actual_fee', '>', 0)
            ->whereRaw('ABS(COALESCE(bookings.excess_luggage_fee, 0) - excess_luggage_escrow.actual_fee) >= 0.01')
            ->get([
                'bookings.id',
                'bookings.booking_code',
                'bookings.excess_luggage_fee',
                'excess_luggage_escrow.actual_fee',
            ]);

        foreach ($rows as $row) {
            DB::table('bookings')->where('id', $row->id)->update([
                'excess_luggage_fee' => round((float) $row->actual_fee, 2),
            ]);

            Log::info('Synced weighed luggage fee with escrow actual fee', [
                'booking_id' => $row->id,
                'booking_code' => $row->booking_code,
                'from' => (float) $row->excess_luggage_fee,
                'to' => (float) $row->actual_fee,
            ]);
        }
    }

    public function down(): void
    {
        // Data correction only.
    }
};
