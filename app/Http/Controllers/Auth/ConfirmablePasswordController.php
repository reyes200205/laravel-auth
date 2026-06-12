<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para verificar la contraseña del usuario antes de permitir acciones sensibles.
 */
class ConfirmablePasswordController extends Controller
{
    /**
     * Muestra la vista de confirmación de contraseña.
     *
     * @return \Inertia\Response Vista de Inertia para confirmación de contraseña.
     */
    public function show(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }

    /**
     * Confirma la contraseña del usuario.
     *
     * Valida que la contraseña provista coincida con la del usuario autenticado.
     * Si es exitoso, guarda la marca de tiempo en la sesión y redirige al destino original.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con la contraseña.
     * @return \Illuminate\Http\RedirectResponse Redirección al destino deseado o al Home.
     * @throws \Illuminate\Validation\ValidationException Si la contraseña no es válida.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
