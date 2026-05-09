# Libris (SSK) — Project documentation

Libris is a PHP + MySQL book-library application: catalog, personal reading states, favorites, multilingual UI (six languages), and an admin area for categories and site content.

This file is the main guide for developers and operators.

---

## 1) Goals and stack

**Goals**

- Rich book metadata and cover handling (local uploads + vetted HTTPS URLs).
- Per-user reading lifecycle: `want_to_read`, `reading`, `read`, plus favorites.
- Opaque public URLs (no `.php` in the address bar) via a single front controller.
- Defense in depth: CSRF, rate limits, CSP, hardened sessions, safe uploads.

**Stack**

- PHP (no framework), MySQL/MariaDB
- Server-rendered templates + `CSS/style.css` + page CSS under `assets/css/pages/`
- Vanilla JS: `assets/js/ssk-ui.js` (global behaviors), `assets/js/pages/*` (per-page)
- Admin charts: Chart.js from jsDelivr (allowed in CSP)

---

## 2) Directory layout

```text
project root/
  router.php                 # Front controller (required for php -S and typical prod PHP process)
  index.php                  # Authenticated catalog (home)
  admin/                     # Dashboard, categories, settings
  auth/                      # login, register, logout
  library/                   # book UI, about, contact, stats, load-books API script
  includes/
    db.php                   # Env, PDO, migrations, security headers, CSRF, cover URL helpers
    routes.php               # sskRoutes(), sskUrl(), path → script map, legacy redirects
    header.php / footer.php  # Layout; sskAssetHref() for cache-busted assets
    i18n.php                 # UI strings (lang/*.php)
    category_i18n.php        # Localized category names from name_i18n JSON
    rate_limit.php           # Fixed-window limits (file + flock)
  lang/                      # en, hy, ru, fr, de, it
  assets/
    css/pages/               # e.g. about.css, activity.css
    js/                      # ssk-ui.js, lang-menu.js, pages/*.js
    images/
  CSS/style.css              # Global styles
  uploads/                   # Cover files (+ .htaccess to block script execution)
  scripts/                   # seed.php, backfill-category-translations.php
  database/
    database_schema.sql      # Reference schema (app also auto-creates/migrates via db.php)
    tables.txt               # Human-readable table notes
  deploy/                    # Optional reverse-proxy rate-limit examples
  docs/
    PROJECT_MEMORY.md        # Changelog-style decisions and deploy notes
    STRUCTURE.md             # Short structure reference
```

---

## 3) HTTP entry and routing

**Public entry** is always through **`router.php`**:

- Built-in server:  
  `php -S 127.0.0.1:8080 -t /path/to/project /path/to/project/router.php`
- Production: run the equivalent (systemd unit, etc.) so dynamic requests hit `router.php`; static files are served from the project root as usual.

**Opaque paths** (user-visible) are defined in `includes/routes.php`. Use **`sskUrl('key')`** in PHP and links/forms — never hard-code legacy `.php` URLs for new work.

| Route key        | Path              | Script (internal)              |
|------------------|-------------------|--------------------------------|
| `home`           | `/`               | `index.php`                    |
| `sign_in`        | `/sign-in`        | `auth/login.php`               |
| `sign_up`        | `/sign-up`        | `auth/register.php`            |
| `sign_out`       | `/sign-out`       | `auth/logout.php`              |
| `about`          | `/about`          | `library/about.php`            |
| `contact`        | `/contact`        | `library/contact.php`          |
| `shelf`          | `/shelf`          | `library/my-library.php`       |
| `activity`       | `/activity`       | `library/stats.php`            |
| `read`           | `/read`           | `library/book-details.php`     |
| `write`          | `/write`          | `library/book-form.php`        |
| `list`           | `/list`           | `library/load-books.php` (JSON)|
| `manage`         | `/manage`         | `admin/dashboard.php`          |
| `manage_topics`  | `/manage/topics`  | `admin/categories.php`         |
| `manage_content` | `/manage/content`| `admin/settings.php`           |

**Legacy URLs** (`/auth/login.php`, `/library/book-details.php`, …) return **301** to the opaque path; see `sskLegacyRedirects()`.

**Book detail / edit IDs** are not exposed as numeric query parameters after navigation: CSRF POST handoff stores `ssk_view_book_id` / `ssk_edit_book_id` in the session (same pattern as before routing).

**Catalog infinite scroll** fetches **`/list`** (not `library/load-books.php` in the browser).

---

## 4) Functional modules

**Auth (`auth/`)** — Login and register validate CSRF; login uses per-IP rate limiting; `session_regenerate_id(true)` on success; password policy on register (see i18n).

**Catalog (`index.php`)** — Requires login; search/category filtering with server-side pagination via `/list`.

**User library (`library/my-library.php`, route `shelf`)** — Tabs: uploads, favorites, reading, wishlist, completed.

**Book detail (`library/book-details.php`, `read`)** — Status/favorite toggles with allowlisted values.

**Book form (`library/book-form.php`, `write`)** — Create/edit; edits are scoped to `books.user_id = current user` (non-admin owners); uploads validated (MIME, size, `uploads/` only).

**Activity (`library/stats.php`, `activity`)** — Logged-in only: expandable sections for finished / reading / wishlist with full book grids; top genres from finished books; opens book via same CSRF POST as catalog.

**About / Contact** — About uses `site_settings`; contact uses `CONTACT_EMAIL` (see below).

**Admin (`admin/`)** — Metrics dashboard, category CRUD with `name_i18n`, whitelisted `site_settings` keys.

---

## 5) Database

Canonical DDL: `database/database_schema.sql` and **`database/tables.txt`** (narrative).

**Tables:** `users`, `categories` (with `name_i18n` JSON), `books`, `user_books`, `site_settings`.

**`books.cover_url`:** May store a path under `uploads/…` or a **normalized HTTPS** URL accepted by `sskNormalizeStoredCoverUrl()` / `sskTrustedHttpsCoverUrl()` in `includes/db.php` (e.g. Open Library). Display uses `sskPublicCoverImgSrc()`.

**Category i18n:** Six JSON keys `en`, `hy`, `ru`, `fr`, `de`, `it` in one column. Runtime helpers: `includes/category_i18n.php`.

**Bulk fill default genre translations:**

```bash
php scripts/backfill-category-translations.php
```

---

## 6) Setup (local)

**Prerequisites**

- PHP 8.x with `pdo_mysql`
- **Recommended:** `php-mbstring` (Unicode slugs/search; without it, ASCII fallbacks apply in `category_i18n.php`)
- MySQL or MariaDB

**Database**

```bash
sudo mysql < database/database_schema.sql
```

App user (example):

```sql
CREATE USER IF NOT EXISTS 'ssk_app'@'127.0.0.1' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON ssk.* TO 'ssk_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```

**Environment (`.env`, not committed)**

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` (optional: `DB_PASSWORD_FALLBACKS`)
- Optional: `CONTACT_EMAIL` for the contact page / `mailto:`
- Optional tuning: `SSK_GLOBAL_RL_MAX` (default `480`), `SSK_GLOBAL_RL_WINDOW` seconds (default `60`) — global request cap in `router.php`; set `SSK_GLOBAL_RL_MAX=0` to disable.

**Seed admin**

```bash
SEED_ADMIN_EMAIL="admin@example.com" \
SEED_ADMIN_PASSWORD="change-this-password" \
php scripts/seed.php
```

**Run**

```bash
php -S 127.0.0.1:8080 -t . router.php
```

Open `http://127.0.0.1:8080/` (redirects unauthenticated users to `/sign-in`).

---

## 7) Security (current)

- **Headers:** `sskSendSecurityHeaders()` — CSP with per-request **`sskCspNonce()`**; `script-src` allows `'self'`, nonce, and `https://cdn.jsdelivr.net`; `img-src` includes `https:` (covers, placeholders) and `blob:` where needed; `style-src` still allows `'unsafe-inline'` until CSS is fully externalized.
- **CSRF** on state-changing forms; `verifyCsrfTokenOrFail()` where required.
- **Session:** cookie `Secure` when HTTPS (including behind `X-Forwarded-Proto`); `HttpOnly`, `SameSite=Lax`; sync with DB each request via `sskSyncSessionWithDatabase()`.
- **Auth rate limits:** login (per IP), register (per IP); see `includes/rate_limit.php`.
- **Catalog API (`/list`):** per-user and per-IP buckets to reduce abuse behind NAT (see `library/load-books.php`).
- **Global router cap:** `router.php` optional env limits above.
- **Covers:** `sskSafePublicCoverPath()` for local files; trusted HTTPS only for remote; upload pipeline uses `finfo`, size cap, `is_uploaded_file`.
- **`uploads/.htaccess`:** blocks execution of script extensions when served by Apache.

Further edge tuning: see `deploy/nginx-limit-req.conf.example` and `deploy/apache-mod-ratelimit.conf.example`.

---

## 8) Assets and i18n

- Global + page CSS; **`sskAssetHref()`** appends `?v=filemtime` for cache busting.
- **`assets/js/ssk-ui.js`:** delegated behaviors (`data-ssk-placeholder`, confirmations, autosubmit) — avoids inline event handlers for CSP.
- Languages: `lang/*.php`; picker in header; category display follows active UI language.

---

## 9) Maintenance patterns

**New public page**

1. Add script under `admin/`, `auth/`, or `library/`.
2. Register path in `sskPublicPathHandlers()` and a stable key in `sskRoutes()` / `sskUrl()`.
3. Add legacy mapping in `sskLegacyRedirects()` if replacing an old `.php` URL.
4. Guard with `isLoggedIn()` or `isAdmin()` as appropriate.

**Schema change**

1. Update `database/database_schema.sql` and `database/tables.txt`.
2. Add idempotent migration in `includes/db.php` (`columnExists` / similar).

---

## 10) Troubleshooting

- **Redirect to sign-in:** expected for protected routes when logged out.
- **403 invalid CSRF:** refresh page and retry.
- **429 on catalog:** rate limit triggered; wait or adjust limits in dev only.
- **mb_strtolower errors on server:** install `php-mbstring` or rely on fallbacks (reduced Unicode behavior).
- **Covers broken:** check `uploads/` permissions and that stored paths are root-relative `uploads/…` or allowed HTTPS URLs.

---

## 11) Documentation map

| File | Purpose |
|------|---------|
| `README.md` | This guide |
| `docs/STRUCTURE.md` | Compact tree + scripts |
| `docs/PROJECT_MEMORY.md` | Decisions, incidents, deploy checklist |
| `database/database_schema.sql` | SQL reference |
| `database/tables.txt` | Table-by-table notes |

---

## 12) Backlog (optional)

- Further reduce `'unsafe-inline'` in CSP by moving remaining inline styles to CSS.
- Automated tests (routing smoke, auth, catalog API).
- CI: `php -l` on changed files.
