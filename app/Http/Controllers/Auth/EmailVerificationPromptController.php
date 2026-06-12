<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para mostrar el recordatorio/aviso de verificación de correo electrónico.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * Muestra la advertencia de verificación de correo electrónico o redirige al Home.
     *
     * Si el usuario ya verificó su cuenta, se le redirige al Home.
     * En caso contrario, se renderiza la interfaz indicándole que verifique su correo.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP del usuario autenticado.
     * @return \Illuminate\Http\RedirectResponse|\Inertia\Response Redirección al Home o la vista de verificación.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(RouteServiceProvider::HOME)
                    : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
