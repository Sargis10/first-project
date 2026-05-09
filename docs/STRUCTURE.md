# Project structure (quick reference)

Feature folders hold PHP pages; **all public HTTP traffic** should go through **`router.php`** at the repo root (see `README.md` §3).

## Root

- `router.php` — Front controller: legacy 301 redirects, optional global rate limit, dispatches to handlers from `includes/routes.php` (`sskPublicPathHandlers()`).
- `index.php` — Catalog (home `/`).

## `includes/`

| File | Role |
|------|------|
| `db.php` | PDO, migrations, sessions, CSRF, security headers, cover URL helpers, `sskSyncSessionWithDatabase()` |
| `routes.php` | `sskRoutes()`, `sskUrl()`, handler map, `sskLegacyRedirects()` |
| `rate_limit.php` | `sskRateLimitExceeded()`, `sskClientIp()` |
| `i18n.php` | `t()`, language resolution |
| `category_i18n.php` | `sskCategoryDisplayName()`, `sskBookCategoryDisplay()`, `sskLower()` |
| `header.php` / `footer.php` | Layout, `sskAssetHref()`, global + per-page CSS, `ssk-ui.js` |

## `auth/`

- `login.php`, `register.php`, `logout.php` — Public routes `/sign-in`, `/sign-up`, `/sign-out`.

## `library/`

- `book-details.php` — `/read`
- `book-form.php` — `/write`
- `load-books.php` — `/list` (JSON for catalog)
- `my-library.php` — `/shelf`
- `stats.php` — `/activity` (reading activity + expandable lists)
- `about.php`, `contact.php` — `/about`, `/contact`
- `catalog.php` — Thin include of `../index.php` (legacy path support)

## `admin/`

- `dashboard.php` — `/manage`
- `categories.php` — `/manage/topics`
- `settings.php` — `/manage/content`

## Front-end assets

- `CSS/style.css` — Global
- `assets/css/pages/*.css` — Page-specific (e.g. `about.css`, `activity.css`); set `$pageStyles` in PHP before `header.php`
- `assets/js/ssk-ui.js` — Global UI (CSP-friendly delegated handlers)
- `assets/js/pages/*.js` — Per-page (e.g. catalog `index.js`)
- `assets/js/lang-menu.js` — Language dropdown

## `lang/`

- `en.php`, `hy.php`, `ru.php`, `fr.php`, `de.php`, `it.php` — UI strings (including `activity.*`, `nav.*`, etc.)

## Data and tooling

- `uploads/` — Cover files; `.htaccess` limits executable types under Apache
- `scripts/seed.php` — CLI admin seed (env vars)
- `scripts/backfill-category-translations.php` — CLI category `name_i18n` fill
- `database/database_schema.sql`, `database/tables.txt` — Schema reference

## `deploy/`

- Example reverse-proxy snippets for extra rate limiting (optional; not required for app boot)
