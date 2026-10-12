<script setup lang="ts">
import CardImage from '@/components/cards/CardImage.vue';
import { matchesCardQuery } from '@/lib/cards';
import type { CardData } from '@/types/deck';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        cards: CardData[];
        /** Cards already in the reading, shown but not selectable again. */
        unavailableIds?: number[];
    }>(),
    { unavailableIds: () => [] },
);

const emit = defineEmits<{ pick: [card: CardData] }>();

type Tab = 'major' | 'wands' | 'cups' | 'swords' | 'disks';

const tabs: { key: Tab; label: string }[] = [
    { key: 'major', label: 'Majors' },
    { key: 'wands', label: 'Wands' },
    { key: 'cups', label: 'Cups' },
    { key: 'swords', label: 'Swords' },
    { key: 'disks', label: 'Disks' },
];

const activeTab = ref<Tab>('major');
const query = ref('');

const visibleCards = computed(() => {
    const searching = query.value.trim() !== '';

    return props.cards.filter((card) => {
        if (searching) {
            return matchesCardQuery(card, query.value);
        }

        return activeTab.value === 'major'
            ? card.arcana === 'major'
            : card.suit === activeTab.value;
    });
});

function isUnavailable(card: CardData): boolean {
    return props.unavailableIds.includes(card.id);
}
</script>

<template>
    <div class="space-y-4">
        <div
            role="tablist"
            aria-label="Card suits"
            class="flex flex-wrap gap-1.5"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.key && query.trim() === ''"
                class="min-h-11 rounded-full border px-4 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                :class="
                    activeTab === tab.key && query.trim() === ''
                        ? 'border-accent bg-accent text-accent-ink'
                        : 'border-line text-muted hover:text-ink'
                "
                @click="
                    activeTab = tab.key;
                    query = '';
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <label class="block">
            <span class="sr-only">Search cards</span>
            <input
                v-model="query"
                type="search"
                placeholder="Search: aeon, 5 wands, prudence"
                class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none"
            />
        </label>

        <p v-if="visibleCards.length === 0" class="text-sm text-muted">
            No cards match “{{ query }}”.
        </p>

        <ul
            class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8"
        >
            <li v-for="card in visibleCards" :key="card.id">
                <button
                    type="button"
                    class="group block w-full rounded-md text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:opacity-40"
                    :disabled="isUnavailable(card)"
                    :aria-label="`${card.name}${card.title ? `, ${card.title}` : ''}`"
                    @click="emit('pick', card)"
                >
                    <CardImage
                        :card="card"
                        size="thumbnail"
                        class="transition-transform group-hover:-translate-y-0.5"
                    />
                    <span class="mt-1 block truncate text-xs text-muted">{{
                        card.name
                    }}</span>
                </button>
            </li>
        </ul>
    </div>
</template>
