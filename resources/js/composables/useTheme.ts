import { router } from '@inertiajs/vue3';
import { update as updateSettings } from '@/actions/App/Http/Controllers/SettingsController';
import { onMounted, onUnmounted, ref } from 'vue';

export type ThemePreference = 'system' | 'light' | 'dark' | 'goblin';
type ResolvedTheme = 'light' | 'dark' | 'goblin';

// The saved preference is rendered into the page head, so the first paint already uses it.
function savedPreference(): ThemePreference {
    const saved = document
        .querySelector<HTMLMetaElement>('meta[name="theme-preference"]')
        ?.getAttribute('content');

    return (saved ?? 'system') as ThemePreference;
}

// Module-level so every component using this composable shares one state.
const preference = ref<ThemePreference>(savedPreference());

function resolveSystemTheme(): ResolvedTheme {
    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';
}

function applyTheme(theme: ThemePreference): void {
    document.documentElement.setAttribute(
        'data-theme',
        theme === 'system' ? resolveSystemTheme() : theme,
    );
}

export function useTheme() {
    function setPreference(next: ThemePreference): void {
        preference.value = next;
        applyTheme(next);

        router.patch(
            updateSettings.url(),
            { theme: next },
            { preserveScroll: true, preserveState: true },
        );
    }

    const media = window.matchMedia('(prefers-color-scheme: dark)');
    const onSystemChange = () => {
        if (preference.value === 'system') {
            applyTheme('system');
        }
    };

    onMounted(() => media.addEventListener('change', onSystemChange));
    onUnmounted(() => media.removeEventListener('change', onSystemChange));

    return { preference, setPreference };
}
