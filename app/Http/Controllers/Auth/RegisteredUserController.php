<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Rules\Turnstile;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar el registro de nuevos usuarios en el sistema.
 */
class RegisteredUserController extends Controller
{
    /**
     * Muestra la vista de registro de usuario.
     *
     * @return \Inertia\Response Vista de Inertia para el formulario de registro.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Procesa la solicitud de registro de un nuevo usuario.
     *
     * Valida los datos provistos (nombre, email único, robustez de la contraseña y validación del captcha de Turnstile si está activo).
     * Crea el registro en la base de datos, le asigna el rol 'guest' por defecto, genera logs de auditoría de seguridad,
     * dispara el evento `Registered`, realiza el inicio de sesión automático y redirige al Home.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con los datos de registro y el token del captcha.
     * @return \Illuminate\Http\RedirectResponse Redirección a la ruta de inicio (Home).
     * @throws \Illuminate\Validation\ValidationException Si falla la validación de campos o del captcha.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#.\-_]).{8,}$/'],
            'cf-turnstile-response' => [
                config('services.turnstile.enabled') ? 'required' : 'nullable',
                new Turnstile
            ],
        ], [
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres y contener al menos una mayúscula, una minúscula, un número y un carácter especial.',
            'cf-turnstile-response.required' => 'Por favor, completa el captcha de seguridad.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('guest');


        Log::channel('auth')->info('New User registred In The System', [
            'action' => 'Register',
            'resource_id' => $user->id,
            'resource_type' => 'User',
            'data' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);



        Log::channel('session')->info('User registered successfully', [
            'user' => $user->email,
            'name' => $user->name,
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
