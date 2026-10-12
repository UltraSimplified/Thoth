import {
    calendar,
    cards,
    imports,
    notes,
    settings,
    stats,
    themes,
    today,
} from '@/routes';
import {
    BarChart3,
    Calendar as CalendarIcon,
    LayoutGrid,
    NotebookPen,
    Settings as SettingsIcon,
    Sun,
    Tags,
    Upload,
} from 'lucide-vue-next';
import type { Component } from 'vue';

export type NavGroupId = 'daily' | 'journal' | 'system';

export interface NavItem {
    label: string;
    /** The Inertia page component this item links to, e.g. "Today". Used to detect the active item. */
    component: string;
    href: string;
    icon: Component;
    group: NavGroupId;
    /** Whether this item appears in the phone bottom tab bar (otherwise it lives in the "More" sheet). */
    tabBar: boolean;
}

export const navGroups: { id: NavGroupId; label: string }[] = [
    { id: 'daily', label: 'Daily' },
    { id: 'journal', label: 'Journal' },
    { id: 'system', label: 'System' },
];

export const navigation: NavItem[] = [
    {
        label: 'Today',
        component: 'Today',
        href: today.url(),
        icon: Sun,
        group: 'daily',
        tabBar: true,
    },
    {
        label: 'Calendar',
        component: 'Calendar',
        href: calendar.url(),
        icon: CalendarIcon,
        group: 'daily',
        tabBar: true,
    },
    {
        label: 'Stats',
        component: 'Stats',
        href: stats.url(),
        icon: BarChart3,
        group: 'daily',
        tabBar: true,
    },
    {
        label: 'Cards',
        component: 'Cards',
        href: cards.url(),
        icon: LayoutGrid,
        group: 'daily',
        tabBar: true,
    },
    {
        label: 'Notes',
        component: 'Notes',
        href: notes.url(),
        icon: NotebookPen,
        group: 'journal',
        tabBar: false,
    },
    {
        label: 'Themes',
        component: 'Themes',
        href: themes.url(),
        icon: Tags,
        group: 'journal',
        tabBar: false,
    },
    {
        label: 'Import',
        component: 'Import',
        href: imports.url(),
        icon: Upload,
        group: 'system',
        tabBar: false,
    },
    {
        label: 'Settings',
        component: 'Settings',
        href: settings.url(),
        icon: SettingsIcon,
        group: 'system',
        tabBar: false,
    },
];
