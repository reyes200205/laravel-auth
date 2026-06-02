<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MfaService;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MfaController extends Controller
{
    protected MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Display the MFA challenge page.
     */
    public function create(): Response|RedirectResponse
    {
        if (!session()->has('mfa:user_id')) {
            return redirect()->route('login');
        }

        $userId = session()->get('mfa:user_id');
        $user = User::findOrFail($userId);

        // Ofuscar parte del correo para mejorar la privacidad en la UI
        $emailParts = explode('@', $user->email);
        $maskedEmail = substr($emailParts[0], 0, 3) . '***@' . $emailParts[1];

        return Inertia::render('Auth/MfaChallenge', [
            'status' => session('status'),
            'email' => $maskedEmail,
        ]);
    }

    /**
     * Verify the MFA code.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!session()->has('mfa:user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'El código de verificación es obligatorio.',
            'code.size' => 'El código debe tener exactamente 6 dígitos.',
        ]);

        $userId = session()->get('mfa:user_id');
        $user = User::findOrFail($userId);

        // Verificamos el código usando el servicio
        $isValid = $this->mfaService->verifyChallenge($user, 'email_otp', $request->code);

        if ($isValid) {
            // Logueamos al usuario con la preferencia guardada
            Auth::login($user, session()->get('mfa:remember', false));

            // Regeneramos la sesión por seguridad
            $request->session()->regenerate();

            // Limpiamos las variables de sesión del flujo MFA
            $request->session()->forget(['mfa:user_id', 'mfa:remember']);

            Log::channel('session')->info('new user session registered (MFA passed)', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user' => $user->email,
            ]);

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        throw ValidationException::withMessages([
            'code' => 'El código ingresado es incorrecto, ha expirado o no coincide con tu sesión.',
        ]);
    }

    /**
     * Resend the MFA code.
     */
    public function resend(Request $request): RedirectResponse
    {
        if (!session()->has('mfa:user_id')) {
            return redirect()->route('login');
        }

        $userId = session()->get('mfa:user_id');
        $user = User::findOrFail($userId);

        // Re-enviamos el desafío
        $this->mfaService->sendChallenge($user, 'email_otp');

        return back()->with('status', 'Se ha enviado un nuevo código de verificación a tu correo.');
    }
}
