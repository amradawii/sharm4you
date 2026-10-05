# Sharm4You — Admin Guide

A practical guide for the owner. The admin panel is at **https://sharm4you.com/admin** and is always in English. Your tourists see the site in their own language.

Every action shows a short message (a "toast") in the corner of the screen: green when it worked, red with the reason when it didn't. When a form has a mistake, the field is highlighted and the toast tells you what to fix.

---

## 1. Signing in and your profile

- Sign in with your **username** (capital letters don't matter: `Owner` and `owner` are the same) and password.
- After 5 wrong attempts in one minute you must wait a minute before trying again. This protects the account from password guessing.
- **Profile** (top-right menu or sidebar): change your name, username, e-mail and photo (≤ 2 MB).
- **Change password**: enter the current password, then a new one with at least 8 characters including an uppercase letter, a lowercase letter, a number and a symbol. Very long passwords with special characters are capped at 72 bytes; the form tells you if you go over.
- Always use **Logout** on shared computers.
- You stay signed in on the same browser as long as you use the panel at least once every 7 days. After that, or after logging out, you'll be asked to sign in again.

## 2. Bookings

The sidebar badge shows how many **New** booking requests are waiting.

### Booking requests from tourists

A tourist's booking is a **request to be contacted**, not a payment. It contains the name, phone with country code, optional e-mail, package, date and time. Dates and times are always **Sharm El Sheikh local time**.

Typical flow:

| Status | Meaning |
| --- | --- |
| New | Just received |
| Contacted | You've reached the tourist (WhatsApp/phone) |
| Confirmed | The session is agreed |
| Completed | The session is done |
| Cancelled | It won't happen |

On the booking page you can change the status and keep private **notes** (only you see them). A tourist's own details can't be edited, which keeps the original request intact. Bookings are never deleted, so your history stays complete.

### Recording your own booking ("ad-hoc")

Use **New Booking** for a session you arranged yourself, for example on the beach, already shot and paid. Enter the name, phone, the date (today or earlier), time, the **amount received** and its currency (USD, EUR, EGP or RUB). These bookings are marked **By Admin**, start as *Confirmed*, can be moved to *Completed*, and can be edited later. This is what lets you deliver photos to those clients too.

## 3. Delivering photos to a client

Open the booking. The right side has **Upload photos for the customer** and the access-code panel.

### Upload

- Drag photos onto the upload area or click to choose them: **up to 10 per drop**, each **up to 10 MB**, JPEG, PNG or WebP.
- Each photo is resized to a maximum of 2560 px on its longest side (enough for screens and normal prints) and stored privately in Cloudflare R2. Nothing is kept on the website server.
- **Limit: 50 photos per booking.** The uploader shows how many slots are left and stops at 50. This limit keeps storage costs low so the account can stay within (or close to) Cloudflare's free storage for as long as possible. It can be raised later if needed.
- If a photo fails, it stays in the queue with the reason, and successful photos leave the queue. After a **network error**, reload the page first to check whether that photo was actually saved, then retry only what's missing.
- Very large images (above 24 megapixels) are rejected. Export them smaller from your editing software first.
- Each photo gets a clear filename such as `SH-KFAPH2-20261005-…jpg` (booking number + upload date). The client sees this name when downloading.

### Send the access code

1. Click **Generate access code**. A 6-character code is created together with a ready-made **WhatsApp message** containing the link, booking number and code.
2. Copy the message and send it to the client.
3. The client opens *My Photos*, enters the booking number and code, and can view the photos, download them one by one, or press **Download all** for a ZIP.

Good to know:

- The code **does not expire**. The client can come back any time while the photos are stored.
- Each time the client opens the page, fresh private links are created that work for **24 hours**.
- **Regenerate** the code if it was sent to the wrong person. The old code stops working immediately, but links that were already opened stay valid for up to 24 hours. That can't be cancelled early.
- **Download all** works up to about 100 MB of photos. If a delivery is bigger, the client sees a message and can still download each photo individually.

### Deleting photos and storage

- Delete a photo with its delete button. It's removed from Cloudflare at the same moment. If Cloudflare can't be reached, the photo stays and you'll see an error; try again later.
- The **storage meter** shows the approximate total size of all delivery photos against Cloudflare's 10 GB free tier. It is an estimate; the real figure (and any bill) is in the Cloudflare dashboard.
- Cloudflare charges for storage above the free allowance and for very large numbers of downloads. To keep costs low, **delete deliveries that clients have already downloaded** (for example a few months after the trip) and tell clients to save their photos.
- The website does **not** back up delivery photos. Keep your originals until the client has their photos.

## 4. Gallery

- **Create album:** give it a name in all six languages. Names may use letters (any language), numbers and normal punctuation like `Beach & Desert` or `Family's Trip`. Names made only of symbols (e.g. `!!!`) are rejected.
- **Active / inactive:** inactive albums are hidden from the website, and their photos disappear from the featured sections.
- **Upload photos:** up to 5 at a time, each ≤ 3 MB (JPEG, PNG, WebP or AVIF). They are converted to WebP automatically. The first photo becomes the album cover.
- Add **alt text** (a short description) per language. It helps Google and visually impaired visitors.
- **Featured** photos (★) appear on the Home and Gallery pages, with a maximum of 12 in total.
- Remember: the gallery is **public**. Use it only for photos you're allowed to show; client deliveries belong in bookings.

## 5. Packages

- A package (tier) is one duration, for example 2 hours, offered at three prices: USD for the English and Turkish sites, EUR for the Italian, Romanian and Bulgarian sites, and RUB for the Russian site.
- Duration: 0.5 to 24 hours in half-hour steps. Each duration can exist only once.
- Fill in the name and description in all six languages, the three prices, an optional **discount** (5–70 %), an optional image (≤ 5 MB), and the **time slots** tourists can choose (Sharm time).
- **Active** controls whether it appears on the site. **Featured** highlights it.
- Deleting a package does not delete its past bookings; they simply show no package.

## 6. Reviews

- Reviews from tourists arrive as **Pending** (the sidebar badge shows how many) and are not shown on the site until you **Approve** them. **Reject** hides them permanently.
- You can also add a review yourself (for example one received on WhatsApp), edit text, or delete one.
- **Featured** approved reviews appear on the Home page, with a maximum of 12.
- Reviews are shown in the language they were written in.

## 7. Limits at a glance

| What | Limit |
| --- | --- |
| Delivery photos per booking | 50 |
| Delivery photos per drop | 10, each ≤ 10 MB |
| Download all (ZIP) | about 100 MB |
| Delivery links | valid 24 h (renewed on every visit) |
| Gallery upload | 5 photos per upload, each ≤ 3 MB |
| Featured photos / reviews | 12 / 12 |
| Image resolution | ≤ 24 megapixels |
| Tourist booking form | 3 submissions per minute per device |

## 8. If something goes wrong

| You see | What to do |
| --- | --- |
| "Your session expired. Refresh the page and try again." / "Please sign in again" | Refresh the page (sign in if asked) and repeat the action |
| "Too many attempts" | Wait one minute |
| "Photo storage is temporarily unavailable" | Cloudflare or the server disk didn't respond. Wait and retry; nothing was half-saved |
| "This booking already has the maximum of 50 photos" | Delete some photos, or ask the developer to raise the limit |
| "The image resolution is too large" | Export the photo at a smaller size and upload again |
| Anything else unexpected | Note the time and what you clicked, and contact the developer |

Developer: Amr Adawi · amr.m.adawi@outlook.com
