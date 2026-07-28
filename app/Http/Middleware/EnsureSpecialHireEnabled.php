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
            return $this->deny($request, 'Unauthenticated.', 401);
        }

        if ($user->isSpecialHire() && !$user->isActive()) {
            return $this->deny(
                $request,
                'Your special hire account has been disabled. Please contact the administrator.',
                403
            );
        }

        return $next($request);
    }

    /**
     * API clients (mobile app) get JSON; the web portal keeps its login redirect.
     * Without this the admin's Active/Disabled toggle only gated the web portal.
     */
    private function deny(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()->route('login')->with('error', $message);
    }
}
