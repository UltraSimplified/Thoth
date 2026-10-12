<script setup lang="ts">
import { update as saveDay } from '@/actions/App/Http/Controllers/DayController';
import CardImage from '@/components/cards/CardImage.vue';
import CardPicker from '@/components/cards/CardPicker.vue';
import type { CardData, DayData } from '@/types/deck';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        date: string;
        day: DayData | null;
        cards: CardData[];
        disabled?: boolean;
    }>(),
    { disabled: false },
);

type Slot = 'main' | 'jumper' | 'clarifier';

const reading = props.day?.reading ?? null;

const form = useForm({
    event_note: props.day?.eventNote ?? '',
    card_id: reading?.card.id ?? (null as number | null),
    question: reading?.question ?? '',
    interpretation_notes: reading?.interpretationNotes ?? '',
    jumper_ids: reading?.jumpers.map((card) => card.id) ?? ([] as number[]),
    clarifier_ids:
        reading?.clarifiers.map((card) => card.id) ?? ([] as number[]),
});

const cardsById = computed(
    () => new Map(props.cards.map((card) => [card.id, card])),
);

const mainCard = computed(() =>
    form.card_id === null ? null : (cardsById.value.get(form.card_id) ?? null),
);
const jumperCards = computed(() =>
    form.jumper_ids.map((id) => cardsById.value.get(id)).filter(isCard),
);
const clarifierCards = computed(() =>
    form.clarifier_ids.map((id) => cardsById.value.get(id)).filter(isCard),
);

const usedIds = computed(() => [
    ...(form.card_id === null ? [] : [form.card_id]),
    ...form.jumper_ids,
    ...form.clarifier_ids,
]);

const pickingFor = ref<Slot | null>(null);

const pickerHeading = computed(() => {
    switch (pickingFor.value) {
        case 'main':
            return 'Choose the main card';
        case 'jumper':
            return 'Add a jumper';
        case 'clarifier':
            return 'Add a clarifier';
        default:
            return '';
    }
});

function isCard(card: CardData | undefined): card is CardData {
    return card !== undefined;
}

function pick(card: CardData): void {
    if (pickingFor.value === 'main') {
        form.card_id = card.id;
    } else if (pickingFor.value === 'jumper') {
        form.jumper_ids.push(card.id);
    } else if (pickingFor.value === 'clarifier') {
        form.clarifier_ids.push(card.id);
    }

    pickingFor.value = null;
}

function removeJumper(cardId: number): void {
    form.jumper_ids = form.jumper_ids.filter((id) => id !== cardId);
}

function removeClarifier(cardId: number): void {
    form.clarifier_ids = form.clarifier_ids.filter((id) => id !== cardId);
}

function submit(): void {
    form.put(saveDay.url(props.date), { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <fieldset
            :disabled="disabled || form.processing"
            class="space-y-6 disabled:opacity-60"
        >
            <div class="space-y-2">
                <label
                    for="morning-question"
                    class="text-sm font-medium text-ink"
                >
                    Question
                    <span class="font-normal text-muted"
                        >(optional, asked before drawing)</span
                    >
                </label>
                <input
                    id="morning-question"
                    v-model="form.question"
                    type="text"
                    maxlength="500"
                    class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none"
                />
            </div>

            <section class="space-y-3" aria-labelledby="main-card-heading">
                <h2 id="main-card-heading" class="text-sm font-medium text-ink">
                    Card drawn
                </h2>

                <div v-if="mainCard" class="flex items-center gap-4">
                    <CardImage
                        :card="mainCard"
                        size="thumbnail"
                        class="w-20 shrink-0"
                    />
                    <div class="min-w-0 space-y-2">
                        <p class="truncate font-heading text-lg text-ink">
                            {{ mainCard.name }}
                        </p>
                        <button
                            type="button"
                            class="min-h-11 rounded-md border border-line px-3 text-sm text-muted hover:text-ink"
                            @click="pickingFor = 'main'"
                        >
                            Change card
                        </button>
                        <button
                            type="button"
                            class="ml-2 min-h-11 px-3 text-sm text-muted underline-offset-2 hover:text-ink hover:underline"
                            @click="form.card_id = null"
                        >
                            No card today
                        </button>
                    </div>
                </div>

                <button
                    v-else
                    type="button"
                    class="min-h-11 rounded-md bg-accent px-4 text-sm font-medium text-accent-ink"
                    @click="pickingFor = 'main'"
                >
                    Choose the card
                </button>
                <p
                    v-if="form.errors.card_id"
                    class="text-sm text-danger"
                    role="alert"
                >
                    {{ form.errors.card_id }}
                </p>
            </section>

            <section class="space-y-3" aria-labelledby="jumpers-heading">
                <h2 id="jumpers-heading" class="text-sm font-medium text-ink">
                    Jumpers
                    <span class="font-normal text-muted"
                        >(cards that jumped out while shuffling)</span
                    >
                </h2>
                <ul v-if="jumperCards.length" class="flex flex-wrap gap-3">
                    <li v-for="card in jumperCards" :key="card.id" class="w-20">
                        <CardImage :card="card" size="thumbnail" />
                        <p class="mt-1 truncate text-xs text-muted">
                            {{ card.name }}
                        </p>
                        <button
                            type="button"
                            class="min-h-8 text-xs text-muted underline-offset-2 hover:text-ink hover:underline"
                            @click="removeJumper(card.id)"
                        >
                            Remove
                        </button>
                    </li>
                </ul>
                <button
                    type="button"
                    class="min-h-11 rounded-md border border-line px-3 text-sm text-muted hover:text-ink"
                    :disabled="mainCard === null"
                    @click="pickingFor = 'jumper'"
                >
                    Add jumper
                </button>
            </section>

            <section class="space-y-3" aria-labelledby="clarifiers-heading">
                <h2
                    id="clarifiers-heading"
                    class="text-sm font-medium text-ink"
                >
                    Clarifiers
                    <span class="font-normal text-muted"
                        >(extra cards drawn to clarify the main card)</span
                    >
                </h2>
                <ul v-if="clarifierCards.length" class="flex flex-wrap gap-3">
                    <li
                        v-for="card in clarifierCards"
                        :key="card.id"
                        class="w-20"
                    >
                        <CardImage :card="card" size="thumbnail" />
                        <p class="mt-1 truncate text-xs text-muted">
                            {{ card.name }}
                        </p>
                        <button
                            type="button"
                            class="min-h-8 text-xs text-muted underline-offset-2 hover:text-ink hover:underline"
                            @click="removeClarifier(card.id)"
                        >
                            Remove
                        </button>
                    </li>
                </ul>
                <button
                    type="button"
                    class="min-h-11 rounded-md border border-line px-3 text-sm text-muted hover:text-ink"
                    :disabled="mainCard === null"
                    @click="pickingFor = 'clarifier'"
                >
                    Add clarifier
                </button>
            </section>

            <section
                v-if="pickingFor"
                class="space-y-4 rounded-lg border border-line bg-surface p-4"
                aria-live="polite"
            >
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-medium text-ink">
                        {{ pickerHeading }}
                    </h2>
                    <button
                        type="button"
                        class="min-h-11 px-3 text-sm text-muted hover:text-ink"
                        @click="pickingFor = null"
                    >
                        Close
                    </button>
                </div>
                <CardPicker
                    :cards="cards"
                    :unavailable-ids="usedIds"
                    @pick="pick"
                />
            </section>

            <div class="space-y-2">
                <label for="morning-event" class="text-sm font-medium text-ink">
                    Event to observe
                    <span class="font-normal text-muted">(optional)</span>
                </label>
                <input
                    id="morning-event"
                    v-model="form.event_note"
                    type="text"
                    maxlength="255"
                    class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none"
                />
                <p
                    v-if="form.errors.event_note"
                    class="text-sm text-danger"
                    role="alert"
                >
                    {{ form.errors.event_note }}
                </p>
            </div>

            <div class="space-y-2">
                <label
                    for="morning-interpretation"
                    class="text-sm font-medium text-ink"
                >
                    Interpretation notes
                    <span class="font-normal text-muted">(optional)</span>
                </label>
                <textarea
                    id="morning-interpretation"
                    v-model="form.interpretation_notes"
                    rows="4"
                    class="w-full resize-y rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none"
                />
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    class="min-h-11 rounded-md bg-accent px-5 text-sm font-medium text-accent-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                >
                    {{ form.card_id === null ? 'Save note' : 'Save reading' }}
                </button>
                <p
                    v-if="form.recentlySuccessful"
                    class="text-sm text-muted"
                    aria-live="polite"
                >
                    Saved.
                </p>
            </div>
        </fieldset>
    </form>
</template>
