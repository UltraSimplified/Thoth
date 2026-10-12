<script setup lang="ts">
defineProps<{ url: string; label?: string }>();
</script>

<template>
    <a
        :href="url"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex min-h-11 items-center justify-center rounded-md border border-line px-4 text-sm text-ink transition-colors hover:border-accent hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    >
        {{ label ?? '750words' }}
    </a>
</template>
