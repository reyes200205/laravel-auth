<template>

    <Head title="Panel de Administración" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    Dashboard &mdash; Super Administrador
                </h2>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200 shadow-sm">
                    Modo Control Total
                </span>
            </div>
        </template>

        <div class="py-12 bg-gray-50/50 min-h-[calc(100vh-6.5rem)]">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Tarjeta de Bienvenida Principal -->
                <div class="bg-white border border-gray-200 overflow-hidden shadow-sm rounded-2xl p-6 relative">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-indigo-50 rounded-bl-full -z-10"></div>
                    <div class="flex items-center space-x-4">
                        <div
                            class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md font-bold text-xl">
                            {{ user.name[0] }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">
                                ¡Bienvenido de nuevo, {{ user.name }}!
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                Correo: <span class="font-medium text-gray-700">{{ user.email }}</span> &bull; Rol
                                asignado:
                                <span class="font-semibold text-indigo-600 uppercase">{{ user.role }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cuadrícula de KPIs -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- KPI 1: Usuarios -->
                    <div
                        class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold text-gray-500">Usuarios Totales</span>
                            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900">{{ totalUsers }}</div>
                        <p class="text-xs text-green-600 font-medium mt-1">Registrados en el sistema</p>
                    </div>

                    <!-- KPI 2: Oficinas -->
                    <div
                        class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold text-gray-500">Oficinas Geocercadas</span>
                            <div
                                class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900">{{ totalOffices }}</div>
                        <p class="text-xs text-gray-500 font-medium mt-1">Oficinas UTT y Casa activas</p>
                    </div>

                    <!-- KPI 3: Alertas de Intentos Fallidos -->
                    <div
                        class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold text-gray-500">Alertas de Acceso (Fail)</span>
                            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-extrabold text-gray-900">{{ failedLoginsCount }}</div>
                        <p class="text-xs text-rose-600 font-medium mt-1">Registradas en logs (auth.log)</p>
                    </div>

                    <!-- KPI 4: Estado del Sistema -->
                    <div
                        class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:scale-[1.01] transition-all duration-200">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-semibold text-gray-500">Estado de Seguridad</span>
                            <div
                                class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-extrabold text-indigo-600 uppercase text-lg">{{ systemStatus }}</div>
                        <p class="text-xs text-indigo-600 font-medium mt-1">Protección activa Turnstile + 3FA</p>
                    </div>
                </div>

                <!-- Registro Rápido del Servidor -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        Auditoría y Parámetros del Sistema
                    </h3>
                    <div class="border-t border-gray-100 pt-4 space-y-3 text-sm text-gray-600">
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="font-medium">Servicio MFA:</span>
                            <span class="text-gray-900">Activo (Filtro por Rol super-admin)</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50">
                            <span class="font-medium">Fórmula de Geolocalización:</span>
                            <span class="text-gray-900">Haversine (Cálculo de Distancia en Servidor)</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="font-medium">Protección de Captcha:</span>
                            <span class="text-gray-900">Turnstile (Cloudflare) Habilitado</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
        Head,
    },
    props: {
        user: {
            type: Object,
            required: true,
        },
        totalUsers: {
            type: Number,
            required: true,
        },
        totalOffices: {
            type: Number,
            required: true,
        },
        failedLoginsCount: {
            type: Number,
            required: true,
        },
        systemStatus: {
            type: String,
            required: true,
        },
    },
};
</script>