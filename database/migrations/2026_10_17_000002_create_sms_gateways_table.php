<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named FastHub SMS gateway configurations.
 *
 * The admin can keep more than one set of API credentials (for example a
 * production and a backup account) and pick one of them as the default in
 * Settings > SMS Gateway.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id();
            // Friendly label shown in the default-gateway dropdown.
            $table->string('name', 100);
            // FastHub API credentials (client_secret is encrypted by the model).
            $table->string('client_id', 191);
            $table->text('client_secret');
            // Where FastHub posts delivery statuses for this configuration.
            $table->string('status_callback_url', 500)->nullable();
            // Optional per-gateway override of the FastHub send endpoint.
            $table->string('api_endpoint', 500)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_gateways');
    }
};
