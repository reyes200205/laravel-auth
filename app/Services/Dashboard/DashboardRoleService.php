<?php

namespace App\Services\Dashboard;

use App\Models\User;

interface DashboardRoleService
{
    /**
     * Get the Inertia view component name for the dashboard.
     */
    public function getView(): string;

    /**
     * Get the data required for this dashboard view.
     *
     * @param User $user
     * @return array
     */
    public function getData(User $user): array;
}
