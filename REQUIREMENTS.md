# Sharm4You — Requirements Specification

This is the authoritative specification of the delivered system. Requirement IDs (`FR-…`, `BR-…`, `NFR-…`) are referenced by tests and the acceptance checklist in [docs/TESTING.md](docs/TESTING.md).

## 1. Purpose and scope

Sharm4You is the website of a tourist photography agency in Sharm El Sheikh. It must:

1. present the agency, its packages, gallery and reviews to tourists in six languages;
2. collect session booking **requests** (contact requests, not payments);
3. let the owner deliver each tourist's session photos privately; and
4. give the owner a single admin panel for all of the above.

### Out of scope

Online payment, price snapshots or financial reporting (a future finance module), availability locking or calendar reservations, customer accounts, e-mail/SMS notifications, multiple admin roles, public API, queues and scheduled jobs.

## 2. Actors

| Actor | Description |
| --- | --- |
| Visitor / tourist | Anonymous user of the public site. Identified for delivery only by booking number plus access code. |
| Owner (admin) | Authenticated user of `/admin`. All admin accounts have the same full permissions. |
| Cloudflare R2 | External private object storage for delivery photos. |

## 3. Localisation and time

- **BR-1** Public locales: `en` (default, unprefixed URLs), `ru`, `it`, `ro`, `bg`, `tr` (prefixed URLs, e.g. `/ru/services`). `/en`, `/de` and `/uk` permanently redirect (301) to unprefixed URLs.
- **BR-2** Package currency follows locale: `en`/`tr` → USD, `it`/`ro`/`bg` → EUR, `ru` → RUB.
- **BR-3** The admin panel and every admin message, validation error and toast are in English, whatever the public locale. User-entered content (names, notes, reviews) is not translated.
- **BR-4** Session dates, time slots and "today / past slot" checks use Sharm El Sheikh local time (`Africa/Cairo`, including daylight saving), regardless of the visitor's device timezone. The booking form labels times as Sharm El Sheikh local time. Ordinary database timestamps stay in UTC.

## 4. Functional requirements — public site

### Pages

- **FR-1** Home shows the hero, featured gallery photos (max 12), active packages in the locale currency, approved featured reviews (max 12) and the review form.
- **FR-2** About, Services (packages and the inline booking form), Gallery (album list and featured photos) and album pages are available in every locale.
- **FR-3** Inactive albums return 404, and their photos never appear in public featured sections.
- **FR-4** The header "Book" button opens the booking popup on pages that include it (Home, Services). Elsewhere it navigates to the localized Services booking section.

### Booking request

- **FR-5** A visitor submits: name, country dial code, phone, optional e-mail, package, session date and session time.
- **FR-6** Validation, identical in browser and server:
  - name 2–255 characters;
  - phone: spaces and dashes are stripped, then 6–15 digits only; dial code plus phone ≤ 15 digits (E.164);
  - dial code from the public supported list;
  - e-mail optional, valid, ≤ 255 characters;
  - package must be active and priced in the current locale's currency;
  - date `Y-m-d`, today or later (Cairo);
  - time must be one of the package's slots, and for today must be later than the current Cairo time.
- **FR-7** An empty or invalid submission sends no request. It shows every field error inline, scrolls to and focuses the first invalid field, and shows one toast. A valid submission shows a success toast and resets the form. Server-side 422 errors are displayed the same way.
- **FR-8** Bookings are created with status `new` and source `customer`. Visitors cannot set status, source, amount or notes.

### Reviews

- **FR-9** A visitor submits name, country (ISO-2) and comment (10–1000 characters). The review is stored as `pending` and is not public until approved.

### Photo delivery ("My Photos")

- **FR-10** A visitor enters booking number and access code (case-insensitive, trimmed). Wrong, malformed or unknown credentials produce the same generic error. The access code is never flashed back into the form.
- **FR-11** On success the visitor sees all delivery photos with inline previews, a lightbox (keyboard and visible mobile controls), per-photo download links and **Download all (ZIP)**.
- **FR-12** Preview and download use separate presigned R2 URLs valid for 24 hours. Download URLs force `Content-Disposition: attachment` with the stored filename.
- **FR-13** The ZIP contains every photo exactly once. Entries are numbered (`01-…`, `02-…`) so equal names never collide. Photos are stored without recompression. If any object is missing or a limit is exceeded, a clear error is returned instead of an incomplete ZIP.
- **FR-14** Delivery pages, results, errors and downloads are sent with `Cache-Control: private, no-store` and `X-Robots-Tag: noindex, nofollow`.
- **FR-15** All delivery links and forms keep the visitor's locale prefix.

## 5. Functional requirements — admin panel

### Authentication and profile

- **FR-20** Login uses username and password. Usernames are stored and compared lowercase and trimmed. Logging in regenerates the session, and logging out invalidates it. There is no registration endpoint.
- **FR-21** Every `/admin` route except login requires the `admin` guard. JSON requests without a session receive 401, and browser requests are redirected to the login page.
- **FR-22** Profile: name (2–100 characters), username (3–50 characters: letters, digits, underscore; unique), e-mail (unique) and avatar (≤ 2 MB). A new avatar is saved before the old one is removed.
- **FR-23** Password change requires the current password. The new password must be at least 8 characters with uppercase, lowercase, a digit and a symbol, confirmed, and at most 72 UTF-8 bytes (bcrypt limit).

### Bookings

- **FR-30** List all bookings with a status filter and a count of new bookings. View details, change status and edit notes (≤ 2000 characters).
- **FR-31** Statuses: `new`, `contacted`, `confirmed`, `completed`, `cancelled`. Admin-created bookings only move between `confirmed` and `completed`.
- **FR-32** The owner can record an **ad-hoc booking** for a session already done and paid. It has a name, dial code, phone, date (today or past, Cairo time), time, currency (USD, EUR, EGP or RUB), amount (> 0, 2 decimal places) and notes. It is stored with source `admin`, status `confirmed` and no package.
- **FR-33** Only admin-created bookings can be edited. Tourist bookings keep their submitted data; only status and notes change. Bookings cannot be deleted.
- **FR-34** Every booking receives a unique number `SH-XXXXXX` (6 characters from an unambiguous alphabet without 0/O/1/I/L).

### Photo delivery uploads

- **FR-40** The owner uploads delivery photos on the booking page. Each selection or drop accepts up to 10 images, and the queue uploads them one per request.
- **FR-41** Accepted formats are JPEG, PNG and WebP, each ≤ 10 MB and ≤ 24 megapixels / 12,000 px per edge (and within the server's memory budget). Photos are scaled down proportionally to a longest edge of 2560 px and re-encoded in their original format (JPEG/WebP quality 85).
- **FR-42** Each booking holds at most **50** photos. This is enforced on the server under a row lock, so two browser tabs cannot exceed it. The UI shows the remaining slots and disables selection at capacity. Existing bookings with more than 50 photos are never trimmed.
- **FR-43** Each photo is stored in R2 as `bookings/{booking_number}/{uuid}.{ext}` with the filename `{booking_number}-{YYYYMMDD Cairo}-{uuid}.{ext}`. The filename is generated once and never changes.
- **FR-44** Deleting a photo deletes its R2 object first. If R2 deletion fails, the database row is kept and an error is shown.
- **FR-45** **Generate / regenerate access code** creates a new 6-character code and a ready-to-send WhatsApp message with the localized link, booking number and code. Regenerating blocks future lookups with the old code, but links already issued remain valid until they expire (≤ 24 h). The UI warns about this.
- **FR-46** A storage meter shows the approximate total size of delivery photos (from the database) against a 10 GB reference. It is not a Cloudflare billing figure.

### Gallery

- **FR-50** Albums have names in all six locales and an active flag. Names allow Unicode letters/digits and normal punctuation (e.g. "Beach & Desert", "Family's Trip"), but must contain at least one letter or digit and no control characters or path symbols. The slug comes from the English name, falls back to `album`, and is made unique.
- **FR-51** Renaming the English name moves the album folder. A failed move is rolled back. Unsafe or empty slugs never cause a move or delete.
- **FR-52** Photo upload: up to 5 files per request, JPEG/PNG/WebP/AVIF, ≤ 3 MB each, pixel limits as FR-41. Photos are converted to WebP (quality 90) on the public disk. Each photo has alt text in six locales and can be featured (12 globally). The first uploaded photo becomes the album cover automatically. If the cover is deleted, the next photo takes its place.
- **FR-53** Deleting an album deletes its photos and folder.

### Packages

- **FR-60** A tier is one duration offered in USD, EUR and RUB (three rows), each with its own price (0–99,999). The rows share localized names and descriptions (6 locales, description ≤ 1000 characters), the discount (0–70 % in steps of 5), the time slots (`HH:MM`, at least one) and an optional image (≤ 5 MB, converted to WebP).
- **FR-61** Duration: 0.5–24 hours in 0.5-hour steps. A duration–currency pair is unique (database index). Conflicts return a validation error, not a server error.
- **FR-62** Tiers can be toggled active/featured and deleted. Deleting a package keeps its bookings (the package reference is set to null).

### Reviews

- **FR-70** The owner can create, edit, approve, reject, feature and delete reviews. The country name is derived on the server from the ISO code. At most 12 reviews can be featured, enforced on create, edit and toggle.

### Feedback

- **FR-80** Every admin action reports its result as an English toast. Validation errors appear both inline and in a toast. 401, 419 (expired session), 429 and non-JSON failures show helpful English messages and restore the button state.

## 6. Limits summary

| Limit | Value | Where configured |
| --- | --- | --- |
| Delivery photos per booking | 50 | `config/photo_delivery.php` → `max_per_booking` |
| Delivery files per selection/drop | 10 | `max_per_drop` |
| Delivery file size | 10 MB | `max_upload_kb` |
| Image resolution (all uploads) | 24 MP, 12,000 px edge, plus a memory-budget check | `max_pixels`, `max_image_edge`, `decode_*` |
| ZIP source size | 100 MiB | `zip_max_bytes` |
| Delivery link lifetime | 24 h | `BookingPhoto::LINK_TTL_HOURS` |
| Gallery upload | 5 files × 3 MB per request | `UploadPhotosRequest` |
| Featured photos / reviews | 12 / 12 | `PhotoController`, `Review::FEATURED_LIMIT` |
| Admin login | 5/min per username+IP, 20/min per IP | `AppServiceProvider` |
| Public booking / review / lookup / ZIP | 3 / 3 / 5 / 3 per minute per IP, independent | `AppServiceProvider` |

## 7. Non-functional requirements

- **NFR-1 Security.** CSRF protection on all state-changing routes. Admin routes are behind the `admin` guard. Output is escaped (Blade `{{ }}` / Alpine `x-text`). There is no raw SQL built from user input. Malformed input types return 422, never 500. Uploaded images are checked for real MIME type and dimensions before decoding. Secrets come from `.env` only.
- **NFR-2 Privacy.** Delivery photos never touch the public web root. The R2 bucket is private, and access is only through presigned URLs. Delivery pages are not cached or indexed. Logs never contain access codes, R2 keys or signed URLs.
- **NFR-3 Consistency.** Storage write failures are surfaced as errors, never reported as success. If a database save fails after a file write, the new file is cleaned up. Replacement images are persisted before the old file is removed. External storage and the database are not atomically transactional, so a failed cleanup is logged and reported (see ARCHITECTURE.md §7).
- **NFR-4 Hosting.** Runs on cPanel shared hosting (PHP 8.2, 512 MB memory, 60 s execution limit; ZIP requests raise their own limit to 120 s) without SSH-only tooling, queues, cron or Node at runtime.
- **NFR-5 Performance.** Pages that don't need packages skip the package query. ZIP generation streams each R2 object to a temporary file instead of holding all photos in memory.
- **NFR-6 Maintainability.** Laravel conventions, form requests for validation, configuration for limits, Pint code style, automated tests on a dedicated database.

## 8. Acceptance criteria

The release is accepted when:

1. the PHPUnit suite, the Node frontend tests, Pint, the Tailwind build and `composer audit` pass (see [docs/TESTING.md](docs/TESTING.md));
2. the manual checklist in docs/TESTING.md passes on desktop and mobile, in at least two locales;
3. the deployment smoke test in [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) passes on production, including one real R2 upload, preview, single download, ZIP download and delete.

## 9. Known limitations

- Presigned links cannot be revoked before they expire (≤ 24 h).
- The storage meter is an estimate from stored sizes. Cloudflare's dashboard is the billing source of truth.
- ZIP generation is synchronous and limited to 100 MiB of photos. Larger deliveries must use individual downloads.
- A host process killed mid-request may leave a temporary file in the system temp directory.
- `npm audit` reports a development-only advisory in the Tailwind 3 build chain (`braces`, no upstream fix). It does not affect the production runtime, which ships only the compiled CSS.
