<script setup lang="ts">
import { navigation } from '@/config/navigation';
import { Link, usePage } from '@inertiajs/vue3';
import { MoreHorizontal } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ more: [] }>();

const page = usePage();
const currentComponent = computed(() => page.component);
const tabItems = computed(() => navigation.filter((item) => item.tabBar));
const moreItems = computed(() => navigation.filter((item) => !item.tabBar));
const isMoreActive = computed(() =>
    moreItems.value.some((item) => item.component === currentComponent.value),
);
</script>

<template>
    <nav
        aria-label="Primary"
        class="fixed inset-x-0 bottom-0 z-40 flex flex-col border-t border-line bg-surface md:hidden"
    >
        <div class="flex">
            <Link
                v-for="item in tabItems"
                :key="item.component"
                :href="item.href"
                :aria-current="
                    currentComponent === item.component ? 'page' : undefined
                "
                class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 py-2 text-xs text-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                :class="
                    currentComponent === item.component ? 'text-accent' : ''
                "
            >
                <component
                    :is="item.icon"
                    :size="20"
                    aria-hidden="true"
                    :class="
                        currentComponent === item.component ? '' : 'text-icon'
                    "
                />
                <span>{{ item.label }}</span>
            </Link>
            <button
                type="button"
                :aria-expanded="props.open"
                aria-haspopup="dialog"
                class="flex min-h-11 flex-1 flex-col items-center justify-center gap-1 py-2 text-xs text-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                :class="isMoreActive ? 'text-accent' : ''"
                @click="emit('more')"
            >
                <MoreHorizontal
                    :size="20"
                    aria-hidden="true"
                    :class="isMoreActive ? '' : 'text-icon'"
                />
                <span>More</span>
            </button>
        </div>
        <div class="h-[env(safe-area-inset-bottom)]" aria-hidden="true" />
    </nav>
</template>
