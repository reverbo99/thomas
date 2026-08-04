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
        'payment_status',
        'payment_method',
        'payment_ref',
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
        'sender_name',
        'sender_contact',
        'parcel_instructions',
        'receiver_name',
        'receiver_contact_1',
        'receiver_contact_2',
        'receiver_delivery_address',
        'settled_at',
        'departed_at',
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
        'departed_at' => 'datetime',
        'arrived_at' => 'datetime',
        'collected_at' => 'datetime',
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
