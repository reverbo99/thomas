<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_hire_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('coaster_id')->constrained('coasters')->cascadeOnDelete();
            $table->json('payload');
            $table->decimal('amount', 12, 2);
            $table->string('phone', 30);
            $table->string('clickpesa_ref', 64)->nullable()->index();
            $table->string('status', 20)->default('pending')->index(); // pending|paid|consumed|expired
            $table->foreignId('special_hire_order_id')->nullable()->constrained('special_hire_orders')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_hire_payment_intents');
    }
};
