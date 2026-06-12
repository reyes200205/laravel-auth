<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

/**
 * Controlador para gestionar el acceso al panel de control (Dashboard).
 *
 * Delega la resolución y renderización del dashboard según el rol del usuario autenticado
 * hacia el servicio DashboardService.
 */
class DashboardController extends Controller
{
    /**
     * Servicio de gestión del panel de control.
     *
     * @var \App\Services\DashboardService
     */
    protected DashboardService $dashboardService;

    /**
     * Crea una nueva instancia de DashboardController.
     *
     * @param \App\Services\DashboardService $dashboardService Servicio para delegar el renderizado.
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Muestra el panel de control correspondiente al rol del usuario autenticado.
     *
     * Invoca el método render del servicio DashboardService pasándole el usuario actual
     * para resolver la vista y datos correspondientes de Inertia.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP entrante con el usuario autenticado.
     * @return \Inertia\Response Respuesta de renderizado de vista Inertia.
     */
    public function index(Request $request): InertiaResponse
    {
        return $this->dashboardService->render($request->user());
    }
}
