<script setup lang="ts">
import { show as showCard } from '@/actions/App/Http/Controllers/CardController';
import { PATH_INFO, SEPHIRA_INFO } from '@/lib/tree';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CardRef {
    slug: string;
    name: string;
    title: string | null;
}

/** The selected sephira or path, as the tree emits it: "binah" or "path-15". */
const props = defineProps<{
    selected: string | null;
    cards: CardRef[];
}>();

const path = computed(() => {
    if (props.selected === null || !props.selected.startsWith('path-')) {
        return null;
    }

    const number = Number(props.selected.slice('path-'.length));

    return PATH_INFO.find((info) => info.number === number) ?? null;
});

const sephira = computed(() => {
    if (props.selected === null || props.selected.startsWith('path-')) {
        return null;
    }

    return SEPHIRA_INFO[props.selected] ?? null;
});

function nameOf(key: string): string {
    return SEPHIRA_INFO[key]?.name ?? key;
}
</script>

<template>
    <section
        class="space-y-4 rounded-lg border border-line bg-surface p-5"
        aria-live="polite"
        aria-labelledby="tree-detail-heading"
    >
        <template v-if="path">
            <div>
                <h3
                    id="tree-detail-heading"
                    class="font-heading text-lg text-ink"
                >
                    Path {{ path.number }}, {{ path.letter }}
                </h3>
                <p class="text-sm text-muted">
                    The Hebrew letter means “{{ path.letterMeaning }}”
                </p>
            </div>

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">Thoth card</dt>
                    <dd class="text-ink">{{ path.card }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Joins</dt>
                    <dd class="text-ink">
                        {{ nameOf(path.from) }} and {{ nameOf(path.to) }}
                    </dd>
                </div>
            </dl>

            <p class="text-sm leading-relaxed text-ink">
                {{ path.symbolism }}
            </p>
        </template>

        <template v-else-if="sephira">
            <div>
                <h3
                    id="tree-detail-heading"
                    class="font-heading text-lg text-ink"
                >
                    {{ sephira.name }}, sephira {{ sephira.number }}
                </h3>
                <p class="text-sm text-muted">“{{ sephira.meaning }}”</p>
            </div>

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">Pillar</dt>
                    <dd class="text-ink">{{ sephira.pillar }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Triad</dt>
                    <dd class="text-ink">{{ sephira.triad }}</dd>
                </div>
            </dl>

            <p class="text-sm leading-relaxed text-ink">
                {{ sephira.symbolism }}
            </p>
        </template>

        <div class="space-y-2 border-t border-line pt-4">
            <p class="text-sm font-medium text-ink">Cards here</p>
            <ul
                v-if="cards.length"
                class="flex flex-wrap gap-2 text-sm text-muted"
            >
                <li v-for="entry in cards" :key="entry.slug">
                    <Link
                        :href="showCard.url(entry.slug)"
                        class="hover:text-accent hover:underline"
                    >
                        {{ entry.name }}
                    </Link>
                </li>
            </ul>
            <p v-else class="text-sm text-muted">No cards sit here.</p>
        </div>
    </section>
</template>
