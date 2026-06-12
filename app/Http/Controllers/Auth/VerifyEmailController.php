<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador para gestionar la verificación del correo electrónico del usuario.
 */
class VerifyEmailController extends Controller
{
    /**
     * Marca la dirección de correo electrónico del usuario autenticado como verificada.
     *
     * Si el usuario ya verificó su correo electrónico anteriormente, redirige directamente al Home.
     * De lo contrario, lo marca como verificado, dispara el evento `Verified` y redirige al Home con un flag de éxito.
     *
     * @param \Illuminate\Foundation\Auth\EmailVerificationRequest $request Petición de verificación firmada de Laravel.
     * @return \Illuminate\Http\RedirectResponse Redirección al Home.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
    }
}
