<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\SpecialHireApiController;
use App\Http\Controllers\Api\DriverApiController;
use App\Http\Controllers\Api\CustomerApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Public routes (no auth required)
    Route::match(['get', 'post'], '/login', [AuthApiController::class, 'login']);
    Route::match(['get', 'post'], '/register', [AuthApiController::class, 'register']);

    // Protected routes (auth required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthApiController::class, 'user']);
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::post('/logout-all', [AuthApiController::class, 'logoutAll']);
        Route::post('/refresh', [AuthApiController::class, 'refresh']);
    });
});

/*
|--------------------------------------------------------------------------
| Special Hire API Routes - ADMIN (special_hire role)
|--------------------------------------------------------------------------
| These routes are for special hire business owners/admins to manage
| their fleet, orders, pricing, and view analytics.
|--------------------------------------------------------------------------
*/
Route::prefix('special-hire/admin')
    ->middleware(['auth:sanctum', 'api.role:special_hire'])
    ->group(function () {
        // Dashboard & Analytics
        Route::get('/dashboard', [SpecialHireApiController::class, 'dashboard']);
        Route::get('/dashboard/earnings', [SpecialHireApiController::class, 'earnings']);

        // Coasters CRUD (Full Management)
        Route::get('/coasters', [SpecialHireApiController::class, 'indexCoasters']);
        Route::get('/coasters/{id}', [SpecialHireApiController::class, 'showCoaster']);
        Route::post('/coasters', [SpecialHireApiController::class, 'storeCoaster']);
        Route::put('/coasters/{id}', [SpecialHireApiController::class, 'updateCoaster']);
        Route::delete('/coasters/{id}', [SpecialHireApiController::class, 'destroyCoaster']);

        // Location Tracking
        Route::get('/coasters/locations/all', [SpecialHireApiController::class, 'allLocations']);
        Route::get('/coasters/{id}/location', [SpecialHireApiController::class, 'getLocation']);
        Route::put('/coasters/{id}/location', [SpecialHireApiController::class, 'updateLocation']);

        // Orders Management (Full Control)
        Route::get('/orders', [SpecialHireApiController::class, 'indexOrders']);
        Route::get('/orders/{id}', [SpecialHireApiController::class, 'showOrder']);
        Route::post('/orders', [SpecialHireApiController::class, 'storeOrder']);
        Route::put('/orders/{id}', [SpecialHireApiController::class, 'updateOrder']);
        Route::delete('/orders/{id}', [SpecialHireApiController::class, 'destroyOrder']);

        // Pricing Management
        Route::get('/pricing/{coasterId}', [SpecialHireApiController::class, 'getPricing']);
        Route::put('/pricing/{coasterId}', [SpecialHireApiController::class, 'updatePricing']);

        // Price Calculation (for admin reference)
        Route::post('/calculate-price', [SpecialHireApiController::class, 'calculatePrice']);

        // Driver Management
        Route::post('/drivers', [SpecialHireApiController::class, 'createDriver']);
        Route::get('/drivers', [SpecialHireApiController::class, 'getDrivers']);
        Route::put('/drivers/{driverId}', [SpecialHireApiController::class, 'updateDriver']);
        Route::post('/coasters/{coasterId}/assign-driver', [SpecialHireApiController::class, 'assignDriver']);
        Route::post('/coasters/{coasterId}/unassign-driver', [SpecialHireApiController::class, 'unassignDriver']);
        Route::post('/orders/{id}/accept-hire', [SpecialHireApiController::class, 'acceptHireBooking']);
        Route::delete('/drivers/{driverId}', [SpecialHireApiController::class, 'deleteDriver']);
        Route::post('/drivers/{driverId}/disable', [SpecialHireApiController::class, 'disableDriver']);
        Route::post('/drivers/{driverId}/enable', [SpecialHireApiController::class, 'enableDriver']);
    });

/*
|--------------------------------------------------------------------------
| Special Hire API Routes - CUSTOMER (customer role)
|--------------------------------------------------------------------------
| These routes are for customers to browse available coasters,
| calculate prices, make bookings, and view their orders.
|--------------------------------------------------------------------------
*/
Route::prefix('special-hire/customer')
    ->middleware(['auth:sanctum', 'api.role:customer'])
    ->group(function () {
        // Browse Available Coasters (Read-only)
        Route::get('/coasters', [SpecialHireApiController::class, 'customerIndexCoasters']);
        Route::get('/coasters/{id}', [SpecialHireApiController::class, 'customerShowCoaster']);

        // Price Calculation
        Route::post('/calculate-price', [SpecialHireApiController::class, 'customerCalculatePrice']);

        // Customer Orders (own orders only)
        Route::get('/orders', [SpecialHireApiController::class, 'customerIndexOrders']);
        Route::get('/orders/{id}', [SpecialHireApiController::class, 'customerShowOrder']);
        Route::post('/orders', [SpecialHireApiController::class, 'customerStoreOrder']);
        Route::post('/orders/{id}/cancel', [SpecialHireApiController::class, 'customerCancelOrder']);

        // Track Order Location
        Route::get('/orders/{id}/track', [SpecialHireApiController::class, 'customerTrackOrder']);
    });

/*
|--------------------------------------------------------------------------
| Special Hire API Routes - PUBLIC (no auth required)
|--------------------------------------------------------------------------
| These routes are publicly accessible for browsing available coasters
| and getting price estimates before login.
|--------------------------------------------------------------------------
*/
Route::prefix('special-hire/public')->group(function () {
    // Browse Available Coasters (Public)
    Route::get('/coasters', [SpecialHireApiController::class, 'publicIndexCoasters']);
    Route::get('/coasters/{id}', [SpecialHireApiController::class, 'publicShowCoaster']);

    // Price Estimation (Public)
    Route::post('/estimate-price', [SpecialHireApiController::class, 'publicEstimatePrice']);
});

/*
|--------------------------------------------------------------------------
| Special Hire API Routes - DRIVER (driver role)
|--------------------------------------------------------------------------
| These routes are for coaster drivers to manage their trips,
| view orders, update location, and track their schedule.
| Driver accounts are created by coaster admins.
|--------------------------------------------------------------------------
*/
Route::prefix('special-hire/driver')->group(function () {
    // Driver Login (no auth required)
    Route::post('/login', [DriverApiController::class, 'login']);

    // Protected Driver Routes
    Route::middleware(['auth:sanctum', 'api.role:driver'])->group(function () {
        // Profile
        Route::get('/profile', [DriverApiController::class, 'profile']);
        Route::put('/profile', [DriverApiController::class, 'updateProfile']);

        // Coaster
        Route::get('/coaster', [DriverApiController::class, 'getCoaster']);

        // Orders
        Route::get('/orders', [DriverApiController::class, 'getOrders']);
        Route::get('/orders/{id}', [DriverApiController::class, 'getOrder']);
        Route::put('/orders/{id}/status', [DriverApiController::class, 'updateOrderStatus']);

        // Special hire: driver accepts/declines (replaces operator web "Accept")
        Route::get('/hire-requests', [DriverApiController::class, 'hirePendingBookings']);
        Route::post('/hire-requests/{id}/accept', [DriverApiController::class, 'acceptHireRequest']);
        Route::post('/hire-requests/{id}/decline', [DriverApiController::class, 'declineHireRequest']);

        // History & Schedule
        Route::get('/history', [DriverApiController::class, 'getHistory']);
        Route::get('/schedule', [DriverApiController::class, 'getSchedule']);

        // Location Tracking
        Route::post('/location', [DriverApiController::class, 'updateLocation']);

        // Push notifications (FCM device token registration)
        Route::post('/device-token', [DriverApiController::class, 'registerDeviceToken']);
        Route::delete('/device-token', [DriverApiController::class, 'deleteDeviceToken']);

        // Logout
        Route::post('/logout', [DriverApiController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Special Hire API Routes - CUSTOMER (customer role)
|--------------------------------------------------------------------------
| These routes are for customers to register, browse coasters,
| make bookings, and track their orders.
|--------------------------------------------------------------------------
*/
Route::prefix('special-hire/customer')->group(function () {
    // Customer Registration & Login (no auth required)
    Route::post('/register', [CustomerApiController::class, 'register']);
    Route::post('/login', [CustomerApiController::class, 'login']);

    // Protected Customer Routes
    Route::middleware(['auth:sanctum', 'api.role:customer'])->group(function () {
        // Profile
        Route::get('/profile', [CustomerApiController::class, 'profile']);
        Route::put('/profile', [CustomerApiController::class, 'updateProfile']);

        // Browse Coasters (with availability)
        Route::get('/coasters', [CustomerApiController::class, 'getCoasters']);
        Route::get('/coasters/{id}', [CustomerApiController::class, 'getCoaster'])
            ->whereNumber('id');

        // Price Calculation
        Route::post('/calculate-price', [CustomerApiController::class, 'calculatePrice']);

        // Pay first (no hire until ClickPesa succeeds), then sync creates the order.
        // Register BEFORE /bookings/{id} so "prepare-payment" is never captured as {id}.
        Route::post('/bookings/prepare-payment', [CustomerApiController::class, 'preparePayment']);
        Route::post('/bookings/payment-intents/{intentId}/sync', [CustomerApiController::class, 'syncIntentPayment'])
            ->whereNumber('intentId');

        // Bookings ({id} is numeric only — avoids 405 when a static path is missing on an older deploy)
        Route::post('/bookings', [CustomerApiController::class, 'createBooking']);
        Route::get('/bookings', [CustomerApiController::class, 'getBookings']);
        Route::get('/bookings/{id}', [CustomerApiController::class, 'getBooking'])
            ->whereNumber('id');
        Route::post('/bookings/{id}/cancel', [CustomerApiController::class, 'cancelBooking'])
            ->whereNumber('id');
        Route::get('/bookings/{id}/track', [CustomerApiController::class, 'trackBooking'])
            ->whereNumber('id');
        Route::post('/bookings/{id}/pay-deposit', [CustomerApiController::class, 'specialHirePayDeposit'])
            ->whereNumber('id');
        Route::post('/bookings/{id}/pay-balance', [CustomerApiController::class, 'specialHirePayBalance'])
            ->whereNumber('id');
        Route::post('/bookings/{id}/passengers', [CustomerApiController::class, 'specialHirePassengers'])
            ->whereNumber('id');
        Route::post('/bookings/{id}/sync-payment', [CustomerApiController::class, 'specialHireSyncPayment'])
            ->whereNumber('id');

        // Logout
        Route::post('/logout', [CustomerApiController::class, 'logout']);
    });
});
