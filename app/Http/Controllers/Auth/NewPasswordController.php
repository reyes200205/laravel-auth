<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para gestionar el establecimiento de una nueva contraseña tras solicitar su restablecimiento.
 */
class NewPasswordController extends Controller
{
    /**
     * Muestra la vista para restablecer la contraseña.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP que contiene el token y el email.
     * @return \Inertia\Response Vista de Inertia para resetear la contraseña.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Procesa la solicitud para guardar la nueva contraseña del usuario.
     *
     * Valida la contraseña usando reglas de robustez complejas (mayúscula, minúscula, número, carácter especial).
     * Si el restablecimiento mediante el proveedor de contraseñas de Laravel es exitoso,
     * actualiza la base de datos, dispara el evento `PasswordReset` y redirige al login con éxito.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con el token y las nuevas credenciales.
     * @return \Illuminate\Http\RedirectResponse Redirección al login en caso de éxito.
     * @throws \Illuminate\Validation\ValidationException Si falla la validación o el token es inválido.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#.\-_]).{8,}$/'],
        ], [
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres y contener al menos una mayúscula, una minúscula, un número y un carácter especial.',
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
