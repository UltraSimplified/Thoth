<script setup lang="ts">
import type { CardData } from '@/types/deck';

const props = withDefaults(
    defineProps<{
        card: Pick<CardData, 'name' | 'imageUrl' | 'thumbnailUrl'>;
        size?: 'thumbnail' | 'full';
    }>(),
    { size: 'full' },
);
</script>

<template>
    <img
        :src="props.size === 'thumbnail' ? card.thumbnailUrl : card.imageUrl"
        :alt="card.name"
        loading="lazy"
        class="aspect-[3/5] w-full rounded-md border border-line bg-surface-2 object-cover"
    />
</template>
