# Public Interactive Demo Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publish a safe, isolated, free Render demo of the real ordering app, with private visitor orders and read-only admin viewing.

**Architecture:** A demo-only backend flag enables automatic anonymous visitor provisioning through a rate-limited Sanctum endpoint and prevents external integrations. An ephemeral SQLite database is initialized only at a guarded container startup. A separate Filament viewer sees only fictional seeded data and cannot invoke mutations; deployment fails closed until verified.

**Tech Stack:** Laravel 13, Sanctum, Filament 5, Vue 3/Pinia/Vite, PHPUnit, Vitest, Docker, SQLite, Render Free.

---

## Boundaries / file map

- `config/lunch.php`: demo-mode flag, default false; never change the existing reset guard.
- `app/Http/Controllers/Api/DemoSessionController.php`, `routes/api.php`: create one disposable visitor and return `{data:{token,user}}`, only when demo mode, with Laravel throttle and capacity limit.
- `resources/js/api/auth.js`, `resources/js/stores/auth.js`, `resources/js/App.vue`, `resources/views/app.blade.php`: demo login-on-load, reset stale tokens after database recreation, warning banner; preserve non-demo behavior.
- `app/Enums/UserRole.php`, `app/Models/User.php`, `app/Filament/**`, `app/Policies/**`, `database/seeders/DemoHostedSeeder.php`: separate readonly viewer, seeded sample-only scopes; custom actions and exports deny viewer, with server-side checks.
- `app/Console/Commands/InitializeHostedDemoCommand.php`, `Dockerfile`, `docker/start.sh`, `docker/nginx.conf` or equivalent PHP-capable server setup: guarded initial bootstrap; production APP_ENV, non-production demo data.
- `tests/Feature/Demo/**`, `resources/js/**/*.test.js`, `README.md`, `docs/DEPLOYMENT.md`, `render.yaml`: negative authorization tests, startup tests, documented free-tier deploy.

## Task 1: Visitor API and isolation

- [ ] **Step 1: Write failing feature tests.** In `tests/Feature/Demo/DemoSessionTest.php`, assert POST `/api/demo/session` returns 404 with demo flag false; with flag true returns 201 and `{data:{token,user}}` whose role is `user` and has no published password; requesting own order with token A succeeds, accessing B's order/item using A returns 403/404, excess creation is 429. Include tests for per-IP throttling and total cap, with separate cache state per test.
- [ ] **Step 2: Run targeted test and observe failure.** `php artisan test tests/Feature/Demo/DemoSessionTest.php` should fail at the new missing endpoint.
- [ ] **Step 3: Implement.** Add `hosted_demo` boolean env flag in `config/lunch.php`, and an API endpoint with demo-only guard, `throttle` and a small global daily cap checked atomically in cache. Create `User` with random name/email under `.invalid`, `UserRole::User`, `is_active=true`, `password=null`, issue Sanctum token. Return 404 outside hosted-demo mode, 429 when limits exceeded. Add a demo-only block to password/Telegram/external authentication endpoints (keep normal mode unchanged). Explicitly throttle protected order mutations without lifting current access checks.
- [ ] **Step 4: Re-run targeted tests and regression.** `php artisan test tests/Feature/Demo/DemoSessionTest.php tests/Feature/Api` should pass.
- [ ] **Step 5: Commit.** `git add config/lunch.php routes/api.php app/Http/Controllers/Api tests/Feature/Demo && git commit -m 'feat: isolate and rate-limit public demo visitors'`.

## Task 2: Frontend auto-session and temporary-data messaging

- [ ] **Step 1: Write failing Vitest coverage.** Verify demo bootstrap calls POST `/auth/demo-session` only when an injected page flag is true, reuses a still-valid stored token, reprovisions after a 401 from `/me`, and non-demo login UI remains unchanged.
- [ ] **Step 2: Run test to observe failure.** `npm test -- --run` should fail for the new demo case.
- [ ] **Step 3: Implement.** Inject the demo flag into the SPA HTML from server-side config; add `createDemoSession()` to `resources/js/api/auth.js`; add `authWithDemo()` to the Pinia store that saves token and user; in `ensureAuth()` of `App.vue`, try stored auth, then provision demo visitor *before* Telegram logic if demo mode. Show a clear banner: 'Демо: только вымышленные данные. Не вводите личную информацию. Заказы исчезают при перезапуске.' Do not show published shared login credentials.
- [ ] **Step 4: Run frontend tests/build.** `npm test -- --run && npm run build` should pass.
- [ ] **Step 5: Commit.** `git add resources/js resources/views && git commit -m 'feat: automatically log in isolated demo visitors'`.

## Task 3: Viewer-only admin and seeding

- [ ] **Step 1: Write failing Filament feature tests.** Visitor can enter through a demo-only route that logs in viewer via web guard with session regeneration; viewer can load permitted dashboard and fictional seeded lists. It cannot access any create/edit/import/export/deliver routes or invoke Livewire action handlers; direct POST/Livewire requests return 403. A visitor account cannot open `/admin`; a viewer cannot read visitor-created order, user, or exports. Full-admin tests in normal mode remain green.
- [ ] **Step 2: Run tests to observe failures.** `php artisan test tests/Feature/Demo/DemoViewerTest.php` should fail on missing viewer access/authorization.
- [ ] **Step 3: Implement.** Introduce `UserRole::DemoViewer`; allow panel access only in hosted-demo mode and only for the new role, while retaining admin access in normal mode. Add server-side resource policies/scopes for every exposed Filament page; protect custom table actions, widget actions, imports, exports and direct Livewire calls. Restrict viewer pages to seeded fictional data; hide users, visitor orders and supplier exports entirely if a secure record scope is unavailable. Seed one viewer with a random noncommitted credential and synthetic reference data via a separate hosted seeder (do not reuse `DemoDatabaseSeeder` admin password or change `demo:reset`). Create a demo-only, throttled web route that logs in this viewer via `Auth::login()` and regenerates the session before redirecting to the dashboard; never expose the password. Do not expose the viewer entry URL until mutation and scope tests pass.
- [ ] **Step 4: Run all PHP tests and audit viewer capabilities.** `php artisan test` must pass; manually inspect all `app/Filament` write actions for viewer bypasses.
- [ ] **Step 5: Commit.** `git add app database/seeders tests/Feature/Demo && git commit -m 'feat: expose read-only synthetic admin demo'`.

## Task 4: Container + destructive-operation guard

- [ ] **Step 1: Write failing command tests.** Dedicated initializer refuses missing demo mode and wrong DB path; refuses an unrecognized nonempty DB; fresh demo DB migrates and seeds; re-running with a valid initialized DB is a no-op; no `DEMO_RESET_ALLOWED` override is needed.
- [ ] **Step 2: Run new tests and observe failure.** `php artisan test tests/Feature/Demo/InitializeHostedDemoTest.php` should fail on missing command.
- [ ] **Step 3: Implement.** Guard dedicated `/var/www/html/storage/demo.sqlite` database path and a fresh empty file before migrate/seed; initialize only if absent. Build multi-stage Docker image with Composer and npm artifacts, PHP SQLite/extensions, nginx or Apache serving `public/` on Render `$PORT`, writable dedicated SQLite and cache/session directories. Run initializer on container start, then exec server. Pin image/runtime versions; do not include `.env`, `database/database.sqlite`, local logs, node_modules, or vendor in the build context. Restrict Render config to Free compute; configure secret APP_KEY in Render dashboard, APP_ENV=production, APP_DEBUG=false, demo flag, SQLite path, no Telegram/external secrets. Do not invoke `demo:reset` or `migrate:fresh` on production.
- [ ] **Step 4: Verify.** `docker build -t nethammereda-demo .`, launch with disposable environment, curl catalog and provision endpoint; restart with the same DB file and verify no records lost; restart with a new ephemeral filesystem and verify reseeding. If Docker is unavailable, report explicitly and do not claim container verification.
- [ ] **Step 5: Commit.** `git add Dockerfile docker .dockerignore render.yaml app/Console/Commands tests/Feature/Demo && git commit -m 'build: package guarded ephemeral Render demo'`.

## Task 5: Review and publish

- [ ] **Step 1: Update docs.** `README.md` and `docs/DEPLOYMENT.md` should describe temporary data, read-only admin, free-tier cold starts and retired domain; add link only once deployment works.
- [ ] **Step 2: Check changes and run full suite.** `composer audit && php artisan test && npm test -- --run && npm run build && git diff --check`; targeted security review of admin action list and public login endpoints.
- [ ] **Step 3: Deploy only with owner's Render account authorization.** Use a new Free web service linked to `hattwell/nethammereda` after PR/CI passes. Never enter personal secrets in chat or commit them. Smoke test two browser profiles, ordering, forbidden admin writes, and cold start. If any test fails, leave demo URL unpublished and revert/disable service.
- [ ] **Step 4: Publish and rollback.** Only after smoke tests pass, add the HTTPS demo link to README/profile through reviewed changes. Rollback by removing the link and deleting the isolated service; no production migration or backup restore involved.
