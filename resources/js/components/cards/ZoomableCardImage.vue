<script setup lang="ts">
import CardImage from '@/components/cards/CardImage.vue';
import type { CardData } from '@/types/deck';
import { X } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

/**
 * A card that opens full screen when tapped, so its detail can be read. The thumbnail stays small in the layout.
 */
const props = withDefaults(
    defineProps<{
        card: Pick<CardData, 'name' | 'imageUrl' | 'thumbnailUrl'>;
        size?: 'thumbnail' | 'full';
    }>(),
    { size: 'thumbnail' },
);

const isOpen = ref(false);
const trigger = ref<HTMLButtonElement | null>(null);
const closeButton = ref<HTMLButtonElement | null>(null);

watch(isOpen, async (nowOpen) => {
    // Stop the page behind the card from scrolling while it is open.
    document.body.style.overflow = nowOpen ? 'hidden' : '';

    if (nowOpen) {
        await nextTick();
        closeButton.value?.focus();
    } else {
        trigger.value?.focus();
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <button
        ref="trigger"
        type="button"
        class="group block w-full rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        :aria-label="`View ${props.card.name} full screen`"
        @click="isOpen = true"
    >
        <CardImage :card="props.card" :size="props.size" />
    </button>

    <Teleport to="body">
        <div
            v-if="isOpen"
            role="dialog"
            aria-modal="true"
            :aria-label="props.card.name"
            class="fixed inset-0 z-50 flex items-center justify-center bg-bg p-2"
            @click.self="isOpen = false"
            @keydown.esc="isOpen = false"
        >
            <img
                :src="props.card.imageUrl"
                :alt="props.card.name"
                class="max-h-full max-w-full rounded-lg object-contain shadow-2xl"
            />

            <button
                ref="closeButton"
                type="button"
                class="absolute top-4 right-4 inline-flex size-11 items-center justify-center rounded-full border border-line bg-surface text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                aria-label="Close"
                @click="isOpen = false"
            >
                <X :size="20" aria-hidden="true" />
            </button>
        </div>
    </Teleport>
</template>
