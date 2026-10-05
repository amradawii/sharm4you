# Sharm4You — Testing

## 1. Commands

| Suite | Command |
| --- | --- |
| Backend (PHPUnit 11, MySQL `sharm4you_test`) | `composer test` |
| One test file | `php vendor/bin/phpunit --filter PhotoDeliveryTest` |
| Frontend logic (Node built-in runner) | `npm test` |
| Code style | `php vendor/bin/pint --test` |
| CSS build | `npm run build` |
| Dependencies | `composer audit --locked` and `npm audit --omit=dev` |

Result at release: **179 PHPUnit tests (869 assertions)** and **26 Node tests**, all passing.

## 2. One-time setup

1. Create an empty MySQL database named exactly **`sharm4you_test`**.
2. Copy `.env.example` to `.env.testing` (git-ignored), then set `APP_ENV=testing`, `DB_DATABASE=sharm4you_test`, `CACHE_STORE=array`, `SESSION_DRIVER=array` and `MAIL_MAILER=array`, and leave the `R2_*` values empty. Run `php artisan key:generate --env=testing`.
3. `npm ci`.

## 3. Safety

Tests can never touch the development database, the real gallery folder or Cloudflare R2:

- Before `RefreshDatabase` runs any migration, `tests/TestCase.php` checks that the environment is `testing` and that both the configured and the live database are `sharm4you_test`. It also checks that the cache, session, queue and mail drivers are non-persistent. If any check fails, the suite stops.
- The `public` and `r2` disks are faked for every test.
- `TestSafetyTest` proves these guards work.

If you ever ran `php artisan config:cache` locally, run `php artisan config:clear` before testing.

## 4. What is covered

Each feature file under `tests/Feature` matches one area (`AdminAuthTest`, `PublicBookingTest`, `PhotoDeliveryTest`, `PhotoStorageTest`, …), and test names describe the behaviour they check. Together they cover:

- every requirement in [REQUIREMENTS.md](../REQUIREMENTS.md);
- the failure paths: malformed input returns 422, storage failures clean up after themselves, rate limits apply, CSRF is enforced, and unsafe album paths are refused.

`tests/Frontend/*.test.cjs` extract the real inline Alpine code from the Blade files and run it in Node with stubs. They cover booking-form validation and Cairo time slots, the toast store, CMS forms, the delivery uploader, the lightbox and the profile form. They don't replace a real browser check, which is what the checklist below is for.

## 5. Manual acceptance checklist

Run on desktop and on a phone, in English and at least one other locale, with test data only.

**Public site**
- [ ] All pages load with styles and images. Prices are in the locale's currency.
- [ ] Booking form (inline and popup): an empty submit shows the field errors and a toast; a valid submit succeeds. Past time slots for today are hidden.
- [ ] The header **Book** button works on every page.
- [ ] A submitted review stays hidden until approved.
- [ ] My Photos: a wrong code shows an error. With the correct code, check previews, the mobile lightbox arrows, a single download and Download All.

**Admin**
- [ ] Login, errors and logout are all in English.
- [ ] Profile and password errors appear inline and in a toast.
- [ ] Bookings: status colors, status and notes, ad-hoc booking create and edit.
- [ ] Delivery: upload, the 50-photo cap, delete, regenerate code (a warning is shown).
- [ ] Gallery: `Beach & Desert` is accepted, `!!!` is rejected, inactive albums disappear from the featured sections.
- [ ] Packages: duration `2.05` is rejected; create, edit and toggle work.
- [ ] Reviews: approve, feature (cap of 12), edit, delete.

**Production:** the smoke test in [DEPLOYMENT.md §13](DEPLOYMENT.md#13-production-smoke-test).
