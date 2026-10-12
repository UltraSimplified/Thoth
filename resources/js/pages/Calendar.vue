<script setup lang="ts">
import EmptyStatePanel from '@/components/EmptyStatePanel.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Suit } from '@/types/deck';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, onMounted, reactive } from 'vue';

interface GridCell {
    date: string;
    dayNumber: number;
    inMonth: boolean;
    isToday: boolean;
    url: string;
    noteOnly: boolean;
    card: { name: string; thumbnailUrl: string; suit: Suit | null } | null;
    jumpers: number;
    clarifiers: number;
}

interface ListRow {
    date: string;
    dateLabel: string;
    url: string;
    card: { name: string; thumbnailUrl: string } | null;
    question: string | null;
    eventNote: string | null;
    resonated: boolean | null;
    themes: string[];
}

interface Filters {
    q?: string;
    from?: string;
    to?: string;
    suit?: string;
    arcana?: string;
    element?: string;
    planet?: string;
    sign?: string;
    theme?: string;
    resonance?: string;
}

const props = defineProps<{
    view: 'grid' | 'list';
    month: {
        label: string;
        prevUrl: string;
        nextUrl: string;
        todayUrl: string;
        gridUrl: string;
        listUrl: string;
    };
    weekdays?: string[];
    weeks?: GridCell[][];
    filters?: Filters;
    options?: {
        suits: { value: Suit; label: string }[];
        elements: string[];
        planets: string[];
        signs: string[];
        themes: string[];
    };
    days?: {
        data: ListRow[];
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

// Remember the last view on this device, so the calendar opens the way it was last used.
const VIEW_KEY = 'thoth.calendarView';

onMounted(() => {
    const requested = new URLSearchParams(window.location.search).get('view');

    try {
        const remembered = window.localStorage.getItem(VIEW_KEY);

        if (requested === null && remembered === 'list') {
            router.visit(props.month.listUrl, { replace: true });
        }

        window.localStorage.setItem(VIEW_KEY, props.view);
    } catch {
        // Storage can be unavailable in private windows; the calendar works without it.
    }
});

const weekRows = computed(() => props.weeks ?? []);

const suitBorder: Record<string, string> = {
    wands: 'border-t-wands',
    cups: 'border-t-cups',
    swords: 'border-t-swords',
    disks: 'border-t-disks',
    major: 'border-t-majors',
};

function borderFor(cell: GridCell): string {
    if (cell.card === null) {
        return 'border-t-line';
    }

    return suitBorder[cell.card.suit ?? 'major'] ?? 'border-t-line';
}

const filterState = reactive<Filters>({ ...props.filters });

const hasFilters = computed(() =>
    Object.values(filterState).some(
        (value) => value !== undefined && value !== '',
    ),
);

function applyFilters(): void {
    const query: Record<string, string> = { view: 'list' };

    for (const [key, value] of Object.entries(filterState)) {
        if (value !== undefined && value !== '') {
            query[key] = value;
        }
    }

    router.get(window.location.pathname, query, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters(): void {
    for (const key of Object.keys(filterState) as (keyof Filters)[]) {
        filterState[key] = '';
    }

    applyFilters();
}

function resonanceLabel(resonated: boolean | null): string {
    if (resonated === null) {
        return '';
    }

    return resonated ? 'Resonated' : 'Did not resonate';
}
</script>

<template>
    <Head title="Calendar" />

    <AppLayout>
        <PageHeader title="Calendar" :subtitle="month.label">
            <template #actions>
                <nav
                    aria-label="Calendar view"
                    class="inline-flex rounded-full border border-line bg-surface-2 p-1"
                >
                    <Link
                        :href="month.gridUrl"
                        class="min-h-11 rounded-full px-4 text-sm leading-[2.75rem]"
                        :class="
                            view === 'grid'
                                ? 'bg-accent text-accent-ink'
                                : 'text-muted hover:text-ink'
                        "
                        :aria-current="view === 'grid' ? 'page' : undefined"
                    >
                        Grid
                    </Link>
                    <Link
                        :href="month.listUrl"
                        class="min-h-11 rounded-full px-4 text-sm leading-[2.75rem]"
                        :class="
                            view === 'list'
                                ? 'bg-accent text-accent-ink'
                                : 'text-muted hover:text-ink'
                        "
                        :aria-current="view === 'list' ? 'page' : undefined"
                    >
                        List
                    </Link>
                </nav>
            </template>
        </PageHeader>

        <!-- Grid view: one month, Monday first. -->
        <section
            v-if="view === 'grid'"
            class="space-y-4"
            aria-label="Month grid"
        >
            <div class="flex items-center justify-between gap-2">
                <Link
                    :href="month.prevUrl"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-line text-muted hover:text-ink"
                    aria-label="Previous month"
                >
                    <ChevronLeft :size="18" aria-hidden="true" />
                </Link>
                <Link
                    :href="month.todayUrl"
                    class="inline-flex min-h-11 items-center justify-center rounded-md border border-line px-4 text-sm text-muted hover:text-ink"
                    >Today</Link
                >
                <Link
                    :href="month.nextUrl"
                    class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-line text-muted hover:text-ink"
                    aria-label="Next month"
                >
                    <ChevronRight :size="18" aria-hidden="true" />
                </Link>
            </div>

            <table class="w-full table-fixed border-separate border-spacing-1">
                <caption class="sr-only">
                    {{
                        month.label
                    }}
                </caption>
                <thead>
                    <tr>
                        <th
                            v-for="weekday in weekdays"
                            :key="weekday"
                            scope="col"
                            class="pb-1 text-center text-xs font-medium text-muted"
                        >
                            {{ weekday }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(week, weekIndex) in weekRows" :key="weekIndex">
                        <td
                            v-for="cell in week"
                            :key="cell.date"
                            class="align-top"
                        >
                            <Link
                                :href="cell.url"
                                :aria-label="`${cell.date}${cell.card ? `, ${cell.card.name}` : ''}${cell.noteOnly ? ', note only' : ''}`"
                                class="flex min-h-14 flex-col gap-1 rounded-md border border-t-4 border-line bg-surface p-1 text-left transition-colors hover:border-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent md:min-h-24"
                                :class="[
                                    borderFor(cell),
                                    {
                                        'opacity-40': !cell.inMonth,
                                        'ring-1 ring-accent': cell.isToday,
                                    },
                                ]"
                            >
                                <span
                                    class="text-xs"
                                    :class="
                                        cell.isToday
                                            ? 'font-semibold text-accent'
                                            : 'text-ink'
                                    "
                                    >{{ cell.dayNumber }}</span
                                >
                                <img
                                    v-if="cell.card"
                                    :src="cell.card.thumbnailUrl"
                                    :alt="cell.card.name"
                                    loading="lazy"
                                    class="hidden aspect-[3/5] w-full rounded-sm object-cover md:block"
                                />
                                <span
                                    class="mt-auto flex flex-wrap gap-0.5 text-[10px] leading-none text-muted"
                                    aria-hidden="true"
                                >
                                    <span
                                        v-for="n in cell.jumpers"
                                        :key="`j${n}`"
                                        >●</span
                                    >
                                    <span
                                        v-for="n in cell.clarifiers"
                                        :key="`c${n}`"
                                        >○</span
                                    >
                                    <span v-if="cell.noteOnly">·note</span>
                                </span>
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="text-xs text-muted">
                Tinted top border shows the suit. ● jumper, ○ clarifier. Empty
                days open the reading form.
            </p>
        </section>

        <!-- List view: every day, newest first, with filters. -->
        <section v-else class="space-y-6" aria-label="Days list">
            <form
                class="grid gap-3 rounded-lg border border-line bg-surface p-4 sm:grid-cols-2 lg:grid-cols-4"
                @submit.prevent="applyFilters"
            >
                <label class="space-y-1 sm:col-span-2">
                    <span class="text-xs text-muted"
                        >Search questions, notes and outcomes</span
                    >
                    <input
                        v-model="filterState.q"
                        type="search"
                        maxlength="100"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    />
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">From</span>
                    <input
                        v-model="filterState.from"
                        type="date"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    />
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">To</span>
                    <input
                        v-model="filterState.to"
                        type="date"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    />
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">Suit</span>
                    <select
                        v-model="filterState.suit"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    >
                        <option value="">Any suit</option>
                        <option
                            v-for="suit in options?.suits"
                            :key="suit.value"
                            :value="suit.value"
                        >
                            {{ suit.label }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">Arcana</span>
                    <select
                        v-model="filterState.arcana"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    >
                        <option value="">Any</option>
                        <option value="major">Major</option>
                        <option value="minor">Minor</option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">Element</span>
                    <select
                        v-model="filterState.element"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    >
                        <option value="">Any element</option>
                        <option
                            v-for="element in options?.elements"
                            :key="element"
                            :value="element"
                        >
                            {{ element }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">Planet</span>
                    <select
                        v-model="filterState.planet"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    >
                        <option value="">Any planet</option>
                        <option
                            v-for="planet in options?.planets"
                            :key="planet"
                            :value="planet"
                        >
                            {{ planet }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">Sign</span>
                    <select
                        v-model="filterState.sign"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    >
                        <option value="">Any sign</option>
                        <option
                            v-for="sign in options?.signs"
                            :key="sign"
                            :value="sign"
                        >
                            {{ sign }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">Theme</span>
                    <select
                        v-model="filterState.theme"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    >
                        <option value="">Any theme</option>
                        <option
                            v-for="theme in options?.themes"
                            :key="theme"
                            :value="theme"
                        >
                            {{ theme }}
                        </option>
                    </select>
                </label>
                <label class="space-y-1">
                    <span class="text-xs text-muted">Resonance</span>
                    <select
                        v-model="filterState.resonance"
                        class="min-h-11 w-full rounded-md border border-line bg-bg px-3 text-sm text-ink focus:border-accent focus:outline-none"
                    >
                        <option value="">Any</option>
                        <option value="yes">Resonated</option>
                        <option value="no">Did not resonate</option>
                    </select>
                </label>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                    <button
                        type="submit"
                        class="min-h-11 rounded-md bg-accent px-5 text-sm font-medium text-accent-ink"
                    >
                        Apply
                    </button>
                    <button
                        v-if="hasFilters"
                        type="button"
                        class="min-h-11 px-3 text-sm text-muted hover:text-ink"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </form>

            <EmptyStatePanel v-if="!days || days.data.length === 0">
                No days match these filters yet.
            </EmptyStatePanel>

            <ol v-else class="space-y-3">
                <li
                    v-for="row in days.data"
                    :key="row.date"
                    class="grid gap-3 rounded-lg border border-line bg-surface p-4 sm:grid-cols-[72px_minmax(0,1fr)]"
                >
                    <img
                        v-if="row.card"
                        :src="row.card.thumbnailUrl"
                        :alt="row.card.name"
                        loading="lazy"
                        class="aspect-[3/5] w-16 rounded-sm object-cover"
                    />
                    <div class="min-w-0 space-y-1.5">
                        <div
                            class="flex flex-wrap items-baseline justify-between gap-2"
                        >
                            <Link
                                :href="row.url"
                                class="text-sm font-medium text-ink hover:text-accent hover:underline"
                                >{{ row.dateLabel }}</Link
                            >
                            <span class="text-xs text-muted">{{
                                resonanceLabel(row.resonated)
                            }}</span>
                        </div>
                        <p v-if="row.card" class="text-sm text-muted">
                            {{ row.card.name }}
                        </p>
                        <p v-if="row.question" class="text-sm text-ink">
                            {{ row.question }}
                        </p>
                        <p v-if="row.eventNote" class="text-sm text-ink">
                            {{ row.eventNote }}
                        </p>
                        <ul
                            v-if="row.themes.length"
                            class="flex flex-wrap gap-1.5"
                        >
                            <li
                                v-for="theme in row.themes"
                                :key="theme"
                                class="rounded-full bg-surface-2 px-2.5 py-0.5 text-xs text-ink"
                            >
                                {{ theme }}
                            </li>
                        </ul>
                    </div>
                </li>
            </ol>

            <nav
                v-if="days && (days.prev_page_url || days.next_page_url)"
                class="flex justify-between"
                aria-label="Pages of days"
            >
                <Link
                    v-if="days.prev_page_url"
                    :href="days.prev_page_url"
                    class="inline-flex min-h-11 items-center px-3 text-sm text-muted hover:text-ink"
                    >Newer</Link
                >
                <span v-else />
                <Link
                    v-if="days.next_page_url"
                    :href="days.next_page_url"
                    class="inline-flex min-h-11 items-center px-3 text-sm text-muted hover:text-ink"
                    >Older</Link
                >
            </nav>
        </section>
    </AppLayout>
</template>
