<script setup lang="ts">
import { show as showCard } from '@/actions/App/Http/Controllers/CardController';
import BirthBadge from '@/components/cards/BirthBadge.vue';
import CardImage from '@/components/cards/CardImage.vue';
import CardNote from '@/components/cards/CardNote.vue';
import PageHeader from '@/components/PageHeader.vue';
import TreeOfLife from '@/components/tree/TreeOfLife.vue';
import { capitalise, zodiacSpanLabel } from '@/lib/cards';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    Appearance,
    CardData,
    TreeHighlights,
    TreePosition,
} from '@/types/deck';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface CardRef {
    slug: string;
    name: string;
    title: string | null;
}

const props = defineProps<{
    card: CardData;
    position: TreePosition;
    tree: {
        highlight: TreeHighlights;
        positions: {
            nodes: Record<string, CardRef[]>;
            paths: Record<string, CardRef[]>;
        };
    };
    appearances: Appearance[];
}>();

const APPEARANCE_ROLE: Record<Appearance['role'], string> = {
    main: 'Main card',
    jumper: 'Jumper',
    clarifier: 'Clarifier',
};

const ownKey = computed(() =>
    props.position.kind === 'sephira'
        ? props.position.sephira
        : `path-${props.position.number}`,
);

const selected = ref<string>(ownKey.value);

const selectedCards = computed<CardRef[]>(() => {
    if (selected.value.startsWith('path-')) {
        return (
            props.tree.positions.paths[selected.value.slice('path-'.length)] ??
            []
        );
    }

    return props.tree.positions.nodes[selected.value] ?? [];
});

const selectedLabel = computed(() => {
    if (selected.value.startsWith('path-')) {
        return `Path ${selected.value.slice('path-'.length)}`;
    }

    return capitalise(selected.value);
});

const suitOrMajor = computed(() =>
    props.card.suitLabel === null
        ? 'Major Arcana'
        : `${props.card.suitLabel}, ${props.card.world}`,
);

const elementLine = computed(() => {
    if (props.card.element === null) {
        return null;
    }

    return props.card.subElement
        ? `${capitalise(props.card.subElement)} of ${capitalise(props.card.element)}`
        : capitalise(props.card.element);
});

const positionLine = computed(() => {
    if (props.position.kind === 'sephira') {
        return `Sephira ${props.position.number}, ${props.position.label}. ${props.position.pillar} pillar, ${props.position.triad} triad.`;
    }

    return `Path ${props.position.number}, ${props.position.letter}, joins ${props.position.from} and ${props.position.to}.`;
});
</script>

<template>
    <Head :title="props.card.name" />

    <AppLayout>
        <PageHeader
            :title="props.card.name"
            :subtitle="props.card.title ?? undefined"
        />

        <div class="grid gap-10 md:grid-cols-[minmax(0,260px)_minmax(0,1fr)]">
            <div class="space-y-5">
                <div class="mx-auto w-48 md:w-full">
                    <CardImage :card="props.card" size="full" />
                </div>

                <div
                    v-if="props.card.birthRoles.length"
                    class="flex flex-wrap gap-2"
                >
                    <BirthBadge :roles="props.card.birthRoles" />
                </div>

                <a
                    :href="props.card.thothreadingsUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-accent px-4 text-sm font-medium text-accent-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                    Reading on thothreadings.com
                </a>

                <CardNote :key="props.card.slug" :card="props.card" />
            </div>

            <div class="space-y-10">
                <section
                    aria-labelledby="attributions-heading"
                    class="space-y-4"
                >
                    <h2
                        id="attributions-heading"
                        class="font-heading text-lg text-ink"
                    >
                        Attributions
                    </h2>
                    <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted">Arcana</dt>
                            <dd class="text-ink">
                                {{
                                    props.card.arcana === 'major'
                                        ? `Major Arcana, ${props.card.roman}`
                                        : `Minor, ${suitOrMajor}`
                                }}
                            </dd>
                        </div>
                        <div v-if="props.card.rank">
                            <dt class="text-muted">Rank</dt>
                            <dd class="text-ink">
                                {{ capitalise(props.card.rank) }}
                            </dd>
                        </div>
                        <div v-if="elementLine">
                            <dt class="text-muted">Element</dt>
                            <dd class="text-ink">{{ elementLine }}</dd>
                        </div>
                        <div v-if="props.card.hebrewLetter">
                            <dt class="text-muted">Hebrew letter</dt>
                            <dd class="text-ink">
                                {{ props.card.hebrewLetter }}
                            </dd>
                        </div>
                        <div v-if="props.card.planet">
                            <dt class="text-muted">Planet</dt>
                            <dd class="text-ink">{{ props.card.planet }}</dd>
                        </div>
                        <div v-if="props.card.sign">
                            <dt class="text-muted">
                                Sign{{ props.card.decan ? ' and decan' : '' }}
                            </dt>
                            <dd class="text-ink">
                                {{ props.card.sign
                                }}{{
                                    props.card.decan
                                        ? `, decan ${props.card.decan}`
                                        : ''
                                }}
                            </dd>
                        </div>
                        <div v-if="props.card.zodiacSpan" class="sm:col-span-2">
                            <dt class="text-muted">Zodiac domain</dt>
                            <dd class="text-ink">
                                {{ zodiacSpanLabel(props.card.zodiacSpan) }}
                            </dd>
                        </div>
                        <div v-if="props.card.quadrant" class="sm:col-span-2">
                            <dt class="text-muted">Quadrant of the heavens</dt>
                            <dd class="text-ink">{{ props.card.quadrant }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-muted">Tree of Life</dt>
                            <dd class="text-ink">{{ positionLine }}</dd>
                        </div>
                    </dl>
                </section>

                <section aria-labelledby="tree-heading" class="space-y-4">
                    <h2 id="tree-heading" class="font-heading text-lg text-ink">
                        Tree of Life
                    </h2>
                    <TreeOfLife
                        :highlight="props.tree.highlight"
                        :selected="selected"
                        labelled
                        label="Tree of Life showing this card's position"
                        @select="selected = $event"
                    />
                    <div
                        class="rounded-lg border border-line bg-surface p-4"
                        aria-live="polite"
                    >
                        <p class="text-sm font-medium text-ink">
                            {{ selectedLabel }}
                        </p>
                        <ul
                            v-if="selectedCards.length"
                            class="mt-2 flex flex-wrap gap-2 text-sm text-muted"
                        >
                            <li
                                v-for="entry in selectedCards"
                                :key="entry.slug"
                            >
                                <Link
                                    :href="showCard.url(entry.slug)"
                                    class="hover:text-accent hover:underline"
                                >
                                    {{ entry.name }}
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-sm text-muted">
                            No cards sit here.
                        </p>
                    </div>
                </section>

                <section
                    aria-labelledby="appearances-heading"
                    class="space-y-4"
                >
                    <h2
                        id="appearances-heading"
                        class="font-heading text-lg text-ink"
                    >
                        Appearances
                    </h2>
                    <p
                        v-if="props.appearances.length === 0"
                        class="text-sm text-muted"
                    >
                        This card has not appeared in a reading yet.
                    </p>
                    <ol v-else class="space-y-3">
                        <li
                            v-for="appearance in props.appearances"
                            :key="`${appearance.date}-${appearance.role}`"
                            class="rounded-lg border border-line bg-surface p-4"
                        >
                            <div
                                class="flex flex-wrap items-baseline justify-between gap-2"
                            >
                                <Link
                                    :href="appearance.url"
                                    class="text-sm font-medium text-ink hover:text-accent hover:underline"
                                >
                                    {{ appearance.dateLabel }}
                                </Link>
                                <span class="text-xs text-muted">{{
                                    APPEARANCE_ROLE[appearance.role]
                                }}</span>
                            </div>
                            <p
                                v-if="appearance.question"
                                class="mt-2 text-sm text-ink"
                            >
                                {{ appearance.question }}
                            </p>
                            <p
                                v-if="appearance.eventNote"
                                class="mt-1 text-sm text-muted"
                            >
                                {{ appearance.eventNote }}
                            </p>
                        </li>
                    </ol>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
