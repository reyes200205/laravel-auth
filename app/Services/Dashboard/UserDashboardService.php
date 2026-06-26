<?php

namespace App\Services\Dashboard;

use App\Models\User;

class UserDashboardService implements DashboardRoleService
{
    public function getView(): string
    {
        return 'Dashboard/User';
    }

    /**
     * Obtiene los datos del dashboard específicos para un usuario regular.
     *
     * Retorna la información detallada de la oficina asignada al usuario (incluyendo
     * coordenadas, radio y direcciones IP permitidas) para permitir su visualización
     * y edición, así como la dirección IP actual detectada del cliente.
     *
     * @param \App\Models\User $user El usuario autenticado.
     * @return array<string, mixed> Datos consolidados del dashboard del usuario.
     */
    public function getData(User $user): array
    {
        $office = $user->office;

        return [
            'office' => $office ? [
                'id' => $office->id,
                'name' => $office->name,
                'latitude' => $office->latitude,
                'longitude' => $office->longitude,
                'radius' => $office->radius,
                'allowed_ips' => $office->allowed_ips,
            ] : null,
            'clientIp' => request()->ip(),
            'mfaStatus' => 'Verificado (Password + OTP)',
        ];
    }
}
