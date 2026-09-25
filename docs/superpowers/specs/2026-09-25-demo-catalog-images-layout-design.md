# Hosted demo catalog: images and four-card desktop rows

## Problem and scope

The public Render demo is seeded with twelve synthetic dishes: three in each of four categories, all without images. The desktop CSS reserves four columns from 1400px, so each category leaves an empty fourth slot. On larger screens the card's fixed 176px image/copy column adds excessive white space. The committed `screenshots/catalog.png` uses a separate, fuller local demo dataset with existing photos; it is not evidence that the hosted demo failed to deploy.

Improve only the disposable hosted catalog and its responsive presentation. Preserve the existing product styling, ordering flow, restricted viewer, Free plan, and separate visitor accounts. Do not copy supplier rows, local database contents, real user data or credentials into the hosted seed.

## Design

- Extend `DemoHostedSeeder` to four **fictional** dishes per existing category (sixteen total). Keep the same category order, reasonable demo prices and open order cycle. Point each item at an appropriate existing, publicly committed `/images/menu/dish-*.png` file; choose visually plausible photos, check the files exist, and do not link external image hosts. Original supplier names, metadata and dataset rows remain excluded.
- Keep the current catalog shell, category rail and cart. At laptop and large-monitor desktop widths with sufficient catalog space, show four cards per row without an empty slot. Image and copy within each desktop card should use available card width (with a sensible image-size cap) instead of looking detached in a wide blank cell. On intermediate widths reduce to three or two columns before cards become cramped; on phones keep the existing compact two-column treatment and single column on very narrow screens. The layout must not overflow horizontally.
- The current fallback for dishes without photos remains functional for other datasets. No separate image storage service or paid infrastructure.

## Verification and rollout

- Add/update isolated hosted-seeder tests asserting sixteen fictional dishes, four per category, and existing local image paths. Add focused front-end layout/regression coverage where automated tests can express the behavior.
- Run PHP and JS tests, asset build, audit as applicable. Capture and inspect browser screenshots of the *hosted-style seeded* catalog at 1512×982 (MacBook), 2560×1440 (monitor), and a phone viewport. Check visible image loading, four-across rows where space permits, cart/order interaction, and no horizontal overflow.
- Merge only after review/CI. The Render Free redeploy resets disposable orders. Smoke-test the deployed public URL before reporting the visual fix complete.

## Out of scope

Gemesis Vault and NoctVPN are separate follow-up tasks, initially confined to private repositories and synthetic/local data. No change to their deployment, production state or credentials in this task.
