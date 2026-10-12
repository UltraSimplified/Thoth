<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { navGroups, navigation } from '@/config/navigation';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentComponent = computed(() => page.component);
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 hidden w-62 flex-col border-r border-line bg-surface lg:flex"
    >
        <div class="flex h-16 items-center px-6">
            <AppLogo />
        </div>

        <nav aria-label="Primary" class="flex-1 overflow-y-auto px-3 py-2">
            <div v-for="group in navGroups" :key="group.id" class="mb-6">
                <p
                    class="px-3 text-xs font-medium tracking-wide text-muted uppercase"
                >
                    {{ group.label }}
                </p>
                <ul class="mt-2 space-y-1">
                    <li
                        v-for="item in navigation.filter(
                            (i) => i.group === group.id,
                        )"
                        :key="item.component"
                    >
                        <Link
                            :href="item.href"
                            :aria-current="
                                currentComponent === item.component
                                    ? 'page'
                                    : undefined
                            "
                            class="group relative flex min-h-11 items-center gap-3 rounded-md px-3 text-sm text-muted transition-colors hover:bg-surface-2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
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
                                :size="18"
                                aria-hidden="true"
                                :class="
                                    currentComponent === item.component
                                        ? ''
                                        : 'text-icon group-hover:text-current'
                                "
                            />
                            <span>{{ item.label }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="border-t border-line p-4">
            <ThemeSwitcher />
        </div>
    </aside>
</template>
