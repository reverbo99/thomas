<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Which gateway actually sends: 'smscotz' (legacy) or 'africastalking'.
            $table->string('sms_driver', 32)->default('smscotz');
            // Shared across drivers — the alphanumeric sender id / shortcode.
            $table->string('sms_sender_id', 32)->nullable();

            // Africa's Talking
            $table->string('at_username', 100)->nullable();
            $table->text('at_api_key')->nullable();
            $table->boolean('at_sandbox')->default(true);

            // sms.co.tz (legacy gateway, kept switchable)
            $table->string('cotz_username', 100)->nullable();
            $table->text('cotz_password')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'sms_driver',
                'sms_sender_id',
                'at_username',
                'at_api_key',
                'at_sandbox',
                'cotz_username',
                'cotz_password',
            ]);
        });
    }
};
