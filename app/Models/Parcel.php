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
        'weight',
        'height',
        'width',
        'length',
        'status',
        'bus_id',
        'vender_id',
        'sender_name',
        'sender_contact',
        'parcel_instructions',
        'receiver_name',
        'receiver_contact_1',
        'receiver_contact_2',
        'receiver_delivery_address',
        'tra_status',
        'tra_rct_num',
        'tra_z_num',
        'tra_vnum',
        'tra_qr_url',
        'tra_response',
        'tra_error',
    ];

    public function bus()
    {
        return $this->belongsTo(bus::class);
    }

    public function vender()
    {
        return $this->belongsTo(User::class, 'vender_id');
    }
}
