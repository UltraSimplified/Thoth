<script setup lang="ts">
import { update as updateSettings } from '@/actions/App/Http/Controllers/SettingsController';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const dismissed = ref(false);

function confirmTimezone(): void {
    dismissed.value = true;

    router.patch(
        updateSettings.url(),
        { journal_timezone_confirmed: true },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <aside
        v-if="!dismissed"
        class="mb-6 flex flex-col gap-3 rounded-lg border border-line bg-surface p-4 sm:flex-row sm:items-center sm:justify-between"
        aria-label="Journal timezone reminder"
    >
        <p class="text-sm text-muted">
            Check that your 750words account timezone is set to London, so its
            days line up with this app.
        </p>
        <button
            type="button"
            class="min-h-11 shrink-0 rounded-md border border-line px-4 text-sm text-ink hover:border-accent hover:text-accent"
            @click="confirmTimezone"
        >
            Got it
        </button>
    </aside>
</template>
