<script setup lang="ts">
import { PATH_INFO, SEPHIRA_INFO } from '@/lib/tree';
import type { TreeElement } from '@/types/deck';
import { computed } from 'vue';

/**
 * A text key to the highlighted tree: one line per element, its Hebrew name in italics. The variable elements
 * (the day's cards) come first, and the fixed birth positions last. A line lights up while the point or path it
 * names is hovered on the tree.
 */
const props = withDefaults(
    defineProps<{
        elements: TreeElement[];
        /** The tree key under the pointer: a sephira such as "binah", or a path such as "path-15". */
        hovered?: string | null;
    }>(),
    { hovered: null },
);

interface Line {
    key: string;
    /** The tree key this line names, matching the keys TreeOfLife emits on hover. */
    treeKey: string;
    label: string;
    card: string;
    /** The Hebrew name of the sephira or path, shown in italics. */
    hebrew: string;
    rest: string;
}

function lineFor(element: TreeElement): Line | null {
    if (element.path !== null) {
        const path = PATH_INFO.find((info) => info.number === element.path);

        if (path === undefined) {
            return null;
        }

        const from = SEPHIRA_INFO[path.from]?.name ?? path.from;
        const to = SEPHIRA_INFO[path.to]?.name ?? path.to;

        return {
            key: `${element.role}-${element.card}-path-${path.number}`,
            treeKey: `path-${path.number}`,
            label: element.label,
            card: element.card,
            hebrew: path.letter,
            rest: `(${path.letterMeaning}), path ${path.number}, joining ${from} and ${to}`,
        };
    }

    const sephira =
        element.sephira === null ? undefined : SEPHIRA_INFO[element.sephira];

    if (sephira === undefined) {
        return null;
    }

    return {
        key: `${element.role}-${element.card}-${sephira.key}`,
        treeKey: sephira.key,
        label: element.label,
        card: element.card,
        hebrew: sephira.name,
        rest: `(${sephira.meaning}), sephira ${sephira.number}`,
    };
}

function isLine(line: Line | null): line is Line {
    return line !== null;
}

function isLit(line: Line): boolean {
    return line.treeKey === props.hovered;
}

const variable = computed(() =>
    props.elements
        .filter((element) => element.role !== 'birth')
        .map(lineFor)
        .filter(isLine),
);

const fixed = computed(() =>
    props.elements
        .filter((element) => element.role === 'birth')
        .map(lineFor)
        .filter(isLine),
);
</script>

<template>
    <div
        v-if="variable.length || fixed.length"
        class="space-y-3 text-sm text-ink"
    >
        <div v-if="variable.length" class="space-y-1.5">
            <p
                v-for="line in variable"
                :key="line.key"
                class="-mx-2 rounded-md px-2 py-1 transition-colors duration-150"
                :class="{ 'bg-accent/20': isLit(line) }"
            >
                <span class="text-muted">{{ line.label }}</span>
                — {{ line.card }} on <em>{{ line.hebrew }}</em> {{ line.rest }}.
            </p>
        </div>

        <div v-if="fixed.length" class="space-y-1.5 border-t border-line pt-3">
            <p class="text-xs tracking-wide text-muted uppercase">Fixed</p>
            <p
                v-for="line in fixed"
                :key="line.key"
                class="-mx-2 rounded-md px-2 py-1 transition-colors duration-150"
                :class="{ 'bg-accent/20': isLit(line) }"
            >
                <span class="text-muted">{{ line.label }}</span>
                — {{ line.card }} on <em>{{ line.hebrew }}</em> {{ line.rest }}.
            </p>
        </div>
    </div>
</template>
