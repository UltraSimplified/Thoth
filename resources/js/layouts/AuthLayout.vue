<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-bg px-4 py-12">
        <div
            class="w-full max-w-sm rounded-lg border border-line bg-surface p-8 shadow-sm"
        >
            <div class="mb-6 flex justify-center">
                <AppLogo />
            </div>
            <slot />
        </div>
    </div>
</template>
