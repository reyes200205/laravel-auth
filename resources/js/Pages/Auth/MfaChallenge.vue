<script>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
import OtpChallengeForm from './Partials/OtpChallengeForm.vue';
import LocationChallengeForm from './Partials/LocationChallengeForm.vue';

export default {
    components: {
        GuestLayout,
        Head,
        OtpChallengeForm,
        LocationChallengeForm,
    },
    props: {
        email: {
            type: String,
            required: true,
        },
        status: {
            type: String,
            default: '',
        },
        activeMethod: {
            type: String,
            default: 'email_otp',
        },
    },
};
</script>

<template>
    <GuestLayout>
        <Head title="Verificación de Identidad" />

        <!-- Mensajes de Estado del Servidor -->
        <div v-if="status" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-sm text-emerald-600 text-center font-medium">
            {{ status }}
        </div>

        <!-- Renderizado de los subcomponentes modularizados -->
        <OtpChallengeForm 
            v-if="activeMethod !== 'location'" 
            :email="email" 
            :active-method="activeMethod" 
        />
        
        <LocationChallengeForm 
            v-else 
        />
    </GuestLayout>
</template>
