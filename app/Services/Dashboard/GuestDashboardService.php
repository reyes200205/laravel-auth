<?php

namespace App\Services\Dashboard;

use App\Models\User;

class GuestDashboardService implements DashboardRoleService
{
    public function getView(): string
    {
        return 'Dashboard/Guest';
    }

    public function getData(User $user): array
    {
        return [
            'status' => 'Pendiente de aprobación',
            'message' => 'Tu registro fue exitoso con el rol de Invitado (Guest). Por favor, contacta a un administrador para que te asigne una oficina y active tus accesos completos.',
        ];
    }
}
