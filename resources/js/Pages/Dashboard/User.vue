<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    office: {
        type: Object,
        nullable: true,
    },
    clientIp: {
        type: String,
        required: true,
    },
    mfaStatus: {
        type: String,
        required: true,
    },
});

const form = useForm({
    latitude: props.office ? props.office.latitude : '',
    longitude: props.office ? props.office.longitude : '',
    radius: props.office ? props.office.radius : 500,
    allowed_ips: props.office ? props.office.allowed_ips : '',
});

const showSuccessMessage = ref(false);
const errorMessage = ref('');

// Auto-completar campo de IP permitidas con la IP actual del cliente
const autofillIp = () => {
    form.allowed_ips = props.clientIp;
};

// Obtener coordenadas de geolocalización física reales utilizando la API del navegador
const getGpsLocation = () => {
    if (!navigator.geolocation) {
        alert('La geolocalización no es compatible con este navegador.');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude;
            form.longitude = position.coords.longitude;
        },
        (error) => {
            let msg = 'Error al obtener la ubicación: ';
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    msg += 'Permiso de ubicación denegado por el usuario.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg += 'La ubicación física no está disponible.';
                    break;
                case error.TIMEOUT:
                    msg += 'El tiempo de espera para obtener la ubicación ha expirado.';
                    break;
                default:
                    msg += 'Error desconocido de geolocalización.';
            }
            alert(msg);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

// Enviar formulario mediante PUT para actualizar la oficina
const submit = () => {
    if (!props.office) return;
    
    form.put(route('user.offices.update', props.office.id), {
        onSuccess: () => {
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 3000);
        },
        onError: (errors) => {
            errorMessage.value = errors.latitude || errors.longitude || errors.radius || errors.allowed_ips || errors.error || 'Ocurrió un error al actualizar los datos.';
            setTimeout(() => {
                errorMessage.value = '';
            }, 5000);
        }
    });
};
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
                    <!-- Tarjeta de Oficina Asignada (Formulario de Edición) -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            Configuración de Oficina: <span class="font-extrabold text-indigo-600">{{ office ? office.name : 'Ninguna' }}</span>
                        </h3>
                        
                        <div v-if="office" class="space-y-4 pt-2">
                            <form @submit.prevent="submit" class="space-y-4">
                                <!-- Fila: Latitud y Longitud -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase block mb-1">Latitud</label>
                                        <input 
                                            v-model="form.latitude" 
                                            type="text" 
                                            required
                                            class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                            placeholder="Ej. 25.604456"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-gray-500 uppercase block mb-1">Longitud</label>
                                        <input 
                                            v-model="form.longitude" 
                                            type="text" 
                                            required
                                            class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                            placeholder="Ej. -103.387097"
                                        />
                                    </div>
                                </div>

                                <!-- Botón de Captura GPS -->
                                <div>
                                    <button 
                                        type="button" 
                                        @click="getGpsLocation"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-indigo-200 text-indigo-600 bg-indigo-50/50 hover:bg-indigo-50 text-xs font-bold transition shadow-sm w-full sm:w-auto justify-center"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Obtener Ubicación GPS Actual
                                    </button>
                                </div>

                                <!-- Fila: Radio de Tolerancia -->
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase block mb-1">Radio de Tolerancia (Metros)</label>
                                    <input 
                                        v-model="form.radius" 
                                        type="number" 
                                        required
                                        min="1"
                                        class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                        placeholder="Ej. 1000"
                                    />
                                </div>

                                <!-- Fila: IPs Permitidas -->
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase block mb-1">Direcciones IP Permitidas (Separadas por comas)</label>
                                    <div class="flex gap-2">
                                        <input 
                                            v-model="form.allowed_ips" 
                                            type="text" 
                                            required
                                            class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                            placeholder="127.0.0.1, ::1"
                                        />
                                        <button 
                                            type="button"
                                            @click="autofillIp"
                                            class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-xl text-xs hover:bg-indigo-700 transition shadow-sm shrink-0 whitespace-nowrap"
                                        >
                                            Copiar Mi IP
                                        </button>
                                    </div>
                                    <span class="text-xs text-gray-400 mt-1 block">Tu IP detectada: <strong class="text-indigo-600">{{ clientIp }}</strong></span>
                                </div>

                                <!-- Alertas de Éxito / Error -->
                                <div v-if="showSuccessMessage" class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-850 text-xs font-bold rounded-xl flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    ¡Configuración de la oficina actualizada con éxito!
                                </div>
                                <div v-if="errorMessage" class="p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold rounded-xl">
                                    {{ errorMessage }}
                                </div>

                                <!-- Botón de Guardar -->
                                <div class="pt-2">
                                    <button 
                                        type="submit" 
                                        :disabled="form.processing"
                                        class="w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 text-sm font-bold transition shadow-md disabled:opacity-50"
                                    >
                                        <span v-if="form.processing">Guardando Cambios...</span>
                                        <span v-else>Guardar Cambios de Oficina</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div v-else class="text-sm text-gray-500 italic pt-2">
                            No tienes una oficina asignada para configurar.
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
