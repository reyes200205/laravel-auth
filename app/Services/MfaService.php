<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Servicio para gestionar la lógica de autenticación multifactor (MFA/3FA).
 *
 * Este servicio determina los factores de seguridad requeridos por el rol del usuario,
 * identifica el siguiente factor pendiente, maneja el envío de desafíos (OTP o ubicación interactiva)
 * y realiza validaciones híbridas que involucran direcciones IP permitidas y coordenadas GPS
 * mediante la fórmula de Haversine.
 */
class MfaService
{
    /**
     * Define los tipos de factores requeridos según el rol del usuario.
     *
     * - `super-admin`: requiere contraseña, OTP por correo y geolocalización (3 factores).
     * - `user`: requiere contraseña y OTP por correo (2 factores).
     * - Otros roles (p. ej., `guest`): requieren únicamente contraseña (1 factor).
     *
     * @param \App\Models\User $user El usuario evaluado.
     * @return array<string> Lista de factores requeridos.
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
     * Obtiene el siguiente factor de autenticación pendiente para el usuario.
     *
     * Compara los factores requeridos con los ya completados almacenados en la sesión.
     *
     * @param \App\Models\User $user El usuario evaluado.
     * @return string|null El nombre del siguiente factor pendiente (ej. 'email_otp', 'location') o null si se completaron todos.
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
     * Determina si el usuario requiere MFA basado en su rol.
     *
     * Retorna verdadero para cualquier usuario que no sea un invitado ('guest').
     *
     * @param \App\Models\User $user El usuario evaluado.
     * @return bool Verdadero si requiere MFA, falso de lo contrario.
     */
    public function requiresMfa(User $user): bool
    {
        return ! $user->hasRole('guest');
    }

    /**
     * Obtiene la lista completa de métodos de MFA disponibles en el sistema.
     *
     * @param \App\Models\User $user El usuario evaluado.
     * @return array<string> Lista de métodos de MFA (ej. ['email_otp', 'location']).
     */
    public function getAvailableMethods(User $user): array
    {
        return ['email_otp', 'location'];
    }

    /**
     * Envía o inicializa el desafío de seguridad para un método MFA específico.
     *
     * - Para `email_otp`, genera y envía la contraseña de un solo uso al correo.
     * - Para `location`, no requiere acción del servidor ya que es administrada de forma interactiva en el cliente.
     *
     * @param \App\Models\User $user El usuario al que se envía el desafío.
     * @param string $method Método de autenticación activo (ej. 'email_otp', 'location').
     * @return bool Verdadero si el desafío fue enviado/inicializado exitosamente, falso de lo contrario.
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
     * Verifica la respuesta al desafío de seguridad o carga útil (payload).
     *
     * @param \App\Models\User $user El usuario evaluado.
     * @param string $method Método de autenticación a verificar (ej. 'email_otp', 'location').
     * @param mixed $payload Código de un solo uso (string) o arreglo de datos de ubicación/IP.
     * @return bool Verdadero si la verificación fue exitosa, falso de lo contrario.
     */
    public function verifyChallenge(User $user, string $method, $payload): bool
    {
        if ($method === 'email_otp') {
            if (!is_string($payload)) {
                return false;
            }
            return $this->verifyEmailOtp($user, $payload);
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
     * Verifica la respuesta al desafío de correo mediante OTP de un solo uso y registra logs detallados de fallos.
     *
     * @param \App\Models\User $user El usuario evaluado.
     * @param string $payload Código de un solo uso.
     * @return bool Verdadero si el código OTP fue consumido con éxito, falso de lo contrario.
     */
    protected function verifyEmailOtp(User $user, string $payload): bool
    {
        $result = $user->consumeOneTimePassword($payload);
        
        if (!$result->isOk()) {
            $log = Log::channel('auth');
            
            switch ($result) {
                case ConsumeOneTimePasswordResult::OneTimePasswordExpired:
                    $log->warning('Intento de verificación de MFA fallido: El código OTP ha expirado.', [
                        'user' => $user->email,
                        'datetime' => now(),
                    ]);
                    break;
                case ConsumeOneTimePasswordResult::IncorrectOneTimePassword:
                    $log->warning('Intento de verificación de MFA fallido: El código OTP es incorrecto.', [
                        'user' => $user->email,
                        'datetime' => now(),
                    ]);
                    break;
                case ConsumeOneTimePasswordResult::RateLimitExceeded:
                    $log->warning('Intento de verificación de MFA fallido: Límite de intentos excedido.', [
                        'user' => $user->email,
                        'datetime' => now(),
                    ]);
                    break;
                default:
                    $log->warning("Intento de verificación de MFA fallido: Error ({$result->value}).", [
                        'user' => $user->email,
                        'datetime' => now(),
                    ]);
                    break;
            }
        }
        
        return $result->isOk();
    }

    /**
     * Verifica de forma híbrida si la IP y coordenadas GPS coinciden con los datos autorizados de la oficina del usuario.
     *
     * Compara la IP del cliente con la lista de IPs permitidas en la oficina configurada.
     * Adicionalmente, calcula la distancia geodésica entre las coordenadas GPS del cliente y las del edificio.
     *
     * @param \App\Models\User $user El usuario evaluado (debe tener oficina asociada).
     * @param array $payload Arreglo con la IP y las coordenadas del cliente.
     * @return bool Verdadero si la IP está autorizada y la distancia física es menor o igual al radio de la oficina.
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

        // Rechazar direcciones IPv6 (solo se permite IPv4)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
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
     * Calcula la distancia en metros entre dos coordenadas geográficas usando la fórmula de Haversine.
     *
     * @param float $lat1 Latitud del punto de partida.
     * @param float $lon1 Longitud del punto de partida.
     * @param float $lat2 Latitud del punto de destino.
     * @param float $lon2 Longitud del punto de destino.
     * @return float Distancia geodésica calculada en metros.
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
