<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar el reenvío de notificaciones de verificación de correo electrónico.
 */
class EmailVerificationNotificationController extends Controller
{
    /**
     * Envía una nueva notificación con el enlace de verificación de correo al usuario.
     *
     * Si el usuario ya verificó su correo electrónico, redirige directamente al Home.
     * En caso contrario, despacha la notificación y retorna a la página anterior con un estado de éxito.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP del usuario autenticado.
     * @return \Illuminate\Http\RedirectResponse Redirección al Home o a la página anterior.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
