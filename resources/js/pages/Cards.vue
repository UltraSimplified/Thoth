<script setup lang="ts">
import { show as showCard } from '@/actions/App/Http/Controllers/CardController';
import BirthBadge from '@/components/cards/BirthBadge.vue';
import CardImage from '@/components/cards/CardImage.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CardData, Suit } from '@/types/deck';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ cards: CardData[] }>();

const groups = computed(() => {
    const suitGroups: { key: string; heading: string; cards: CardData[] }[] = [
        {
            key: 'major',
            heading: 'Major Arcana',
            cards: props.cards.filter((card) => card.arcana === 'major'),
        },
    ];

    const suits: Suit[] = ['wands', 'cups', 'swords', 'disks'];

    for (const suit of suits) {
        const cards = props.cards.filter((card) => card.suit === suit);
        const world = cards[0]?.world;

        suitGroups.push({
            key: suit,
            heading: world ? `${cards[0]?.suitLabel} · ${world}` : suit,
            cards,
        });
    }

    return suitGroups;
});
</script>

<template>
    <Head title="Cards" />

    <AppLayout>
        <PageHeader
            title="Cards"
            subtitle="All 78 cards of the Thoth deck, grouped by suit."
        />

        <div class="space-y-10">
            <section
                v-for="group in groups"
                :key="group.key"
                :aria-labelledby="`group-${group.key}`"
                class="space-y-4"
            >
                <h2
                    :id="`group-${group.key}`"
                    class="font-heading text-lg text-ink"
                >
                    {{ group.heading }}
                </h2>

                <ul
                    class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8"
                >
                    <li v-for="card in group.cards" :key="card.id">
                        <Link
                            :href="showCard.url(card.slug)"
                            class="group block rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        >
                            <CardImage
                                :card="card"
                                size="thumbnail"
                                class="transition-transform group-hover:-translate-y-0.5"
                            />
                            <span
                                class="mt-1 block truncate text-xs text-ink"
                                >{{ card.name }}</span
                            >
                        </Link>
                        <div v-if="card.birthRoles.length" class="mt-1">
                            <BirthBadge :roles="card.birthRoles" />
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
