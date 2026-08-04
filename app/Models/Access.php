<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    //use HasFactory;

    protected $table = 'access';

    protected $fillable = [
        'user_id',
        'link',
        'status',
    ];

    public const LINKS = [
        'BUS_OPERATORS' => 'bus-operators',
        'BUS_SCHEDULE' => 'bus-schedule',
        'BUSES' => 'buses',
        'CITIES' => 'cities',
        'VENDORS' => 'vendors',
        'DISCOUNTS' => 'discounts',
        'INSURANCE' => 'insurance',
        'BOOKING_HISTORY' => 'booking-history',
        'SYSTEM_INCOME' => 'system-income',
        'PAYMENT_REQUEST' => 'payment-request',
        'LOCAL_ADMINS' => 'local-admins',
        'REFUNDS' => 'refunds',
        'CARDS' => 'cards',
        'SPECIAL_HIRE' => 'special-hire',
    ];

    public const BUS = [
        'DASHBOARD' => 'index',
        'BUSES' => 'buses',
        'ROUTES' => 'routes',
        'SCHEDULES' => 'schedules',
        'CITIES' => 'cities',
        'BOOKING_HISTORY' => 'history',
        'EXCESS_LUGGAGE' => 'bus_owner.excess_luggage.index',
        'RESAVED_TICKETS' => 'resaved.tickets',
        'EARNINGS_PAYMENTS' => 'erning',
        'LOCAL_BUS_OWNERS' => 'local.bus.owners',
        'OWNER_PERMISSIONS_VIEW' => 'owner.permissions.view',
        'OWNER_PERMISSIONS_EDIT' => 'owner.permissions.edit',
        'PROFILE' => 'profile',
        'LOGOUT' => 'logout',
    ];
}
