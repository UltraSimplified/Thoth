<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

withDefaults(defineProps<{ variant?: 'full' | 'mark' }>(), {
    variant: 'full',
});

const page = usePage();
const words = computed(() =>
    page.props.name.trim().split(/\s+/).filter(Boolean),
);
const leading = computed(() => words.value.slice(0, -1).join(' '));
const accentWord = computed(() => words.value.at(-1) ?? '');
const initials = computed(() =>
    words.value
        .map((word) => word.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);
</script>

<template>
    <span
        v-if="variant === 'full'"
        class="font-heading text-sm tracking-[0.18em] text-ink uppercase"
    >
        <span v-if="leading">{{ leading }} </span
        ><span class="text-accent">{{ accentWord }}</span>
    </span>
    <span
        v-else
        role="img"
        :aria-label="page.props.name"
        class="flex h-9 w-9 items-center justify-center rounded-lg border border-line font-heading text-xs tracking-[0.1em] text-ink uppercase"
        >{{ initials }}</span
    >
</template>
