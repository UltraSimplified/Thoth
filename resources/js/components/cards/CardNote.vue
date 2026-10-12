<script setup lang="ts">
import { updateNote } from '@/actions/App/Http/Controllers/CardController';
import type { CardData } from '@/types/deck';
import { useHttp } from '@inertiajs/vue3';
import { onBeforeUnmount, ref } from 'vue';

const props = defineProps<{ card: CardData }>();

const SAVE_DELAY_MS = 600;

const note = useHttp({ personal_note: props.card.personalNote ?? '' });
const status = ref<'idle' | 'pending' | 'saved' | 'failed'>('idle');
let timer: ReturnType<typeof setTimeout> | undefined;

async function save(): Promise<void> {
    clearTimeout(timer);
    timer = undefined;
    status.value = 'pending';

    try {
        await note.put(updateNote.url(props.card.slug));
        status.value = 'saved';
    } catch {
        status.value = 'failed';
    }
}

function scheduleSave(): void {
    status.value = 'pending';
    clearTimeout(timer);
    timer = setTimeout(save, SAVE_DELAY_MS);
}

// Save what was typed if the page changes before the pause ends.
onBeforeUnmount(() => {
    if (timer !== undefined) {
        void save();
    }
});
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-baseline justify-between gap-2">
            <label
                :for="`note-${card.slug}`"
                class="text-sm font-medium text-ink"
                >My notes</label
            >
            <span class="text-xs text-muted" aria-live="polite">
                <template v-if="status === 'pending'">Saving…</template>
                <template v-else-if="status === 'saved'">Saved</template>
                <template v-else-if="status === 'failed'"
                    >Not saved. Try again.</template
                >
            </span>
        </div>
        <textarea
            :id="`note-${card.slug}`"
            v-model="note.personal_note"
            rows="3"
            placeholder="What this card means to you"
            class="w-full resize-y rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none"
            @input="scheduleSave"
        />
    </div>
</template>
