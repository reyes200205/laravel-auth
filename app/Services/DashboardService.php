<?php

namespace App\Services;

use App\Models\User;
use App\Services\Dashboard\DashboardRoleService;
use App\Services\Dashboard\SuperAdminDashboardService;
use App\Services\Dashboard\UserDashboardService;
use App\Services\Dashboard\GuestDashboardService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DashboardService
{
    /**
     * Resolves the proper Dashboard service based on the user's role.
     *
     * @param User $user
     * @return DashboardRoleService
     */
    public function resolveService(User $user): DashboardRoleService
    {
        if ($user->hasRole('super-admin')) {
            return new SuperAdminDashboardService();
        }

        if ($user->hasRole('user')) {
            return new UserDashboardService();
        }

        // Por defecto, se maneja como invitado
        return new GuestDashboardService();
    }

    /**
     * Renders the corresponding Inertia dashboard view with its specific data.
     *
     * @param User $user
     * @return InertiaResponse
     */
    public function render(User $user): InertiaResponse
    {
        $service = $this->resolveService($user);
        
        $view = $service->getView();
        $data = $service->getData($user);

        // Pasamos el usuario y su rol de vuelta por si las vistas compartidas lo requieren
        $data['user'] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->pluck('name')->first() ?? 'guest',
        ];

        return Inertia::render($view, $data);
    }
}
