<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Office;
use Illuminate\Support\Facades\File;

class SuperAdminDashboardService implements DashboardRoleService
{
    public function getView(): string
    {
        return 'Dashboard/SuperAdmin';
    }

    public function getData(User $user): array
    {
        $totalUsers = User::count();
        $totalOffices = Office::count();

        // Contar alertas de intentos fallidos en auth.log
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
