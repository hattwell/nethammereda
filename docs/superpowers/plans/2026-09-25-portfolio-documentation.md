# Nethammereda Portfolio Documentation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publish a verified local demo guide and genuine screenshots for Nethammereda.

**Architecture:** Work in an isolated git worktree with a new ignored SQLite database and local-only environment. Install locked dependencies, run the existing guarded demo reset, test the app over localhost, capture and inspect the running UI, then update documentation only. Do not touch production, change business logic, or use real user data.

**Tech Stack:** PHP 8.3+, Composer, Laravel 13, SQLite, Node/npm, Vue 3/Vite, local browser screenshots.

---

### Task 1: Prepare isolated environment and establish baseline

**Files:** No tracked files. Local ignored `.env`, `database/database.sqlite`, `vendor/`, `node_modules/` only.

- [ ] **Step 1:** Create isolated git worktree following `using-git-worktrees`; check worktree ignore rules and clean branch. Read `AGENTS.md` and `docs/superpowers/specs/2026-09-25-portfolio-documentation-design.md` in that worktree.
- [ ] **Step 2:** Install PHP and Composer with `HOMEBREW_NO_AUTO_UPDATE=1 brew install php composer` if absent; run `php -v`, `composer --version`, `node --version`, `npm --version`. Expected: PHP >= 8.3 and all commands return 0.
- [ ] **Step 3:** Run `composer install --no-interaction` and `npm ci` in worktree. Expected: locked dependencies installed; stop and investigate if lockfile/platform conflicts appear; never use `composer update` or `npm update`.
- [ ] **Step 4:** Run `php artisan test`, `npm test`, `npm run build`; record baseline pass/fail and treat pre-existing failures separately from docs work.

### Task 2: Verify fresh SQLite demo and capture genuine UI

**Files:** Local ignored `.env`, `database/database.sqlite`; create `screenshots/catalog.png`, `screenshots/cart.png`, and/or `screenshots/admin.png` only if captures are safe and genuine.

- [ ] **Step 1:** Create `.env` from `.env.example` in worktree; set `APP_ENV=local`, `APP_DEBUG=false`, `DB_CONNECTION=sqlite`, `DB_DATABASE` to the absolute path of *this worktree's new* `database/database.sqlite`, and leave Telegram integrations unconfigured. Verify the SQLite path does not exist before creation and is ignored by git.
- [ ] **Step 2:** Create that one SQLite file (`touch database/database.sqlite`); run `php artisan key:generate`, `php artisan migrate --force`. Confirm the database path again before every destructive action; do not print `.env` contents.
- [ ] **Step 3:** Run `php artisan demo:reset --force` only against this newly created local DB. Expected: exit 0 and demo data available. Note that this command destroys existing demo data and must never be recommended without the fresh-database guard.
- [ ] **Step 4:** Start local Laravel and Vite servers on loopback only (`php artisan serve --host=127.0.0.1` and `npm run dev -- --host 127.0.0.1`), probe localhost with `curl`, visit demo UI, verify catalog and local demo login, and inspect the admin view.
- [ ] **Step 5:** Capture 2–3 browser screenshots of the real demo in `screenshots/`. Inspect each for credentials, session tokens, private data and unwanted browser chrome. If a view does not work, omit it rather than fabricate it; keep no captures with live data.

### Task 3: Document only verified steps

**Files:** Modify `README.md`; add validated files to `screenshots/`.

- [ ] **Step 1:** Compare actual successful commands and local routes with existing `README.md`. Preserve concise features and stack; replace the placeholder screenshots section with links to actual files. Give local-only SQLite setup in correct order: clone → install Composer/npm → copy template env and set SQLite path → create empty DB → key generation → migration → guarded demo reset → build or two dev servers → visit tested localhost URLs. Include clear demo-only credentials only if verified from `DemoDatabaseSeeder.php` and exercised locally. Do not add real secrets or deployment endpoints.
- [ ] **Step 2:** Check Markdown image links and commands against files and observed behavior. Run `git diff --check`, `git status --short`, `php artisan test`, `npm test`, and `npm run build`. Confirm ignored `.env`, SQLite, vendor, node_modules, and generated browser files are not staged.
- [ ] **Step 3:** Commit only `README.md` and vetted `screenshots/*` on the isolated branch with the established author identity. Before any push, verify review of staged changes and confirm the desired publication step with the user.
