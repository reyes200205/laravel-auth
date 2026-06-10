<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    user: {
        type: Object,
        required: true,
    },
    officeName: {
        type: String,
        required: true,
    },
    officeCoordinates: {
        type: Object,
        nullable: true,
    },
    mfaStatus: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Dashboard Usuario" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    Dashboard &mdash; Usuario
                </h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-sm">
                    Acceso Autorizado
                </span>
            </div>
        </template>

        <div class="py-12 bg-gray-50/50 min-h-[calc(100vh-6.5rem)]">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Tarjeta de Bienvenida Principal -->
                <div class="bg-white border border-gray-200 overflow-hidden shadow-sm rounded-2xl p-6 relative">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-emerald-50 rounded-bl-full -z-10"></div>
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white shadow-md font-bold text-xl">
                            {{ user.name[0] }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">
                                ¡Bienvenido de nuevo, {{ user.name }}!
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                Correo: <span class="font-medium text-gray-700">{{ user.email }}</span> &bull; Rol asignado: <span class="font-semibold text-emerald-600 uppercase">{{ user.role }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tarjeta de Oficina Asignada -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            Oficina y Ubicación de Operación
                        </h3>
                        
                        <div class="space-y-4 pt-2">
                            <div>
                                <span class="text-xs font-semibold text-gray-400 uppercase block">Nombre de la Oficina</span>
                                <span class="text-base font-bold text-gray-800">{{ officeName }}</span>
                            </div>
                            
                            <div v-if="officeCoordinates" class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-4">
                                <div>
                                    <span class="text-xs font-semibold text-gray-400 uppercase block">Latitud</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ officeCoordinates.latitude }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-400 uppercase block">Longitud</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ officeCoordinates.longitude }}</span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-gray-400 uppercase block">Radio Permitido</span>
                                    <span class="text-sm font-semibold text-gray-800">{{ officeCoordinates.radius }} metros</span>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic border-t border-gray-100 pt-4">
                                No tienes una oficina geográfica vinculada a tu perfil de acceso.
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta de Estado de Seguridad -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            Verificación de Seguridad (MFA)
                        </h3>

                        <div class="space-y-4 pt-2">
                            <div>
                                <span class="text-xs font-semibold text-gray-400 uppercase block">Estado de la Sesión</span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 mt-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    Sesión Segura Activa
                                </span>
                            </div>

                            <div class="border-t border-gray-100 pt-4 space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-650">Primer Factor (Contraseña):</span>
                                    <span class="font-bold text-emerald-600 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Completado
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-650">Segundo Factor (OTP por Correo):</span>
                                    <span class="font-bold text-emerald-600 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Completado
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
