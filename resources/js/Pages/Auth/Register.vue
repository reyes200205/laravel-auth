<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const turnstileContainer = ref(null);
let turnstileWidgetId = null;

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    'cf-turnstile-response': '',
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

onMounted(() => {
    const initTurnstile = () => {
        const siteKey = page.props.turnstile_site_key;
        if (siteKey && window.turnstile && turnstileContainer.value) {
            turnstileWidgetId = window.turnstile.render(turnstileContainer.value, {
                sitekey: siteKey,
                callback: (token) => {
                    form['cf-turnstile-response'] = token;
                },
                'expired-callback': () => {
                    form['cf-turnstile-response'] = '';
                },
                'error-callback': () => {
                    form['cf-turnstile-response'] = '';
                }
            });
        }
    };

    if (window.turnstile) {
        initTurnstile();
    } else {
        const interval = setInterval(() => {
            if (window.turnstile) {
                clearInterval(interval);
                initTurnstile();
            }
        }, 100);
    }
});

onUnmounted(() => {
    if (turnstileWidgetId !== null && window.turnstile) {
        window.turnstile.remove(turnstileWidgetId);
    }
});

const submit = () => {
    form.post(route('register'), {
        onError: () => {
            if (turnstileWidgetId !== null && window.turnstile && turnstileContainer.value) {
                try {
                    window.turnstile.reset(turnstileWidgetId);
                } catch (e) {
                    console.warn('Turnstile reset ignored:', e);
                }
                form['cf-turnstile-response'] = '';
            }
        },
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
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
                <div v-if="form.password" class="mt-3 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400">Fuerza de la contraseña:</span>
                        <span class="font-bold" :class="passwordStrength.text">{{ passwordStrength.label }}</span>
                    </div>
                    
                    <div class="h-1 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                        <div 
                            class="h-full transition-all duration-500" 
                            :class="passwordStrength.color"
                            :style="{ width: `${(passwordStrength.score / 3) * 100}%` }"
                        ></div>
                    </div>

                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 mt-2">
                        <li 
                            v-for="(rule, index) in passwordRules" 
                            :key="index"
                            class="flex items-center text-[11px] transition-colors duration-300"
                            :class="rule.val ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-400 dark:text-slate-500'"
                        >
                            <svg v-if="rule.val" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700 mr-2.5 ml-1.5 flex-shrink-0"></span>
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

            <!-- Cloudflare Turnstile Widget -->
            <div v-if="page.props.turnstile_site_key" class="mt-4 flex flex-col items-center justify-center">
                <div ref="turnstileContainer"></div>
                <InputError class="mt-2" :message="form.errors['cf-turnstile-response']" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Already registered?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
