<script setup lang="ts">
withDefaults(defineProps<{ title: string; subtitle?: string }>(), {
    subtitle: undefined,
});
</script>

<template>
    <header
        class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <h1 class="font-heading text-2xl text-ink">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-muted">
                {{ subtitle }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
