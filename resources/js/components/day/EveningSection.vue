<script setup lang="ts">
import { updateEvening as saveEvening } from '@/actions/App/Http/Controllers/DayController';
import type { DayData } from '@/types/deck';
import { useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    date: string;
    day: DayData;
    themeNames: string[];
}>();

const form = useForm({
    outcome_note: props.day.outcomeNote ?? '',
    resonated: props.day.resonated as boolean | null,
    themes: [...props.day.themes],
});

const newTheme = ref('');

const suggestions = computed(() => {
    const typed = newTheme.value.trim().toLowerCase();

    return props.themeNames.filter(
        (name) =>
            !form.themes.some(
                (selected) => selected.toLowerCase() === name.toLowerCase(),
            ) && name.toLowerCase().includes(typed),
    );
});

function addTheme(raw: string): void {
    const name = raw.trim().replace(/\s+/g, ' ');

    if (
        name !== '' &&
        !form.themes.some(
            (selected) => selected.toLowerCase() === name.toLowerCase(),
        )
    ) {
        form.themes.push(name);
    }

    newTheme.value = '';
}

function removeTheme(name: string): void {
    form.themes = form.themes.filter((selected) => selected !== name);
}

function toggleResonance(value: boolean): void {
    form.resonated = form.resonated === value ? null : value;
}

function onThemeKey(event: KeyboardEvent): void {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addTheme(newTheme.value);
    }
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

                <div class="space-y-2">
                    <p class="text-sm font-medium text-ink" id="themes-label">
                        Themes
                    </p>
                    <ul
                        v-if="form.themes.length"
                        class="flex flex-wrap gap-2"
                        aria-labelledby="themes-label"
                    >
                        <li
                            v-for="theme in form.themes"
                            :key="theme"
                            class="inline-flex min-h-8 items-center gap-1 rounded-full bg-surface-2 py-1 pr-1 pl-3 text-sm text-ink"
                        >
                            {{ theme }}
                            <button
                                type="button"
                                class="inline-flex size-7 items-center justify-center rounded-full text-muted hover:text-ink"
                                :aria-label="`Remove theme ${theme}`"
                                @click="removeTheme(theme)"
                            >
                                <X :size="14" aria-hidden="true" />
                            </button>
                        </li>
                    </ul>
                    <label class="block">
                        <span class="sr-only">Add a theme</span>
                        <input
                            v-model="newTheme"
                            type="text"
                            maxlength="60"
                            placeholder="Add a theme and press Enter"
                            class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none"
                            @keydown="onThemeKey"
                        />
                    </label>
                    <ul
                        v-if="suggestions.length && newTheme.trim()"
                        class="flex flex-wrap gap-2"
                    >
                        <li v-for="name in suggestions.slice(0, 8)" :key="name">
                            <button
                                type="button"
                                class="min-h-8 rounded-full border border-line px-3 text-xs text-muted hover:text-ink"
                                @click="addTheme(name)"
                            >
                                {{ name }}
                            </button>
                        </li>
                    </ul>
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
