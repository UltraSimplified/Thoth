<script setup lang="ts">
import { show as showCard } from '@/actions/App/Http/Controllers/CardController';
import BirthBadge from '@/components/cards/BirthBadge.vue';
import CardImage from '@/components/cards/CardImage.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CardData, Suit } from '@/types/deck';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type CardWithDraws = CardData & { drawsSince: number };

const props = defineProps<{
    cards: CardWithDraws[];
    /** The date the counts start from, as written for people, e.g. "1 January 2026". */
    drawsFrom: string;
}>();

const groups = computed(() => {
    const suitGroups: {
        key: string;
        heading: string;
        cards: CardWithDraws[];
    }[] = [
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

/** The disc takes the card's suit colour, faintly, so the count reads against any theme. Cards never drawn stay plain. */
function discStyle(card: CardWithDraws): Record<string, string> {
    if (card.drawsSince === 0) {
        return {};
    }

    return {
        backgroundColor: `color-mix(in srgb, var(--${card.suit ?? 'majors'}) 45%, var(--surface))`,
    };
}

function drawnLabel(card: CardWithDraws): string {
    const times = card.drawsSince === 1 ? 'time' : 'times';

    return `${card.name}, drawn ${card.drawsSince} ${times} since ${props.drawsFrom}`;
}
</script>

<template>
    <Head title="Cards" />

    <AppLayout>
        <PageHeader
            title="Cards"
            :subtitle="`All 78 cards of the Thoth deck, grouped by suit. The circle on each card counts how many times you have drawn it as the main card since ${drawsFrom}.`"
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
                            :aria-label="drawnLabel(card)"
                            class="group block rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                        >
                            <span class="relative block">
                                <CardImage
                                    :card="card"
                                    size="thumbnail"
                                    class="transition-transform group-hover:-translate-y-0.5"
                                />
                                <span
                                    class="absolute -top-1.5 -right-1.5 flex size-7 items-center justify-center rounded-full border border-line text-xs font-semibold shadow-sm"
                                    :class="
                                        card.drawsSince === 0
                                            ? 'bg-surface text-muted'
                                            : 'text-ink'
                                    "
                                    :style="discStyle(card)"
                                    aria-hidden="true"
                                >
                                    {{ card.drawsSince }}
                                </span>
                            </span>
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
