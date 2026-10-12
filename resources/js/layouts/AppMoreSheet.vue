<script setup lang="ts">
import { navigation } from '@/config/navigation';
import { Link, usePage } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const page = usePage();
const currentComponent = ref(page.component);
const items = navigation.filter((item) => !item.tabBar);

const sheetRef = ref<HTMLElement | null>(null);

function close(): void {
    emit('update:open', false);
}

watch(
    () => props.open,
    async (open) => {
        if (open) {
            currentComponent.value = page.component;
            await nextTick();
            sheetRef.value?.querySelector<HTMLElement>('a, button')?.focus();
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 md:hidden">
            <div class="absolute inset-0 bg-ink/40" @click="close" />
            <div
                ref="sheetRef"
                role="dialog"
                aria-modal="true"
                aria-label="More navigation"
                class="absolute inset-x-0 bottom-0 rounded-t-2xl border-t border-line bg-surface p-4 shadow-lg"
                style="padding-bottom: calc(1rem + env(safe-area-inset-bottom))"
                @keydown.escape="close"
            >
                <div
                    class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-line"
                    aria-hidden="true"
                />
                <ul class="space-y-1">
                    <li v-for="item in items" :key="item.component">
                        <Link
                            :href="item.href"
                            :aria-current="
                                currentComponent === item.component
                                    ? 'page'
                                    : undefined
                            "
                            class="group flex min-h-11 items-center gap-3 rounded-md px-3 text-sm text-muted hover:bg-surface-2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                            :class="
                                currentComponent === item.component
                                    ? 'text-accent'
                                    : ''
                            "
                            @click="close"
                        >
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
                <button
                    type="button"
                    class="mt-4 flex min-h-11 w-full items-center justify-center rounded-md border border-line text-sm text-muted hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                    @click="close"
                >
                    Close
                </button>
            </div>
        </div>
    </Teleport>
</template>
