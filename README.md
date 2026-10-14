# Sharm4You

Multilingual website and admin panel for a tourist photography agency in Sharm El Sheikh, Egypt.

**Production:** [https://sharm4you.com](https://sharm4you.com)

Tourists browse the gallery and packages, request a photo session, leave reviews, and later download their private session photos with a booking number and access code. The owner manages everything from an English admin panel.

> **Private, proprietary project.** Source code © 2026 Amr Adawi. All rights reserved. See [LICENSE.md](LICENSE.md).

## Features

- **Public site in 6 languages:** English (default, no prefix), Russian, Italian, Romanian, Bulgarian and Turkish (`/ru`, `/it`, `/ro`, `/bg`, `/tr`), with per-locale SEO metadata and `hreflang` links.
- **Packages:** duration tiers priced in USD, EUR and RUB. Each locale shows its own currency. Tiers support discounts, a featured flag and their own time slots.
- **Booking requests:** an inline form and a popup form. Validation runs in the browser and is repeated on the server. Dates and time slots use Sharm El Sheikh local time (`Africa/Cairo`).
- **Gallery:** albums with localized names, WebP-converted photos and up to 12 featured photos for the Home and Gallery pages.
- **Reviews:** tourists submit reviews, which stay hidden until the owner approves them. Up to 12 can be featured on the home page.
- **Private photo delivery:** photos are stored in a private Cloudflare R2 bucket and accessed through 24-hour presigned links. Tourists can download photos one by one or all at once as a ZIP that is built in their browser (the server never zips).
- **Admin panel:** bookings (tourist requests and ad-hoc bookings recorded by the owner), delivery uploads (140 photos per booking), gallery, packages, reviews and profile.

## Tech stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+ |
| Database | MySQL / MariaDB |
| Views | Blade templates with inline Alpine.js components |
| CSS | Tailwind CSS 3 CLI (no Vite); compiled to `public/css/app.css` |
| Images | Intervention Image 3 with the GD driver |
| Photo delivery | Cloudflare R2 (S3-compatible, private bucket) via Flysystem |
| Tests | PHPUnit 11 on MySQL `sharm4you_test`, plus Node's built-in test runner for the Alpine logic |

There is no SPA, public REST API, queue worker or cron job. Admin and public forms use session-authenticated JSON endpoints protected by CSRF tokens.

## Requirements

- PHP 8.2 or newer with the extensions `gd` (JPEG, PNG, WebP and AVIF support), `zip`, `fileinfo`, `mbstring`, `pdo_mysql`, `openssl`, `curl`, `ctype`, `tokenizer`, `xml` and `dom`.
- Composer 2
- Node.js 20+ and npm (only for building CSS and running the frontend tests)
- MySQL 8 or MariaDB 10.6+
- A Cloudflare R2 bucket and API token (for photo delivery)

## Local setup

```bash
git clone <repository-url> sharm4you
cd sharm4you

composer install
npm ci

cp .env.example .env          # on Windows: copy .env.example .env
php artisan key:generate
```

Edit `.env` with your local database credentials (`DB_DATABASE=sharm4you`) and, if you want to test delivery against a real bucket, your R2 credentials. Then:

```bash
php artisan migrate
php artisan db:seed --class=PackageSeeder   # optional: the three standard package tiers
php artisan storage:link
npm run build
```

Create the first admin account as described in [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md#7-create-the-admin-account). There is no public registration and there are no default credentials.

> `php artisan db:seed` (without `--class`) also creates a demo web user and demo reviews. Use it only in local development, never in production.

## Everyday commands

| Command | Purpose |
| --- | --- |
| `composer dev` | Runs `php artisan serve` and the Tailwind watcher together |
| `npm run dev` | Tailwind watcher only |
| `npm run build` | Minified production CSS in `public/css/app.css` (commit it) |
| `composer test` | PHPUnit suite (guarded so it only runs on `sharm4you_test`) |
| `npm test` | Node tests for the inline Alpine components |
| `php vendor/bin/pint` | Code style (Laravel preset) |

Because Tailwind scans Blade views and PHP models, run `npm run build` after changing classes in either, and commit the rebuilt CSS.

## Documentation

| Document | Contents |
| --- | --- |
| [REQUIREMENTS.md](REQUIREMENTS.md) | Requirements specification: actors, features, business rules, limits, acceptance criteria |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Modules, data model, request flows, endpoints, storage and security design |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | cPanel shared-hosting deployment, configuration and smoke tests |
| [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) | Owner's guide to the admin panel |
| [docs/TESTING.md](docs/TESTING.md) | Test database setup, automated suites and the manual acceptance checklist |
| [LICENSE.md](LICENSE.md) | Proprietary license |

The `plan docs/` folder contains the historical planning notes written during development. Where they disagree with the documents above, the documents above are authoritative.

## Author

**Amr Adawi** · amr.m.adawi@outlook.com
