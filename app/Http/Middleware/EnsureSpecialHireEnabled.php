<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSpecialHireEnabled
{
    /**
     * Block disabled special hire users from accessing the special hire portal.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isSpecialHire() && !$user->isActive()) {
            return redirect()->route('login')
                ->with('error', 'Your special hire account has been disabled. Please contact the administrator.');
        }

        return $next($request);
    }
}