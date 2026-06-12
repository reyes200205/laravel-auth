<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Office;
use Illuminate\Support\Facades\File;

class SuperAdminDashboardService implements DashboardRoleService
{
    /**
     * Obtiene la ruta de la vista de Inertia correspondiente al dashboard del SuperAdmin.
     *
     * @return string Ruta de la vista.
     */
    public function getView(): string
    {
        return 'Dashboard/SuperAdmin';
    }

    /**
     * Obtiene los datos necesarios para renderizar el dashboard del SuperAdmin.
     *
     * Calcula el total de usuarios registrados, el total de oficinas, y cuenta los
     * intentos de login fallidos registrados en el archivo de log `auth.log`.
     *
     * @param \App\Models\User $user El usuario autenticado (aunque no se usa directamente en el cálculo).
     * @return array Datos a inyectar en la vista.
     */
    public function getData(User $user): array
    {
        $totalUsers = User::count();
        $totalOffices = Office::count();

        $failedLoginsCount = 0;
        $logPath = storage_path('logs/auth.log');
        if (File::exists($logPath)) {
            $content = File::get($logPath);
            $failedLoginsCount = substr_count($content, 'Intento de login fallido') 
                               + substr_count($content, 'incorrecto');
        }

        return [
            'totalUsers' => $totalUsers,
            'totalOffices' => $totalOffices,
            'failedLoginsCount' => $failedLoginsCount,
            'systemStatus' => 'Active',
        ];
    }
}
