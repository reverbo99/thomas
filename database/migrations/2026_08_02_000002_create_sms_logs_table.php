<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 32);
            $table->string('destination', 32);
            $table->text('message');
            // Gateway-side id. Needed to match delivery reports back to a row.
            $table->string('message_id', 191)->nullable()->index();
            // 'sent' | 'failed' locally, then overwritten by the gateway DLR
            // ('Success', 'Failed', 'Rejected', …) once it calls back.
            $table->string('status', 64)->nullable();
            $table->string('failure_reason', 191)->nullable();
            $table->decimal('cost', 10, 4)->nullable();
            $table->string('currency', 8)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
