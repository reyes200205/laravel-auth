<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    status: {
        type: String,
        default: '',
    },
});

const digits = ref(['', '', '', '', '', '']);
const inputs = ref([]);

const form = useForm({
    code: '',
});

const formResend = useForm({});

// Combina los dígitos individuales en un solo código
const combinedCode = computed(() => digits.value.join(''));

const countdown = ref(60);
const timerActive = ref(true);
let timer = null;

const startTimer = () => {
    countdown.value = 60;
    timerActive.value = true;
    if (timer) clearInterval(timer);
    timer = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        } else {
            timerActive.value = false;
            clearInterval(timer);
        }
    }, 1000);
};

onMounted(() => {
    startTimer();
    // Enfoca el primer input de forma automática
    if (inputs.value[0]) {
        inputs.value[0].focus();
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const handleInput = (index, event) => {
    const val = event.target.value;
    
    // Solo permitir números
    if (!/^\d*$/.test(val)) {
        digits.value[index] = '';
        return;
    }

    digits.value[index] = val.slice(-1);

    // Mueve el foco al siguiente input si está lleno
    if (digits.value[index] && index < 5) {
        inputs.value[index + 1].focus();
    }

    // Si todos los dígitos están completos, envía de forma automática
    if (digits.value.every(d => d !== '')) {
        submit();
    }
};

const handleKeyDown = (index, event) => {
    // Si presiona Backspace y el input actual está vacío, regresa al anterior
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
        digits.value[index - 1] = '';
        inputs.value[index - 1].focus();
    }
};

const handlePaste = (event) => {
    event.preventDefault();
    const pasteData = event.clipboardData.getData('text').trim();
    
    // Si pegan un código de 6 dígitos
    if (/^\d{6}$/.test(pasteData)) {
        for (let i = 0; i < 6; i++) {
            digits.value[i] = pasteData[i];
        }
        submit();
    }
};

const submit = () => {
    form.code = combinedCode.value;
    form.post(route('auth.mfa'), {
        onError: () => {
            // Limpia los inputs y enfoca el primero en caso de error
            digits.value = ['', '', '', '', '', ''];
            if (inputs.value[0]) {
                inputs.value[0].focus();
            }
        }
    });
};

const resendCode = () => {
    if (timerActive.value) return;

    formResend.post(route('auth.mfa.resend'), {
        onSuccess: () => {
            startTimer();
            digits.value = ['', '', '', '', '', ''];
            if (inputs.value[0]) {
                inputs.value[0].focus();
            }
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Verificación de Identidad" />

        <div class="mb-6 text-center">
            <!-- Icono decorativo con animación -->
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 mb-4 animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                </svg>
            </div>
            
            <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                Verifica tu identidad
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Hemos enviado un código temporal de 6 dígitos a la dirección:<br>
                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ email }}</span>
            </p>
        </div>

        <div v-if="status" class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900 rounded-lg text-sm text-emerald-600 dark:text-emerald-400 text-center font-medium">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Contenedor de Inputs Segmentados -->
            <div class="flex justify-center gap-2 sm:gap-3" @paste="handlePaste">
                <input
                    v-for="(digit, index) in digits"
                    :key="index"
                    ref="inputs"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="1"
                    v-model="digits[index]"
                    @input="handleInput(index, $event)"
                    @keydown="handleKeyDown(index, $event)"
                    class="w-12 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:focus:ring-indigo-500/10 focus:scale-105 transition-all duration-200 text-gray-900 dark:text-white shadow-sm"
                    autocomplete="one-time-code"
                />
            </div>

            <!-- Error del código principal -->
            <div class="text-center">
                <InputError class="mt-2 text-sm font-medium" :message="form.errors.code" />
            </div>

            <div class="flex flex-col items-center gap-3">
                <PrimaryButton 
                    class="w-full justify-center h-11 text-sm font-semibold tracking-wide" 
                    :class="{ 'opacity-50': form.processing || combinedCode.length < 6 }" 
                    :disabled="form.processing || combinedCode.length < 6"
                >
                    <span v-if="form.processing">Verificando...</span>
                    <span v-else>Verificar Código</span>
                </PrimaryButton>

                <!-- Botón/Texto de Reenvío -->
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <span v-if="timerActive">
                        ¿No recibiste el código? Reenviar en <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ countdown }}s</span>
                    </span>
                    <button
                        v-else
                        type="button"
                        @click="resendCode"
                        class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors duration-150 focus:outline-none focus:underline"
                        :disabled="formResend.processing"
                    >
                        <span v-if="formResend.processing">Enviando nuevo código...</span>
                        <span v-else>Reenviar código de verificación</span>
                    </button>
                </div>
            </div>
            
            <div class="text-center">
                <Link
                    :href="route('login')"
                    class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 underline transition-colors duration-150"
                >
                    Volver al inicio de sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
