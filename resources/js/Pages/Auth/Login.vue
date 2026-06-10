<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const turnstileContainer = ref(null);
let turnstileWidgetId = null;

const form = useForm({
    email: '',
    password: '',
    remember: false,
    'cf-turnstile-response': '',
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
    form.post(route('login'), {
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
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

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
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <!-- Cloudflare Turnstile Widget -->
            <div v-if="page.props.turnstile_site_key" class="mt-4 flex flex-col items-center justify-center">
                <div ref="turnstileContainer"></div>
                <InputError class="mt-2" :message="form.errors['cf-turnstile-response']" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="underline text-xs text-gray-655 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    Forgot your password?
                </Link>
                <div v-else></div>

                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('register')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        Register
                    </Link>

                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Log in
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
