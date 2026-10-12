<script setup lang="ts">
import { show as showCard } from '@/actions/App/Http/Controllers/CardController';
import {
    cardNotes as cardNotesExport,
    days as daysExport,
    json as jsonExport,
} from '@/actions/App/Http/Controllers/ExportController';
import { store as restoreStore } from '@/actions/App/Http/Controllers/RestoreController';
import { update as updateSettings } from '@/actions/App/Http/Controllers/SettingsController';
import PageHeader from '@/components/PageHeader.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SettingsData {
    theme: string;
    birthDate: string | null;
    seasonDefinition: string;
    treePlacement: string;
    showExcerpts: boolean;
}

const props = defineProps<{
    status: string | null;
    settings: SettingsData;
    birthCards: { role: string; name: string; slug: string }[];
}>();

const birthDate = ref(props.settings.birthDate ?? '');
const seasonDefinition = ref(props.settings.seasonDefinition);
const treePlacement = ref(props.settings.treePlacement);

// The date in London, so "today" is the owner's day rather than the UTC day.
const today = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Europe/London',
}).format(new Date());

function save(data: Record<string, string | boolean | null>): void {
    router.patch(updateSettings.url(), data, {
        preserveScroll: true,
        preserveState: true,
    });
}

function saveBirthDate(): void {
    save({ birth_date: birthDate.value === '' ? null : birthDate.value });
}

function saveSeason(): void {
    save({ season_definition: seasonDefinition.value });
}

function saveTreePlacement(): void {
    save({ tree_placement: treePlacement.value });
}

const restore = useForm<{ backup: File | null }>({ backup: null });

function onBackupChosen(event: Event): void {
    const input = event.target as HTMLInputElement;
    restore.backup = input.files?.[0] ?? null;
}

function submitRestore(): void {
    restore.post(restoreStore.url(), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => restore.reset(),
    });
}
</script>

<template>
    <Head title="Settings" />

    <AppLayout>
        <PageHeader
            title="Settings"
            subtitle="Theme, birth cards, the season definition and the Tree placement."
        />

        <p
            v-if="status"
            class="mb-6 rounded-md border border-line bg-surface px-4 py-3 text-sm text-ink"
            role="status"
        >
            {{ status }}
        </p>

        <div class="divide-y divide-line">
            <section
                class="space-y-3 py-6"
                aria-labelledby="appearance-heading"
            >
                <h2
                    id="appearance-heading"
                    class="font-heading text-lg text-ink"
                >
                    Appearance
                </h2>
                <p class="text-sm text-muted">
                    System follows your phone or browser. Goblin is the green
                    dark theme.
                </p>
                <ThemeSwitcher />
            </section>

            <section class="space-y-4 py-6" aria-labelledby="birth-heading">
                <h2 id="birth-heading" class="font-heading text-lg text-ink">
                    Birth cards
                </h2>
                <p class="text-sm text-muted">
                    Set once. Your date of birth gives the cards that are yours,
                    shown with a badge wherever they appear.
                </p>

                <div class="space-y-2">
                    <label for="birth-date" class="text-sm font-medium text-ink"
                        >Date of birth</label
                    >
                    <div class="flex flex-wrap gap-2">
                        <input
                            id="birth-date"
                            v-model="birthDate"
                            type="date"
                            :max="today"
                            class="min-h-11 rounded-md border border-line bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none"
                        />
                        <button
                            type="button"
                            class="min-h-11 rounded-md bg-accent px-4 text-sm font-medium text-accent-ink"
                            @click="saveBirthDate"
                        >
                            Save date
                        </button>
                    </div>
                </div>

                <ul v-if="birthCards.length" class="grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="entry in birthCards"
                        :key="`${entry.role}-${entry.slug}`"
                        class="flex items-baseline justify-between gap-3 rounded-md border border-line bg-surface px-3 py-2 text-sm"
                    >
                        <Link
                            :href="showCard.url(entry.slug)"
                            class="text-ink hover:text-accent hover:underline"
                        >
                            {{ entry.name }}
                        </Link>
                        <span class="text-xs text-muted">{{ entry.role }}</span>
                    </li>
                </ul>
            </section>

            <section class="space-y-4 py-6" aria-labelledby="season-heading">
                <h2 id="season-heading" class="font-heading text-lg text-ink">
                    Seasons
                </h2>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-ink"
                        >Season definition</span
                    >
                    <select
                        v-model="seasonDefinition"
                        class="min-h-11 w-full max-w-md rounded-md border border-line bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none"
                        @change="saveSeason"
                    >
                        <option value="meteorological">
                            Meteorological: Winter Dec–Feb, Spring Mar–May,
                            Summer Jun–Aug, Autumn Sep–Nov
                        </option>
                        <option value="astronomical">
                            Astronomical: solstices and equinoxes
                        </option>
                        <option value="celtic">
                            Celtic fire festivals: Samhain, Imbolc, Beltane and
                            Lughnasadh open each season
                        </option>
                    </select>
                </label>
            </section>

            <section
                class="space-y-4 py-6"
                aria-labelledby="tree-placement-heading"
            >
                <h2
                    id="tree-placement-heading"
                    class="font-heading text-lg text-ink"
                >
                    Tree of Life
                </h2>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-ink"
                        >Placement of The Emperor and The Star</span
                    >
                    <select
                        v-model="treePlacement"
                        class="min-h-11 w-full max-w-md rounded-md border border-line bg-surface px-3 text-sm text-ink focus:border-accent focus:outline-none"
                        @change="saveTreePlacement"
                    >
                        <option value="crowley">
                            Crowley: The Emperor on path 28, The Star on path 15
                        </option>
                        <option value="golden_dawn">
                            Golden Dawn: The Emperor on path 15, The Star on
                            path 28
                        </option>
                    </select>
                </label>
            </section>

            <section class="space-y-4 py-6" aria-labelledby="excerpts-heading">
                <h2 id="excerpts-heading" class="font-heading text-lg text-ink">
                    Excerpts
                </h2>
                <p class="text-sm text-muted">
                    Short excerpts from thothreadings.com are a later option and
                    are off by default. Not built yet.
                </p>
                <label class="flex items-center gap-3 text-sm text-muted">
                    <input
                        type="checkbox"
                        disabled
                        :checked="settings.showExcerpts"
                        class="size-5"
                    />
                    Show excerpts (coming later)
                </label>
            </section>

            <section class="space-y-4 py-6" aria-labelledby="backup-heading">
                <h2 id="backup-heading" class="font-heading text-lg text-ink">
                    Export and restore
                </h2>
                <p class="text-sm text-muted">
                    The backup holds every day, reading, jumper, clarifier,
                    theme and card note. Restoring replaces the days it names
                    and leaves other days alone.
                </p>

                <ul class="flex flex-wrap gap-2">
                    <li>
                        <a
                            :href="jsonExport.url()"
                            class="inline-flex min-h-11 items-center rounded-md border border-line px-4 text-sm text-ink hover:border-accent hover:text-accent"
                        >
                            Backup (JSON)
                        </a>
                    </li>
                    <li>
                        <a
                            :href="daysExport.url()"
                            class="inline-flex min-h-11 items-center rounded-md border border-line px-4 text-sm text-ink hover:border-accent hover:text-accent"
                        >
                            Days (CSV)
                        </a>
                    </li>
                    <li>
                        <a
                            :href="cardNotesExport.url()"
                            class="inline-flex min-h-11 items-center rounded-md border border-line px-4 text-sm text-ink hover:border-accent hover:text-accent"
                        >
                            Card notes (CSV)
                        </a>
                    </li>
                </ul>

                <form class="space-y-3" @submit.prevent="submitRestore">
                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-ink"
                            >Restore from a backup file</span
                        >
                        <input
                            type="file"
                            accept="application/json,.json"
                            class="block min-h-11 w-full max-w-md text-sm text-muted file:mr-3 file:min-h-11 file:rounded-md file:border file:border-line file:bg-surface file:px-3 file:text-ink"
                            @change="onBackupChosen"
                        />
                    </label>
                    <p
                        v-for="(message, field) in restore.errors"
                        :key="field"
                        class="text-sm text-danger"
                        role="alert"
                    >
                        {{ message }}
                    </p>
                    <button
                        type="submit"
                        :disabled="!restore.backup || restore.processing"
                        class="min-h-11 rounded-md bg-accent px-4 text-sm font-medium text-accent-ink disabled:opacity-50"
                    >
                        Restore backup
                    </button>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
