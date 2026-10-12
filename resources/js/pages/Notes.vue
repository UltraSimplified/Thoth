<script setup lang="ts">
import CardImage from '@/components/cards/CardImage.vue';
import EmptyStatePanel from '@/components/EmptyStatePanel.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CardData } from '@/types/deck';
import { Head, Link } from '@inertiajs/vue3';

interface NoteEntry {
    date: string;
    dateLabel: string;
    url: string;
    eventNote: string;
    outcomeNote: string | null;
    resonated: boolean | null;
    themes: string[];
    card: CardData | null;
}

defineProps<{ notes: NoteEntry[] }>();

function resonanceLabel(resonated: boolean | null): string {
    if (resonated === null) {
        return 'Not recorded';
    }

    return resonated ? 'Resonated' : 'Did not resonate';
}
</script>

<template>
    <Head title="Notes" />

    <AppLayout>
        <PageHeader
            title="Notes"
            subtitle="Every event note, with its card, outcome and resonance."
        />

        <EmptyStatePanel v-if="notes.length === 0">
            No event notes yet. Notes appear here once an event to observe is
            saved on the Today screen.
        </EmptyStatePanel>

        <ol v-else class="space-y-4">
            <li
                v-for="note in notes"
                :key="note.date"
                class="grid gap-4 rounded-lg border border-line bg-surface p-4 sm:grid-cols-[88px_minmax(0,1fr)]"
            >
                <div v-if="note.card" class="w-20">
                    <CardImage :card="note.card" size="thumbnail" />
                </div>
                <div class="min-w-0 space-y-2">
                    <div
                        class="flex flex-wrap items-baseline justify-between gap-2"
                    >
                        <Link
                            :href="note.url"
                            class="text-sm font-medium text-ink hover:text-accent hover:underline"
                        >
                            {{ note.dateLabel }}
                        </Link>
                        <span class="text-xs text-muted">{{
                            resonanceLabel(note.resonated)
                        }}</span>
                    </div>
                    <p v-if="note.card" class="text-sm text-muted">
                        {{ note.card.name }}
                    </p>
                    <p class="text-ink">{{ note.eventNote }}</p>
                    <p v-if="note.outcomeNote" class="text-sm text-muted">
                        Outcome: {{ note.outcomeNote }}
                    </p>
                    <ul
                        v-if="note.themes.length"
                        class="flex flex-wrap gap-1.5"
                    >
                        <li
                            v-for="theme in note.themes"
                            :key="theme"
                            class="rounded-full bg-surface-2 px-2.5 py-0.5 text-xs text-ink"
                        >
                            {{ theme }}
                        </li>
                    </ul>
                </div>
            </li>
        </ol>
    </AppLayout>
</template>
