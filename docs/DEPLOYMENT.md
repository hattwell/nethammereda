# Deployment: isolated portfolio demo

The former `nethammereda.ru` domain and VPS are **not available**. Do not use old deployment commands or upload production data. This document describes only the new, disposable Render Free demo: <https://nethammereda-demo.onrender.com/>. Its catalog, isolated orders, read-only viewer, and cold restart were verified before the URL was published.

## Prerequisites

- The reviewed branch/PR has passed PHP/JS tests, `composer audit`, the asset build, and the Docker image smoke test in CI. Do not merge or publish on failing checks.
- The repo owner signs into Render and explicitly selects the **Free** web-service plan. Check [current free-plan rules](https://render.com/docs/free) before creating the service. Do not enable paid products or add a credit card unless the owner explicitly chooses to.
- Use a *new* service linked to `hattwell/nethammereda`, not a previous deployment. Create from the repository's `render.yaml` Blueprint if Render offers it, or configure equivalent Docker settings manually. Confirm `plan: free`, `healthCheckPath: /up`, and container port `$PORT`.

## Secrets and data

At service creation, set `APP_KEY` as a Render secret (`sync: false` in `render.yaml`). Generate a fresh Laravel-format key on your own machine using `php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'`; paste it directly into Render, **not** into chat, git, CI logs or README. Keep `APP_ENV=production`, `APP_DEBUG=false`, `HOSTED_DEMO=true`, `DB_CONNECTION=sqlite`, `DB_DATABASE=/var/www/html/storage/demo.sqlite`, `CACHE_STORE=file`, `SESSION_DRIVER=file`, `DEMO_RESET_ALLOWED=false`, and no Telegram token. The start script refuses unsafe settings. Never set `DEMO_RESET_ALLOWED=true` or use `demo:reset` here.

The container initializes a fresh SQLite file with **fictional menu data** and an unprivileged Filament viewer; it never imports old `.env` files, dumps, real orders, or credentials. Browser visitors each get a separate disposable Sanctum account. `/demo/admin` creates a read-only viewer session (no published password); only the Filament dashboard/menu lists are accessible. Do not copy local demo passwords from README to Render.

Render Free spins down after 15 minutes idle and [erases local SQLite changes on spin-down/restart/deploy](https://render.com/docs/free). A cold start can take roughly a minute. This is expected and must be disclosed to visitors. Do not invite anyone to enter real personal data or rely on order persistence. No queue worker, Telegram, payment or automated production deployment is needed.

## Acceptance checks before publishing a link

1. Verify HTTPS and `/up`; confirm catalog loads after cold start and contains only fictional dishes. Confirm actual Render service uses Free plan and APP_DEBUG is false (without exposing env values).
2. In two independent browser profiles, place an order in each and confirm each sees only their own cart/history/fridge. Check API requests return 401 without a visitor token, even after `/demo/admin` was opened in the same browser.
3. Open `/demo/admin`: only dashboard, menu items and categories are available; user/order/export/edit/create routes and Livewire mutations are rejected. Confirm visitor-created names/orders do not appear there.
4. Allow free instance to spin down, then revisit. Verify reseeding, expired-token recovery, and a usable open ordering cycle. Confirm no operations attempt to contact Telegram, send email, or use old infrastructure.
5. Only after all checks pass, add the provider-issued HTTPS URL to README/GitHub profile. If blocked, do **not** advertise an unverified URL.

Rollback: remove any public link and disable/delete this *isolated* Render service. There are no production files or databases to restore.
