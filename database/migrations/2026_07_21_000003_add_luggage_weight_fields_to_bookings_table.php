<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'estimated_weight')) {
                // Declared by the customer/vendor at booking time.
                $table->decimal('estimated_weight', 8, 2)->nullable()->after('excess_luggage_description');
            }
            if (!Schema::hasColumn('bookings', 'actual_weight')) {
                // Measured by bus-owner staff when the excess luggage is weighed in.
                $table->decimal('actual_weight', 8, 2)->nullable()->after('estimated_weight');
            }
            if (!Schema::hasColumn('bookings', 'luggage_refund_amount')) {
                // Manual adjustment staff can record after weighing: positive = extra
                // amount collected, negative = refund given. The excess luggage fee
                // itself stays flat — this does not feed back into any fee formula.
                $table->decimal('luggage_refund_amount', 10, 2)->nullable()->after('actual_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['estimated_weight', 'actual_weight', 'luggage_refund_amount']);
        });
    }
};
