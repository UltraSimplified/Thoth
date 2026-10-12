<script setup lang="ts">
import { edit as editDay } from '@/actions/App/Http/Controllers/DayController';
import EveningSection from '@/components/day/EveningSection.vue';
import MorningForm from '@/components/day/MorningForm.vue';
import ReadingSummary from '@/components/day/ReadingSummary.vue';
import JournalReminder from '@/components/JournalReminder.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DayPageProps } from '@/types/deck';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<DayPageProps>();
</script>

<template>
    <Head title="Today" />

    <AppLayout>
        <PageHeader title="Today" :subtitle="props.dateLabel" />

        <JournalReminder v-if="props.showJournalReminder" />

        <div class="space-y-10">
            <section
                v-if="props.day?.reading"
                class="space-y-6"
                aria-label="Today's reading"
            >
                <div v-if="!props.locks.morning" class="flex justify-end">
                    <Link
                        :href="editDay.url(props.date)"
                        class="inline-flex min-h-11 items-center px-3 text-sm text-muted hover:text-ink"
                    >
                        Edit this day
                    </Link>
                </div>

                <ReadingSummary
                    :reading="props.day.reading"
                    :event-note="props.day.eventNote"
                    :tree="props.tree"
                    :journal-url="props.journalUrl"
                />
            </section>

            <section v-else aria-label="Log today's card">
                <MorningForm
                    :date="props.date"
                    :day="props.day"
                    :cards="props.cards"
                    :disabled="props.locks.morning"
                />
            </section>

            <EveningSection
                v-if="props.day"
                :date="props.date"
                :day="props.day"
                :theme-names="props.themeNames"
            />
        </div>
    </AppLayout>
</template>
