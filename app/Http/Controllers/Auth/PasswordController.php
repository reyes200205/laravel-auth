<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Controlador para actualizar la contraseña del usuario autenticado.
 */
class PasswordController extends Controller
{
    /**
     * Actualiza la contraseña del usuario.
     *
     * Valida que se provea la contraseña actual y que la nueva cumpla con los requisitos
     * de robustez (mínimo 8 caracteres, mayúscula, minúscula, número y carácter especial).
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con las contraseñas.
     * @return \Illuminate\Http\RedirectResponse Redirección hacia atrás (back).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#.\-_]).{8,}$/'],
        ], [
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres y contener al menos una mayúscula, una minúscula, un número y un carácter especial.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}
