# Proposal

## Why

The application is currently named "Thoth Log". The owner wants it to be called simply "Thoth" everywhere the name is shown.

## What Changes

- The application name changes from "Thoth Log" to "Thoth" in `.env` and `.env.example` (`APP_NAME`), which also feeds `MAIL_FROM_NAME` and `VITE_APP_NAME`.
- The name shown when no name is configured changes from "Laravel" to "Thoth" (server config default, the Blade `<title>` fallback and the frontend title fallback).
- The wordmark in the layouts reads "Thoth" as a single accent-coloured word, and the compact mark shows "T" instead of "TL". `AppLogo.vue` already derives both from the name, so this follows from the rename without a component change.
- Browser tab titles read "Thoth" and "<Page> - Thoth".
- `spec.md` is retitled from "Thoth Daily Reading Log: Specification" to "Thoth: Specification".
- Side effect: the session cookie name and cache key prefix are derived from the app name (`thoth-log-session` becomes `thoth-session`, `thoth-log-cache-` becomes `thoth-cache-`). The current browser session is signed out once and existing cache entries are no longer read.

Out of scope: the project directory, the `composer.json` package name, database names, and references to the Thoth deck or thothreadings.com.

## Capabilities

### New Capabilities

- `app-branding`: the name the application presents to the user, in the page title, the wordmark and compact mark, and outgoing mail.

### Modified Capabilities

None. `openspec/specs/` is empty.

## Impact

- Configuration: `.env`, `.env.example`, `config/app.php` (default name).
- Frontend: `resources/js/app.ts` (title fallback), `resources/views/app.blade.php` (title fallback). `resources/js/components/AppLogo.vue` is affected in output only.
- Documentation: `spec.md` title.
- Tests: a new feature test for the presented name.
- Runtime: one-time sign-out and orphaned cache rows, as described above. The frontend must be rebuilt (or the dev server restarted) for `VITE_APP_NAME` to take effect.
