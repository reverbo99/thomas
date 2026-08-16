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

    public static function busLinkLabels(): array
    {
        return [
            self::BUS['DASHBOARD'] => __('vendor_sidebar.dashboard'),
            self::BUS['BUSES'] => __('vendor_sidebar.my_buses'),
            self::BUS['ROUTES'] => __('vendor_sidebar.manage_routes'),
            self::BUS['SCHEDULES'] => __('vendor_sidebar.schedule'),
            self::BUS['CITIES'] => __('vendor_sidebar.cities'),
            self::BUS['BOOKING_HISTORY'] => __('vendor_sidebar.booking_history'),
            self::BUS['EXCESS_LUGGAGE'] => __('vendor_sidebar.excess_luggage'),
            self::BUS['RESAVED_TICKETS'] => __('vendor_sidebar.resaved_tickets'),
            self::BUS['EARNINGS_PAYMENTS'] => __('local_bus_owners.earnings_payments'),
            self::BUS['LOCAL_BUS_OWNERS'] => __('vendor_sidebar.local_bus_owners'),
            self::BUS['OWNER_PERMISSIONS_VIEW'] => __('local_bus_owners.owner_permissions_view'),
            self::BUS['OWNER_PERMISSIONS_EDIT'] => __('local_bus_owners.owner_permissions_edit'),
            self::BUS['PROFILE'] => __('vendor_sidebar.profile'),
            self::BUS['LOGOUT'] => __('vendor_sidebar.logout'),
        ];
    }

    public static function labelForLink(string $link): string
    {
        return self::busLinkLabels()[$link] ?? str_replace(['.', '-'], ' ', $link);
    }
}
