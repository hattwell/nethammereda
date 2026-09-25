# Hosted Demo Catalog Images and Four-Card Rows Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the isolated public demo resemble the committed catalog screenshot by rendering four fictional, photographed dishes per category in balanced desktop rows.

**Architecture:** Change only the guarded hosted seeder's synthetic menu and the existing MenuItemCard desktop-width styling. Reuse the sixteen already committed, publicly served food PNGs. Test the seeder with Laravel feature tests and the layout with focused component/browser checks before merging; no real menu rows, old database, credentials or production services are involved.

**Tech Stack:** Laravel 13/PHPUnit, Vue 3/Vitest, CSS, existing Playwright installation in `/tmp/nethammer-browser` for visual QA.

---

### Task 1: Give hosted-demo categories four fictional items with local photos

**Files:**
- Modify: `tests/Feature/Demo/DemoHostedSeederTest.php`
- Modify: `database/seeders/DemoHostedSeeder.php`

- [ ] **Step 1: Write failing assertions in `test_hosted_seeder_creates_only_fictional_menu_and_viewer_without_public_admin_password`.** After `$this->seed(...)`, replace the weak item-count assertion with:

```php
$this->assertSame(16, MenuItem::query()->count());
foreach (\App\Models\MenuCategory::query()->get() as $category) {
    $this->assertSame(4, $category->items()->count());
}
foreach (MenuItem::query()->get() as $item) {
    $this->assertStringStartsWith('/images/menu/dish-', $item->image_url);
    $this->assertFileExists(public_path(ltrim($item->image_url, '/')));
    $this->assertSame('Вымышленное блюдо для демонстрации.', $item->description);
}
```

- [ ] **Step 2: Run RED:** `php artisan test tests/Feature/Demo/DemoHostedSeederTest.php --compact`; expect item count 12 rather than 16.
- [ ] **Step 3: Replace the current four category/title lists in `DemoHostedSeeder::run()` with these fictional title => preexisting image mappings and set `'image_url' => $imageUrl` in `MenuItem::query()->create()`. Iterate with `foreach ($dishes as $title => $imageUrl)`; keep a separate integer index for the existing synthetic price formula, rather than treating the path as an index:**

```php
'Супы' => [
    'Суп овощной' => '/images/menu/dish-069.png',
    'Борщ' => '/images/menu/dish-071.png',
    'Суп с лапшой' => '/images/menu/dish-070.png',
    'Суп-пюре' => '/images/menu/dish-068.png',
],
'Горячее' => [
    'Курица с рисом' => '/images/menu/dish-012.png',
    'Овощное рагу' => '/images/menu/dish-003.png',
    'Котлета с пюре' => '/images/menu/dish-002.png',
    'Гречка с котлетой' => '/images/menu/dish-009.png',
],
'Салаты' => [
    'Салат овощной' => '/images/menu/dish-054.png',
    'Салат с фасолью' => '/images/menu/dish-053.png',
    'Винегрет' => '/images/menu/dish-065.png',
    'Салат с кукурузой' => '/images/menu/dish-067.png',
],
'Выпечка' => [
    'Пирожок с капустой' => '/images/menu/dish-043.png',
    'Булочка с корицей' => '/images/menu/dish-044.png',
    'Слойка с яблоком' => '/images/menu/dish-031.png',
    'Домашний пирог' => '/images/menu/dish-045.png',
],
```

Use an integer `$index = 0;` before the inner loop, `150 + $index * 60` for price, `$index++;` after `create`; keep everything else unchanged. Verify visual plausibility against the contact sheet; do not import `belyeruchki-dishes.json`.

- [ ] **Step 4: Run GREEN:** same PHP command; expect all hosted-seeder tests passing. Then `php artisan test tests/Feature/Demo --compact` to catch any hard-coded menu assumptions.
- [ ] **Step 5: Commit:** `git add database/seeders/DemoHostedSeeder.php tests/Feature/Demo/DemoHostedSeederTest.php && git commit -m 'feat: photograph four fictional dishes per demo category'`.

### Task 2: Use the width of desktop catalog cards

**Files:**
- Modify: `resources/js/components/MenuItemCard.spec.js`
- Modify: `resources/js/components/MenuItemCard.vue`
- Modify: `resources/css/app.css`

- [ ] **Step 1: Add a component regression test next to the existing image-area test:**

```js
it('marks the image column for responsive desktop sizing', () => {
    const wrapper = mountCard({ item: { ...baseItem, image_url: '/images/menu/dish-071.png' } });
    expect(wrapper.find('[data-testid="menu-item-image-column"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="menu-item-image-area"] img').attributes('src'))
        .toBe('/images/menu/dish-071.png');
});
```

- [ ] **Step 2: Run RED:** `npm test -- --run resources/js/components/MenuItemCard.spec.js`; expect the new image-column assertion to fail.
- [ ] **Step 3: Give the existing outer image wrapper in `MenuItemCard.vue` `data-testid="menu-item-image-column"`. Add the following rule after the `@media (min-width: 1536px)` block in `resources/css/app.css` (without modifying the mobile selectors):**

```css
@media (min-width: 1536px) {
    .menu-card [data-testid='menu-item-image-column'],
    .menu-card [data-testid='menu-item-copy-column'] {
        width: min(100%, 220px);
    }

    .menu-card [data-testid='menu-item-image-area'] {
        width: 100%;
        height: auto;
        aspect-ratio: 1;
    }
}
```

This preserves the existing 176px card content at 1512px, expands it to up to 220px on large monitors, and never changes the 4/3/2/1-column breakpoints already in `app.css`.

- [ ] **Step 4: Run GREEN and build:** `npm test -- --run resources/js/components/MenuItemCard.spec.js && npm run build`; expect both exit 0.
- [ ] **Step 5: Commit:** `git add resources/js/components/MenuItemCard.spec.js resources/js/components/MenuItemCard.vue resources/css/app.css && git commit -m 'fix: fill wide desktop demo cards without changing mobile layout'`.

### Task 3: Local browser acceptance, review and public rollout

**Files:** No further application-code edits unless visual QA exposes a defect; remove the throwaway local `storage/demo.sqlite` after the browser test. Keep `/tmp` screenshots and local generated key out of Git.

- [ ] **Step 1: Run full verification:** `php artisan test --compact`, `npm test`, `npm run build`, `composer audit`, `git diff --check`. Do not run `demo:reset` on an existing database.
- [ ] **Step 2: In the isolated worktree only, generate an in-memory APP_KEY with `php -r 'echo "base64:".base64_encode(random_bytes(32));'` without logging it. For a fresh, nonexistent `storage/demo.sqlite`, run `APP_ENV=local APP_DEBUG=false HOSTED_DEMO=true DB_CONNECTION=sqlite DB_DATABASE="$PWD/storage/demo.sqlite" php artisan demo:initialize-hosted`, then `php artisan serve --host=127.0.0.1 --port=18080` under the same env. Cleanup server and `storage/demo.sqlite` after QA; never point the command at real data.
- [ ] **Step 3: Use the existing `/tmp/nethammer-browser/node_modules/playwright` headless browser (Browser/IAB unavailable) to capture the seeded catalog at 1512×982, 2560×1440, and 390×844. At 1512 and 2560 require four DOM cards per category, four computed columns and all sixteen local images `complete && naturalWidth > 0`, no horizontal overflow; at 390 expect two columns, no overflow, visible cart navigation. Inspect the screenshots with `read` and check cart plus-button interaction.
- [ ] **Step 4: Inspect the branch diff for data provenance, image matching, CSS specificity and browser results. Push a PR; wait for PHP/JS/asset/Docker CI success before merge. Merging docs/assets resets disposable Render SQLite; do not touch real infrastructure.
- [ ] **Step 5: After Render redeploy, check HTTPS `/up`, the public catalog (`16` items, four per category, image GET 200), two isolated visitor orders and protected viewer URLs. If Render has not finished rebuilding, report the incomplete state rather than claiming the visual fix is live. Record the live link already present in README/profile; no new domain or hosting bill.
