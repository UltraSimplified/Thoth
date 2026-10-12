<script setup lang="ts">
import { useTheme, type ThemePreference } from '@/composables/useTheme';
import { Leaf, Monitor, Moon, Sun } from 'lucide-vue-next';
import type { Component } from 'vue';

withDefaults(defineProps<{ orientation?: 'horizontal' | 'vertical' }>(), {
    orientation: 'horizontal',
});

const { preference, setPreference } = useTheme();

const options: { value: ThemePreference; label: string; icon: Component }[] = [
    { value: 'system', label: 'System', icon: Monitor },
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'goblin', label: 'Goblin', icon: Leaf },
];
</script>

<template>
    <div
        role="group"
        aria-label="Theme"
        class="inline-flex gap-0.5 rounded-full border border-line bg-surface-2 p-1"
        :class="orientation === 'vertical' ? 'flex-col' : 'flex-row'"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :aria-label="option.label"
            :aria-pressed="preference === option.value"
            class="group flex h-11 w-11 items-center justify-center rounded-full transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
            :class="
                preference === option.value
                    ? 'bg-accent text-accent-ink'
                    : 'text-muted hover:bg-surface hover:text-ink'
            "
            @click="setPreference(option.value)"
        >
            <component
                :is="option.icon"
                :size="16"
                aria-hidden="true"
                :class="
                    preference === option.value
                        ? ''
                        : 'text-icon group-hover:text-current'
                "
            />
        </button>
    </div>
</template>
