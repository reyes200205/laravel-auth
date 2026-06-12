<?php

namespace App\Services;

use App\Models\User;
use App\Services\Dashboard\DashboardRoleService;
use App\Services\Dashboard\SuperAdminDashboardService;
use App\Services\Dashboard\UserDashboardService;
use App\Services\Dashboard\GuestDashboardService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Servicio para orquestar la lógica de negocio y presentación de los dashboards de usuario.
 */
class DashboardService
{
    /**
     * Resuelve y retorna el servicio de dashboard correspondiente basado en el rol del usuario.
     *
     * @param \App\Models\User $user El usuario autenticado cuyo rol se evaluará.
     * @return \App\Services\Dashboard\DashboardRoleService El servicio especializado para el rol del usuario.
     */
    public function resolveService(User $user): DashboardRoleService
    {
        if ($user->hasRole('super-admin')) {
            return new SuperAdminDashboardService();
        }

        if ($user->hasRole('user')) {
            return new UserDashboardService();
        }

        return new GuestDashboardService();
    }

    /**
     * Resuelve el servicio correspondiente y renderiza la vista de Inertia con sus datos específicos.
     *
     * @param \App\Models\User $user El usuario autenticado.
     * @return \Inertia\Response Respuesta de renderizado de vista Inertia con datos consolidados.
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
