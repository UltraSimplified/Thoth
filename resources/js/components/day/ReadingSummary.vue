<script setup lang="ts">
import BirthBadge from '@/components/cards/BirthBadge.vue';
import CardImage from '@/components/cards/CardImage.vue';
import CardNote from '@/components/cards/CardNote.vue';
import JournalLink from '@/components/day/JournalLink.vue';
import TreeOfLife from '@/components/tree/TreeOfLife.vue';
import { capitalise, zodiacSpanLabel } from '@/lib/cards';
import type { CardData, DayPageProps, ReadingData } from '@/types/deck';

const props = defineProps<{
    reading: ReadingData;
    eventNote: string | null;
    tree: DayPageProps['tree'];
    journalUrl: string;
}>();

function attributions(card: CardData): string[] {
    return [
        card.suitLabel === null
            ? 'Major Arcana'
            : `${card.suitLabel}, ${card.world}`,
        card.element === null
            ? null
            : `${capitalise(card.element)}${card.subElement ? ` of ${capitalise(card.subElement)}` : ''}`,
        card.planet ? `Planet ${card.planet}` : null,
        card.sign && card.decan
            ? `${card.sign}, decan ${card.decan}`
            : card.sign
              ? `Sign ${card.sign}`
              : null,
        card.zodiacSpan ? zodiacSpanLabel(card.zodiacSpan) : null,
        card.quadrant,
    ].filter((part): part is string => part !== null);
}
</script>

<template>
    <div class="grid gap-8 md:grid-cols-[minmax(0,220px)_minmax(0,1fr)]">
        <div class="space-y-4">
            <!-- Jumpers sit above the main card, smaller. -->
            <ul
                v-if="reading.jumpers.length"
                class="flex flex-wrap justify-center gap-2"
                aria-label="Jumpers"
            >
                <li
                    v-for="jumper in reading.jumpers"
                    :key="jumper.id"
                    class="w-14"
                >
                    <CardImage :card="jumper" size="thumbnail" />
                    <p
                        class="mt-0.5 truncate text-center text-[10px] text-muted"
                    >
                        Jumper: {{ jumper.name }}
                    </p>
                </li>
            </ul>

            <CardImage
                :card="reading.card"
                size="full"
                class="mx-auto w-44 md:w-full"
            />

            <!-- Clarifiers sit beneath the main card, smaller. -->
            <ul
                v-if="reading.clarifiers.length"
                class="flex flex-wrap justify-center gap-2"
                aria-label="Clarifiers"
            >
                <li
                    v-for="clarifier in reading.clarifiers"
                    :key="clarifier.id"
                    class="w-14"
                >
                    <CardImage :card="clarifier" size="thumbnail" />
                    <p
                        class="mt-0.5 truncate text-center text-[10px] text-muted"
                    >
                        Clarifier: {{ clarifier.name }}
                    </p>
                </li>
            </ul>
        </div>

        <div class="space-y-6">
            <header class="space-y-2">
                <h2 class="font-heading text-2xl text-ink">
                    {{ reading.card.name }}
                </h2>
                <p v-if="reading.card.title" class="text-muted">
                    {{ reading.card.title }}
                </p>
                <ul class="flex flex-wrap gap-2 text-sm text-muted">
                    <li v-for="line in attributions(reading.card)" :key="line">
                        {{ line }}
                    </li>
                </ul>
                <div
                    v-if="reading.card.birthRoles.length"
                    class="flex flex-wrap gap-2"
                >
                    <BirthBadge :roles="reading.card.birthRoles" />
                </div>
            </header>

            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div v-if="reading.question">
                    <dt class="text-muted">Question</dt>
                    <dd class="text-ink">{{ reading.question }}</dd>
                </div>
                <div v-if="eventNote">
                    <dt class="text-muted">Event to observe</dt>
                    <dd class="text-ink">{{ eventNote }}</dd>
                </div>
                <div v-if="reading.interpretationNotes" class="sm:col-span-2">
                    <dt class="text-muted">Interpretation</dt>
                    <dd class="whitespace-pre-line text-ink">
                        {{ reading.interpretationNotes }}
                    </dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-3">
                <a
                    :href="reading.card.thothreadingsUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-11 items-center justify-center rounded-md bg-accent px-4 text-sm font-medium text-accent-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                    Reading on thothreadings.com
                </a>
                <JournalLink :url="journalUrl" label="Open 750words" />
            </div>

            <CardNote :key="reading.card.slug" :card="reading.card" />

            <section class="space-y-3" aria-labelledby="tree-heading">
                <h3 id="tree-heading" class="text-sm font-medium text-ink">
                    Place on the Tree of Life
                </h3>
                <TreeOfLife
                    :highlight="tree.main"
                    :jumpers="tree.jumpers"
                    :clarifiers="tree.clarifiers"
                    :birth="tree.birth"
                    label="Tree of Life showing this day's cards"
                />
                <ul class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
                    <li>Solid: main card</li>
                    <li>Dashed: jumper</li>
                    <li>Dotted: clarifier</li>
                    <li>Outlined: birth card position</li>
                </ul>
            </section>
        </div>
    </div>
</template>
