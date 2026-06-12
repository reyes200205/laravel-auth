<?php

namespace App\Models;

use Spatie\OneTimePasswords\Models\OneTimePassword as BaseOneTimePassword;

/**
 * Modelo de datos personalizado para gestionar las contraseñas de un solo uso (OTP) con encriptación.
 */
class OneTimePassword extends BaseOneTimePassword
{
    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * Encripta automáticamente la contraseña en la base de datos y la desencripta al acceder a ella en memoria.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'origin_properties' => 'array',
        'expires_at' => 'datetime',
        'password' => 'encrypted',
    ];
}
