<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'campany_id', // Updated from campany_id
        'user_id',
        'amount',
        'payment_method',
        'payment_number',
        'status',
        'vender_id',
        'created_at',
        'reference_number',
        'cancel_reason',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campany()
    {
        return $this->hasOne(Campany::class, 'id', 'campany_id');// Updated from campany_id
    }

    public function vender()
    {
        return $this->hasOne(VenderBalance::class, 'id', 'vender_id');
    }
}
