<?php

namespace App\Services;

use App\Models\User;

class MfaService
{
    /**
     * Determine if the user requires MFA based on their role.
     */
    public function requiresMfa(User $user): bool
    {
        // El rol 'guest' NO requiere doble factor.
        // Cualquier otro rol (como 'user' o 'super-admin') sí requiere doble factor.
        return ! $user->hasRole('guest');
    }

    /**
     * Get available MFA methods for the user.
     * En el futuro, esta lista puede guardarse en la base de datos por usuario.
     */
    public function getAvailableMethods(User $user): array
    {
        // Diseñado de forma escalable para soportar múltiples métodos en el futuro.
        // Ejemplo: ['email_otp', 'totp', 'sms_otp', 'webauthn_passkey']
        return ['email_otp'];
    }

    /**
     * Send/initialize the challenge for a specific method.
     */
    public function sendChallenge(User $user, string $method): bool
    {
        if ($method === 'email_otp') {
            $user->sendOneTimePassword();
            return true;
        }

        // Futuros métodos:
        // if ($method === 'sms_otp') { ... }
        // if ($method === 'totp') { ... } // TOTP no envía nada, el usuario lo genera en su app.

        return false;
    }

    /**
     * Verify the challenge code.
     */
    public function verifyChallenge(User $user, string $method, string $code): bool
    {
        if ($method === 'email_otp') {
            $result = $user->attemptLoginUsingOneTimePassword($code);
            return $result->isOk();
        }

        // Futuros métodos:
        // if ($method === 'totp') { ... }
        // if ($method === 'sms_otp') { ... }

        return false;
    }
}
