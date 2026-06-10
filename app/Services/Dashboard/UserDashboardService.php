<?php

namespace App\Services\Dashboard;

use App\Models\User;

class UserDashboardService implements DashboardRoleService
{
    public function getView(): string
    {
        return 'Dashboard/User';
    }

    public function getData(User $user): array
    {
        $office = $user->office;

        return [
            'officeName' => $office ? $office->name : 'Ninguna oficina asignada',
            'officeCoordinates' => $office ? [
                'latitude' => $office->latitude,
                'longitude' => $office->longitude,
                'radius' => $office->radius,
            ] : null,
            'mfaStatus' => 'Verificado (Password + OTP)',
        ];
    }
}
