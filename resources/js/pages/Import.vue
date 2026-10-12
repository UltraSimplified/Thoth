<script setup lang="ts">
import {
    review as reviewImport,
    show as showImport,
    store as storeImport,
} from '@/actions/App/Http/Controllers/ImportController';
import CardImage from '@/components/cards/CardImage.vue';
import CardPicker from '@/components/cards/CardPicker.vue';
import EmptyStatePanel from '@/components/EmptyStatePanel.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CardData } from '@/types/deck';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface ReviewRow {
    line: number;
    raw: string;
    card_text: string | null;
    date: string | null;
    card_id: number | null;
    clarifier_ids: number[];
    notes: string | null;
    event_note: string | null;
    problems: string[];
    include: boolean;
}

interface EditableRow extends ReviewRow {
    jumper_ids: number[];
}

const props = defineProps<{
    review: { text: string; year: number; rows: ReviewRow[] } | null;
    cards: CardData[];
    defaultYear: number;
    status: string | null;
}>();

const paste = useForm({
    text: '',
    year: props.defaultYear,
});

function startReview(): void {
    paste.post(reviewImport.url(), { preserveScroll: false });
}

const rows = ref<EditableRow[]>([]);

const cardsById = computed(
    () => new Map(props.cards.map((card) => [card.id, card])),
);

function cardFor(id: number | null): CardData | undefined {
    return id === null ? undefined : cardsById.value.get(id);
}

function cardName(id: number | null): string | null {
    return cardFor(id)?.name ?? null;
}

const readyCount = computed(
    () => rows.value.filter((row) => row.problems.length === 0).length,
);

const confirmed = computed(() => rows.value.filter((row) => row.include));

const savable = computed(
    () =>
        confirmed.value.length > 0 &&
        confirmed.value.every((row) => row.date !== null),
);

const picking = ref<{
    index: number;
    slot: 'main' | 'clarifier' | 'jumper';
} | null>(null);

// This page is also the review screen, so it keeps its instance when the review arrives. Follow the prop
// rather than reading it once at setup, or the review would stay empty.
watch(
    () => props.review,
    (review) => {
        rows.value = (review?.rows ?? []).map((row) => ({
            ...row,
            clarifier_ids: [...row.clarifier_ids],
            jumper_ids: [],
        }));
        picking.value = null;
    },
    { immediate: true },
);

const pickerHeading = computed(() => {
    switch (picking.value?.slot) {
        case 'main':
            return 'Choose the card';
        case 'clarifier':
            return 'Add a clarifier';
        case 'jumper':
            return 'Add a jumper';
        default:
            return '';
    }
});

const usedIdsFor = (row: EditableRow): number[] => [
    ...(row.card_id === null ? [] : [row.card_id]),
    ...row.clarifier_ids,
    ...row.jumper_ids,
];

function pick(card: CardData): void {
    if (picking.value === null) {
        return;
    }

    const row = rows.value[picking.value.index];

    if (row === undefined) {
        return;
    }

    if (picking.value.slot === 'main') {
        row.card_id = card.id;
    } else if (picking.value.slot === 'clarifier') {
        row.clarifier_ids.push(card.id);
    } else {
        row.jumper_ids.push(card.id);
    }

    picking.value = null;
}

function switchToJumper(row: EditableRow, cardId: number): void {
    row.clarifier_ids = row.clarifier_ids.filter((id) => id !== cardId);
    row.jumper_ids.push(cardId);
}

function switchToClarifier(row: EditableRow, cardId: number): void {
    row.jumper_ids = row.jumper_ids.filter((id) => id !== cardId);
    row.clarifier_ids.push(cardId);
}

function removeClarifier(row: EditableRow, cardId: number): void {
    row.clarifier_ids = row.clarifier_ids.filter((id) => id !== cardId);
}

function removeJumper(row: EditableRow, cardId: number): void {
    row.jumper_ids = row.jumper_ids.filter((id) => id !== cardId);
}

type ConfirmedDay = {
    date: string;
    card_id: number | null;
    jumper_ids: number[];
    clarifier_ids: number[];
    interpretation_notes: string | null;
    event_note: string | null;
};

const save = useForm({ days: [] as ConfirmedDay[] });

function saveConfirmed(): void {
    save.days = confirmed.value.map((row) => ({
        date: row.date ?? '',
        card_id: row.card_id,
        jumper_ids: row.jumper_ids,
        clarifier_ids: row.clarifier_ids,
        interpretation_notes: row.notes,
        event_note: row.event_note,
    }));

    save.post(storeImport.url(), { preserveScroll: false });
}

function startOver(): void {
    router.visit(showImport.url());
}
</script>

<template>
    <Head title="Import" />

    <AppLayout>
        <PageHeader
            title="Import"
            subtitle="Paste the existing reading list. Every day is shown for review before anything is saved."
        />

        <p
            v-if="status"
            class="mb-6 rounded-md border border-line bg-surface px-4 py-3 text-sm text-ink"
            role="status"
        >
            {{ status }}
        </p>

        <form v-if="!review" class="space-y-5" @submit.prevent="startReview">
            <div class="space-y-2">
                <label for="import-text" class="text-sm font-medium text-ink"
                    >Reading list</label
                >
                <textarea
                    id="import-text"
                    v-model="paste.text"
                    rows="16"
                    spellcheck="false"
                    placeholder="* FEBRUARY (4 Cups, 10 Disks, ...) = DISKS&#10;   * XVI — The Tower / Knight of Disks — 05/02"
                    class="w-full resize-y rounded-md border border-line bg-surface px-3 py-2 font-mono text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none"
                />
                <p
                    v-if="paste.errors.text"
                    class="text-sm text-danger"
                    role="alert"
                >
                    {{ paste.errors.text }}
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-4">
                <label class="space-y-2">
                    <span class="text-sm font-medium text-ink">Year</span>
                    <input
                        v-model.number="paste.year"
                        type="number"
                        min="1900"
                        max="2100"
                        class="block min-h-11 w-32 rounded-md border border-line bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    />
                </label>
                <button
                    type="submit"
                    :disabled="paste.processing || paste.text.trim() === ''"
                    class="min-h-11 rounded-md bg-accent px-5 text-sm font-medium text-accent-ink disabled:opacity-50"
                >
                    Review the list
                </button>
            </div>
        </form>

        <div v-else class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-muted">
                    {{ rows.length }}
                    {{ rows.length === 1 ? 'entry' : 'entries' }} read for
                    {{ review?.year }}. {{ readyCount }} ready,
                    {{ rows.length - readyCount }} need a look.
                </p>
                <button
                    type="button"
                    class="min-h-11 rounded-md border border-line px-4 text-sm text-muted hover:text-ink"
                    @click="startOver"
                >
                    Paste a different list
                </button>
            </div>

            <EmptyStatePanel v-if="rows.length === 0">
                Nothing in the list looked like a reading. Check the format and
                paste it again.
            </EmptyStatePanel>

            <ol v-else class="space-y-4">
                <li
                    v-for="(row, index) in rows"
                    :key="row.line"
                    class="space-y-4 rounded-lg border p-4"
                    :class="
                        row.problems.length ? 'border-danger' : 'border-line'
                    "
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <label
                            class="inline-flex min-h-11 items-center gap-2 text-sm text-ink"
                        >
                            <input
                                v-model="row.include"
                                type="checkbox"
                                class="size-5"
                            />
                            Import this day
                        </label>
                        <span class="text-xs text-muted"
                            >Line {{ row.line }}</span
                        >
                    </div>

                    <p class="font-mono text-xs text-muted">{{ row.raw }}</p>

                    <ul
                        v-if="row.problems.length"
                        class="space-y-1 text-sm text-danger"
                        role="alert"
                    >
                        <li v-for="problem in row.problems" :key="problem">
                            {{ problem }}
                        </li>
                    </ul>

                    <div class="grid gap-4 md:grid-cols-[180px_minmax(0,1fr)]">
                        <div class="space-y-2">
                            <label
                                :for="`row-date-${index}`"
                                class="text-sm font-medium text-ink"
                                >Date</label
                            >
                            <input
                                :id="`row-date-${index}`"
                                v-model="row.date"
                                type="date"
                                class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                            />
                        </div>

                        <div class="space-y-3">
                            <p class="text-sm font-medium text-ink">
                                Main card
                            </p>
                            <div
                                v-if="row.card_id !== null"
                                class="flex items-center gap-3"
                            >
                                <div class="w-14" v-if="cardFor(row.card_id)">
                                    <CardImage
                                        :card="cardFor(row.card_id) as CardData"
                                        size="thumbnail"
                                    />
                                </div>
                                <div class="space-y-1">
                                    <p class="text-ink">
                                        {{ cardName(row.card_id) }}
                                    </p>
                                    <button
                                        type="button"
                                        class="min-h-11 text-sm text-muted hover:text-ink"
                                        @click="
                                            picking = { index, slot: 'main' }
                                        "
                                    >
                                        Change card
                                    </button>
                                </div>
                            </div>
                            <button
                                v-else
                                type="button"
                                class="min-h-11 rounded-md border border-line px-3 text-sm text-ink hover:border-accent"
                                @click="picking = { index, slot: 'main' }"
                            >
                                Choose a card
                            </button>

                            <div
                                v-if="
                                    row.clarifier_ids.length ||
                                    row.jumper_ids.length
                                "
                                class="space-y-2"
                            >
                                <p class="text-xs text-muted">
                                    Clarifiers and jumpers
                                </p>
                                <ul class="flex flex-wrap gap-2">
                                    <li
                                        v-for="id in row.clarifier_ids"
                                        :key="`c-${id}`"
                                        class="flex items-center gap-2 rounded-full bg-surface-2 py-1 pr-1 pl-3 text-sm text-ink"
                                    >
                                        {{ cardName(id) }}
                                        <span class="text-xs text-muted"
                                            >clarifier</span
                                        >
                                        <button
                                            type="button"
                                            class="px-2 text-xs text-muted hover:text-ink"
                                            @click="switchToJumper(row, id)"
                                        >
                                            Make jumper
                                        </button>
                                        <button
                                            type="button"
                                            class="px-2 text-xs text-muted hover:text-ink"
                                            @click="removeClarifier(row, id)"
                                        >
                                            Remove
                                        </button>
                                    </li>
                                    <li
                                        v-for="id in row.jumper_ids"
                                        :key="`j-${id}`"
                                        class="flex items-center gap-2 rounded-full bg-surface-2 py-1 pr-1 pl-3 text-sm text-ink"
                                    >
                                        {{ cardName(id) }}
                                        <span class="text-xs text-muted"
                                            >jumper</span
                                        >
                                        <button
                                            type="button"
                                            class="px-2 text-xs text-muted hover:text-ink"
                                            @click="switchToClarifier(row, id)"
                                        >
                                            Make clarifier
                                        </button>
                                        <button
                                            type="button"
                                            class="px-2 text-xs text-muted hover:text-ink"
                                            @click="removeJumper(row, id)"
                                        >
                                            Remove
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="min-h-11 rounded-md border border-line px-3 text-sm text-muted hover:text-ink"
                                    :disabled="row.card_id === null"
                                    @click="
                                        picking = { index, slot: 'clarifier' }
                                    "
                                >
                                    Add clarifier
                                </button>
                                <button
                                    type="button"
                                    class="min-h-11 rounded-md border border-line px-3 text-sm text-muted hover:text-ink"
                                    :disabled="row.card_id === null"
                                    @click="picking = { index, slot: 'jumper' }"
                                >
                                    Add jumper
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-ink"
                                >Interpretation notes</span
                            >
                            <input
                                v-model="row.notes"
                                type="text"
                                class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                            />
                        </label>
                        <label
                            v-if="row.card_id === null"
                            class="block space-y-2"
                        >
                            <span class="text-sm font-medium text-ink"
                                >Event to observe</span
                            >
                            <input
                                v-model="row.event_note"
                                type="text"
                                maxlength="255"
                                class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                            />
                        </label>
                    </div>

                    <section
                        v-if="picking?.index === index"
                        class="space-y-4 rounded-lg border border-line bg-surface p-4"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-medium text-ink">
                                {{ pickerHeading }}
                            </h2>
                            <button
                                type="button"
                                class="min-h-11 px-3 text-sm text-muted hover:text-ink"
                                @click="picking = null"
                            >
                                Close
                            </button>
                        </div>
                        <CardPicker
                            :cards="cards"
                            :unavailable-ids="usedIdsFor(row)"
                            @pick="pick"
                        />
                    </section>
                </li>
            </ol>

            <div
                class="sticky bottom-20 flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface p-4 md:bottom-4"
            >
                <button
                    type="button"
                    :disabled="!savable || save.processing"
                    class="min-h-11 rounded-md bg-accent px-5 text-sm font-medium text-accent-ink disabled:opacity-50"
                    @click="saveConfirmed"
                >
                    Import {{ confirmed.length }}
                    {{ confirmed.length === 1 ? 'day' : 'days' }}
                </button>
                <p
                    v-if="!savable && confirmed.length > 0"
                    class="text-sm text-danger"
                >
                    Every confirmed day needs a date.
                </p>
                <p
                    v-if="save.hasErrors"
                    class="text-sm text-danger"
                    role="alert"
                >
                    Some days could not be saved. Check the highlighted rows and
                    try again.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
