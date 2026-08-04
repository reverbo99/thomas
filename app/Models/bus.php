<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bus extends Model
{
    //use HasFactory;

    protected $table = "buses";
    protected $fillable = [
        'campany_id',
        'bus_number',
        'bus_type',
        'total_seats',
        'conductor',
        'bus_features',
        'driver_name',
        'driver_contact',
        'driver_name_2',
        'driver_contact_2',
        'conductor_name',
        'customer_service_name_1',
        'customer_service_contact_1',
        'customer_service_name_2',
        'customer_service_contact_2',
        'customer_service_name_3',
        'customer_service_contact_3',
        'customer_service_name_4',
        'customer_service_contact_4',
        'bus_model',
        'seate_json',
        'accept_parcels',
        'max_parcel_weight_kg',
    ];

    protected $casts = [
        'seate_json' => 'string',
    ];

    public function campany()
    {
        return $this->belongsTo(Campany::class);
    }

    public function route()
    {
        return $this->hasOne(route::class, 'bus_id', 'id');
    }

    public function routes()
    {
        return $this->hasMany(route::class, 'bus_id', 'id');
    }

    public function rout()
    {
        return $this->belongsTo(Route::class);
    }


    public function busname()
    {
        return $this->hasOne(Campany::class, 'id', 'campany_id');
    }

    public function booking()
    {
        return $this->hasMany(Booking::class, 'bus_id', 'id');
    }

    public function point()
    {
        return $this->hasMany(Point::class);
    }

    public function stend()
    {
        return $this->hasOne(Station::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }
}
