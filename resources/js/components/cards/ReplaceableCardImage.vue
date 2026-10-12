<script setup lang="ts">
import { updateImage as replaceImage } from '@/actions/App/Http/Controllers/CardController';
import CardImage from '@/components/cards/CardImage.vue';
import type { CardData } from '@/types/deck';
import { useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ card: CardData }>();

const input = ref<HTMLInputElement | null>(null);

const form = useForm<{ image: File | null }>({ image: null });

function choose(): void {
    input.value?.click();
}

function upload(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (file === null) {
        return;
    }

    form.clearErrors();
    form.image = file;
    form.post(replaceImage.url(props.card.slug), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            form.image = null;

            // Clear the picker so choosing the same file again still fires a change.
            if (input.value !== null) {
                input.value.value = '';
            }
        },
    });
}
</script>

<template>
    <div class="space-y-2">
        <button
            type="button"
            class="group block w-full rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-wait"
            :disabled="form.processing"
            :aria-label="`Replace the image of ${card.name}`"
            @click="choose"
        >
            <span class="relative block">
                <CardImage :card="card" size="full" />
                <span
                    v-if="form.processing"
                    class="absolute inset-0 flex items-center justify-center rounded-md bg-bg/70 text-sm text-ink"
                    aria-live="polite"
                >
                    Uploading…
                </span>
            </span>
        </button>

        <p class="flex items-center justify-center gap-1.5 text-xs text-muted">
            <Upload :size="12" aria-hidden="true" />
            Click the card to replace its image
        </p>

        <input
            ref="input"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="sr-only"
            tabindex="-1"
            aria-hidden="true"
            @change="upload"
        />

        <p
            v-if="form.errors.image"
            class="text-center text-sm text-danger"
            role="alert"
        >
            {{ form.errors.image }}
        </p>
    </div>
</template>
