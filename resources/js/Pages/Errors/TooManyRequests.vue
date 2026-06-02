<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

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

const formattedTime = computed(() => {
    const mins = Math.floor(remainingSeconds.value / 60);
    const secs = remainingSeconds.value % 60;
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
});
</script>

<template>
    <Head title="Acceso Restringido" />

    <div class="min-h-screen bg-slate-950 flex flex-col justify-center items-center p-6 relative overflow-hidden select-none">
        <!-- Fondos de brillo difusos (Glow Effects) -->
        <div class="absolute w-96 h-96 bg-red-600/10 rounded-full blur-[100px] -top-12 -left-12"></div>
        <div class="absolute w-96 h-96 bg-amber-500/10 rounded-full blur-[100px] -bottom-12 -right-12"></div>

        <div class="w-full max-w-md bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 rounded-2xl p-8 shadow-2xl relative z-10 text-center">
            
            <!-- Icono de Seguridad Animado -->
            <div class="mx-auto w-24 h-24 mb-6 flex items-center justify-center rounded-2xl bg-gradient-to-tr from-red-500/20 to-amber-500/20 border border-red-500/30 shadow-[0_0_20px_rgba(239,68,68,0.15)] animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <!-- Título -->
            <h1 class="text-3xl font-extrabold text-white tracking-tight mb-2">
                Peticiones Excedidas
            </h1>
            <p class="text-slate-400 text-sm mb-6">
                Tu dirección IP ha sido temporalmente restringida debido a demasiados intentos de solicitud.
            </p>

            <!-- Cronómetro de Cuenta Regresiva -->
            <div class="bg-slate-950/80 border border-slate-800/60 rounded-xl py-6 px-4 mb-8">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1">
                    Tiempo de Espera Restante
                </div>
                <div class="text-4xl font-mono font-bold bg-gradient-to-r from-red-400 to-amber-400 bg-clip-text text-transparent">
                    {{ formattedTime }}
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="space-y-3">
                <button
                    @click="retry"
                    :disabled="!isTimeUp"
                    class="w-full py-3.5 px-4 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center space-x-2"
                    :class="isTimeUp 
                        ? 'bg-gradient-to-r from-red-600 to-amber-600 text-white hover:from-red-500 hover:to-amber-500 shadow-[0_4px_20px_rgba(239,68,68,0.3)] active:scale-[0.98]' 
                        : 'bg-slate-800 text-slate-500 cursor-not-allowed border border-slate-700/50'"
                >
                    <span v-if="!isTimeUp">Espera para reintentar</span>
                    <span v-else class="flex items-center gap-2 justify-center w-full">
                        Reintentar ahora
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </button>

                <Link
                    v-if="!isTimeUp"
                    :href="route('login')"
                    class="block text-sm text-slate-500 hover:text-slate-300 transition-colors py-2"
                >
                    Volver al Inicio
                </Link>
            </div>
        </div>

        <!-- Marca de seguridad sutil -->
        <div class="absolute bottom-6 text-xs text-slate-600 flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M2.166 4.9L10 1.154 17.834 4.9a1 1 0 01.616.92v5.336c0 5.228-3.46 9.642-8 10.748-4.54-1.106-8-5.52-8-10.748V5.82a1 1 0 01.616-.92zM10 11.882l3.293-3.293a1 1 0 00-1.414-1.414L10 9.054 8.121 7.175a1 1 0 00-1.414 1.414L10 11.882z" clip-rule="evenodd" />
            </svg>
            Conexión Segura &bull; Sistema de Protección Activo
        </div>
    </div>
</template>
