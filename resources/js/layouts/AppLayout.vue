<script setup lang="ts">
import AppBottomTabBar from '@/layouts/AppBottomTabBar.vue';
import AppIconRail from '@/layouts/AppIconRail.vue';
import AppMoreSheet from '@/layouts/AppMoreSheet.vue';
import AppSidebar from '@/layouts/AppSidebar.vue';
import AppTopBar from '@/layouts/AppTopBar.vue';
import { ref } from 'vue';

const isMoreOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-bg text-ink">
        <a
            href="#main-content"
            class="sr-only focus-visible:not-sr-only focus-visible:fixed focus-visible:top-2 focus-visible:left-2 focus-visible:z-50 focus-visible:rounded-md focus-visible:bg-accent focus-visible:px-4 focus-visible:py-2 focus-visible:text-sm focus-visible:font-medium focus-visible:text-accent-ink"
        >
            Skip to content
        </a>

        <AppSidebar />
        <AppIconRail />
        <AppTopBar />

        <main
            id="main-content"
            tabindex="-1"
            class="min-h-screen pt-14 pb-[calc(4rem_+_env(safe-area-inset-bottom))] md:pt-0 md:pb-0 md:pl-18 lg:pl-62"
        >
            <div class="mx-auto w-full max-w-[1100px] px-4 py-6 lg:px-8">
                <slot />
            </div>
        </main>

        <AppBottomTabBar :open="isMoreOpen" @more="isMoreOpen = true" />
        <AppMoreSheet :open="isMoreOpen" @update:open="isMoreOpen = $event" />
    </div>
</template>
