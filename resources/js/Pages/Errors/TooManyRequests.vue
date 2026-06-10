<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    seconds: {
        type: Number,
        default: 60
    }
});

const remainingSeconds = ref(props.seconds);
let intervalId = null;

onMounted(() => {
    intervalId = setInterval(() => {
        if (remainingSeconds.value > 0) {
            remainingSeconds.value--;
        } else {
            clearInterval(intervalId);
            router.visit(route('login'));
        }
    }, 1000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});

const isTimeUp = computed(() => remainingSeconds.value <= 0);

const retry = () => {
    if (isTimeUp.value) {
        router.visit(route('login'));
    }
};

const formattedTimeMessage = computed(() => {
    if (remainingSeconds.value <= 0) {
        return 'Ya puedes intentar acceder de nuevo.';
    }
    const mins = Math.ceil(remainingSeconds.value / 60);
    return `Excediste el límite de solicitudes. Intenta de nuevo en ${mins} ${mins === 1 ? 'minuto' : 'minutos'}.`;
});
</script>

<template>
    <GuestLayout>
        <Head title="Acceso Restringido" />

        <div class="text-center py-4">
            <!-- Icono de Seguridad/Alerta en Modo Claro con Alto Contraste -->
            <div class="mx-auto w-16 h-16 mb-6 flex items-center justify-center rounded-full bg-red-50 border border-red-200 text-red-600 shadow-sm animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Título -->
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight mb-3">
                Peticiones Excedidas
            </h1>
            
            <!-- Mensaje Dinámico de Tiempo Restante en Minutos -->
            <p class="text-gray-700 text-sm mb-6 leading-relaxed font-medium">
                {{ formattedTimeMessage }}
            </p>

            <!-- Botones de Acción -->
            <div class="space-y-3">
                <button
                    @click="retry"
                    :disabled="!isTimeUp"
                    class="w-full py-2.5 px-4 rounded-xl font-semibold transition-all duration-200 flex items-center justify-center space-x-2 text-sm"
                    :class="isTimeUp 
                        ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm active:scale-[0.98]' 
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'"
                >
                    <span v-if="!isTimeUp">Espera para reintentar</span>
                    <span v-else class="flex items-center gap-2 justify-center w-full">
                        Reintentar ahora
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </button>

                <Link
                    v-if="!isTimeUp"
                    :href="route('login')"
                    class="block text-xs text-gray-500 hover:text-gray-800 underline transition-colors py-2"
                >
                    Volver al Inicio
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>

