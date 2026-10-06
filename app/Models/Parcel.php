<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parcel extends Model
{
    protected $fillable = [
        'parcel_number',
        'parcel_type',
        'description',
        'amount_paid',
        'admin_share',
        'vendor_share',
        'government_levy',
        'owner_share',
        'discount_code',
        'discount_amount',
        'amount_before_discount',
        'payment_status',
        'payment_method',
        'payment_ref',
        'payment_mode',
        'paid_amount',
        'balance_due',
        'deposit_paid_at',
        'balance_paid_at',
        'weight',
        'height',
        'width',
        'length',
        'status',
        'bus_id',
        'vender_id',
        'created_by',
        'receiving_user_id',
        'receiving_agent_name',
        'receiving_agent_phone',
        'delivery_rider_name',
        'delivery_rider_phone',
        'conductor_name',
        'conductor_phone',
        'collector_name',
        'collector_phone',
        'collector_signature',
        'collected_by_user_id',
        'loaded_by_user_id',
        'sender_name',
        'sender_contact',
        'parcel_instructions',
        'receiver_name',
        'receiver_contact_1',
        'receiver_contact_2',
        'receiver_delivery_address',
        'settled_at',
        'received_at',
        'departed_at',
        'loaded_at',
        'arrived_at',
        'collected_at',
        'tra_status',
        'tra_rct_num',
        'tra_z_num',
        'tra_vnum',
        'tra_qr_url',
        'tra_response',
        'tra_error',
    ];

    protected $casts = [
        'settled_at' => 'datetime',
        'received_at' => 'datetime',
        'loaded_at' => 'datetime',
        'departed_at' => 'datetime',
        'arrived_at' => 'datetime',
        'collected_at' => 'datetime',
        'deposit_paid_at' => 'datetime',
        'balance_paid_at' => 'datetime',
    ];

    public function bus()
    {
        return $this->belongsTo(bus::class);
    }

    public function vender()
    {
        return $this->belongsTo(User::class, 'vender_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivingUser()
    {
        return $this->belongsTo(User::class, 'receiving_user_id');
    }
}
