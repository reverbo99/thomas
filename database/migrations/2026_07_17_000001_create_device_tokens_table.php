<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512);
            $table->string('platform', 20)->default('android');
            $table->string('app', 40)->nullable(); // e.g. bushire_driver / bushire_customer
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique('token');
            $table->index(['user_id', 'app']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
