# Nethammereda portfolio documentation — design

## Goal and scope

Present Nethammereda as a credible public portfolio project at `hattwell/nethammereda`: real screenshots from an isolated local demo and reproducible, verified local setup instructions. Keep business behavior, production, and the existing public/private status unchanged. A short bilingual `hattwell` profile README is a separate follow-up after the project documentation is ready.

## Approach

Use a clean clone of the repository on this Mac. Install PHP and Composer if missing, and use the existing Node/npm installation. Start with a newly created, ignored SQLite database and a local-only `.env` copied from `.env.example`; configure `APP_ENV=local`, `DB_CONNECTION=sqlite`, an explicit absolute SQLite path, and no live external integrations. Install locked Composer and npm dependencies, generate a local app key, migrate and run the guarded `php artisan demo:reset` command **only** after confirming the database is the new throwaway local file. Do not connect to production DB, run deploy scripts, reuse old archives, or display real secrets.

## Deliverables

- `README.md`: short project overview and feature list; accurate prerequisites, local SQLite setup, exact demo reset command, app and Vite startup, demo credentials (clearly labeled local-only), tests, and screenshots. Document only steps confirmed in the actual clean local run; note optional external Telegram integration separately.
- `screenshots/`: 2–3 PNG or WebP captures of the actual running demo (catalog, cart/order or personal view, admin) with neutral seeded data. No secrets, session tokens, private user information, or browser chrome revealing identity. If one view cannot be obtained safely, use fewer real captures rather than a fabricated image.
- No behavior changes just to make a screenshot look better. If the documented setup requires a fix, investigate and propose that fix separately.

## Validation and safety

Verify `php artisan migrate`, the guarded demo reset, frontend build, local HTTP response, representative authenticated demo flow, and available tests. Inspect every screenshot before committing. Validate Git diff for generated files, environment values, secrets, production paths, and accidental SQLite files. Do not publish documentation or screenshots until the captured data and setup instructions have been reviewed. Successful local run does not imply production changes.

## Alternatives not chosen

Using existing marketing assets would not prove the interface runs. Copying data from live service or old backups creates privacy and consistency risks. Screenshots produced by mock HTML would misrepresent the application.
