<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const passwordRules = computed(() => {
    const pwd = form.password || '';
    return [
        { label: 'Mínimo 8 caracteres', val: pwd.length >= 8 },
        { label: 'Una letra mayúscula (A-Z)', val: /[A-Z]/.test(pwd) },
        { label: 'Una letra minúscula (a-z)', val: /[a-z]/.test(pwd) },
        { label: 'Un número (0-9)', val: /\d/.test(pwd) },
        { label: 'Un carácter especial (@$!%*?&#.-_)', val: /[@$!%*?&#.\-_]/.test(pwd) },
    ];
});

const passwordStrength = computed(() => {
    const pwd = form.password || '';
    if (pwd.length === 0) {
        return { score: 0, label: 'Vacía', color: 'bg-slate-700', text: 'text-slate-500' };
    }
    const rulesPassed = passwordRules.value.filter(r => r.val).length;
    if (rulesPassed <= 2) {
        return { score: 1, label: 'Débil', color: 'bg-red-500', text: 'text-red-500' };
    }
    if (rulesPassed <= 4) {
        return { score: 2, label: 'Media', color: 'bg-amber-500', text: 'text-amber-500' };
    }
    return { score: 3, label: 'Fuerte', color: 'bg-emerald-500', text: 'text-emerald-500' };
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />

                <!-- Medidor de Fuerza de Contraseña Interactivo -->
                <div class="mt-3 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Seguridad de la contraseña:</span>
                        <span class="text-xs font-bold" :class="passwordStrength.text">{{ passwordStrength.label }}</span>
                    </div>
                    
                    <div class="h-1.5 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden mb-3">
                        <div 
                            class="h-full transition-all duration-500" 
                            :class="passwordStrength.color"
                            :style="{ width: `${(passwordStrength.score / 3) * 100}%` }"
                        ></div>
                    </div>

                    <ul class="space-y-1.5">
                        <li 
                            v-for="(rule, index) in passwordRules" 
                            :key="index"
                            class="flex items-center text-xs transition-colors duration-300"
                            :class="rule.val ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-500'"
                        >
                            <svg v-if="rule.val" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-600 mr-2.5 ml-1.5 flex-shrink-0"></span>
                            {{ rule.label }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Reset Password
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
