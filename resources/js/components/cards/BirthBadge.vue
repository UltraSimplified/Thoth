<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ roles: string[] }>();

const open = ref(false);
</script>

<template>
    <button
        v-if="props.roles.length > 0"
        type="button"
        class="inline-flex min-h-7 items-center gap-1 rounded-full border border-accent px-2 text-xs text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        :title="props.roles.join(', ')"
        :aria-label="`Birth card: ${props.roles.join(', ')}`"
        :aria-expanded="open"
        @click="open = !open"
        @blur="open = false"
    >
        <Sparkles :size="12" aria-hidden="true" />
        <span v-if="open">{{ props.roles.join(', ') }}</span>
        <span v-else>Birth card</span>
    </button>
</template>
