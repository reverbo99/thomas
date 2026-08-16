<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('excess_luggage_escrow')) {
            Schema::create('excess_luggage_escrow', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('booking_id')->unique();
                $table->string('booking_code', 64)->index();
                $table->decimal('estimated_weight', 10, 2)->nullable();
                $table->decimal('estimated_fee', 14, 2)->default(0);
                $table->decimal('held_amount', 14, 2)->default(0);
                $table->decimal('actual_weight', 10, 2)->nullable();
                $table->decimal('actual_fee', 14, 2)->nullable();
                $table->decimal('delta_amount', 14, 2)->nullable();
                $table->string('weight_verdict', 32)->nullable();
                $table->decimal('released_fee', 14, 2)->nullable();
                $table->decimal('surplus_amount', 14, 2)->default(0);
                $table->decimal('admin_share', 14, 2)->default(0);
                $table->decimal('government_share', 14, 2)->default(0);
                $table->decimal('owner_share', 14, 2)->default(0);
                $table->string('status', 32)->default('held')->index();
                $table->decimal('refund_amount', 14, 2)->nullable();
                $table->timestamp('weighed_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->timestamp('refund_requested_at')->nullable();
                $table->timestamp('refund_approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('excess_luggage_escrow_transactions')) {
            Schema::create('excess_luggage_escrow_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('escrow_id')->index();
                $table->unsignedBigInteger('booking_id')->index();
                $table->string('type', 32)->index();
                $table->decimal('amount', 14, 2);
                $table->string('reference', 100)->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('excess_luggage_escrow_transactions');
        Schema::dropIfExists('excess_luggage_escrow');
    }
};
