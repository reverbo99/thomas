<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend coupons so they can target ticket fare, excess luggage,
     * parcels, and/or special hire. Existing rows default to ticket-only.
     */
    public function up(): void
    {
        Schema::table('discount', function (Blueprint $table) {
            if (!Schema::hasColumn('discount', 'applies_to_ticket')) {
                $table->boolean('applies_to_ticket')->default(true)->after('expires_at');
            }
            if (!Schema::hasColumn('discount', 'applies_to_luggage')) {
                $table->boolean('applies_to_luggage')->default(false)->after('applies_to_ticket');
            }
            if (!Schema::hasColumn('discount', 'applies_to_parcel')) {
                $table->boolean('applies_to_parcel')->default(false)->after('applies_to_luggage');
            }
            if (!Schema::hasColumn('discount', 'applies_to_special_hire')) {
                $table->boolean('applies_to_special_hire')->default(false)->after('applies_to_parcel');
            }
        });

        Schema::table('parcels', function (Blueprint $table) {
            if (!Schema::hasColumn('parcels', 'discount_code')) {
                $table->string('discount_code', 64)->nullable()->after('amount_paid');
            }
            if (!Schema::hasColumn('parcels', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_code');
            }
            if (!Schema::hasColumn('parcels', 'amount_before_discount')) {
                $table->decimal('amount_before_discount', 12, 2)->nullable()->after('discount_amount');
            }
        });

        Schema::table('special_hire_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('special_hire_orders', 'discount_code')) {
                $table->string('discount_code', 64)->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('special_hire_orders', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_code');
            }
            if (!Schema::hasColumn('special_hire_orders', 'total_before_discount')) {
                $table->decimal('total_before_discount', 12, 2)->nullable()->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('discount', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('discount', 'applies_to_ticket') ? 'applies_to_ticket' : null,
                Schema::hasColumn('discount', 'applies_to_luggage') ? 'applies_to_luggage' : null,
                Schema::hasColumn('discount', 'applies_to_parcel') ? 'applies_to_parcel' : null,
                Schema::hasColumn('discount', 'applies_to_special_hire') ? 'applies_to_special_hire' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('parcels', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('parcels', 'discount_code') ? 'discount_code' : null,
                Schema::hasColumn('parcels', 'discount_amount') ? 'discount_amount' : null,
                Schema::hasColumn('parcels', 'amount_before_discount') ? 'amount_before_discount' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('special_hire_orders', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('special_hire_orders', 'discount_code') ? 'discount_code' : null,
                Schema::hasColumn('special_hire_orders', 'discount_amount') ? 'discount_amount' : null,
                Schema::hasColumn('special_hire_orders', 'total_before_discount') ? 'total_before_discount' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
