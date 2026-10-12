# Tasks

## 1. Light accent

- [x] 1.1 Change `--accent` in the `:root, :root[data-theme='light']` block of `resources/css/app.css` to `#14121c`, leaving `--accent-ink` at `#ffffff`; verify the Dark and Goblin `--accent` values are unchanged (`grep -n -- "--accent:" resources/css/app.css` shows `#14121c`, `#d8b25c`, `#34d36f`)

## 2. Icon colour token

- [x] 2.1 Add `--icon` to each theme block in `resources/css/app.css` (Light `#5e5873`, Dark `#cfc9de`, Goblin `#b9dcc4`) and map `--color-icon: var(--icon)` in `@theme inline`; verify `npm run build` succeeds and the built CSS contains a `text-icon` rule once task 2.2 uses it
- [x] 2.2 Apply `text-icon` to the icon element of inactive items, returning to the parent's colour on hover, in `AppSidebar.vue`, `AppIconRail.vue`, `AppBottomTabBar.vue` (nav items and the More button), `AppMoreSheet.vue` and `ThemeSwitcher.vue`; leave current and selected items inheriting as before; verify `npm run types:check` and `npm run check` pass

## 3. Integration check

- [x] 3.1 Rebuild the frontend and, on `thoth.test`, check computed colours in each theme: Light wordmark and selected switcher button are `rgb(20, 18, 28)`; Dark inactive nav and switcher icons are `rgb(207, 201, 222)` with labels still `rgb(163, 157, 181)`; Goblin inactive icons are `rgb(185, 220, 196)`; active and hovered icons match their label colour; Light inactive icons are `rgb(94, 88, 115)`
- [x] 3.2 Run `php artisan test --compact`; verify the suite still passes
