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

/**
 * Controlador para gestionar el flujo de autenticación multifactor (MFA/3FA).
 *
 * Administra la presentación del desafío de seguridad (por OTP de correo o ubicación física GPS/IP),
 * procesa las respuestas de verificación del usuario y completa la sesión al validar todos los factores.
 */
class MfaController extends Controller
{
    /**
     * Servicio de autenticación multifactor.
     *
     * @var \App\Services\MfaService
     */
    protected MfaService $mfaService;

    /**
     * Crea una nueva instancia de MfaController.
     *
     * @param \App\Services\MfaService $mfaService Servicio para el flujo MFA.
     */
    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Muestra la página del desafío MFA correspondiente al factor activo.
     *
     * Si no hay un usuario en sesión MFA (`mfa:user_id`), redirige al formulario de login.
     * Ofusca el correo del usuario antes de pasarlo a la vista por razones de privacidad.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse Retorna la vista del desafío o redirección.
     */
    public function create(): Response|RedirectResponse
    {
        if (!session()->has('mfa:user_id')) {
            return redirect()->route('login');
        }

        $userId = session()->get('mfa:user_id');
        $user = User::findOrFail($userId);
        
        $activeMethod = session()->get('mfa:active_method', 'email_otp');

        // Ofuscar parte del correo para mejorar la privacidad en la UI
        $emailParts = explode('@', $user->email);
        $maskedEmail = substr($emailParts[0], 0, 3) . '***@' . $emailParts[1];

        return Inertia::render('Auth/MfaChallenge', [
            'status' => session('status'),
            'email' => $maskedEmail,
            'activeMethod' => $activeMethod,
        ]);
    }

    /**
     * Verifica la respuesta al desafío de seguridad (OTP o Ubicación).
     *
     * Valida de manera condicional según el método activo:
     * - Coordenadas (latitud, longitud) e IP para verificación híbrida por ubicación.
     * - Código de 6 caracteres para verificación OTP de correo.
     * Si la verificación es correcta, avanza al siguiente factor o inicia sesión oficialmente.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con el código o las coordenadas.
     * @return \Illuminate\Http\RedirectResponse Redirección a la siguiente verificación de MFA o al Home.
     * @throws \Illuminate\Validation\ValidationException Si el código OTP es incorrecto/expirado o si la ubicación no está autorizada.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!session()->has('mfa:user_id')) {
            return redirect()->route('login');
        }

        $userId = session()->get('mfa:user_id');
        $user = User::findOrFail($userId);
        $activeMethod = session()->get('mfa:active_method', 'email_otp');

        // Validación condicional según el método de autenticación activo
        if ($activeMethod === 'location') {
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ], [
                'latitude.required' => 'La latitud es requerida para verificar tu ubicación.',
                'latitude.numeric' => 'La latitud debe ser un número válido.',
                'longitude.required' => 'La longitud es requerida para verificar tu ubicación.',
                'longitude.numeric' => 'La longitud debe ser un número válido.',
            ]);

            $payload = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'ip' => $request->ip(),
            ];
        } else {
            $request->validate([
                'code' => 'required|string|size:6',
            ], [
                'code.required' => 'El código de verificación es obligatorio.',
                'code.size' => 'El código debe tener exactamente 6 dígitos.',
            ]);

            $payload = $request->code;
        }

        // Verificamos el factor de autenticación actual
        $isValid = $this->mfaService->verifyChallenge($user, $activeMethod, $payload);

        if ($isValid) {
            Log::channel('auth')->info('Factor de autenticación MFA verificado con éxito.', [
                'user' => $user->email,
                'factor' => $activeMethod,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'datetime' => now(),
            ]);

            // Agregamos el método actual a la lista de factores completados en la sesión
            session()->push('mfa:completed_factors', $activeMethod);

            // Consultamos si queda algún factor pendiente por cumplir
            $nextFactor = $this->mfaService->getNextPendingFactor($user);

            if ($nextFactor) {
                // Hay otro factor pendiente de verificar (ej. 'location')
                $this->mfaService->sendChallenge($user, $nextFactor);
                session()->put('mfa:active_method', $nextFactor);

                return redirect()->route('auth.mfa')->with('status', 'Paso verificado. Por favor, completa el siguiente factor de seguridad.');
            }

            // Si se superaron todos los factores, se loguea oficialmente al usuario
            Auth::login($user, session()->get('mfa:remember', false));

            // Regeneramos la sesión por seguridad
            $request->session()->regenerate();

            // Limpiamos las variables de sesión del flujo MFA
            $request->session()->forget(['mfa:completed_factors', 'mfa:user_id', 'mfa:remember', 'mfa:active_method']);

            Log::channel('session')->info('new user session registered (MFA passed)', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user' => $user->email,
            ]);

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        if ($activeMethod === 'location') {
            Log::channel('auth')->warning('Intento fallido de verificación de ubicación híbrida 3FA.', [
                'user' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'datetime' => now(),
            ]);

            throw ValidationException::withMessages([
                'latitude' => 'Tu ubicación física o red de conexión no están autorizadas para esta oficina.',
            ]);
        }

        Log::channel('auth')->warning('Se intento iniciar sesión con un código OTP incorrecto.', [
            'user' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'datetime' => now(),
        ]);
            
        throw ValidationException::withMessages([
            'code' => 'El código ingresado es incorrecto, ha expirado o no coincide con tu sesión.',
        ]);
    }

    /**
     * Vuelve a enviar el desafío OTP de verificación.
     *
     * Solicita al servicio MFA que despache un nuevo código al correo del usuario y retorna a la vista del reto.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP.
     * @return \Illuminate\Http\RedirectResponse Redirección hacia atrás con el estado del envío.
     */
    public function resend(Request $request): RedirectResponse
    {
        if (!session()->has('mfa:user_id')) {
            return redirect()->route('login');
        }

        $userId = session()->get('mfa:user_id');
        $user = User::findOrFail($userId);
        $activeMethod = session()->get('mfa:active_method', 'email_otp');

        // Re-enviamos el desafío según el método activo actual
        $this->mfaService->sendChallenge($user, $activeMethod);

        return back()->with('status', 'Se ha enviado un nuevo código de verificación.');
    }
}
