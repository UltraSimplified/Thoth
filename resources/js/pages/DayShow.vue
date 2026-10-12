<script setup lang="ts">
import { edit as editDay } from '@/actions/App/Http/Controllers/DayController';
import EmptyStatePanel from '@/components/EmptyStatePanel.vue';
import LockBadge from '@/components/LockBadge.vue';
import PageHeader from '@/components/PageHeader.vue';
import EveningSection from '@/components/day/EveningSection.vue';
import ReadingSummary from '@/components/day/ReadingSummary.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DayPageProps } from '@/types/deck';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<DayPageProps>();

const canEdit = !props.locks.morning;
</script>

<template>
    <Head :title="props.dateLabel" />

    <AppLayout>
        <PageHeader
            :title="props.dateLabel"
            :subtitle="props.isToday ? 'Today' : undefined"
        >
            <template #actions>
                <LockBadge
                    v-if="props.locks.morning"
                    :until="props.locks.morningUntil"
                />
                <Link
                    v-if="canEdit"
                    :href="editDay.url(props.date)"
                    class="inline-flex min-h-11 items-center rounded-md border border-line px-4 text-sm text-ink hover:border-accent hover:text-accent"
                >
                    Edit
                </Link>
            </template>
        </PageHeader>

        <div class="space-y-10">
            <section v-if="props.day?.reading" aria-label="Reading">
                <ReadingSummary
                    :reading="props.day.reading"
                    :event-note="props.day.eventNote"
                    :tree="props.tree"
                    :journal-url="props.journalUrl"
                    replaceable-image
                />
            </section>

            <section
                v-else-if="props.day"
                class="space-y-2 rounded-lg border border-line bg-surface p-5"
                aria-label="Note"
            >
                <p class="text-sm text-muted">No card was drawn on this day.</p>
                <p v-if="props.day.eventNote" class="text-ink">
                    {{ props.day.eventNote }}
                </p>
            </section>

            <EmptyStatePanel v-else>
                Nothing was recorded for this day.
                <Link
                    v-if="!props.locks.morning"
                    :href="editDay.url(props.date)"
                    class="ml-1 text-accent underline-offset-2 hover:underline"
                >
                    Record it
                </Link>
            </EmptyStatePanel>

            <EveningSection
                v-if="props.day"
                :date="props.date"
                :day="props.day"
                :theme-names="props.themeNames"
            />
        </div>
    </AppLayout>
</template>
