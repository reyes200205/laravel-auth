<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use App\Rules\Turnstile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Petición de formulario (Form Request) para gestionar la validación del inicio de sesión.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta petición.
     *
     * @return bool Verdadero si está autorizado, falso de lo contrario.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación aplicables a la petición de inicio de sesión.
     *
     * Valida la presencia de email, contraseña y el captcha de Cloudflare Turnstile si está habilitado.
     *
     * @return array<string, array<mixed>|string|\App\Rules\Turnstile> Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => [
                config('services.turnstile.enabled') ? 'required' : 'nullable',
                new Turnstile
            ],
        ];
    }

    /**
     * Obtiene los mensajes personalizados de error de validación.
     *
     * @return array<string, string> Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'cf-turnstile-response.required' => 'Por favor, completa el captcha de seguridad.',
        ];
    }

    /**
     * Intenta autenticar las credenciales provistas en la petición.
     *
     * Verifica que no se haya superado el límite de intentos (Rate Limit), busca al usuario
     * por correo electrónico y comprueba que la contraseña sea correcta usando `Hash::check`.
     * Si falla, registra el intento fallido en los logs de auditoría y bloquea temporalmente al usuario.
     *
     * @return \App\Models\User El usuario autenticado si las credenciales son válidas.
     * @throws \Illuminate\Validation\ValidationException Si las credenciales son incorrectas o no coinciden.
     */
    public function authenticate(): \App\Models\User
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->email)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            Log::channel('auth')->warning('Intento de login fallido', [
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'user' => $this->email,
            ]);
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * Asegura que la petición de inicio de sesión no haya superado el límite de intentos permitidos (Rate Limit).
     *
     * Permite un máximo de 5 intentos. Si se supera, dispara un evento `Lockout` y lanza una excepción.
     *
     * @return void
     * @throws \Illuminate\Validation\ValidationException Si se superó el límite de intentos fallidos.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Obtiene la clave de restricción (throttle key) basada en el email y la dirección IP para el limitador de peticiones.
     *
     * @return string Clave generada para el rate limiter.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
