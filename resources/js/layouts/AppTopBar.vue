<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-40 flex h-14 items-center justify-between border-b border-line bg-surface px-4 md:hidden"
    >
        <AppLogo />
        <ThemeSwitcher />
    </header>
</template>
