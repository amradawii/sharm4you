# Deployment — cPanel shared hosting (A2 Hosting / Hosting.com)

Production URL: **https://sharm4you.com**

This guide assumes cPanel with the Terminal feature, MySQL, and the layout below: the Laravel app lives **outside** the web root, and only the contents of `public/` are copied into `public_html`. No queue worker, cron job or Node.js is needed on the server.

```
/home/<cpanel-user>/
├── sharm4you/            ← the Laravel app (app, bootstrap, config, vendor, storage, .env, …)
│   └── public/           ← kept, but not served
└── public_html/          ← contents of sharm4you/public (index.php, .htaccess, css/, images, favicon…)
    └── storage → /home/<cpanel-user>/sharm4you/storage/app/public   (symlink)
```

With this layout, `.env`, the source code and `storage/` cannot be reached from the web. Only two steps differ from a standard Laravel install: the edited `public_html/index.php` (§4) and the manual storage link (§8).

> **On a VPS or any host where you can set the document root:** point the domain at `sharm4you/public`, skip §4, and run `php artisan storage:link` instead of §8. Everything else is the same.

---

## 1. Server requirements

Verified in cPanel → *Select PHP Version* (PHP 8.2, CloudLinux alt-php):

| Setting | Server value | Needed | Notes |
| --- | --- | --- | --- |
| PHP version | 8.2 | ≥ 8.2 | |
| `memory_limit` | 512M | ≥ 384M | Allows the full 24 MP image limit |
| `max_execution_time` | 60 | ≥ 60 | Download All raises its own limit to 120 s |
| `max_input_time` | 600 | ≥ 60 | |
| `upload_max_filesize` | 512M | ≥ 16M | App limits are 10 MB per delivery photo and 3 MB per gallery photo |
| `post_max_size` | 512M | ≥ 20M | Gallery uploads up to 5 × 3 MB in one request |
| `max_input_vars` | 10000 | ≥ 1000 | |
| `file_uploads` | On | On | |
| `display_errors` | Off | **Off** | |
| `expose_php` | Off | Off | |
| `disable_functions` | (empty) | must not include `set_time_limit`, `tempnam`, `symlink` | |
| `allow_url_fopen` | On | On | |
| `opcache.revalidate_freq` | 30 | — | Changed PHP files can take up to 30 s to take effect after an upload |

Required extensions (Extensions tab), all confirmed enabled: `gd`, `zip`, `fileinfo`, `mbstring`, `pdo_mysql`, `openssl`, `curl`. Laravel also needs `ctype`, `tokenizer`, `xml`, `dom`, `filter` and `session`, which are on by default.

Check the GD formats once in Terminal:

```bash
php -r 'print_r(array_intersect_key(gd_info(), array_flip(["JPEG Support","PNG Support","WebP Support","AVIF Support"])));'
```

If AVIF is missing, gallery AVIF uploads fail with a clear validation error. JPEG/PNG/WebP still work.

> Make sure the Terminal's `php` is the same version as the website's (cPanel may default the CLI to another version). If `php -v` doesn't show 8.2, use the full path, e.g. `/opt/alt/php82/usr/bin/php`.

## 2. Prepare the release (on your PC)

```bash
git status                 # clean, everything committed
npm ci && npm run build    # refreshes public/css/app.css
composer test && npm test  # all green
```

Upload everything **except**: `.git/`, `node_modules/`, `vendor/` (installed on the server), `.env`, `.env.testing`, `tests/`, `plan docs/`, `.devin/`, `.vscode/`, `storage/logs/*.log`, `storage/framework/{cache,sessions,views,testing}/*` (keep the `.gitignore` files), and `public/storage` if it exists locally.

A simple method is `git archive --format=zip -o sharm4you.zip HEAD`, which includes only tracked files, so it never contains `.env`, `vendor/` or `node_modules/`. It does include `tests/` and `plan docs/`; you can delete those on the server, but they are harmless because they sit outside the web root. Upload the ZIP with cPanel → File Manager and extract it into `/home/<user>/sharm4you`.

## 3. Install dependencies on the server

```bash
cd ~/sharm4you
composer install --no-dev --optimize-autoloader --no-interaction
```

If `composer` isn't available, run `composer install --no-dev --optimize-autoloader` locally in a clean copy, upload `vendor/` too, and run `composer install` again locally to get the dev tools back.

## 4. Publish `public/` into `public_html`

1. Copy **all** contents of `~/sharm4you/public/` into `~/public_html/`, **including the hidden `.htaccess`** (enable "Show hidden files" in File Manager).
2. Edit `~/public_html/index.php` so it points at the app folder and tells Laravel where the real public directory is:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../sharm4you/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../sharm4you/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../sharm4you/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
```

The `usePublicPath(__DIR__)` line is what makes `public_path()` resolve to `public_html`. Without it, the layouts read the CSS timestamp from `sharm4you/public/css/app.css` (a stale copy, or a crash if it is missing).

3. Every later deployment must copy the rebuilt `public/css/app.css` (and any changed public assets) into `public_html/css/`. **Never overwrite your edited `public_html/index.php`.**

## 5. Configure `.env`

Copy `.env.example` to `~/sharm4you/.env` (permissions `600`) and change only these values:

| Key | Production value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://sharm4you.com` |
| `LOG_STACK` / `LOG_LEVEL` | `daily` / `warning` |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `localhost` and the cPanel database credentials |
| `SESSION_SECURE_COOKIE` | `true` |
| `R2_*` | Bucket name, endpoint and API token (§9) |

Keep the remaining defaults: database sessions with a 7-day idle lifetime, database cache (rate limits need a persistent store), and the `sync` queue (the app dispatches no jobs). Then generate the key:

```bash
php artisan key:generate --force
```

Keep a copy of the final `.env` (especially `APP_KEY`) in a password manager. It is not in Git or in the backups described below.

Create the database and user in cPanel → *MySQL Databases*, and give the user ALL PRIVILEGES on that database only.

## 6. Database

First deployment (empty database):

```bash
php artisan migrate --force
php artisan db:seed --class=PackageSeeder --force   # optional: the 3 standard tiers in USD/EUR/RUB
```

**Do not run `php artisan db:seed` without `--class`.** The default seeder creates a demo web user and demo reviews.

Later deployments: **take a database backup first** (section 11), then `php artisan migrate --force`.

`2026_10_03_110000_add_unique_duration_currency_to_packages_table` adds a unique (hours, currency) index. On an existing database that already has duplicate tiers it will fail without changing data. Check first:

```sql
SELECT hours, currency, COUNT(*) FROM packages GROUP BY hours, currency HAVING COUNT(*) > 1;
```

If this returns rows, merge or delete the duplicates in the admin panel, then migrate.

## 7. Create the admin account

There is no registration page and there are no default credentials. Create the owner account from Terminal with a random one-time password, so the real password is never typed into a shell or a history file:

```bash
cd ~/sharm4you
php artisan tinker
```

```php
$p = Str::password(16); App\Models\Admin::create(['name' => 'Owner Name', 'username' => 'owner', 'email' => 'owner@example.com', 'password' => $p]); echo $p;
```

Type `exit`, sign in at `https://sharm4you.com/admin/login` with the printed password, and **immediately** set a new password in *Profile → Change password*. The username is saved lowercase.

## 8. Storage link and permissions

`php artisan storage:link` run from Terminal would create the link in `sharm4you/public`, not `public_html`, so create it manually:

```bash
ln -s ~/sharm4you/storage/app/public ~/public_html/storage
```

Directories should be `755` and files `644`. `storage/` and `bootstrap/cache/` must be writable by your cPanel user (the default on CloudLinux).

## 9. Cloudflare R2

1. Cloudflare dashboard → R2 → **Create bucket** (e.g. `sharm4you-delivery`).
2. Bucket → Settings: keep **Public access disabled**. Don't enable the `r2.dev` subdomain and don't connect a custom domain. CORS is not needed.
3. R2 → *Manage API tokens* → **Create API token**: permission *Object Read & Write*, **limited to this bucket only**. Copy the access key, secret and the S3 endpoint into `.env`.
4. Don't add lifecycle rules that delete objects. The app deletes objects when the owner deletes photos.
5. R2 objects are **not** included in cPanel backups. Delivery photos exist only in R2, plus the photographer's own originals. Make sure the photographer keeps the originals until the client has downloaded them.

Pricing and the free allowance are at [developers.cloudflare.com/r2/pricing](https://developers.cloudflare.com/r2/pricing/). Check actual usage in the Cloudflare dashboard, not only in the app's approximate meter.

## 10. Caches, HTTPS and final checks

```bash
php artisan optimize        # caches config, routes, views and events
```

Run `php artisan optimize` again after **every** change to `.env` or code. Use `php artisan optimize:clear` to undo it.

- Enable AutoSSL / Let's Encrypt for `sharm4you.com` and `www`. Force HTTPS in cPanel → *Domains* (or add an `.htaccess` redirect).
- Confirm `https://sharm4you.com/up` returns 200, and that an error page doesn't show a stack trace (APP_DEBUG is off).
- Confirm `https://sharm4you.com/.env` and `https://sharm4you.com/../sharm4you` are not reachable (404/403).

## 11. Backups and rollback

- **Before each deployment:** cPanel → *Backup* → download a MySQL backup of the database, and a home-directory backup or at least `sharm4you/storage/app/public` (gallery, package images, avatar).
- Schedule regular cPanel backups if the plan offers them. Keep `.env` safe separately.
- **Rollback:** `php artisan down`, restore the previous release folder and the database backup, run `php artisan optimize`, then `php artisan up`. If you keep each release in its own folder (e.g. `sharm4you-2026-10-05`), rolling back only means switching the folder name used in `public_html/index.php`.

## 12. Updating

```bash
php artisan down
# upload and extract the new release over ~/sharm4you (keep .env and storage/app/public)
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force          # after a DB backup
php artisan optimize
# copy public/css/app.css (and other changed public assets) into public_html
php artisan up
```

## 13. Production smoke test

Use a booking created for testing, not a real client:

1. Home, About, Services, Gallery and an album open in `en` and one other locale (e.g. `/ru`), with styles loaded and gallery images visible.
2. Submit the booking form empty: inline errors and a toast appear. Submit it valid: a success toast appears, and the booking shows as **New** in the admin.
3. Submit a review, then approve it in the admin, and check it appears on Home if featured.
4. Admin login. A wrong password shows an English error, and logout works.
5. On the test booking: upload 2–3 photos, generate an access code, open `/my-photos` in a private window, look up the booking, then preview, single-download, Download All (ZIP), and use mobile lightbox navigation.
6. Delete the test photos, and check in the Cloudflare dashboard that the objects are gone.
7. Check `storage/logs/` for new errors.
