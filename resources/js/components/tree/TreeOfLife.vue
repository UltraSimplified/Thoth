<script setup lang="ts">
import { PATH_INFO, type PathInfo } from '@/lib/tree';
import type { TreeHighlights } from '@/types/deck';
import { computed } from 'vue';

/*
 * The Qabalistic Tree of Life on a fixed 200 by 310 canvas. Sephiroth are keyed by name ("binah"), paths by
 * number ("path-15"). The path table mirrors App\Services\TreePlacement::PATHS on the server.
 */
interface Sephira {
    key: string;
    name: string;
    number: number;
    x: number;
    y: number;
}

const SEPHIROTH: Sephira[] = [
    { key: 'kether', name: 'Kether', number: 1, x: 100, y: 26 },
    { key: 'chokmah', name: 'Chokmah', number: 2, x: 40, y: 72 },
    { key: 'binah', name: 'Binah', number: 3, x: 160, y: 72 },
    { key: 'chesed', name: 'Chesed', number: 4, x: 40, y: 130 },
    { key: 'geburah', name: 'Geburah', number: 5, x: 160, y: 130 },
    { key: 'tiphareth', name: 'Tiphareth', number: 6, x: 100, y: 152 },
    { key: 'netzach', name: 'Netzach', number: 7, x: 40, y: 212 },
    { key: 'hod', name: 'Hod', number: 8, x: 160, y: 212 },
    { key: 'yesod', name: 'Yesod', number: 9, x: 100, y: 240 },
    { key: 'malkuth', name: 'Malkuth', number: 10, x: 100, y: 290 },
];

const DAATH = { x: 100, y: 100 };

const PATHS = PATH_INFO;

const props = withDefaults(
    defineProps<{
        /** The card being shown: lit solid. */
        highlight?: TreeHighlights;
        /** Jumpers: faint, dashed. */
        jumpers?: TreeHighlights;
        /** Clarifiers: faint, dotted. */
        clarifiers?: TreeHighlights;
        /** Birth cards: outlined on every tree. */
        birth?: TreeHighlights;
        /** Optional 0 to 1 heat per node key or path key, for the statistics heat map. */
        heat?: Record<string, number>;
        /** The selected node or path key, such as "binah" or "path-15". */
        selected?: string | null;
        /** Show sephira and path names next to the numbers. */
        labelled?: boolean;
        /** Accessible name for the whole tree. */
        label?: string;
    }>(),
    {
        highlight: () => ({ nodes: [], paths: [] }),
        jumpers: () => ({ nodes: [], paths: [] }),
        clarifiers: () => ({ nodes: [], paths: [] }),
        birth: () => ({ nodes: [], paths: [] }),
        heat: () => ({}),
        selected: null,
        labelled: false,
        label: 'Tree of Life',
    },
);

const emit = defineEmits<{ select: [key: string] }>();

function pathKey(path: PathInfo): string {
    return `path-${path.number}`;
}

function sephiraAt(key: string): Sephira {
    return SEPHIROTH.find((sephira) => sephira.key === key) as Sephira;
}

function lit(list: TreeHighlights, node: Sephira | PathInfo): boolean {
    return 'key' in node
        ? list.nodes.includes(node.key)
        : list.paths.includes(node.number);
}

function heatFor(key: string): number {
    return Math.min(1, Math.max(0, props.heat[key] ?? 0));
}

const paths = computed(() =>
    PATHS.map((path) => {
        const from = sephiraAt(path.from);
        const to = sephiraAt(path.to);

        return {
            ...path,
            key: pathKey(path),
            x1: from.x,
            y1: from.y,
            x2: to.x,
            y2: to.y,
            mx: (from.x + to.x) / 2,
            my: (from.y + to.y) / 2,
            main: lit(props.highlight, path),
            jumper: lit(props.jumpers, path),
            clarifier: lit(props.clarifiers, path),
            birth: lit(props.birth, path),
        };
    }),
);

const nodes = computed(() =>
    SEPHIROTH.map((sephira) => ({
        ...sephira,
        main: lit(props.highlight, sephira),
        jumper: lit(props.jumpers, sephira),
        clarifier: lit(props.clarifiers, sephira),
        birth: lit(props.birth, sephira),
    })),
);

function pathLabel(path: PathInfo): string {
    return `Path ${path.number}, ${path.letter}, joining ${sephiraAt(path.from).name} and ${sephiraAt(path.to).name}`;
}

function nodeLabel(sephira: Sephira): string {
    return `${sephira.name}, sephira ${sephira.number}`;
}

function activate(key: string): void {
    emit('select', key);
}
</script>

<template>
    <svg
        viewBox="0 0 200 310"
        role="group"
        :aria-label="label"
        class="mx-auto h-auto w-full max-w-xs overflow-visible"
    >
        <!-- Daath is not a sephira: dashed, never lit, not selectable. -->
        <g aria-hidden="false">
            <title>Daath, not a sephira</title>
            <circle
                :cx="DAATH.x"
                :cy="DAATH.y"
                r="9"
                fill="var(--surface)"
                stroke="var(--muted)"
                stroke-width="1.5"
                stroke-dasharray="3 3"
            />
        </g>

        <g v-for="path in paths" :key="path.key">
            <g
                role="button"
                tabindex="0"
                class="cursor-pointer focus:outline-none"
                :aria-label="pathLabel(path)"
                :aria-pressed="selected === path.key"
                @click="activate(path.key)"
                @keydown.enter.prevent="activate(path.key)"
                @keydown.space.prevent="activate(path.key)"
            >
                <title>{{ pathLabel(path) }}</title>

                <!-- Wide transparent stroke so the path is easy to tap. -->
                <line
                    :x1="path.x1"
                    :y1="path.y1"
                    :x2="path.x2"
                    :y2="path.y2"
                    stroke="transparent"
                    stroke-width="14"
                />

                <line
                    :x1="path.x1"
                    :y1="path.y1"
                    :x2="path.x2"
                    :y2="path.y2"
                    stroke="var(--line)"
                    stroke-width="2"
                />

                <line
                    v-if="path.birth"
                    :x1="path.x1"
                    :y1="path.y1"
                    :x2="path.x2"
                    :y2="path.y2"
                    stroke="var(--ink)"
                    stroke-opacity="0.45"
                    stroke-width="7"
                />

                <line
                    v-if="path.jumper"
                    :x1="path.x1"
                    :y1="path.y1"
                    :x2="path.x2"
                    :y2="path.y2"
                    stroke="var(--accent)"
                    stroke-opacity="0.5"
                    stroke-width="4"
                    stroke-dasharray="6 4"
                />

                <line
                    v-if="path.clarifier"
                    :x1="path.x1"
                    :y1="path.y1"
                    :x2="path.x2"
                    :y2="path.y2"
                    stroke="var(--accent)"
                    stroke-opacity="0.5"
                    stroke-width="4"
                    stroke-linecap="round"
                    stroke-dasharray="0.5 5"
                />

                <line
                    v-if="heatFor(path.key) > 0"
                    :x1="path.x1"
                    :y1="path.y1"
                    :x2="path.x2"
                    :y2="path.y2"
                    stroke="var(--accent)"
                    :stroke-opacity="0.2 + heatFor(path.key) * 0.8"
                    stroke-width="6"
                />

                <line
                    v-if="path.main"
                    :x1="path.x1"
                    :y1="path.y1"
                    :x2="path.x2"
                    :y2="path.y2"
                    stroke="var(--accent)"
                    stroke-width="5"
                />

                <g
                    v-if="
                        path.main ||
                        path.jumper ||
                        path.clarifier ||
                        labelled ||
                        selected === path.key
                    "
                >
                    <circle
                        :cx="path.mx"
                        :cy="path.my"
                        r="8"
                        fill="var(--surface)"
                        :stroke="
                            selected === path.key
                                ? 'var(--ink)'
                                : 'var(--accent)'
                        "
                        stroke-width="1.5"
                    />
                    <text
                        :x="path.mx"
                        :y="path.my + 3"
                        text-anchor="middle"
                        font-size="8"
                        fill="var(--ink)"
                    >
                        {{ path.number }}
                    </text>
                </g>
            </g>
        </g>

        <g v-for="node in nodes" :key="node.key">
            <g
                role="button"
                tabindex="0"
                class="cursor-pointer focus:outline-none"
                :aria-label="nodeLabel(node)"
                :aria-pressed="selected === node.key"
                @click="activate(node.key)"
                @keydown.enter.prevent="activate(node.key)"
                @keydown.space.prevent="activate(node.key)"
            >
                <title>{{ nodeLabel(node) }}</title>

                <!-- Larger invisible target so each sephira is easy to tap. -->
                <circle :cx="node.x" :cy="node.y" r="18" fill="transparent" />

                <circle
                    v-if="node.birth"
                    :cx="node.x"
                    :cy="node.y"
                    r="19.5"
                    fill="none"
                    stroke="var(--ink)"
                    stroke-width="2"
                />

                <circle
                    :cx="node.x"
                    :cy="node.y"
                    r="12"
                    :fill="
                        node.main
                            ? 'var(--accent)'
                            : heatFor(node.key) > 0
                              ? `color-mix(in srgb, var(--accent) ${Math.round(heatFor(node.key) * 100)}%, var(--surface))`
                              : 'var(--surface)'
                    "
                    :stroke="
                        selected === node.key
                            ? 'var(--ink)'
                            : node.main
                              ? 'var(--accent)'
                              : 'var(--muted)'
                    "
                    :stroke-width="selected === node.key ? 3 : 1.5"
                    :stroke-dasharray="node.jumper ? '4 3' : undefined"
                />

                <circle
                    v-if="node.clarifier"
                    :cx="node.x"
                    :cy="node.y"
                    r="15"
                    fill="none"
                    stroke="var(--accent)"
                    stroke-opacity="0.6"
                    stroke-width="1.5"
                    stroke-dasharray="0.5 3"
                    stroke-linecap="round"
                />

                <text
                    :x="node.x"
                    :y="node.y + 4"
                    text-anchor="middle"
                    font-size="10"
                    font-weight="600"
                    :fill="node.main ? 'var(--accent-ink)' : 'var(--ink)'"
                >
                    {{ node.number }}
                </text>

                <text
                    v-if="labelled"
                    :x="node.x"
                    :y="node.y + 26"
                    text-anchor="middle"
                    font-size="9"
                    fill="var(--muted)"
                    stroke="var(--bg)"
                    stroke-width="3"
                    paint-order="stroke"
                >
                    {{ node.name }}
                </text>
            </g>
        </g>
    </svg>
</template>
