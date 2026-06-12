<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar el envío de enlaces de restablecimiento de contraseña.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Muestra la vista para solicitar el restablecimiento de contraseña.
     *
     * @return \Inertia\Response Vista de Inertia para solicitar el enlace.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Procesa la solicitud y envía el enlace de restablecimiento al correo del usuario.
     *
     * Valida la presencia de un correo válido y solicita al proveedor de contraseñas de Laravel
     * que envíe el enlace de reinicio.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con el email del usuario.
     * @return \Illuminate\Http\RedirectResponse Redirección de regreso con el estado del proceso.
     * @throws \Illuminate\Validation\ValidationException Si el correo no es válido o no se encuentra registrado.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
