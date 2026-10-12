<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/login';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post(store.url(), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Log in" />

    <AuthLayout>
        <h1 class="mb-1 text-center font-heading text-lg text-ink">Log in</h1>
        <p class="mb-6 text-center text-sm text-muted">
            Single-user access to your reading log.
        </p>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1 block text-sm text-ink"
                    >Email</label
                >
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                    class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-wands">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm text-ink"
                    >Password</label
                >
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-wands">
                    {{ form.errors.password }}
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm text-muted">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="h-4 w-4 rounded border-line"
                />
                Remember me
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex min-h-11 w-full items-center justify-center rounded-md bg-accent text-sm font-medium text-accent-ink transition-opacity focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-60"
            >
                Log in
            </button>
        </form>
    </AuthLayout>
</template>
