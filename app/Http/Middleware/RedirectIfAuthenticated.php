<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                
                if (EnsureTwoFactorEnabled::requiresTwoFactor($user)) {
                    // If 2FA is not verified, redirect to 2FA login
                    if (is_null($user->two_factor_confirmed_at)) {
                        return redirect()->route('two-factor.login')
                            ->with('error', 'Please complete Two-Factor Authentication to continue.');
                    }
                }
                
                // Redirect based on role if 2FA is verified (or not required)
                if ($user->role === 'bus_campany' || $user->role === 'local_bus_owner') {
                    return redirect()->route('index');
                } elseif ($user->role === 'admin') {
                    return redirect()->route('system.index');
                } elseif ($user->role === 'vender') {
                    return redirect()->route('vender.index');
                } elseif ($user->role === 'customer') {
                    return redirect()->route('customer.index');
                }
                
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
