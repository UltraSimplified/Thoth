# Proposal

## Why

The Light theme's bronze-yellow accent (`#8a6a1f`) is not the look the owner wants; it should be the navy of the Dark theme's background. In the Dark and Goblin themes the inactive icons are drawn in the dim "muted" colour and are too faint.

## What Changes

- The Light theme's accent changes from `#8a6a1f` to `#14121c`, the Dark theme's background colour. This recolours the wordmark, the selected theme-switcher button, the active-item marker and active tab, the login button and focus outlines in Light.
- System mode keeps following the operating system. It shows the navy accent when the OS is light, because it shows the Light theme; when the OS is dark it shows the Dark theme with its gold accent, unchanged.
- The Dark and Goblin accents (gold `#d8b25c`, green `#34d36f`) do not change.
- Inactive icons become brighter in Dark and Goblin: the navigation icons in the sidebar, icon rail, bottom tab bar and More sheet, and the unselected icons in the theme switcher. Their contrast against the surface behind them rises from about 6:1 to at least 9:1.
- Text labels next to those icons, and icons in the Light theme, keep their current colour. Hover, active and selected states keep their current colours.

## Capabilities

### New Capabilities

- `theming`: the colour themes the application offers (Light, Dark, Goblin, and System which follows the OS), including each theme's accent colour and the legibility of icons.

### Modified Capabilities

None. `openspec/specs/` is empty.

## Impact

- `resources/css/app.css`: Light accent value; a new icon colour token per theme and its Tailwind mapping.
- `resources/js/components/ThemeSwitcher.vue`, `resources/js/layouts/AppSidebar.vue`, `AppIconRail.vue`, `AppBottomTabBar.vue`, `AppMoreSheet.vue`: inactive icons use the new token.
- No backend, database or dependency changes. The frontend must be rebuilt.
