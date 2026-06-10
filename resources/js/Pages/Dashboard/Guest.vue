<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    user: {
        type: Object,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Acceso Restringido" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-xl text-gray-900 leading-tight">
                    Dashboard &mdash; Invitado
                </h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200 shadow-sm animate-pulse">
                    Acceso Limitado
                </span>
            </div>
        </template>

        <div class="py-12 bg-gray-50/50 min-h-[calc(100vh-6.5rem)]">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Tarjeta de Advertencia Principal / Pendiente de Aprobación -->
                <div class="bg-white border border-amber-200 overflow-hidden shadow-sm rounded-2xl p-8 text-center space-y-6">
                    <!-- Icono Animado de Pendiente -->
                    <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-amber-50 border border-amber-100 text-amber-500 shadow-sm animate-bounce">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <!-- Mensaje e Información -->
                    <div class="space-y-3">
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ status }}
                        </h3>
                        <p class="text-sm font-semibold text-gray-500">
                            Hola {{ user.name }}, tu dirección de correo es <span class="text-gray-700 font-bold">{{ user.email }}</span>.
                        </p>
                        <p class="text-gray-700 text-sm max-w-lg mx-auto leading-relaxed">
                            {{ message }}
                        </p>
                    </div>

                    <!-- Línea de Soporte -->
                    <div class="border-t border-gray-100 pt-6 text-xs text-gray-500">
                        Si crees que esto es un error o necesitas ayuda con tu asignación, comunícate con el equipo de soporte de tu oficina central.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
