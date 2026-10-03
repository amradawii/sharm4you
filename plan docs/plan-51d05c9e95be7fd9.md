---
agent: devin-local
session: abyssinian-lifter
created: 2026-08-26T13:24:43Z
---
# Admin (Ad-hoc) Bookings: source column, create/edit modal, money fields, no deletion

Add an admin-created booking type to /admin/bookings (source column, create/edit modal with native date/time + currency & decimal amount, confirmed→completed status only), remove booking deletion entirely, and fix the stale-notes modal bug.

## Findings from the audit

- Bookings are currently created only by tourists via `BookSessionController::store` (status `new`, always tied to a `package_id`).
- `bookings.package_id`, `session_date`, `session_time` are already **nullable** — no schema change needed for package-less admin bookings.
- `Booking::booted()` already auto-generates `SH-XXXXXX` on create, so the R2 photo-delivery flow (`/admin/bookings/{booking}` → upload → access code → `/my-photos`) works for admin bookings with **zero extra work**. That is exactly the goal ("give him the photos via platform").
- **Notes bug diagnosis (no silent error):** DB row `SH-V9WHZF` has `notes = "test"` persisted (`updated_at` = today). The endpoint and validation are fine. The bug is UI-only: the notes modal is opened with the value baked into Blade at render time (`openNotesModal({{ $booking->id }}, @js($booking->name), @js($booking->notes))`), and after the AJAX save the page is never reloaded — so reopening the modal shows the stale, empty value. Fix = client-side notes cache updated from the JSON response.
- Notes stay **optional/nullable** (per your decision) — no "empty notes" validation, admin can clear a note.
- Blocking booking deletion is the right standard: financial/analytics records should be append-only (correct via edit or a `cancelled` status, never `DELETE`). Photo deletion stays allowed (that's storage, not a record).

## Decisions (confirmed)

| Topic | Decision |
|---|---|
| Source column | `enum('customer','admin')` default `customer`, indexed — matches the existing `bookings.status` / `reviews.status` convention; validated in PHP with `Rule::in(Booking::SOURCES)` |
| Source labels | `customer` → **By Tourist**, `admin` → **By Admin** |
| Admin booking date | Required, **past or today only** (`before_or_equal:today`) — finished sessions |
| Time | Required, any manual time, stored `HH:MM`, displayed AM/PM |
| Package | Not selectable → `package_id` stays `null` |
| Amount | `decimal(10,2)` + cast `decimal:2` (mirrors `packages.price`); UI shows whole numbers, cents kept when entered |
| Currency | Select: USD, EUR, EGP, RUB — required |
| Notes | Optional in the create/edit form, still clearable in the notes modal |
| Status | Created as `confirmed`; only `confirmed` ⇄ `completed` allowed for admin bookings |
| Deletion | Removed from UI, controller and routes (all bookings) |
| Edit icon | Only for `source = admin` rows; enforced in the backend too (403) |
| Inputs | Native `<input type="date">` / `<input type="time">` + dial-code `<select>` (no new JS libs) |

## 1. Migration (additive, `php artisan migrate`)

New `database/migrations/..._add_source_and_amount_to_bookings_table.php`:
- `source` enum(`customer`,`admin`) default `customer`, after `package_id`, indexed.
- `currency` string(3) nullable, after `status`.
- `amount` decimal(10,2) nullable, after `currency`.

Existing 2 test rows keep `source = customer`, `amount = null` — nothing to backfill.

## 2. Config

`config/currencies.php` — add a `booking` list (the package `locale_map` / `symbols` stay untouched):
```php
'booking' => [
    'USD' => ['symbol' => '$',   'label' => 'US Dollar'],
    'EUR' => ['symbol' => '€',   'label' => 'Euro'],
    'EGP' => ['symbol' => 'E£',  'label' => 'Egyptian Pound'],
    'RUB' => ['symbol' => '₽',   'label' => 'Russian Ruble'],
],
```
`config/countries.php` — add a small curated `dial_codes` list (`+20 Egypt`, `+7 Russia`, `+44 UK`, `+49 Germany`, `+39 Italy`, `+380 Ukraine`, `+1 USA/Canada`, `+971`, `+966`, `+48`, `+34`, `+33`, `+90`, …) for the admin phone select, default `+20`. The public form keeps intl-tel-input.

## 3. `app/Models/Booking.php`

- Consts `SOURCE_CUSTOMER`, `SOURCE_ADMIN`, `SOURCES`.
- `$fillable` += `source`, `currency`, `amount`; `$casts` += `'amount' => 'decimal:2'`.
- `isAdminBooking(): bool`.
- `allowedStatuses(): array` — admin → `[confirmed, completed]`, customer → `STATUSES`.
- Accessors:
  - `source_label` ("By Tourist" / "By Admin") + `source_color` (badge classes from the existing palette).
  - `amount_label` — same shape as `Package::getFormattedPriceAttribute()`: symbol from `config('currencies.booking')`, `number_format` with 0 decimals when the value is whole, 2 when it has cents; `—` when null.

## 4. Form requests

- **NEW** `app/Http/Requests/Admin/StoreAdminBookingRequest.php`
  ```
  name          required|string|min:2|max:255
  session_date  required|date|before_or_equal:today
  session_time  required|string|size:5|regex:/^([01]\d|2[0-3]):[0-5]\d$/
  country_code  required|string|max:5|in:<config dial codes>
  phone         required|string|max:20|regex:/^[0-9\s\-]+$/   (+ ≥6 digits check)
  currency      required|in:USD,EUR,EGP,RUB
  amount        required|numeric|min:0.01|max:99999999.99|decimal:0,2
  notes         nullable|string|max:2000
  ```
  `prepareForValidation()` strips spaces/dashes from `phone` and prefixes `+` on `country_code` if missing; friendly `messages()` matching the frontend strings word-for-word.
- **NEW** `app/Http/Requests/Admin/UpdateAdminBookingRequest.php` — `extends StoreAdminBookingRequest`, `authorize()` returns `$this->route('booking')->isAdminBooking()` so customer bookings can never be edited through it (403).

## 5. `app/Http/Controllers/Admin/BookingController.php`

- `index()` — also pass `$currencies` and `$dialCodes` for the modal.
- **NEW** `store(StoreAdminBookingRequest)` → creates with `source = admin`, `status = confirmed`, `package_id = null`, phone normalized (same `preg_replace` as `BookSessionController`), returns `{ success, message, booking: serializeBooking(...) }`.
- **NEW** `update(UpdateAdminBookingRequest, Booking)` → updates name/date/time/phone/country_code/currency/amount/notes only (never `status`, never `booking_number`), returns the serialized booking.
- `updateStatus()` → validate against `$booking->allowedStatuses()` instead of `Booking::STATUSES`, so an admin booking can never be pushed to `new`/`contacted`/`cancelled` even by a crafted request.
- **DELETE** `destroy()` (bookings). `destroyPhoto()` stays.
- `serializeBooking()` += `source`, `source_label`, `currency`, `amount`, `amount_label`, `is_admin_booking`, `allowed_statuses`.
- Keep the `Booking::deleting` R2-cleanup hook in the model — still correct for the rare manual/DB-side deletion you'll do yourself.

## 6. Routes (`routes/admin.php`)

```php
Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
// removed: Route::delete('/bookings/{booking}', ... 'destroy')
```

## 7. `resources/views/admin/bookings/index.blade.php`

Table:
- New **Source** column (badge: *By Tourist* / *By Admin*) and **Amount** column (`amount_label`).
- Package cell already renders `—` for admin bookings.
- Status `<select>` loops `$booking->allowedStatuses()` → admin rows only offer Confirmed / Completed.
- Actions: deliver-photos icon (all), **edit icon only when `$booking->isAdminBooking()`** (opens the form modal prefilled via `@js(...)`), notes icon (all). **Delete icon and delete modal removed.**
- Empty-state `colspan` updated; the filter row gains a **"New Booking"** primary CTA on the right (same style as "Add Review").

New create/edit modal (visual pattern copied from `admin/reviews/index.blade.php`): Customer name · Date (`type="date"`, `max` = today) · Time (`type="time"`, browser AM/PM) · Country code select + Phone · Currency select · Amount (`type="number"` step `0.01` min `0.01`) · Notes (optional). No booking-number field — auto-generated. Per-field inline errors from `errors.<field>?.[0]`, plus a hint that the booking is saved as *Confirmed*.

Alpine `bookingManager()`:
- `form`, `formModalOpen`, `editingBooking`, `errors`, `submitting`.
- `validate()` mirroring the backend rules **exactly** (name ≥2, date required & not in the future, time required, phone ≥6 digits & allowed chars, currency in list, amount numeric > 0 with ≤2 decimals, notes ≤2000) — messages kept in one shared object so front/back wording matches.
- `submitForm()` → `POST /admin/bookings` or `PUT /admin/bookings/{id}`; maps 422 `data.errors` into `errors`, toasts via the existing `window.showToast` / `window.toastFromResponse`, then `window.location.reload()` so the server-rendered table, filters and sidebar badge stay accurate.
- **Notes bug fix:** `notesById: @json($bookings->pluck('notes', 'id'))`; `openNotesModal(id, name)` reads from that map, and `saveNotes()` writes `this.notesById[id] = data.booking.notes` on success — reopening the modal now shows what's stored. Also map `data.errors` so a >2000-char note surfaces its real message.
- Remove `deleteTarget`, `confirmDelete()`, `deleteBooking()`.

## 8. `resources/views/admin/bookings/show.blade.php`

Add **Source** badge and **Amount** rows to the details list; the "Package" row renders `—` for admin bookings. Photo upload / access code / WhatsApp flow unchanged and already works for admin bookings.

## Verification

- [ ] `php artisan migrate` (additive only, never `migrate:fresh`) → `source/currency/amount` present, 2 existing rows = `customer`.
- [ ] `php artisan route:list --name=bookings` → `bookings.store`, `bookings.update` present, `bookings.destroy` gone.
- [ ] `./vendor/bin/pint --dirty` clean.
- [ ] UI: create an admin booking → appears as **By Admin**, **Confirmed**, package `—`, amount shown; status select offers only Confirmed/Completed.
- [ ] Frontend validation: submit empty form → per-field errors, no request sent. Then bypass JS (DevTools/curl) with a future date, `amount = 0`, `amount = 10.999`, bad currency → 422 with the same messages.
- [ ] Edit icon: present on admin rows, absent on tourist rows; `PUT /admin/bookings/{tourist-id}` returns 403.
- [ ] Notes: save on a booking → reopen modal → note is there (no reload); clear it → saves empty; confirm with `php artisan tinker`.
- [ ] Delete: no delete icon anywhere; `DELETE /admin/bookings/{id}` → 405.
- [ ] Photo delivery on an admin booking: upload → generate code → `/my-photos` lookup works.

## Risks / considerations

- Changing the `updateStatus` whitelist per source is a behaviour change for existing rows — customer bookings keep all 5 statuses, so no regression.
- `decimal:2` casts return strings in Laravel; all display goes through `amount_label`, and future `SUM(amount)` must be grouped by `currency` (no cross-currency totals without rates).
- Removing the destroy route means any bookmarked/cached JS calling it fails; the UI no longer references it, so impact is nil.

## Explicitly out of scope (avoiding overengineering)

Finance module/reports, soft deletes, exchange-rate conversion, per-source filter tabs, audit log of edits, retro-filling amounts for tourist bookings.
