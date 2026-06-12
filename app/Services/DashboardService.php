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
 *
 * Utiliza un patrón de factoría para instanciar el servicio específico del rol del usuario,
 * abstrayendo la obtención de la vista y la carga de datos necesarios para renderizar el panel.
 */
class DashboardService
{
    /**
     * Resuelve y retorna el servicio de dashboard correspondiente basado en el rol del usuario.
     *
     * Este método actúa como una fábrica. Su objetivo principal es mapear el rol del usuario
     * a su correspondiente servicio especializado (p. ej., `SuperAdminDashboardService` para 'super-admin',
     * `UserDashboardService` para 'user', o `GuestDashboardService` para otros).
     *
     * **Diferencia con render():** A diferencia de `render()`, este método NO genera ninguna respuesta visual
     * ni maneja el flujo de respuesta HTTP. Solo se encarga de instanciar la clase de servicio adecuada
     * para delegar el control de los datos y vistas.
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
     * Este método gestiona el flujo completo de presentación: primero llama a `resolveService()` para obtener
     * el servicio del rol, luego obtiene la ruta de la vista y los datos del panel para ese rol específico,
     * inyecta los datos del perfil y rol del usuario para la plantilla compartida, y finalmente construye y retorna
     * la respuesta renderizada de Inertia.
     *
     * **Diferencia con resolveService():** A diferencia de `resolveService()`, que solo decide qué clase
     * instanciar (fábrica), `render()` ejecuta la lógica de obtención de datos del servicio obtenido, adjunta los
     * metadatos del usuario actual y produce la respuesta de presentación visual final (`InertiaResponse`).
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
