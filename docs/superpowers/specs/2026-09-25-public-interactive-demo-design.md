# Public interactive portfolio demo — design

Date: 2026-09-25. Status: approved in conversation, pending review of this written specification.

## Outcome and constraints

Publish a no-cost, publicly accessible demo of the real Laravel/Vue Nethammereda application under a provider-issued HTTPS URL. Visitors can browse the catalog and place test orders; they can inspect the admin interface but cannot change administrative data. The old nethammereda.ru domain/VPS is unavailable and must not be used. Never copy production data, credentials, environment files, or backups. Never disable security checks to make the demo work.

## Hosting choice

Use one Render Free Docker web service with its ephemeral filesystem and a dedicated SQLite file. Build the Vue assets and PHP dependencies into the container; serve Laravel via a production-capable HTTP stack, not `php artisan serve`. Use a provider-provided HTTPS URL and an APP_KEY stored only in Render environment settings. Run with APP_ENV=production, APP_DEBUG=false, no Telegram credentials, no background workers, and a demo-specific feature flag. The old deployment document must be updated so it does not suggest the retired domain/VPS is current.

Render documents a spin-down after 15 minutes idle, about a minute for cold starts, and loss of local files (including SQLite) on spin-down, restart, and redeploy: https://render.com/docs/free. This is intended: the database and sessions are disposable. The site must tell visitors that orders are fictional and temporary, not accept payments, warn visitors not to enter real personal information, and never promise data persistence. Free-plan availability and signup requirements must be checked again when creating the service; do not silently switch to a paid plan.

The SQLite database is created from migrations and synthetic seed data only on a *new, empty* dedicated demo database at container startup. Do not run the existing destructive `demo:reset` command on APP_ENV=production and do not set DEMO_RESET_ALLOWED=true there. Fail startup if the configured database is not the dedicated ephemeral demo path. A subsequent restart can initialize an empty database, but must never reset a nonempty database in place. Do not share the database or app key with any non-demo environment.

## Visitor workflow and isolation

Keep the existing catalog/order API and Vue app. In demo mode, add an explicitly rate-limited endpoint that provisions one new unprivileged, synthetic visitor account per browser profile and issues its own Sanctum token. Reuse that token on refresh until the browser storage or demo database resets; never publish or reuse one shared employee password. Restrict order/history/fridge reads and mutations to that visitor with the existing authorization policies, and add tests proving one visitor cannot access another's objects. Limit demo account creation and order writes to keep abuse within the free service's capacity; if a limit is hit, return a clear 429 rather than failing open. Visitor-created free-text data must not appear in a public shared admin view. No real Telegram login, email delivery, uploads, supplier exports, or external network integrations are required in demo mode. Normal non-demo behavior stays unchanged.

## Admin viewing

Do not seed the publicly documented `admin@lunch.local`/`password` administrator into the hosted demo. Provide a distinct demo-viewer identity that can open selected Filament dashboard/list/detail pages for *seeded fictional records only*. Enforce read-only authorization server-side for every resource, page, widget, and custom action; hide create/edit/delete/import/export controls, and deny direct URLs and Livewire action requests as well. The viewer cannot change roles, invoke deliveries, download visitor data, or generate credentials. The underlying full admin identity is not publicly accessible. If complete read-only and data isolation cannot be demonstrated, do not release the admin URL; resolve the gap or ask the user to approve a clearly labeled static preview instead.

## Verification, publication, rollback

Test demo vs normal-mode behavior, startup guards, visitor isolation, throttling, forbidden admin routes/actions, absence of real credentials and integrations, and startup after SQLite deletion; retain existing PHP/JS tests and build. Review changes, then deploy only the new isolated service after the user authorizes account setup. Smoke-test HTTPS, cold-start behavior, catalog, two independent browsers, order placement, admin view, denied mutation, and recreation after an idle spin-down/restart. Publish the URL in README/profile only after those checks pass. Rollback is disabling/deleting the demo service and reverting its link; no production system or data needs migration or restoration.

## Deferred

Paid hosting, custom domain, durable order storage, real customer registration, genuine admin write access, Telegram, payment, periodic external reset service, and CI/CD deployment automation are out of scope.
