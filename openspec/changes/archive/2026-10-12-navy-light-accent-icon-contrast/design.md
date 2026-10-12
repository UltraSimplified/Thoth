# Design

## Context

Themes are sets of CSS custom properties in `resources/css/app.css`, selected by `data-theme` on the root element and mapped to Tailwind colours in the `@theme inline` block. System mode is resolved in JavaScript (`useTheme.ts` and the inline script in `app.blade.php`) to `light` or `dark`, so it has no palette of its own.

Icons are Lucide components that inherit `currentColor` from their parent link or button. Inactive items use `text-muted`; hover and active states change the parent's colour to `text-ink` or `text-accent`, or to `text-accent-ink` on the selected theme-switcher button.

Current contrast of the muted colour against the surfaces icons sit on: Dark 6.5:1 on `--surface` and 5.8:1 on `--surface-2`; Goblin 7.4:1 and 6.5:1.

## Goals / Non-Goals

**Goals:**

- Change one token value for the Light accent, with no component changes for that part.
- Brighten inactive icons without touching the labels that share their parent's colour.

**Non-Goals:**

- Changing `--muted` or any other existing token in Dark or Goblin.
- Changing how System mode resolves, or persisting the theme preference.

## Decisions

**Light accent is `#14121c`, the Dark theme's `--bg`.** `--accent-ink` stays `#ffffff` (18.5:1 on the new accent). Alternative considered: the Dark `--surface` (`#1e1b29`), which is slightly lighter; rejected because the request names the background colour.

**System mode needs no code change.** It already resolves to Light or Dark, so it picks up the navy accent through the Light palette. The `:root` fallback selector shares the Light block, so the navy also applies before the theme script runs.

**A new `--icon` token carries the inactive icon colour.** Values: Light `#5e5873` (equal to `--muted`, so Light is unchanged), Dark `#cfc9de` (10.5:1 on `--surface`, 9.5:1 on `--surface-2`), Goblin `#b9dcc4` (10.4:1 and 9.0:1). It is mapped to Tailwind as `--color-icon`, giving `text-icon`. Alternatives considered:

- Brightening `--muted` itself: rejected, it would also brighten labels and all secondary text, which the owner chose not to do.
- Using `--ink` for inactive icons: rejected, inactive icons would become as bright as hovered and active ones, losing the state difference in the sidebar and icon rail.

**`text-icon` is applied to the icon element only while the item is inactive, and yields to the parent on hover.** The icon gets `text-icon` when its item is not current or selected, plus a hover variant that returns it to the parent's colour (a `group` on the parent with `group-hover:text-current` on the icon). When the item is current or selected the class is omitted and the icon inherits as it does today. This applies to the icon components in `AppSidebar.vue`, `AppIconRail.vue`, `AppBottomTabBar.vue` (including the More button), `AppMoreSheet.vue` and `ThemeSwitcher.vue`.

## Risks / Trade-offs

- [Navy `#14121c` is almost the same as the Light ink colour `#1c1a24`] → In Light, accent-coloured text (the wordmark, the active bottom tab and More-sheet item) no longer stands out from body text by hue. It still differs from the muted inactive items, and the sidebar and icon rail also mark the active item with a background. Accepted as the requested look.
- [Icons brighter than their labels in Dark and Goblin] → Intended; the label colour was explicitly kept.
- [No frontend test runner in the project] → Verification is by build, type check and computed colours in the browser for each theme.
