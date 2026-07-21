<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->after('parcel_type');
            $table->string('sender_contact')->nullable()->after('sender_name');
            $table->string('parcel_instructions')->nullable()->after('sender_contact');

            $table->string('receiver_name')->nullable()->after('parcel_instructions');
            $table->string('receiver_contact_1')->nullable()->after('receiver_name');
            $table->string('receiver_contact_2')->nullable()->after('receiver_contact_1');
            $table->string('receiver_delivery_address')->nullable()->after('receiver_contact_2');

            $table->decimal('length', 8, 2)->nullable()->after('width');

            $table->string('tra_status')->nullable()->default('pending');
            $table->string('tra_rct_num')->nullable();
            $table->string('tra_z_num')->nullable();
            $table->string('tra_vnum')->nullable();
            $table->text('tra_qr_url')->nullable();
            $table->text('tra_response')->nullable();
            $table->text('tra_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name', 'sender_contact', 'parcel_instructions',
                'receiver_name', 'receiver_contact_1', 'receiver_contact_2', 'receiver_delivery_address',
                'length',
                'tra_status', 'tra_rct_num', 'tra_z_num', 'tra_vnum', 'tra_qr_url', 'tra_response', 'tra_error',
            ]);
        });
    }
};
