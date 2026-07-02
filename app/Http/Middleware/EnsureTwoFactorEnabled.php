<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorEnabled
{
    public static function isGloballyEnabled(): bool
    {
        try {
            if (!Schema::hasTable('settings') || !Schema::hasColumn('settings', 'enforce_2fa')) {
                // Fail open for availability when schema is behind code.
                return false;
            }

            return (bool) (Setting::query()->value('enforce_2fa') ?? true);
        } catch (\Throwable $e) {
            Log::warning('2FA global setting check failed. Falling back to disabled.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function requiresTwoFactor($user): bool
    {
        return self::isGloballyEnabled() && $user !== null;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If not logged in, redirect to login
        if (! $user) {
            return redirect()->route('login');
        }

        if (self::requiresTwoFactor($user)) {
            // If user does not have 2FA enabled
            if (is_null($user->two_factor_secret) || is_null($user->two_factor_recovery_codes)) {
                return redirect()->route('two-factor.setup')
                    ->with('error', 'Please enable Two-Factor Authentication before accessing this section.');
            }
            
            // If 2FA is enabled but not verified in this session, redirect to verification
            if (is_null($user->two_factor_confirmed_at)) {
                return redirect()->route('two-factor.login')
                    ->with('error', 'Please complete Two-Factor Authentication to continue.');
            }
        }

        return $next($request);
    }
}
