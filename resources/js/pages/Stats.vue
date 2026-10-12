<script setup lang="ts">
import EmptyStatePanel from '@/components/EmptyStatePanel.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Stats" />

    <AppLayout>
        <PageHeader
            title="Stats"
            subtitle="Expected-vs-observed counts across suits, elements, astrology and the Tree of Life."
        />
        <EmptyStatePanel>
            Deck structure, time patterns, attribution and theme analyses will
            live here, each with a period selector, a comparison against the
            previous period, and an expected-vs-observed chi-square verdict.
        </EmptyStatePanel>
    </AppLayout>
</template>
