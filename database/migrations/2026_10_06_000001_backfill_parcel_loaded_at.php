<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recover the "Loaded" timestamp for parcels that already moved to/past the
 * loaded stage but never stored `loaded_at`.
 *
 * Root cause: `loaded_at` was added by 2026_10_05_000001_add_parcel_movement_fields
 * but omitted from `Parcel::$fillable`, so the mass-assignment in
 * ParcelFlowService::markLoaded() silently dropped it.
 *
 * This is a best-effort backfill only — it never invents a time. It uses the
 * best available evidence and leaves the value null when there is none:
 *   1. `departed_at` — the legacy dispatch timestamp, when present.
 *   2. `sms_logs`    — the load/departure notification sent at that step.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('parcels', 'loaded_at')) {
            return;
        }

        $progressed = ['loaded', 'in_transit', 'received', 'arrived', 'completed'];

        $parcels = DB::table('parcels')
            ->whereNull('loaded_at')
            ->whereIn('status', $progressed)
            ->get(['id', 'parcel_number', 'departed_at']);

        $hasSmsLogs = Schema::hasTable('sms_logs');

        foreach ($parcels as $parcel) {
            $loadedAt = $parcel->departed_at;

            if (!$loadedAt && $hasSmsLogs && filled($parcel->parcel_number)) {
                $number = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $parcel->parcel_number);

                // The load notification text differs between the old flow
                // ("umeondoka" / departed) and the new one ("umepakiwa" / loaded).
                $loadedAt = DB::table('sms_logs')
                    ->where('message', 'like', '%' . $number . '%')
                    ->where(function ($query) {
                        $query->where('message', 'like', '%umepakiwa%')
                            ->orWhere('message', 'like', '%umeondoka%');
                    })
                    ->orderBy('created_at')
                    ->value('created_at');
            }

            if ($loadedAt) {
                DB::table('parcels')->where('id', $parcel->id)->update(['loaded_at' => $loadedAt]);
            }
        }
    }

    public function down(): void
    {
        // Data backfill only. The pre-backfill nulls cannot be restored safely,
        // and clearing all loaded_at would destroy genuine load timestamps.
    }
};
