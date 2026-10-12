# Tasks

## 1. Application name

- [x] 1.1 Set `APP_NAME="Thoth"` in `.env` and `.env.example`; verify `php artisan config:show app.name` prints `Thoth` (run `php artisan config:clear` first if config is cached)
- [x] 1.2 Change the default name from `'Laravel'` to `'Thoth'` in `config/app.php`, the `<title>` fallback in `resources/views/app.blade.php` and the `appName` fallback in `resources/js/app.ts`; verify `grep -n "'Laravel'" config/app.php resources/views/app.blade.php resources/js/app.ts` returns nothing
- [x] 1.3 Add a Pest feature test that a rendered page has the document title "Thoth" and shares `name` = "Thoth" with Inertia, including when `app.name` falls back to its default; verify `php artisan test --compact` passes
- [x] 1.4 Retitle `spec.md` to "Thoth: Specification"; verify `grep -rn "Thoth Log\|Daily Reading Log" . --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=openspec --exclude-dir=storage` returns nothing

## 2. Integration check

- [x] 2.1 Run `vendor/bin/pint --dirty`, `npm run types:check` and `php artisan test --compact`; verify all pass
- [x] 2.2 Rebuild the frontend (`npm run build`, or restart `npm run dev`), sign in again, and verify in the browser that the tab title reads "Cards - Thoth" on the Cards page, the wordmark reads "Thoth" in the accent colour and the compact mark shows "T"
