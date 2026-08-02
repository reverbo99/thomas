<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    
    protected $fillable =
        [
            'international',
            'local',
            'insurance_company',
            'insurance_policy_local',
            'insurance_policy_foreign',
            'service',
            'service_percentage',
            'parcel_commission_percentage',
            'excess_luggage_fee_per_kg',
            'parcel_fee_per_kg',
            'enable_customer_sms_notifications',
            'enable_customer_email_notifications',
            'enable_conductor_sms_notifications',
            'enable_conductor_email_notifications',
            'test_mode',
            'enforce_2fa',
            'enforce_customer_email_verification',
            'sms_driver',
            'sms_sender_id',
            'at_username',
            'at_api_key',
            'at_sandbox',
            'cotz_username',
            'cotz_password',
        ];

    protected $casts = [
        'at_sandbox' => 'boolean',
        // Gateway secrets are stored encrypted at rest; they are only ever
        // decrypted inside the SMS drivers, never rendered back to the form.
        'at_api_key' => 'encrypted',
        'cotz_password' => 'encrypted',
    ];

    protected $hidden = [
        'at_api_key',
        'cotz_password',
    ];

    public static function requiresCustomerEmailVerification(): bool
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('settings')
            || !\Illuminate\Support\Facades\Schema::hasColumn('settings', 'enforce_customer_email_verification')) {
            return false;
        }

        return (bool) (static::query()->value('enforce_customer_email_verification') ?? false);
    }
}