<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();
        $mfaService = app(\App\Services\MfaService::class);

        if ($mfaService->requiresMfa($user)) {
            $remember = $request->boolean('remember');

            // Cierra la sesión activa de inmediato
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Almacena el ID de usuario y la opción de recordar en la sesión limpia
            session()->put('mfa:user_id', $user->id);
            session()->put('mfa:remember', $remember);

            // Dispara el envío de OTP por correo
            $mfaService->sendChallenge($user, 'email_otp');

            return redirect()->route('auth.mfa');
        }

        $request->session()->regenerate();

        Log::channel('session')->info('new user session registered', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user' => $user->email,
        ]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
