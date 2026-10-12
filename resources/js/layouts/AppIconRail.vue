<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { navigation } from '@/config/navigation';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentComponent = computed(() => page.component);
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 hidden w-18 flex-col items-center border-r border-line bg-surface py-4 md:flex lg:hidden"
    >
        <AppLogo variant="mark" class="mb-6" />

        <nav
            aria-label="Primary"
            class="flex flex-1 flex-col items-center gap-1"
        >
            <Link
                v-for="item in navigation"
                :key="item.component"
                :href="item.href"
                :aria-label="item.label"
                :aria-current="
                    currentComponent === item.component ? 'page' : undefined
                "
                class="group relative flex h-11 w-11 items-center justify-center rounded-md text-muted transition-colors hover:bg-surface-2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                :class="
                    currentComponent === item.component
                        ? 'bg-surface-2 text-ink'
                        : ''
                "
            >
                <span
                    v-if="currentComponent === item.component"
                    aria-hidden="true"
                    class="absolute inset-y-1 left-0 w-[3px] rounded-full bg-accent"
                />
                <component
                    :is="item.icon"
                    :size="20"
                    aria-hidden="true"
                    :class="
                        currentComponent === item.component
                            ? ''
                            : 'text-icon group-hover:text-current'
                    "
                />
                <span
                    aria-hidden="true"
                    role="tooltip"
                    class="pointer-events-none absolute left-full ml-2 rounded-md border border-line bg-surface px-2 py-1 text-xs whitespace-nowrap text-ink opacity-0 shadow-sm transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
                    >{{ item.label }}</span
                >
            </Link>
        </nav>

        <ThemeSwitcher orientation="vertical" />
    </aside>
</template>
