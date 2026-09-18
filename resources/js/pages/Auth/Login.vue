<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import FormField from '@/components/ui/FormField.vue';
import { login } from '@/services/authService';
import { authState } from '@/services/authState';

const email = ref('');
const password = ref('');
const error = ref<string | null>(null);
const submitting = ref(false);

async function submit(): Promise<void> {
    if (submitting.value) {
        return;
    }

    error.value = null;
    submitting.value = true;

    try {
        const user = await login({
            email: email.value,
            password: password.value,
        });

        authState.setUser(user);

        router.visit('/');
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to sign in.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <Head title="Sign in" />

    <main
        class="flex min-h-screen items-center justify-center bg-app px-4 py-12 sm:px-6"
    >
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <img
                    src="/images/branding/fleettrack-logo.svg"
                    alt="FleetTrack"
                    class="mx-auto h-14 w-14 object-contain"
                />

                <h1
                    class="mt-5 text-2xl font-semibold tracking-tight text-content"
                >
                    Sign in to FleetTrack
                </h1>

                <p class="mt-2 text-sm text-muted">
                    Fleet management and vehicle tracking
                </p>
            </div>

            <div
                class="rounded-xl border border-border-default bg-surface p-6 shadow-sm sm:p-8"
            >
                <form class="space-y-5" @submit.prevent="submit">
                    <div
                        v-if="error"
                        class="rounded-lg border border-danger/20 bg-danger-soft px-4 py-3 text-sm text-danger-dark"
                        role="alert"
                    >
                        {{ error }}
                    </div>

                    <FormField label="Email address" input-id="email" required>
                        <AppInput
                            id="email"
                            v-model="email"
                            type="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            required
                            :disabled="submitting"
                        />
                    </FormField>

                    <FormField label="Password" input-id="password" required>
                        <AppInput
                            id="password"
                            v-model="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required
                            :disabled="submitting"
                        />
                    </FormField>

                    <AppButton
                        type="submit"
                        variant="primary"
                        class="w-full"
                        :disabled="submitting"
                    >
                        {{ submitting ? 'Signing in...' : 'Sign in' }}
                    </AppButton>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-muted">
                &copy; {{ new Date().getFullYear() }} FleetTrack. All rights
                reserved.
            </p>
        </div>
    </main>
</template>
