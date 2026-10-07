<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marker for whether the wallet credits created at parcel settlement have been
 * reversed (on cancellation). Idempotency for reversal is driven by this column
 * rather than the parcel status, so a cancelled parcel can be repaired safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('parcels', 'commission_reversed_at')) {
            return;
        }

        $after = Schema::hasColumn('parcels', 'settled_at') ? 'settled_at' : null;

        Schema::table('parcels', function (Blueprint $table) use ($after) {
            $column = $table->timestamp('commission_reversed_at')->nullable();
            if ($after !== null) {
                $column->after($after);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('parcels', 'commission_reversed_at')) {
            Schema::table('parcels', function (Blueprint $table) {
                $table->dropColumn('commission_reversed_at');
            });
        }
    }
};
