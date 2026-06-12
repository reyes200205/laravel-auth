<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\MfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar la sesión de autenticación del usuario.
 *
 * Se encarga de mostrar el formulario de inicio de sesión, verificar las credenciales,
 * iniciar el flujo de autenticación multifactor (MFA) si el rol del usuario lo requiere,
 * y destruir la sesión activa al cerrar sesión.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra la vista del formulario de inicio de sesión.
     *
     * @return \Inertia\Response Vista de Inertia para el login de usuario.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Procesa la solicitud de inicio de sesión del usuario.
     *
     * Valida las credenciales a través de LoginRequest, determina si el usuario
     * requiere completar factores de seguridad adicionales para la autenticación multifactor (MFA)
     * e inicia dicho flujo redirigiendo al reto de seguridad o realiza el inicio de sesión directo.
     *
     * @param \App\Http\Requests\Auth\LoginRequest $request Solicitud de inicio de sesión validada.
     * @return \Illuminate\Http\RedirectResponse Redirección a la ruta de MFA o a la página de inicio (Home).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Valida credenciales y retorna el usuario sin iniciar sesión
        $user = $request->authenticate();

        $mfaService = app(MfaService::class);

        // Inicializa el registro de factores de autenticación
        session()->put('mfa:completed_factors', ['password']);
        session()->put('mfa:user_id', $user->id);
        session()->put('mfa:remember', $request->boolean('remember'));

        // Busca si hay algún factor pendiente para el rol de este usuario
        $nextFactor = $mfaService->getNextPendingFactor($user);

        if ($nextFactor) {
            Log::channel('auth')->info('Primer factor de autenticación (contraseña) verificado con éxito. Iniciando flujo MFA.', [
                'user' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'next_factor' => $nextFactor,
                'datetime' => now(),
            ]);

            $mfaService->sendChallenge($user, $nextFactor);
            session()->put('mfa:active_method', $nextFactor);

            return redirect()->route('auth.mfa');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();
        $request->session()->forget(['mfa:completed_factors', 'mfa:user_id', 'mfa:remember', 'mfa:active_method']);

        Log::channel('session')->info('new user session registered', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user' => $user->email,
        ]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Cierra la sesión activa del usuario (logout).
     *
     * Registra el cierre de sesión en los logs del sistema, invalida la sesión HTTP
     * actual y regenera el token CSRF para prevenir ataques.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP actual.
     * @return \Illuminate\Http\RedirectResponse Redirección a la página de bienvenida principal.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            Log::channel('session')->info('Sesión cerrada con éxito.', [
                'user' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'datetime' => now(),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
