<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

class MfaService
{
    /**
     * Define los tipos de factores requeridos según el rol del usuario
     */
    public function getRequiredFactors(User $user): array
    {
        if ($user->hasRole('super-admin')) {
            // Requiere contraseña, OTP de correo y geolocalización (3 factores)
            return ['password', 'email_otp', 'location'];
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
        return ['email_otp', 'location'];
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

        if ($method === 'location') {
            // El factor de localización es administrado por el cliente de forma interactiva ,
            // por lo que no es necesario realizar un envío activo desde el servidor.
            return true;
        }

        return false;
    }

    /**
     * Verify the challenge code or payload.
     */
    public function verifyChallenge(User $user, string $method, $payload): bool
    {
        if ($method === 'email_otp') {
            if (!is_string($payload)) {
                return false;
            }
            $result = $user->consumeOneTimePassword($payload);
            return $result->isOk();
        }

        if ($method === 'location') {
            if (!is_array($payload)) {
                return false;
            }
            return $this->verifyLocation($user, $payload);
        }

        return false;
    }

    /**
     * Verifica de forma híbrida si la IP del cliente y sus coordenadas GPS coinciden con los datos autorizados de la oficina.
     */
    protected function verifyLocation(User $user, array $payload): bool
    {
        $office = $user->office;

        // Si es super-admin pero no tiene oficina asignada, no se le permite el acceso.
        if (!$office) {
            return false;
        }

        // 1. Validar la dirección IP
        $ip = $payload['ip'] ?? null;
        if (is_null($ip)) {
            return false;
        }

        $allowedIps = array_map('trim', explode(',', $office->allowed_ips ?? ''));

        // Si no hay IPs configuradas, por seguridad denegamos el acceso
        if (empty($allowedIps) || (count($allowedIps) === 1 && $allowedIps[0] === '')) {
            return false;
        }

        // Si la IP no está dentro del rango/lista permitida, denegar
        if (!IpUtils::checkIp($ip, $allowedIps)) {
            return false;
        }

        Log::channel('auth')->info('IP del usuario autenticada exitosamente.', [
            'user' => $user->email,
            'ip' => $ip,
            'datetime' => now(),
        ]);

        // 2. Validar las coordenadas geográficas
        $userLat = $payload['latitude'] ?? null;
        $userLng = $payload['longitude'] ?? null;

        if (is_null($userLat) || is_null($userLng)) {
            return false;
        }

        $distance = $this->calculateDistance(
            (float) $userLat,
            (float) $userLng,
            (float) $office->latitude,
            (float) $office->longitude
        );

        // Retorna verdadero si la distancia es menor o igual al radio permitido
        return $distance <= (float) $office->radius;
    }

    /**
     * Calcula la distancia en metros entre dos coordenadas geográficas usando Haversine.
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Radio de la Tierra en metros

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
