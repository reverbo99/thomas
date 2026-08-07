<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    //use HasFactory;

    protected $table = "bookings";

    protected $casts = [
        'passengers' => 'array',
        'luggage_weighed_at' => 'datetime',
        'luggage_assigned_at' => 'datetime',
        'luggage_retrieved_at' => 'datetime',
    ];

    protected $fillable = [
        'booking_code',
        'campany_id',
        'bus_id',
        'route_id',
        'pickup_point',
        'dropping_point',
        'travel_date',
        'seat',
        'amount',
        'customer_paid_total',
        'payment_method',
        'booking_channel',
        'payment_status',
        'resaved_until',
        'trans_status',
        'transaction_ref_id',
        'external_ref_id',
        'gender',
        'age',
        'infant_child',
        'age_group',
        'has_excess_luggage',
        'excess_luggage_fee',
        'excess_luggage_description',
        'estimated_weight',
        'actual_weight',
        'actual_length',
        'actual_height',
        'actual_width',
        'luggage_refund_amount',
        'luggage_weight_verdict',
        'luggage_status',
        'luggage_payment_ref',
        'luggage_payment_status',
        'luggage_weighed_at',
        'luggage_weighed_by',
        'luggage_assigned_at',
        'luggage_assigned_by',
        'luggage_retrieved_at',
        'luggage_retrieved_by',
        'mfs_id',
        'verification_code',
        ////////
        'customer_phone',
        'customer_name',
        'passengers',
        'customer_email',
        'user_id',
        'bima',
        'bima_amount',
        'insuranceDate',
        'vender_id',
        'fee',
        'service',
        'fee_vat',
        'service_vat',
        //////
        'vender_fee',
        'vender_service',
        'schedule_id',
        'vat',
        'government_levy',
        'system_service_fee',
        'discount',
        'discount_amount',
        'distance',
        'busFee',
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

    public function route()
    {
        return $this->belongsTo(route::class);
    }
 
    public function route_name()
    {
        return $this->hasOne(route::class, 'id', 'route_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function campany()
    {
        return $this->hasOne(Campany::class, 'id', 'campany_id');
    }

    public function vender()
    {
        return $this->hasOne(User::class, 'id', 'vender_id');
    }

    public function discounta()
    {
        return $this->hasOne(Discount::class, 'code', 'discount');
    }

    /**
     * Government levy on service fees (same rows as Admin → System Income → Government Levy from Service).
     * {@see \App\Models\GovernmentLevy} stores booking_id as the booking_code string.
     */
    public function governmentLeviesOnService(): HasMany
    {
        return $this->hasMany(GovernmentLevy::class, 'booking_id', 'booking_code');
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (empty($booking->booking_channel)) {
                $booking->booking_channel = sales_channel_for_booking(
                    $booking->vender_id ? (int) $booking->vender_id : null,
                    $booking->payment_method
                );
            }
        });

        static::saving(function (Booking $booking) {
            if (
                empty($booking->luggage_status)
                && (
                    (int) ($booking->has_excess_luggage ?? 0) === 1
                    || (float) ($booking->excess_luggage_fee ?? 0) > 0
                )
            ) {
                $booking->luggage_status = \App\Services\ExcessLuggageService::STATUS_DECLARED;
            }
        });
    }

    public function getSalesChannelAttribute(): string
    {
        if (!empty($this->attributes['booking_channel'])) {
            return $this->attributes['booking_channel'];
        }

        return sales_channel_for_booking(
            $this->vender_id ? (int) $this->vender_id : null,
            $this->payment_method
        );
    }
}
