<script setup lang="ts">
import { updateEvening as saveEvening } from '@/actions/App/Http/Controllers/DayController';
import type { DayData } from '@/types/deck';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    date: string;
    day: DayData;
}>();

const form = useForm({
    outcome_note: props.day.outcomeNote ?? '',
    resonated: props.day.resonated as boolean | null,
    // Themes are no longer edited here. They are sent back unchanged, because a save without them clears them.
    themes: [...props.day.themes],
});

function toggleResonance(value: boolean): void {
    form.resonated = form.resonated === value ? null : value;
}

function submit(): void {
    form.put(saveEvening.url(props.date), { preserveScroll: true });
}
</script>

<template>
    <section
        class="space-y-5 rounded-lg border border-line bg-surface p-5"
        aria-labelledby="evening-heading"
    >
        <h2 id="evening-heading" class="font-heading text-lg text-ink">
            How the day played out
        </h2>

        <form class="space-y-5" @submit.prevent="submit">
            <fieldset :disabled="form.processing" class="space-y-5">
                <div class="space-y-2">
                    <label
                        for="evening-outcome"
                        class="text-sm font-medium text-ink"
                    >
                        Outcome
                        <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <input
                        id="evening-outcome"
                        v-model="form.outcome_note"
                        type="text"
                        maxlength="255"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    />
                    <p
                        v-if="form.errors.outcome_note"
                        class="text-sm text-danger"
                        role="alert"
                    >
                        {{ form.errors.outcome_note }}
                    </p>
                </div>

                <div class="space-y-2">
                    <p
                        class="text-sm font-medium text-ink"
                        id="resonance-label"
                    >
                        Did it resonate?
                    </p>
                    <div
                        class="flex gap-2"
                        role="group"
                        aria-labelledby="resonance-label"
                    >
                        <button
                            type="button"
                            class="min-h-11 rounded-full border px-4 text-sm transition-colors"
                            :class="
                                form.resonated === true
                                    ? 'border-accent bg-accent text-accent-ink'
                                    : 'border-line text-muted hover:text-ink'
                            "
                            :aria-pressed="form.resonated === true"
                            @click="toggleResonance(true)"
                        >
                            Yes
                        </button>
                        <button
                            type="button"
                            class="min-h-11 rounded-full border px-4 text-sm transition-colors"
                            :class="
                                form.resonated === false
                                    ? 'border-accent bg-accent text-accent-ink'
                                    : 'border-line text-muted hover:text-ink'
                            "
                            :aria-pressed="form.resonated === false"
                            @click="toggleResonance(false)"
                        >
                            No
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="min-h-11 rounded-md bg-accent px-5 text-sm font-medium text-accent-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                    >
                        Save evening
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
    </section>
</template>
