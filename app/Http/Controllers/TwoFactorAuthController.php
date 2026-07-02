<?php
// app/Http/Controllers/TwoFactorAuthController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthController extends Controller
{
    // Helper used in both confirm & verify
    private function redirectByRole($user)
    {
        if ($user->role === 'bus_campany' || $user->role === 'local_bus_owner') {
            return redirect()->route('index')->with('success', 'Logged in successfully.');
        } elseif ($user->role === 'admin') {
            return redirect()->route('system.index')->with('success', 'Login successful.');
        } elseif ($user->role === 'vender') {
            return redirect()->route('vender.index')->with('success', 'Login successful.');
        } elseif ($user->role === 'customer') {
            return redirect()->route('customer.index')->with('success', 'Login successful.');
        }
        return redirect()->route('home')->with('success', 'Logged in successfully.');
    }

    public function showTwoFactorSetup(Request $request)
    {
        if (is_null(Auth::user()->two_factor_secret)) {
            app(EnableTwoFactorAuthentication::class)(Auth::user());
        }

        $qrCodeSvg = $request->user()->twoFactorQrCodeSvg();

        return view('auth.two-factor-setup', [
            'user' => $request->user(),
            'qrCodeSvg' => $qrCodeSvg,
            'recoveryCodes' => json_decode(decrypt($request->user()->two_factor_recovery_codes), true),
        ]);
    }

     public function showTwoFactorSetupTwo(Request $request)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        
        $user = Auth::user();
        
        if (!\App\Http\Middleware\EnsureTwoFactorEnabled::requiresTwoFactor($user)) {
            return $this->redirectByRole($user);
        }
        
        // If 2FA is not set up, redirect to setup
        if (is_null($user->two_factor_secret)) {
            return redirect()->route('two-factor.setup')
                ->with('error', 'Please enable Two-Factor Authentication first.');
        }

        $qrCodeSvg = $user->twoFactorQrCodeSvg();

        return view('auth.two-login', [
            'user' => $user,
            'qrCodeSvg' => $qrCodeSvg,
            'recoveryCodes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
        ]);
    }

    public function enableTwoFactorAuthentication(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $enable(Auth::user());
        return back()->with('status', 'Two-factor authentication enabled.');
    }

    // When the user scans the QR & enters first OTP to "confirm" setup
    public function confirmTwoFactorAuthentication(
        Request $request,
        ConfirmTwoFactorAuthentication $confirm
    ) {
        $request->validate(['code' => 'required|string']);
        try {
            $confirm(Auth::user(), $request->input('code'));
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput();
        }

        // At this point 2FA is confirmed for the account.
        // If they came here right after signup or from a settings page,
        // just send them to the correct dashboard.
        $request->session()->regenerate();

        return $this->redirectByRole($request->user())
            ->with('status', 'Two-factor authentication confirmed.');
    }

    public function disableTwoFactorAuthentication(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $disable(Auth::user());
        return back()->with('status', 'Two-factor authentication disabled.');
    }

    public function generateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate)
    {
        $generate(Auth::user());
        return back()->with('status', 'New recovery codes generated.');
    }

    // ==== NEW: challenge screen & verify during login ====

    public function challenge()
    {
        // If no pending 2FA session, bounce to login
        if (! session()->has('two_factor:id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor-challenge'); // simple form with "code" and "recovery_code"
    }

    public function verify(Request $request, TwoFactorAuthenticationProvider $provider)
    {
        $request->validate([
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        // Get user from session or authenticated user
        $userId = session('two_factor:id');
        $user = null;
        
        if ($userId) {
            $user = \App\Models\User::find($userId);
        } elseif (Auth::check()) {
            $user = Auth::user();
        }
        
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        if (!\App\Http\Middleware\EnsureTwoFactorEnabled::requiresTwoFactor($user)) {
            return $this->redirectByRole($user);
        }

        $verified = false;

        // Prefer TOTP if provided
        if ($request->filled('code')) {
            $verified = $provider->verify(
                decrypt($user->two_factor_secret),
                $request->input('code')
            );
        }

        // Or recovery code
        if (! $verified && $request->filled('recovery_code')) {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
            $entered = trim($request->input('recovery_code'));

            if (in_array($entered, $codes, true)) {
                // consume the used recovery code
                $remaining = array_values(array_diff($codes, [$entered]));
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode($remaining)),
                ])->save();
                $verified = true;
            }
        }

        if (! $verified) {
            return back()->withErrors([
                'code' => 'Invalid authentication code.',
                'recovery_code' => 'Or enter a valid recovery code.',
            ]);
        }

        // Success: Mark 2FA as verified for this session
        $user->two_factor_confirmed_at = now();
        $user->save();
        
        // Log them in if not already logged in
        if (!Auth::check()) {
            session()->forget(['two_factor:id','two_factor:remember']);
            Auth::login($user, (bool) session('two_factor:remember', false));
        }
        
        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }
}
