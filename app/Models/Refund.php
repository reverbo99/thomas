<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $table = "refund";
    protected $fillable = [
        'booking_code',
        'booking_id',
        'amount',
        'status',
        'phone',
        'fullname',
        'payout_method',
        'bank_name',
        'bank_bic',
        'disbursed_at',
        'disbursement_item_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'disbursed_at' => 'datetime',
    ];
}
