<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArtisanCommandsController;
use App\Http\Controllers\AirtelPaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BimaController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CancelController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OnePageBookingController;
use App\Http\Controllers\OtherController;
use App\Http\Controllers\PDOController;
use App\Http\Controllers\ClickPesaController; 
use App\Http\Controllers\QRcodeScannerController;
use App\Http\Controllers\RebookController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ResaveController;
use App\Http\Controllers\RoundTripController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SelcomController;
use App\Http\Controllers\status\Vender;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TwoFactorAuthController; // Add this line
use App\Http\Controllers\VenderController;
use App\Http\Controllers\VenderWalletController;
use App\Http\Controllers\ExcessLuggageController;
use App\Http\Controllers\SpecialHireController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\TigosecureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;



Route::get('/qr-scanner', [QRcodeScannerController::class, 'index'])->name('qr.scanner');
Route::post('/qr-scan', [QRcodeScannerController::class, 'scan'])->name('qr.scan');

Route::get('/seed', function () {
    abort_unless(app()->environment(['local', 'development']), 403, 'This route is only available in local environment.');

    Artisan::call('db:seed', [
        '--class' => \Database\Seeders\AdminUserSeeder::class,
        '--force' => true,
    ]);

    return response()->json([
        'message' => 'Admin user seeded successfully.',
        'email' => 'admin@gmail.com',
        'password' => 'Admin@12345',
    ]);
});


// Authentication Routes

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('qrcode', [TestController::class, 'qrcode'])->name('qrcode');
Route::get('/qrcode/download', [TestController::class, 'download'])->name('qrcode.download');
Route::post('/print/ticket', [BookingController::class, 'print_ticket'])->name('ticket.print');
Route::get('password/reset', [AuthController::class, 'showResetForm'])->name('password.request');
Route::post('password/email', [AuthController::class, 'email'])->name('password.email');
Route::post('password/reset', [AuthController::class, 'phone'])->name('password.phone');
Route::get('reset/otp', [AuthController::class, 'showOtpForm'])->name('reset.otp');
Route::post('reset/otp', [AuthController::class, 'verifyOtp'])->name('reset.otp.verify');
Route::post('reset/form', [AuthController::class, 'showResetFormWithId'])->name('password.reset');

// Email Verification Routes
Route::get('email/verification', [AuthController::class, 'showEmailVerificationForm'])->name('email.verification.show');
Route::post('email/verification', [AuthController::class, 'verifyEmail'])->name('email.verification.verify');
Route::post('email/verification/resend', [AuthController::class, 'resendVerificationCode'])->name('email.verification.resend');

// Booking Verification Routes
Route::get('booking/verification', [BookingController::class, 'showBookingVerificationForm'])->name('booking.verification.show');
Route::post('booking/verification', [BookingController::class, 'verifyBookingEmail'])->name('booking.verification.verify');
Route::post('booking/verification/resend', [BookingController::class, 'resendBookingVerificationCode'])->name('booking.verification.resend');
Route::get('/edit/{id}', [BookingController::class, 'edit'])->name(name: 'booking.edit');
Route::post('/edit', [BookingController::class, 'update'])->name('booking.update');
/////////////////////new//////////////////////////




// Prevent redirecting back to POST-only URLs (e.g. /by_route_search).
$resolveSafeBackUrl = function (Request $request, array $fallbackMap = []) {
    $previousUrl = url()->previous();
    if (!$previousUrl || $previousUrl === url()->current()) {
        return route('home');
    }

    $host = parse_url($previousUrl, PHP_URL_HOST);
    if ($host && $host !== $request->getHost()) {
        return route('home');
    }

    $path = (string) (parse_url($previousUrl, PHP_URL_PATH) ?? '');
    if (isset($fallbackMap[$path])) {
        return $fallbackMap[$path];
    }

    $query = parse_url($previousUrl, PHP_URL_QUERY);
    $uri = $path !== '' ? $path : '/';
    if ($query) {
        $uri .= '?' . $query;
    }

    try {
        Route::getRoutes()->match(Request::create($uri, 'GET'));
        return $previousUrl;
    } catch (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        return $fallbackMap[$path] ?? route('home');
    }
};

Route::get('/set-locale', function (Request $request) use ($resolveSafeBackUrl) {
    $locale = $request->input('lang', config('app.locale'));
    if (in_array($locale, ['en', 'sw'])) {
        session(['locale' => $locale]);
    }
    $safeBackUrl = $resolveSafeBackUrl($request, [
        '/by_route_search' => route('by_route'),
        '/booking/get_form' => route('seates'),
        '/booking/seates' => route('seates'),
        '/booking/payment/data' => route('pay'),
        '/round-trip/booking_form' => route('round.trip.seats'),
        '/round-trip/seats' => route('round.trip.seats'),
        '/round-trip/payment/pay' => route('round.trip.checkout'),
        '/round-trip/get_payment' => route('round.trip.checkout'),
        '/customer/mybooking/search/form' => route('customer.by_route'),
        '/vender/route/by_search' => route('vender.route'),
    ]);

    return redirect($safeBackUrl);
})->name('set.locale');

Route::get('/currency/{currency}', function (Request $request, $currency) use ($resolveSafeBackUrl) {
    $safeBackUrl = $resolveSafeBackUrl($request, [
        '/by_route_search' => route('by_route'),
        '/booking/get_form' => route('seates'),
        '/booking/seates' => route('seates'),
        '/booking/payment/data' => route('pay'),
        '/round-trip/booking_form' => route('round.trip.seats'),
        '/round-trip/seats' => route('round.trip.seats'),
        '/round-trip/payment/pay' => route('round.trip.checkout'),
        '/round-trip/get_payment' => route('round.trip.checkout'),
        '/customer/mybooking/search/form' => route('customer.by_route'),
        '/vender/route/by_search' => route('vender.route'),
    ]);

    // Validate currency value
    if (!in_array($currency, ['Tsh', 'Usd', 'TSH', 'USD'])) {
        return redirect($safeBackUrl)->with('error', 'Invalid currency selected');
    }
    
    // Normalize currency value
    $normalizedCurrency = ucfirst(strtolower($currency));
    if ($normalizedCurrency === 'Tsh') {
        $normalizedCurrency = 'Tsh';
    } elseif ($normalizedCurrency === 'Usd') {
        $normalizedCurrency = 'Usd';
    }
    
    Session::put('currency', $normalizedCurrency);
    
    return redirect($safeBackUrl);
})->name('set.currency');




Route::get('/currency', [CurrencyController::class, 'convert'])->name('currency');
Route::get('/campany/id', [RouteController::class, 'bus_name'])->name('busname');

/////////////////////////////////////////////////////


///Route::post('/dpo/initiate', [PDOController::class, 'initiatePayment'])->name('dpo.initiate');
// Existing PDO callback routes
Route::get('/dpo/callback', [PDOController::class, 'handleCallback'])->name('dpo.callback');
Route::get('/dpo/cancel', [PDOController::class, 'handleCallback'])->name('dpo.cancel');

// ClickPesa callback routes
Route::get('/clickpesa/callback', [ClickPesaController::class, 'handleCallback'])->name('clickpesa.callback');
Route::get('/clickpesa/cancel', [ClickPesaController::class, 'handleCallback'])->name('clickpesa.cancel');
Route::get('/clickpesa/check-status', [ClickPesaController::class, 'checkPaymentStatus'])->name('clickpesa.check-status');
Route::get('/clickpesa/retry', [ClickPesaController::class, 'retryPayment'])->name('clickpesa.retry');

// Test Payment Routes (for test mode - bypasses real payment gateways)
use App\Http\Controllers\TestPaymentController;
// GET + POST: controllers redirect here after creating the booking in session (redirect = GET).
Route::match(['get', 'post'], '/test-payment/process', [TestPaymentController::class, 'processPayment'])->name('test.payment.process');
Route::get('/test-payment/status', [TestPaymentController::class, 'status'])->name('test.payment.status');

// New Tigosecure Callback Route (outside auth middleware if it's a public callback)
//Route::get('/tigosecure/callback', [VenderWalletController::class, 'handleTigosecureCallback'])->name('tigo.callback');

//Route::get('/tigosecure/redirect/{transactionRefId}', function ($transactionRefId) {
//    return redirect()->route('tigo.callback', ['transactionRefId' => $transactionRefId]);
//})->name('tigo.redirect');

// New PDO Callback Route for VenderWalletController (outside auth middleware if it's a public callback)
Route::any('/vender/dpo/callback', [VenderWalletController::class, 'handlePdoCallback'])->name('vender.dpo.callback');
Route::any('/vender/dpo/cancel', [VenderWalletController::class, 'handlePdoCallback'])->name('vender.dpo.cancel');


use App\Http\Controllers\Webhooks\AfricasTalkingWebhookController;
// Africa's Talking delivery reports. Public by design — register this URL in
// the AT dashboard (SMS > SMS Callback URLs > Delivery Reports).
Route::post('/webhooks/africastalking/dlr', [AfricasTalkingWebhookController::class, 'deliveryReport'])
    ->name('webhooks.africastalking.dlr');

Route::view('/dpo/example', 'dpo.example')->name('dpo.example');
// General Routes (Accessible to all authenticated users)
Route::get('/', function () {
    // Check if user is authenticated and requires 2FA
    if (Auth::check()) {
        $user = Auth::user();
        $requires2FA = \App\Http\Middleware\EnsureTwoFactorEnabled::requiresTwoFactor($user);
        
        if ($requires2FA && is_null($user->two_factor_confirmed_at)) {
            return redirect()->route('two-factor.login')
                ->with('error', 'Please complete Two-Factor Authentication to continue.');
        }
    }
    
    $todaySchedules = \App\Models\Schedule::whereDate('schedule_date', \Carbon\Carbon::today())
        ->with(['bus.campany', 'bus.busname', 'route'])
        ->whereHas('bus', function ($q) {
            $q->whereHas('busname', function ($b) {
                $b->where('status', 1);
            });
        })
        ->orderBy('start')
        ->limit(4)
        ->get();

    $popularRoutes = \App\Models\route::query()
        ->select('from', 'to')
        ->selectRaw('MIN(id) as id, MIN(price) as min_price')
        ->groupBy('from', 'to')
        ->orderByDesc(\Illuminate\Support\Facades\DB::raw('COUNT(*)'))
        ->limit(12)
        ->get();

    return view('welcome', compact('todaySchedules', 'popularRoutes'));
})->name('home');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/session-timeout', [AuthController::class, 'handleSessionTimeout'])->name('session.timeout');

Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');

Route::get('/routes', function () {
    return view('routes');
})->name('routes');

Route::get('/schedules/today', function () {
    $todaySchedules = \App\Models\Schedule::whereDate('schedule_date', \Carbon\Carbon::today())
        ->with(['bus.campany', 'bus.busname', 'route'])
        ->whereHas('bus', function ($q) {
            $q->whereHas('busname', function ($b) {
                $b->where('status', 1);
            });
        })
        ->orderBy('start')
        ->get();

    return view('schedules_today', compact('todaySchedules'));
})->name('schedules.today');

// Traveler Routes (Accessible to travelers)
Route::post('/booking/cancel', [CancelController::class, 'cancel'])->name('cancel');
Route::post('/booking_info', [BookingController::class, 'booking_info'])->name('booking_info');
Route::get('/booking_info', [BookingController::class, 'form'])->name('info');
Route::get('/booking/choose', [BookingController::class, 'choose'])->name('choose');
Route::get('/booking', [BookingController::class, 'booking'])->name('booking');
Route::post('/booking', [BookingController::class, 'search'])->name('search');
Route::get('/booking_form/{id}/{from}/{to}', [BookingController::class, 'booking_form'])->name('booking_form');
Route::get('/booking/inline/{id}/{from}/{to}', [BookingController::class, 'inlineBookingForm'])->name('booking.inline.form');
Route::post('/booking/inline/prepare', [BookingController::class, 'inlinePreparePayment'])->name('booking.inline.prepare');
Route::get('/booking/inline/wallet', [BookingController::class, 'inlineWalletLookup'])->name('booking.inline.wallet');
Route::get('/booking/seates', [BookingController::class, 'seates'])->name('seates');
Route::post('/booking/get_form', [BookingController::class, 'get_form'])->name('store');
Route::post('/booking/seates', [BookingController::class, 'get_seats'])->name('get_seats');
Route::get('/booking/payment', [BookingController::class, 'payment'])->name('pay');
Route::post('/booking/payment', [BookingController::class, 'payment_info'])->name('payment_store');
Route::post('/booking/payment/data', [BookingController::class, 'get_payment'])->name('verify');
Route::match(['get', 'post'], '/tigo/redirect/{transactionRefId}', [BookingController::class, 'handleRedirect'])->name('tigo.redirect');
Route::match(['get', 'post'], '/tigo/callback', [BookingController::class, 'handleCallback'])->name('tigo.callback');
Route::get('/payment/success', [BookingController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/failed', [BookingController::class, 'paymentFailed'])->name('payment.failed');
Route::get('/booking-status/{bookingId}', [RedirectController::class, 'showBookingStatus'])->name('booking.status');
Route::get('/by_route', [BookingController::class, 'by_route'])->name('by_route');
Route::post('/by_route_search', [BookingController::class, 'by_route_search'])->name('by_route_search');

Route::post('/airtel/auth', [AirtelPaymentController::class, 'getAuthToken']);
Route::post('/airtel/payment', [AirtelPaymentController::class, 'initiatePayment'])->name('airtel.payment');
Route::post('/airtel/booking-payment', [AirtelPaymentController::class, 'initiateBookingPayment'])->name('airtel.booking.payment');
Route::post('/airtel/webhook', [AirtelPaymentController::class, 'paymentCallback'])->name('airtel.webhook');
Route::view('airtel', 'airtel');

Route::view('/booking/policy', 'policy.booking')->name('policy.booking');
Route::view('/ticket_purchase', 'policy.ticket_purchase')->name('ticket.purchase');
Route::view('/terms', 'policy.terms')->name('terms');

// Test Route (Assuming accessible to all roles for now)
Route::get('/test', [TestController::class, 'test']);
Route::get('/test2', [TestController::class, 'test2']);
Route::get('/tigo/test-token', [TigosecureController::class, 'testToken'])->name('tigo.test-token');


Route::post('print/recipt', [AdminController::class, 'print_recipt'])->name('print.recipt');
Route::post('print/recipt2', [AdminController::class, 'print_recipt2'])->name('print.recipt2');
Route::post('print/recipt3', [AdminController::class, 'print_service'])->name('print.service');

Route::get('cancel-booking', [CancelController::class, 'index'])->name('cancel.booking');


Route::post('/refund', [RefundController::class, 'get_booking'])->name('customer.refund');

Route::post('/resaved-tickets/mix/', [ResaveController::class, 'byMix'])->name('resaved.mix');
Route::post('/resaved-tickets/pdo/', [ResaveController::class, 'byPdo'])->name('resaved.pdo');
Route::post('/resaved-tickets/clickpesa/', [ResaveController::class, 'byClickPesa'])->name('resaved.clickpesa');
Route::post('/resaved-tickets/cash/', [ResaveController::class, 'byCash'])->name('resaved.cash')->middleware('auth');
Route::post('/resaved-tickets/test-pay', [ResaveController::class, 'testPayResaved'])->name('resaved.test.pay');
Route::get('/pay-resaved/{id}', [BookingController::class, 'payResavedTicket'])->name('guest.pay.resaved');




// Protected Routes
Route::middleware('auth')->group(function () {
    Route::prefix('user/two-factor-authentication')->group(function () {
        Route::get('/', [TwoFactorAuthController::class, 'showTwoFactorSetup'])->name('two-factor.setup');
        Route::get('/verify', [TwoFactorAuthController::class, 'showTwoFactorSetupTwo'])->name('two-factor.login');
        Route::post('/enable', [TwoFactorAuthController::class, 'enableTwoFactorAuthentication'])->name('two-factor.enable');
        Route::post('/confirm', [TwoFactorAuthController::class, 'confirmTwoFactorAuthentication'])->name('two-factor.confirm');
        Route::post('/disable', [TwoFactorAuthController::class, 'disableTwoFactorAuthentication'])->name('two-factor.disable');
        Route::post('/recovery-codes', [TwoFactorAuthController::class, 'generateRecoveryCodes'])->name('two-factor.recovery-codes');

        // NEW: challenge during login
        Route::get('/challenge', [TwoFactorAuthController::class, 'challenge'])->name('two-factor.challenge');
        Route::post('/verify', [TwoFactorAuthController::class, 'verify'])->name('two-factor.verify');
    });
    // MFA Management Routes
    // Route::prefix('user/two-factor-authentication')->group(function () {
    //     Route::get('/', [TwoFactorAuthController::class, 'showTwoFactorSetup'])->name('two-factor.setup');
    //     Route::post('/enable', [TwoFactorAuthController::class, 'enableTwoFactorAuthentication'])->name('two-factor.enable');
    //     Route::post('/confirm', [TwoFactorAuthController::class, 'confirmTwoFactorAuthentication'])->name('two-factor.confirm');
    //     Route::post('/disable', [TwoFactorAuthController::class, 'disableTwoFactorAuthentication'])->name('two-factor.disable');
    //     Route::post('/recovery-codes', [TwoFactorAuthController::class, 'generateRecoveryCodes'])->name('two-factor.recovery-codes');
    // });

    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Bus Company Routes (Accessible only to bus_company role)

    Route::prefix('bus-company')->middleware(['role:bus_campany,local_bus_owner', '2fa'])->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/buses', [AdminController::class, 'buses'])->name('buses');
        Route::get('/add_bus', [AdminController::class, 'add_bus'])->name('add_bus');
        Route::post('/add_bus', [AdminController::class, 'get_bus'])->name('add_bus.store');
        Route::post('/update/bus', [AdminController::class, 'update_bus'])->name('update.bus');
        Route::post('/bus/delete', [AdminController::class, 'delete_bus'])->name('bus.delete');
        Route::post('profile/update', [AdminController::class, 'update_profile'])->name('profile.update');
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');

        Route::get('/routes', [AdminController::class, 'route_page'])->name('routes');
        Route::get('/route', [AdminController::class, 'route'])->name('route');
        Route::post('/route', [AdminController::class, 'get_route'])->name('route.store');
        Route::get('/route/edit/{id}', [AdminController::class, 'edit_route'])->name('edit.route');
        Route::post('/route/update', [AdminController::class, 'update'])->name('update.route');
        Route::post('/route/delete', [AdminController::class, 'delete_route'])->name('route.delete');

        Route::get('/history', [AdminController::class, 'history'])->name('history');
        Route::get('/history/search', [AdminController::class, 'search'])->name('history.search');
        Route::get('/history/{id}', [AdminController::class, 'show'])->name('history.show');
        //Route::get('/earning', [AdminController::class, 'erning'])->name('erning');
        Route::get('/report', [AdminController::class, 'report'])->name('report');
        Route::get('/schedules', [AdminController::class, 'schedules'])->name('schedules');
        Route::get('/schedules/add', [AdminController::class, 'add_schedule'])->name('add_schedule');
        Route::post('/schedules', [AdminController::class, 'store_schedule'])->name('store_schedule');
        Route::post('/delete/schedule', [AdminController::class, 'delete_schedule'])->name('delete.schedule');
        Route::post('/transaction_request', [AdminController::class, 'transaction_request'])->name('transaction.request');

        Route::post('/print', [AdminController::class, 'print'])->name('admin.print');
        Route::post('/manifest', [AdminController::class, 'manifest'])->name('admin.print.manifest');

        Route::post('/earnings/filter', [AdminController::class, 'filterEarnings'])->name('earnings.filter');
        Route::get('/earnings', [AdminController::class, 'erning'])->name('erning');

        Route::post('/export', [AdminController::class, 'export'])->name('export');
        Route::get('bus/edit/{id}', [AdminController::class, 'edit_bus'])->name('edit.bus');

        Route::get('/cities', [AdminController::class, 'cities'])->name('cities');
        Route::post('/cities', [AdminController::class, 'store_city'])->name('city.store');

        Route::get('/get_bus', [AdminController::class, 'get_bus_id'])->name('get.bus');
        Route::get('/schedules/unbooked', [AdminController::class, 'getUnbookedSchedules'])->name('schedules.unbooked');

        // Local Bus Owner Routes
        Route::get('/local-bus-owners', [AdminController::class, 'localBusOwners'])->name('local.bus.owners');
        Route::post('/local-bus-owners/create', [AdminController::class, 'createLocalBusOwner'])->name('local.bus.owners.create');
        Route::put('/local-bus-owners/{id}', [AdminController::class, 'updateLocalBusOwner'])->name('local.bus.owners.update');
        Route::delete('/local-bus-owners/{id}', [AdminController::class, 'deleteLocalBusOwner'])->name('local.bus.owners.destroy');

        // Local Bus Owner Permissions Routes
        Route::get('/owner-permissions/view', [AdminController::class, 'viewOwnerPermissions'])->name('owner.permissions.view');
        Route::get('/owner-permissions/edit', [AdminController::class, 'editOwnerPermissions'])->name('owner.permissions.edit');

        Route::get('schedule/{id}/edit', [ScheduleController::class, 'edit'])->name('edit.schedule');
        Route::post('/update-schedule/{id}', [ScheduleController::class, 'update'])->name('update_schedule');

        Route::get('/schedule/cancel/{id}', [CancelController::class, 'cancel_schedule'])->name('cancel.schedule');
        Route::get('/bus/local_admin/{id}', [OtherController::class, 'local_bus_owners'])->name('local_admin.bus');
        Route::post('/update-role', [OtherController::class, 'local_bus_update'])->name('admin.update.role');

        Route::post('/booking/transfer', [BookingController::class, 'transferBooking'])->name('booking.transfer');
        Route::get('/booking/transfer/{booking_id?}', [AdminController::class, 'showTransferForm'])->name('booking.transfer.form');

        Route::get('/resaved-tickets', [AdminController::class, 'resavedTickets'])->name('resaved.tickets');
        Route::get('/buses/print/pdf', [AdminController::class, 'printBusesPdf'])->name('bus.print.pdf');
        Route::get('/get-filtered-schedules', [AdminController::class, 'getFilteredSchedules'])->name('get.filtered.schedules');
        Route::get('/calculate-transfer-amounts', [AdminController::class, 'calculateTransferAmounts'])->name('calculate.transfer.amounts');
        Route::post('/booking/excess-luggage/{booking}', [AdminController::class, 'updateExcessLuggage'])->name('booking.excess_luggage.update');
        Route::get('/booking/excess-luggage/{booking}/receipt', [AdminController::class, 'printExcessLuggageReceipt'])->name('excess_luggage.receipt.print');

        Route::prefix('/excess-luggage')->name('bus_owner.excess_luggage.')->group(function () {
            Route::get('/', [ExcessLuggageController::class, 'index'])->name('index');
            Route::get('/lookup', [ExcessLuggageController::class, 'lookupForm'])->name('lookup');
            Route::post('/lookup', [ExcessLuggageController::class, 'lookup'])->name('lookup.post');
            Route::get('/scan', [ExcessLuggageController::class, 'scanForm'])->name('scan');
            Route::post('/scan', [ExcessLuggageController::class, 'scanExit'])->name('scan.post');
            Route::get('/{booking}', [ExcessLuggageController::class, 'show'])->name('show');
            Route::post('/{booking}/weigh', [ExcessLuggageController::class, 'weighIn'])->name('weigh');
            Route::post('/{booking}/pay', [ExcessLuggageController::class, 'pay'])->name('pay');
            Route::post('/{booking}/assign', [ExcessLuggageController::class, 'assign'])->name('assign');
            Route::post('/{booking}/reclaim', [ExcessLuggageController::class, 'reclaim'])->name('reclaim');
            Route::get('/{booking}/print', [ExcessLuggageController::class, 'printReceipt'])->name('print');
        });
        
        // Bus Owner Parcel Management
        Route::prefix('/parcels')->name('bus_owner.parcels.')->group(function () {
             Route::get('/', [AdminController::class, 'busOwnerParcels'])->name('index');
             Route::get('/find-bus', [ParcelController::class, 'searchBus'])->name('find_bus');
             Route::get('/manifest', [ParcelController::class, 'manifest'])->name('manifest');
             Route::get('/create/{bus_id}', [ParcelController::class, 'create'])->name('create');
             Route::post('/store', [ParcelController::class, 'store'])->name('store');
             Route::post('/update-status/{id}', [ParcelController::class, 'updateStatus'])->name('update_status');
             Route::post('/toggle-acceptance', [ParcelController::class, 'toggleAcceptance'])->name('toggle_acceptance');
             Route::post('/capacity', [ParcelController::class, 'updateCapacity'])->name('capacity');
             Route::get('/{id}', [ParcelController::class, 'show'])->name('show');
             Route::post('/{id}/pay', [ParcelController::class, 'pay'])->name('pay');
             Route::post('/{id}/assign', [ParcelController::class, 'assign'])->name('assign');
             Route::post('/{id}/depart', [ParcelController::class, 'depart'])->name('depart');
             Route::post('/{id}/arrive', [ParcelController::class, 'arrive'])->name('arrive');
             Route::post('/{id}/collect', [ParcelController::class, 'collect'])->name('collect');
             Route::get('/{id}/print', [ParcelController::class, 'print'])->name('print');
        });
    });

    // Admin Routes (Accessible only to admin role)

    Route::prefix('admin')->middleware(['role:admin', '2fa'])->group(function () {
        Route::get('/', [SystemController::class, 'index'])->name('system.index');
        Route::get('/companies', [SystemController::class, 'campany'])->name('system.campany');
        Route::get('/companies/print/pdf', [SystemController::class, 'printCampaniesPdf'])->name('system.campany.print');
        Route::get('/companies/{campany}', [SystemController::class, 'campanyShow'])->name('system.campany.show');
        Route::post('/campany_status', [SystemController::class, 'campany_status'])->name('system.campany.status');
        Route::get('/buses', [SystemController::class, 'buses'])->name('system.buses');
        Route::get('/buses/print/pdf', [SystemController::class, 'printBusesPdf'])->name('system.buses.print');
        Route::get('/special-hire', [SystemController::class, 'specialHireIndex'])->name('system.special_hire');
        Route::get('/special-hire/report/pdf', [SystemController::class, 'specialHireReportPdf'])->name('system.special_hire.report.pdf');
        Route::get('/special-hire/report/csv', [SystemController::class, 'specialHireReportCsv'])->name('system.special_hire.report.csv');
        Route::get('/special-hire/{user}/report/pdf', [SystemController::class, 'specialHireOwnerReportPdf'])->name('system.special_hire.show.report.pdf')
            ->whereNumber('user');
        Route::get('/special-hire/{user}/report/csv', [SystemController::class, 'specialHireOwnerReportCsv'])->name('system.special_hire.show.report.csv')
            ->whereNumber('user');
        Route::get('/special-hire/{user}', [SystemController::class, 'specialHireShow'])->name('system.special_hire.show')
            ->whereNumber('user');
        Route::post('/special-hire/withdrawals/{id}', [SystemController::class, 'updateSpecialHireWithdrawal'])->name('system.special_hire.withdrawal');
        Route::post('/special-hire/{user}/platform-percent', [SystemController::class, 'updateSpecialHireOwnerPlatformPercent'])->name('system.special_hire.platform_percent')
            ->whereNumber('user');
        Route::post('/special-hire/{user}/toggle-status', [SystemController::class, 'toggleSpecialHireStatus'])->name('system.special_hire.toggle_status')
            ->whereNumber('user');
        Route::get('/special-hire/orders/{order}/passengers', [SystemController::class, 'specialHireOrderPassengersEdit'])->name('system.special_hire.order.passengers.edit')
            ->whereNumber('order');
        Route::post('/special-hire/orders/{order}/passengers', [SystemController::class, 'specialHireOrderPassengersUpdate'])->name('system.special_hire.order.passengers.update')
            ->whereNumber('order');
        Route::get('/special-hire/orders/{order}/manifest/pdf', [SystemController::class, 'specialHireOrderManifestPdf'])->name('system.special_hire.order.manifest.pdf')
            ->whereNumber('order');
        Route::get('/special-hire/orders/{order}/receipt/customer/pdf', [SystemController::class, 'specialHireOrderCustomerReceiptPdf'])->name('system.special_hire.order.receipt.customer')
            ->whereNumber('order');
        Route::get('/special-hire/orders/{order}/receipt/commission/pdf', [SystemController::class, 'specialHireOrderCommissionReceiptPdf'])->name('system.special_hire.order.receipt.commission')
            ->whereNumber('order');
        Route::get('/special-hire/orders/{order}/transfer', [SystemController::class, 'specialHireOrderTransferEdit'])->name('system.special_hire.order.transfer.edit')
            ->whereNumber('order');
        Route::post('/special-hire/orders/{order}/transfer', [SystemController::class, 'specialHireOrderTransferUpdate'])->name('system.special_hire.order.transfer.update')
            ->whereNumber('order');
        Route::get('/transaction', [SystemController::class, 'pay_request'])->name('pay.request');
        Route::get('/transaction/export/pdf', [SystemController::class, 'paymentRequestReportPdf'])->name('pay.request.pdf');
        Route::get('/transaction/export/csv', [SystemController::class, 'paymentRequestReportCsv'])->name('pay.request.csv');
        Route::post('/transactions/{transaction}/company/{campany}/complete', [SystemController::class, 'complete'])->name('transactions.complete');
        Route::post('/transactions/{transaction}/company/{campany}/cancel', [SystemController::class, 'cancel'])->name('transactions.cancel');
        Route::get('/system_payments', [SystemController::class, 'system_payments'])->name('system.payments');
        Route::get('/system_payments/export/pdf', [SystemController::class, 'systemIncomeReportPdf'])->name('system.payments.pdf');
        Route::get('/system_payments/export/csv', [SystemController::class, 'systemIncomeReportCsv'])->name('system.payments.csv');
        Route::get('/excess-luggage', [ExcessLuggageController::class, 'adminIndex'])->name('system.excess_luggage');
        Route::get('/parcels', [ParcelController::class, 'adminIndex'])->name('system.parcels');
        Route::get('/parcels/manifest', [ParcelController::class, 'adminManifest'])->name('system.parcels.manifest');
        Route::get('/government-levy', [SystemController::class, 'governmentLevyReport'])->name('system.government_levy');
        Route::get('/government-levy/export/pdf', [SystemController::class, 'governmentLevyReportPdf'])->name('system.government_levy.pdf');
        Route::get('/government-levy/export/csv', [SystemController::class, 'governmentLevyReportCsv'])->name('system.government_levy.csv');
        Route::get('/history', [SystemController::class, 'history'])->name('system.history');
        Route::get('/history/manifest/print', [SystemController::class, 'printManifestAll'])->name('system.history.manifest.print');
        Route::get('/cancelled-bookings', [SystemController::class, 'cancelled_bookings'])->name('system.cancelled_bookings');
        Route::post('/print', [SystemController::class, 'print'])->name('system.print');
        Route::post('/print/service', [SystemController::class, 'printService'])->name('system.print.service');
        Route::post('/print/commission', [SystemController::class, 'printCommission'])->name('system.print.commission');
        Route::post('/manifest', [AdminController::class, 'manifest'])->name('system.print.manifest');
        Route::post('/transactions/filter', [SystemController::class, 'filter'])->name('transactions.filter');
        Route::post('/transactions/{transaction}/complete/{campany}/{vender}', [SystemController::class, 'complete'])->name('transactions.complete');
        Route::post('/transactions/{transaction}/cancel/{campany}/{vender}', [SystemController::class, 'cancel'])->name('transactions.cancel');
        Route::get('/bima', [BimaController::class, 'index'])->name('bima.index');
        Route::get('/bima/export/pdf', [BimaController::class, 'reportPdf'])->name('bima.pdf');
        Route::get('/bima/export/csv', [BimaController::class, 'reportCsv'])->name('bima.csv');
        Route::get('/vender', [SystemController::class, 'vender'])->name('system.vender');
        Route::get('/vender/print/pdf', [SystemController::class, 'printVendersPdf'])->name('system.vender.print');
        Route::get('/bima/data', [BimaController::class, 'getData'])->name('bima.data');
        Route::post('/vender/status', [SystemController::class, 'vender_status'])->name('system.vender.status');

        Route::post('profile/update/admin', [SystemController::class, 'update_profile'])->name('system.profile.update');
        Route::get('/profile', [SystemController::class, 'profile'])->name('system.profile');

        Route::get('/cities', [SystemController::class, 'cities'])->name('system.cities');
        Route::post('/cities', [SystemController::class, 'store_city'])->name('system.city.store');

        Route::get('discount', [SystemController::class, 'discount'])->name('system.discount');
        Route::post('add-coupon', [SystemController::class, 'add_coupon'])->name('system.add.coupon');

        Route::get('/bus/bus_route', [SystemController::class, 'bus_route'])->name('system.bus_route');
        Route::get('/balance', [SystemController::class, 'balance'])->name('system.balance');
        Route::post('/balance', [SystemController::class, 'update_balance'])->name('system.update.balance');

        Route::post('print/recipt2', [SystemController::class, 'print_recipt2'])->name('system.print.recipt');
        Route::get('/local-admin', [OtherController::class, 'local_admin'])->name('system.local_admin');

        Route::get('view/busOwnerInfo/{id}', [SystemController::class, 'busOwner'])->name('busowner');
        Route::controller(OtherController::class)->group(function () {
            Route::get('/other', 'local_admin')->name('system.local_admin');
            Route::get('/other/create', 'local_admin_form')->name('system.local_admin.create');
            Route::post('/other', 'local_admin_store')->name('system.local_admin.store');
            Route::get('/other/{id}/edit', 'local_admin_edit')->name('system.local_admin.edit');
            Route::put('/other/{id}', 'local_admin_update')->name('system.local_admin.update');
            Route::delete('/other/{id}', 'local_admin_destroy')->name('system.local_admin.destroy');

            Route::get('/bus/local_admin/{id}', 'local_bus_owners')->name('system.local_admin.bus');

            Route::post('/other/role', 'update_role')->name('system.update.role');
        });

        Route::post('profile/update', [SystemController::class, 'update_profile_bus'])->name('profile.update.bus');
        Route::get('settings', [SystemController::class, 'setting'])->name('system.setting');
        Route::post('settings', [SystemController::class, 'setting_update'])->name('setting.update');
        Route::post('settings/sms/test', [SystemController::class, 'sms_test'])->name('setting.sms.test');
        Route::post('vender/percentage', [SystemController::class, 'vender_percent'])->name('vender.percent');

        Route::get('/refunds', [SystemController::class, 'refunds'])->name('system.refunds');
        Route::get('/refunds/export/pdf', [SystemController::class, 'refundsReportPdf'])->name('system.refunds.pdf');
        Route::get('/refunds/export/csv', [SystemController::class, 'refundsReportCsv'])->name('system.refunds.csv');
        Route::post('/refunds/{id}/approve', [SystemController::class, 'approveRefund'])->name('system.refunds.approve');
        Route::post('/refunds/{id}/reject', [SystemController::class, 'rejectRefund'])->name('system.refunds.reject');

        Route::get('/migrate/{migration?}', [SystemController::class, 'runMigrations'])->name('system.migrate');

        Route::get('/commands', [ArtisanCommandsController::class, 'index'])->name('system.commands');
        Route::post('/commands/run', [ArtisanCommandsController::class, 'run'])->name('system.commands.run');
        Route::post('/commands/log', [ArtisanCommandsController::class, 'readLog'])->name('system.commands.log');
    });

    Route::prefix('vender')->middleware(['role:vender', '2fa', 'vendor.enabled'])->group(function () {
        Route::get('/', [VenderController::class, 'index'])->name('vender.index');
        Route::get('/route', [VenderController::class, 'mybooking_search'])->name('vender.route');
        Route::match(['get', 'post'], 'route/by_search', [VenderController::class, 'by_route_search'])->name('vender.route.by_route_search');
        Route::get('/route/road', [VenderController::class, 'route'])->name('vender.route.road');
        Route::get('/campany/id', [RouteController::class, 'bus_name'])->name('vender.busname');

        Route::get('/round-trip', [RoundTripController::class, 'index'])->name('vender.round.trip');
        Route::get('/round-trip/by-routesearch', [RoundTripController::class, 'by_routesearch'])->name('vender.round.trip.by.routesearch');
        Route::get('/round-trip/inline/{id}/{from}/{to}', [RoundTripController::class, 'inlineBookingForm'])->name('vender.round.trip.inline.form');
        Route::post('/round-trip/inline/prepare', [RoundTripController::class, 'inlinePreparePayment'])->name('vender.round.trip.inline.prepare');
        Route::get('/round-trip/inline/wallet', [RoundTripController::class, 'inlineWalletLookup'])->name('vender.round.trip.inline.wallet');
        Route::get('/round-trip/{id}/{from}/{to}', [RoundTripController::class, 'booking_form'])->name('vender.round.trip.booking_form');
        Route::post('/round-trip/booking_form', [RoundTripController::class, 'get_form'])->name('vender.round.trip.booking_form.store');
        Route::get('/round-trip/seats', [RoundTripController::class, 'seates'])->name('vender.round.trip.seats');
        Route::post('/round-trip/seats', [RoundTripController::class, 'get_seats'])->name('vender.round.trip.seats.post');
        Route::get('/round-trip/payment', [RoundTripController::class, 'payment'])->name('vender.round.trip.payment');
        Route::post('/round-trip/payment/pay', [RoundTripController::class, 'payment_info'])->name('vender.round.trip.payment.pay');
        Route::get('/round-trip/checkout', [RoundTripController::class, 'checkout'])->name('vender.round.trip.checkout');
        Route::post('/round-trip/get_payment', [RoundTripController::class, 'get_payment'])->name('vender.round.trip.get_payment');
        Route::get('/round-trip/payment/success', [RoundTripController::class, 'paymentSuccess'])->name('vender.round.trip.payment.success');
        Route::get('/round-trip/payment/failed', [RoundTripController::class, 'paymentFailed'])->name('vender.round.trip.payment.failed');

        Route::get('/booking/inline/{id}/{from}/{to}', [BookingController::class, 'inlineBookingForm'])->name('vender.booking.inline.form');
        Route::post('/booking/inline/prepare', [BookingController::class, 'inlinePreparePayment'])->name('vender.booking.inline.prepare');
        Route::get('/booking/inline/wallet', [BookingController::class, 'inlineWalletLookup'])->name('vender.booking.inline.wallet');
        Route::get('/booking_form/{id}/{from}/{to}', [VenderController::class, 'booking_form'])->name('vender.booking_form');
        Route::get('/booking/seates', [VenderController::class, 'seates'])->name('seates.vender');
        Route::post('/booking/get_form', [BookingController::class, 'get_form'])->name('vender.get_form');
        Route::post('/booking/get_form/legacy', [VenderController::class, 'get_form'])->name('vender.store');

        Route::post('/booking/seates', [BookingController::class, 'get_seats'])->name('vender.get_seats');
        Route::get('/booking/payment/pay', [VenderController::class, 'payment'])->name('vender.pay');
        Route::post('/booking/payment/pay', [VenderController::class, 'payment_info'])->name('vender.payment_store');
        Route::get('/bus/bus_route', [VenderController::class, 'bus_route'])->name('vender.bus_route');
        Route::get('/transaction', [VenderController::class, 'transaction'])->name('vender.transaction');
        Route::get('/transaction/export/pdf', [VenderController::class, 'transactionExportPdf'])->name('vender.transaction.export.pdf');
        Route::get('/transaction/export/csv', [VenderController::class, 'transactionExportCsv'])->name('vender.transaction.export.csv');
        Route::post('/transaction_request', [VenderController::class, 'transaction_request'])->name('vender.transaction.request');
        Route::get('/history', [VenderController::class, 'history'])->name('vender.history');
        Route::get('/history/export/pdf', [VenderController::class, 'historyExportPdf'])->name('vender.history.export.pdf');
        Route::get('/history/export/csv', [VenderController::class, 'historyExportCsv'])->name('vender.history.export.csv');
        Route::get('/resaved-tickets', [VenderController::class, 'resavedTickets'])->name('vender.resaved.tickets');
        Route::post('/cancel-resaved/{id}', [VenderController::class, 'cancelResavedTicket'])->name('vender.cancel.resaved');
        Route::get('/pay-resaved/{id}', [VenderController::class, 'payResavedTicket'])->name('vender.pay.resaved');
        Route::get('/edit-resaved/{id}', [VenderController::class, 'editResavedTicket'])->name('vender.edit.resaved');
        Route::post('/update-resaved', [VenderController::class, 'updateResavedTicket'])->name('vender.update.resaved');
        Route::post('/print', [VenderController::class, 'print'])->name('vender.print');
        Route::post('/manifest', [VenderController::class, 'manifest'])->name('vender.print.manifest');

        Route::get('schedule/{id}/edit', [ScheduleController::class, 'edit'])->name('vender.edit.schedule');
        Route::post('update-schedule/{id}', [ScheduleController::class, 'update'])->name('vender.update_schedule');

        Route::post('/booking/payment/data', [VenderController::class, 'get_payment'])->name('vender.verify');

        //Route::match(['get', 'post'], '/tigo/redirect/{transactionRefId}', [VenderController::class, 'handleRedirects'])->name('vender.tigo.redirect');
        //Route::match(['get', 'post'], '/tigo/callback', [VenderController::class, 'handleCallbacks'])->name('vender.tigo.callback');


        Route::post('profile/update', [VenderController::class, 'update_profile'])->name('vender.profile.update');
        Route::get('/profile', [VenderController::class, 'profile'])->name('vender.profile');

        Route::prefix('/wallet')->name('vender.wallet.')->group(function () {
            Route::get('/deposit', [VenderWalletController::class, 'showDepositForm'])->name('deposit');
            Route::post('/deposit', [VenderWalletController::class, 'deposit'])->name('processDeposit');
            Route::post('/transfer', [VenderWalletController::class, 'transferInternal'])->name('transfer');
            Route::post('/migrate-legacy-cash', [VenderWalletController::class, 'migrateLegacyBalanceToCash'])->name('migrateLegacyCash');
            Route::get('/deposit/success', [VenderWalletController::class, 'depositSuccess'])->name('success');
            Route::get('/deposit/fail', [VenderWalletController::class, 'depositFail'])->name('fail');
        });

        Route::prefix('/parcels')->name('vender.parcels.')->group(function () {
            Route::get('/', [ParcelController::class, 'index'])->name('index');
            Route::get('/find-bus', [ParcelController::class, 'searchBus'])->name('find_bus');
            Route::get('/create/{bus_id}', [ParcelController::class, 'create'])->name('create');
            Route::post('/store', [ParcelController::class, 'store'])->name('store');
            Route::get('/{id}', [ParcelController::class, 'show'])->name('show');
            Route::post('/{id}/pay', [ParcelController::class, 'pay'])->name('pay');
            Route::post('/{id}/assign', [ParcelController::class, 'assign'])->name('assign');
            Route::post('/{id}/depart', [ParcelController::class, 'depart'])->name('depart');
            Route::post('/{id}/arrive', [ParcelController::class, 'arrive'])->name('arrive');
            Route::post('/{id}/collect', [ParcelController::class, 'collect'])->name('collect');
            Route::get('/{id}/print', [ParcelController::class, 'print'])->name('print');
        });

        Route::prefix('/excess-luggage')->name('vender.excess_luggage.')->group(function () {
            Route::get('/', [ExcessLuggageController::class, 'index'])->name('index');
            Route::get('/lookup', [ExcessLuggageController::class, 'lookupForm'])->name('lookup');
            Route::post('/lookup', [ExcessLuggageController::class, 'lookup'])->name('lookup.post');
            Route::get('/scan', [ExcessLuggageController::class, 'scanForm'])->name('scan');
            Route::post('/scan', [ExcessLuggageController::class, 'scanExit'])->name('scan.post');
            Route::get('/{booking}', [ExcessLuggageController::class, 'show'])->name('show');
            Route::post('/{booking}/weigh', [ExcessLuggageController::class, 'weighIn'])->name('weigh');
            Route::post('/{booking}/pay', [ExcessLuggageController::class, 'pay'])->name('pay');
            Route::post('/{booking}/assign', [ExcessLuggageController::class, 'assign'])->name('assign');
            Route::post('/{booking}/reclaim', [ExcessLuggageController::class, 'reclaim'])->name('reclaim');
            Route::get('/{booking}/print', [ExcessLuggageController::class, 'printReceipt'])->name('print');
        });
    });

    Route::prefix('customer')->middleware(['role:customer', '2fa'])->group(function () {
        Route::get('/home', [CustomerController::class, 'dashboard'])->name('customer.dashboard');
        Route::get('/', [CustomerController::class, 'index'])->name('customer.index');
        Route::get('/mybooking', [CustomerController::class, 'mybooking'])->name('customer.mybooking');
        Route::post('/mybooking/print-report', [CustomerController::class, 'printReport'])->name('customer.print.report');
        Route::get('/mybooking/search', [CustomerController::class, 'mybooking_search'])->name('customer.mybooking.search');
        Route::match(['get', 'post'], '/mybooking/search/form', [CustomerController::class, 'by_route_search'])->name('customer.mybooking.search.form');
        Route::get('/booking/inline/{id}/{from}/{to}', [BookingController::class, 'inlineBookingForm'])->name('customer.booking.inline.form');
        Route::post('/booking/inline/prepare', [BookingController::class, 'inlinePreparePayment'])->name('customer.booking.inline.prepare');
        Route::get('/booking/inline/wallet', [BookingController::class, 'inlineWalletLookup'])->name('customer.booking.inline.wallet');
        Route::get('/booking_form/{id}/{from}/{to}', [BookingController::class, 'booking_form'])->name('customer.booking_form');
        Route::post('get_form', [BookingController::class, 'get_form'])->name('customer.get_form');
        Route::get('/seats', [BookingController::class, 'seates'])->name('customer.seats');
        Route::post('/get_seats', [BookingController::class, 'get_seats'])->name('customer.get_seats');
        Route::get('/booking/payment', [CustomerController::class, 'payment'])->name('customer.pay');
        Route::post('/booking/payment', [CustomerController::class, 'payment_info'])->name('customer.payment_store');
        Route::post('/booking/payment/data', [CustomerController::class, 'get_payment'])->name('customer.verify');

        Route::get('/by_route', [CustomerController::class, 'by_route'])->name('customer.by_route');
        Route::view('profile', 'customer.profile')->name('customer.profile');
        Route::post('profile/update', [CustomerController::class, 'update_profile'])->name('customer.profile.update');

        Route::get('/rebook', [RebookController::class, 'rebook'])->name('customer.rebook');
        Route::get('/cancel', [CancelController::class, 'cancel'])->name('customer.cancel');
        Route::post('/cancel-resaved/{id}', [CustomerController::class, 'cancelResavedTicket'])->name('customer.cancel.resaved');
        Route::get('/pay-resaved/{id}', [CustomerController::class, 'payResavedTicket'])->name('customer.pay.resaved');

        Route::get('/edit/{id}', [CustomerController::class, 'edit'])->name('customer.edit');
        Route::post('/edit', [CustomerController::class, 'update'])->name('customer.update');

        Route::get('/round-trip', [RoundTripController::class, 'index'])->name('customer.round.trip');
        Route::get('/round-trip/by-routesearch', [RoundTripController::class, 'by_routesearch'])->name('customer.round.trip.by.routesearch');
        Route::get('/round-trip/inline/{id}/{from}/{to}', [RoundTripController::class, 'inlineBookingForm'])->name('customer.round.trip.inline.form');
        Route::post('/round-trip/inline/prepare', [RoundTripController::class, 'inlinePreparePayment'])->name('customer.round.trip.inline.prepare');
        Route::get('/round-trip/inline/wallet', [RoundTripController::class, 'inlineWalletLookup'])->name('customer.round.trip.inline.wallet');
        Route::get('/round-trip/{id}/{from}/{to}', [RoundTripController::class, 'booking_form'])->name('customer.round.trip.booking_form');
        Route::post('/round-trip/booking_form', [RoundTripController::class, 'get_form'])->name('customer.round.trip.booking_form.store');
        Route::get('/round-trip/seats', [RoundTripController::class, 'seates'])->name('customer.round.trip.seats');
        Route::post('/round-trip/seats', [RoundTripController::class, 'get_seats'])->name('customer.round.trip.seats.post');
        Route::get('/round-trip/payment', [RoundTripController::class, 'payment'])->name('customer.round.trip.payment');
        Route::post('/round-trip/payment/pay', [RoundTripController::class, 'payment_info'])->name('customer.round.trip.payment.pay');
        Route::get('/round-trip/checkout', [RoundTripController::class, 'checkout'])->name('customer.round.trip.checkout');
        Route::post('/round-trip/get_payment', [RoundTripController::class, 'get_payment'])->name('customer.round.trip.get_payment');
        Route::get('/round-trip/payment/success', [RoundTripController::class, 'paymentSuccess'])->name('customer.round.trip.payment.success');
        Route::get('/round-trip/payment/failed', [RoundTripController::class, 'paymentFailed'])->name('customer.round.trip.payment.failed');
        Route::get('/campany/id', [RouteController::class, 'bus_name'])->name('customer.busname');
    });

    // Special Hire Routes
    Route::prefix('special-hire')->middleware(['role:special_hire', '2fa', 'special_hire.enabled'])->group(function () {
        // Dashboard
        Route::get('/', [SpecialHireController::class, 'index'])->name('special_hire.index');

        // Coasters
        Route::get('/coasters', [SpecialHireController::class, 'coasters'])->name('special_hire.coasters');
        Route::get('/coasters/create', [SpecialHireController::class, 'createCoaster'])->name('special_hire.coasters.create');
        Route::post('/coasters', [SpecialHireController::class, 'storeCoaster'])->name('special_hire.coasters.store');
        Route::get('/coasters/{id}/edit', [SpecialHireController::class, 'editCoaster'])->name('special_hire.coasters.edit');
        Route::put('/coasters/{id}', [SpecialHireController::class, 'updateCoaster'])->name('special_hire.coasters.update');
        Route::delete('/coasters/{id}', [SpecialHireController::class, 'deleteCoaster'])->name('special_hire.coasters.destroy');

        // Live Tracking
        Route::get('/tracking', [SpecialHireController::class, 'tracking'])->name('special_hire.tracking');

        // Orders
        Route::get('/orders', [SpecialHireController::class, 'orders'])->name('special_hire.orders');
        Route::get('/orders/create', [SpecialHireController::class, 'createOrder'])->name('special_hire.orders.create');
        Route::post('/orders', [SpecialHireController::class, 'storeOrder'])->name('special_hire.orders.store');
        Route::get('/orders/{id}', [SpecialHireController::class, 'showOrder'])->name('special_hire.orders.show');
        Route::put('/orders/{id}', [SpecialHireController::class, 'updateOrder'])->name('special_hire.orders.update');
        Route::post('/orders/{id}/accept-hire', [SpecialHireController::class, 'acceptHireBooking'])->name('special_hire.orders.accept_hire');
        Route::post('/orders/{id}/cancel-hire', [SpecialHireController::class, 'cancelHireOrder'])->name('special_hire.orders.cancel_hire');
        Route::post('/orders/{id}/refund-hire', [SpecialHireController::class, 'refundHirePayment'])->name('special_hire.orders.refund_hire');

        Route::get('/drivers/create', [SpecialHireController::class, 'createDriver'])->name('special_hire.drivers.create');
        Route::post('/drivers', [SpecialHireController::class, 'storeDriver'])->name('special_hire.drivers.store');
        Route::get('/drivers/{driver}/reset-password', [SpecialHireController::class, 'resetDriverPasswordForm'])
            ->name('special_hire.drivers.reset_password')
            ->whereNumber('driver');
        Route::post('/drivers/{driver}/reset-password', [SpecialHireController::class, 'resetDriverPassword'])
            ->name('special_hire.drivers.reset_password.store')
            ->whereNumber('driver');

        // Pricing
        Route::get('/pricing', [SpecialHireController::class, 'pricing'])->name('special_hire.pricing');
        Route::post('/pricing', [SpecialHireController::class, 'storePricing'])->name('special_hire.pricing.store');

        // Earnings
        Route::get('/earnings', [SpecialHireController::class, 'earnings'])->name('special_hire.earnings');
        Route::post('/earnings/withdrawal', [SpecialHireController::class, 'storeWithdrawalRequest'])->name('special_hire.withdrawal.store');

        // Profile
        Route::get('/profile', [SpecialHireController::class, 'profile'])->name('special_hire.profile');
        Route::post('/profile', [SpecialHireController::class, 'updateProfile'])->name('special_hire.profile.update');

        // Calculate Price (AJAX)
        Route::post('/calculate-price', [SpecialHireController::class, 'calculatePrice'])->name('special_hire.calculate_price');
    });
});


Route::get('/round-trip', [RoundTripController::class, 'index'])->name('round.trip');
Route::get('/round-trip/by-routesearch', [RoundTripController::class, 'by_routesearch'])->name('round.trip.by.routesearch');
Route::get('/round-trip/by-bus', [RoundTripController::class, 'by_bus'])->name('round.trip.by.bus');

Route::get('/round-trip/schedule', [RoundTripController::class, 'route'])->name('round.trip.schedule');
Route::get('/round-trip/inline/{id}/{from}/{to}', [RoundTripController::class, 'inlineBookingForm'])->name('round.trip.inline.form');
Route::post('/round-trip/inline/prepare', [RoundTripController::class, 'inlinePreparePayment'])->name('round.trip.inline.prepare');
Route::get('/round-trip/inline/wallet', [RoundTripController::class, 'inlineWalletLookup'])->name('round.trip.inline.wallet');
Route::get('/round-trip/{id}/{from}/{to}', [RoundTripController::class, 'booking_form'])->name('round.trip.booking_form');
Route::post('/round-trip/booking_form', [RoundTripController::class, 'get_form'])->name('round.trip.booking_form.store');

Route::get('/round-trip/seats', [RoundTripController::class, 'seates'])->name('round.trip.seats');
Route::post('/round-trip/seats', [RoundTripController::class, 'get_seats'])->name('round.trip.seats.post');

Route::get('/round-trip/payment', [RoundTripController::class, 'payment'])->name('round.trip.payment');

Route::post('/round-trip/payment/pay', [RoundTripController::class, 'payment_info'])->name('round.trip.payment.pay');
// If user hits payment/pay with GET (e.g. back button, refresh), show payment form instead of 405
Route::get('/round-trip/checkout', [RoundTripController::class, 'checkout'])->name('round.trip.checkout');

Route::get('/round-trip/payment/pay', function () {
    return app(RoundTripController::class)->checkout();
})->name('round.trip.payment.pay.get');

Route::post('/round-trip/get_payment', [RoundTripController::class, 'get_payment'])->name('round.trip.get_payment');

// Roundtrip Payment Success/Failure Routes
Route::get('/round-trip/payment/success', [RoundTripController::class, 'paymentSuccess'])->name('round.trip.payment.success');
Route::get('/round-trip/payment/failed', [RoundTripController::class, 'paymentFailed'])->name('round.trip.payment.failed');
Route::get('/roundtrip-booking-status/{booking1Id}/{booking2Id}', [RedirectController::class, 'showRoundTripBookingStatus'])->name('roundtrip.booking.status');
