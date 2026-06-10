<template>
    <div>
        <div class="mb-6 text-center">
            <!-- Icono decorativo con animación -->
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-50 text-indigo-600 mb-4 animate-bounce">
                <!-- Icono de Correo para OTP por email -->
                <svg v-if="activeMethod === 'email_otp'" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 19v-8.93a2 2 0 01.89-1.664l8-5.333a2 2 0 012.22 0l8 5.333A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
                </svg>
                <!-- Icono de Escudo/Llave para TOTP (Google Authenticator, etc.) -->
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            
            <h2 class="text-xl font-bold text-gray-950">
                Verifica tu identidad
            </h2>
            
            <p v-if="activeMethod === 'email_otp'" class="mt-2 text-sm text-gray-700 leading-relaxed">
                Hemos enviado un código temporal de 6 dígitos a la dirección:<br>
                <span class="font-semibold text-gray-800">{{ email }}</span>
            </p>
            <p v-else-if="activeMethod === 'totp'" class="mt-2 text-sm text-gray-700 leading-relaxed">
                Abre tu aplicación de autenticación (Google Authenticator, Authy, etc.) e ingresa el código temporal de 6 dígitos generado.
            </p>
            <p v-else-if="activeMethod === 'sms_otp'" class="mt-2 text-sm text-gray-700 leading-relaxed">
                Hemos enviado un mensaje de texto SMS con tu código de verificación.
            </p>
            <p v-else class="mt-2 text-sm text-gray-700 leading-relaxed">
                Ingresa tu código de verificación de 6 dígitos.
            </p>
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
                    class="w-12 h-14 sm:w-14 sm:h-16 text-center text-xl sm:text-2xl font-bold bg-white border border-gray-300 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:scale-105 transition-all duration-200 text-gray-900 shadow-sm"
                    autocomplete="one-time-code"
                />
            </div>

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

                <!-- Botón/Texto de Reenvío (Solo aplicable para OTP de email o sms) -->
                <div v-if="activeMethod === 'email_otp' || activeMethod === 'sms_otp'" class="text-xs text-gray-650">
                    <span v-if="timerActive">
                        ¿No recibiste el código? Reenviar en <span class="font-bold text-indigo-600">{{ countdown }}s</span>
                    </span>
                    <button
                        v-else
                        type="button"
                        @click="resendCode"
                        class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors duration-150 focus:outline-none focus:underline"
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
                    class="text-xs text-gray-650 hover:text-gray-800 underline transition-colors duration-150"
                >
                    Volver al inicio de sesión
                </Link>
            </div>
        </form>
    </div>
</template>
<script>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, Link } from '@inertiajs/vue3';

export default {
    components: {
        InputError,
        PrimaryButton,
        Link,
    },
    props: {
        email: {
            type: String,
            required: true,
        },
        activeMethod: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            form: useForm({
                code: '',
            }),
            formResend: useForm({}),
            digits: ['', '', '', '', '', ''],
            countdown: 60,
            timerActive: true,
            timer: null,
        };
    },
    computed: {
        combinedCode() {
            return this.digits.join('');
        },
    },
    mounted() {
        if (this.activeMethod === 'email_otp' || this.activeMethod === 'sms_otp') {
            this.startTimer();
        } else {
            this.timerActive = false;
        }

        // Enfoca el primer input de forma automática
        this.$nextTick(() => {
            if (this.$refs.inputs && this.$refs.inputs[0]) {
                this.$refs.inputs[0].focus();
            }
        });
    },
    unmounted() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    },
    methods: {
        startTimer() {
            this.countdown = 60;
            this.timerActive = true;
            if (this.timer) clearInterval(this.timer);
            this.timer = setInterval(() => {
                if (this.countdown > 0) {
                    this.countdown--;
                } else {
                    this.timerActive = false;
                    clearInterval(this.timer);
                }
            }, 1000);
        },
        handleInput(index, event) {
            const val = event.target.value;
            
            // Solo permitir números
            if (!/^\d*$/.test(val)) {
                this.digits[index] = '';
                return;
            }

            this.digits[index] = val.slice(-1);

            // Mueve el foco al siguiente input si está lleno
            if (this.digits[index] && index < 5) {
                this.$refs.inputs[index + 1].focus();
            }

            // Si todos los dígitos están completos, envía de forma automática
            if (this.digits.every(d => d !== '')) {
                this.submit();
            }
        },
        handleKeyDown(index, event) {
            // Si presiona Backspace y el input actual está vacío, regresa al anterior
            if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                this.digits[index - 1] = '';
                this.$refs.inputs[index - 1].focus();
            }
        },
        handlePaste(event) {
            event.preventDefault();
            const pasteData = event.clipboardData.getData('text').trim();
            
            // Si pegan un código de 6 dígitos
            if (/^\d{6}$/.test(pasteData)) {
                for (let i = 0; i < 6; i++) {
                    this.digits[i] = pasteData[i];
                }
                this.submit();
            }
        },
        submit() {
            this.form.code = this.combinedCode;
            this.form.post(route('auth.mfa'), {
                onError: () => {
                    // Limpia los inputs y enfoca el primero en caso de error
                    this.digits = ['', '', '', '', '', ''];
                    if (this.$refs.inputs && this.$refs.inputs[0]) {
                        this.$refs.inputs[0].focus();
                    }
                }
            });
        },
        resendCode() {
            if (this.timerActive) return;

            this.formResend.post(route('auth.mfa.resend'), {
                onSuccess: () => {
                    this.startTimer();
                    this.digits = ['', '', '', '', '', ''];
                    if (this.$refs.inputs && this.$refs.inputs[0]) {
                        this.$refs.inputs[0].focus();
                    }
                }
            });
        },
    },
};
</script>

