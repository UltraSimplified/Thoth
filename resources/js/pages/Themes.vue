<script setup lang="ts">
import EmptyStatePanel from '@/components/EmptyStatePanel.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Themes" />

    <AppLayout>
        <PageHeader
            title="Themes"
            subtitle="Manage the free-form tags used to describe how days went."
        />
        <EmptyStatePanel>
            Theme rename and merge tools, so drifting tags can be tidied, plus
            the theme-by-period heat map, will live here.
        </EmptyStatePanel>
    </AppLayout>
</template>
