<script setup lang="ts">
import { show as showDay } from '@/actions/App/Http/Controllers/DayController';
import PageHeader from '@/components/PageHeader.vue';
import LockBadge from '@/components/LockBadge.vue';
import EveningSection from '@/components/day/EveningSection.vue';
import MorningForm from '@/components/day/MorningForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { DayPageProps } from '@/types/deck';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<DayPageProps>();
</script>

<template>
    <Head :title="`Edit ${props.dateLabel}`" />

    <AppLayout>
        <PageHeader :title="props.dateLabel" subtitle="Edit this day">
            <template #actions>
                <Link
                    :href="showDay.url(props.date)"
                    class="inline-flex min-h-11 items-center rounded-md border border-line px-4 text-sm text-ink hover:border-accent hover:text-accent"
                >
                    Back to the day
                </Link>
            </template>
        </PageHeader>

        <div class="space-y-10">
            <section
                aria-labelledby="morning-section-heading"
                class="space-y-4"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2
                        id="morning-section-heading"
                        class="font-heading text-lg text-ink"
                    >
                        The reading
                    </h2>
                    <LockBadge
                        v-if="props.locks.morning"
                        :until="props.locks.morningUntil"
                    />
                </div>
                <p v-if="props.locks.morning" class="text-sm text-muted">
                    The reading and event note closed after
                    {{ props.locks.morningUntil }}.
                </p>
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
