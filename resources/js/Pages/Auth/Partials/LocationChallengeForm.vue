<template>
    <div>
        <div class="mb-6 text-center">
            <!-- Icono decorativo con animación -->
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-indigo-50 text-indigo-600 mb-4 animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            
            <h2 class="text-xl font-bold text-gray-950">
                Verifica tu identidad
            </h2>
            
            <p class="mt-2 text-sm text-gray-700 leading-relaxed">
                Este factor de seguridad requiere verificar que tu dispositivo esté conectado a la red autorizada (IP pública) de tu oficina asignada y que te encuentres físicamente en ella.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Contenedor de Ubicación Interactiva -->
            <div class="flex flex-col items-center justify-center py-4 gap-5 transition-all duration-300">
                <!-- Visual Radar / Location Pulse Animation -->
                <div class="relative flex items-center justify-center w-24 h-24">
                    <span v-if="locationStatus === 'requesting'" class="animate-ping absolute inline-flex h-20 w-20 rounded-full bg-indigo-400 opacity-20"></span>
                    <span v-if="locationStatus === 'requesting'" class="animate-ping absolute inline-flex h-16 w-16 rounded-full bg-indigo-400 opacity-30 delay-100"></span>
                    
                    <div class="relative flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" :class="{ 'animate-pulse text-indigo-500': locationStatus === 'requesting', 'text-emerald-500': locationStatus === 'success', 'text-rose-500': locationStatus === 'error' }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 1.105 1.343 2 3 2s3-.895 3-2-1.343-2-3-2-3 .895-3 2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.003 9.003 0 008.361-5.638L20 15a8.97 8.97 0 00-6-2.618V11a3 3 0 10-4 0v1.382A8.97 8.97 0 004 15l.361.362A9.003 9.003 0 0012 21z" />
                        </svg>
                    </div>
                </div>

                <!-- Mensaje de estado -->
                <div class="text-center space-y-1">
                    <p v-if="locationStatus === 'idle'" class="text-sm font-semibold text-gray-750">
                        Listo para verificar red y geolocalización
                    </p>
                    <p v-else-if="locationStatus === 'requesting'" class="text-sm font-semibold text-indigo-700 animate-pulse">
                        Obteniendo ubicación y red de oficina...
                    </p>
                    <p v-else-if="locationStatus === 'success'" class="text-sm font-semibold text-emerald-700">
                        Datos obtenidos. Validando con el servidor...
                    </p>
                    <p v-else-if="locationStatus === 'error'" class="text-sm font-semibold text-rose-700">
                        Acceso denegado
                    </p>

                    <!-- Detalle del error (Solo errores del lado del cliente) -->
                    <p v-if="locationError" class="mt-2 text-xs text-rose-600 max-w-xs leading-relaxed mx-auto">
                        {{ locationError }}
                    </p>
                </div>
            </div>

            <!-- Error desde backend (Híbrido: IP o GPS) -->
            <div class="text-center">
                <InputError class="mt-2 text-sm font-medium" :message="form.errors.latitude || form.errors.longitude || form.errors.ip_address" />
            </div>

            <div class="flex flex-col items-center gap-3">
                <PrimaryButton type="button" @click="requestLocation"
                    class="w-full justify-center h-11 text-sm font-semibold tracking-wide bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 focus:ring-indigo-500/20"
                    :class="{ 'opacity-50': form.processing || locationStatus === 'requesting' }"
                    :disabled="form.processing || locationStatus === 'requesting'">
                    <span v-if="locationStatus === 'requesting'">Obteniendo coordenadas...</span>
                    <span v-else-if="form.processing">Verificando...</span>
                    <span v-else>Obtener y Verificar Ubicación</span>
                </PrimaryButton>
            </div>

            <div class="text-center">
                <Link :href="route('login')"
                    class="text-xs text-gray-650 hover:text-gray-800 underline transition-colors duration-150">
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
    data() {
        return {
            locationStatus: 'idle', // 'idle', 'requesting', 'success', 'error'
            locationError: '',
            form: useForm({
                latitude: null,
                longitude: null,
            }),
        };
    },
    mounted() {
        this.requestLocation();
    },
    methods: {
        requestLocation() {
            this.locationStatus = 'requesting';
            this.locationError = '';

            if (!navigator.geolocation) {
                this.locationStatus = 'error';
                this.locationError = 'Tu navegador no soporta la geolocalización o los permisos de ubicación.';
                return;
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.locationStatus = 'success';
                    this.form.latitude = position.coords.latitude;
                    this.form.longitude = position.coords.longitude;
                    this.submit();
                },
                (error) => {
                    this.locationStatus = 'error';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            this.locationError = 'Permiso denegado. Habilita el acceso a la ubicación en la barra de direcciones de tu navegador.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            this.locationError = 'La información de tu ubicación física no está disponible.';
                            break;
                        case error.TIMEOUT:
                            this.locationError = 'Se agotó el tiempo de espera al consultar la geolocalización.';
                            break;
                        default:
                            this.locationError = 'Ocurrió un error inesperado al intentar obtener tu ubicación.';
                            break;
                    }
                },
                options
            );
        },
        submit() {
            this.form.post(route('auth.mfa'), {
                onSuccess: () => {
                    this.locationStatus = 'success';
                },
                onError: () => {
                    this.locationStatus = 'error';
                }
            });
        },
    },
};
</script>
