<?php

namespace App\Services;

use App\Models\User;

class MfaService
{
    /**
     * Define los tipos de factores requeridos según el rol del usuario
     */
    public function getRequiredFactors(User $user): array
    {
        if ($user->hasRole('super-admin')) {
            return ['password', 'email_otp'];
        }

        if ($user->hasRole('user')) {
            return ['password', 'email_otp'];
        }

        return ['password'];
    }

    /**
     * Get the next pending authentication factor.
     */
    public function getNextPendingFactor(User $user): ?string
    {
        $required = $this->getRequiredFactors($user);
        $completed = session()->get('mfa:completed_factors', []);

        foreach ($required as $factor) {
            if (!in_array($factor, $completed)) {
                return $factor;
            }
        }

        return null;
    }

    /**
     * Determine if the user requires MFA based on their role.
     */
    public function requiresMfa(User $user): bool
    {
        return ! $user->hasRole('guest');
    }

    /**
     * Get available MFA methods for the user.
     */
    public function getAvailableMethods(User $user): array
    {
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
        // if ($method === 'totp') { ... } // El TOTP no envía nada de forma activa.

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
