# SSK Project Memory

This file is the persistent working memory for this project and should be updated when core behavior changes.

## Project Type
- Stack: PHP + MySQL (no framework)
- Public entry page: `index.php`
- Shared DB/bootstrap: `includes/db.php`
- UI assets: `CSS/`, `assets/css/pages/`, `assets/js/pages/`, `uploads/`
- Organized feature routes: `admin/`, `auth/`, `library/`

## Main Functional Areas
- **Auth**: user login/registration, role-based access (`user` vs `admin`)
- **Catalog**: create/edit/delete books, detailed metadata
- **User Library**: reading statuses (`want_to_read`, `reading`, `read`), favorites
- **Admin**:
  - category management (`admin/categories.php`)
  - dashboard analytics (`admin/dashboard.php`)
  - site content settings (`admin/settings.php`)

## Route Layout
- `auth/login.php`, `auth/register.php`, `auth/logout.php`
- `admin/dashboard.php`, `admin/categories.php`, `admin/settings.php`
- `library/catalog.php`, `library/my-library.php`, `library/book-form.php`, `library/book-details.php`, `library/stats.php`, `library/about.php`
- `scripts/seed.php`
- Book detail/edit routes avoid exposing numeric ids in the URL; session keys `ssk_view_book_id` / `ssk_edit_book_id` after CSRF POST handoff.

## Database Map (authoritative)
- `users`: id, role, email, password, created_at
- `categories`: id, name (canonical English label, unique), slug (stable ASCII key for filters, unique), **name_i18n** — one `TEXT` column containing JSON (logical “six fields”: keys `en`, `hy`, `ru`, `fr`, `de`, `it`; not six separate SQL columns). See `README.md` §5 and `database/tables.txt`.
- `books`: id, user_id, title, author, description, cover_url, created_at, updated_at, category_id, isbn, publisher, publish_year, language, page_count, author_bio, format, edition
- `user_books`: id, user_id, book_id, status, is_favorite, created_at, updated_at, unique(user_id, book_id)
- `site_settings`: setting_key, setting_value, updated_at

## Important Runtime Assumptions
- Database name: `ssk`
- Host: `127.0.0.1`
- App user: `ssk_app`
- DB secret source: environment variable `DB_PASSWORD` from local `.env` (not committed)
- `includes/db.php` auto-creates DB/tables and applies basic column migrations.

## Startup Dependencies
- PHP runtime with MySQL extension (`php`, `php-mysql`)
- **Strongly recommended:** `php-mbstring` (UTF-8 helpers). Without it, the app uses ASCII `strtolower` fallbacks for slugs; install on servers: e.g. `apt install php8.3-mbstring`.
- Apache or built-in PHP server
- MySQL/MariaDB server

## Latest updates (2026-05-09)
- Category i18n backfill CLI: `scripts/backfill-category-translations.php` writes six-language JSON for known genre slugs; docs updated (`README.md`, `database/tables.txt`, `database_schema.sql` column comment).
- Catalog search UX: index page has no duplicate H1/subtitle block; only `index.search_label` + search field + category chips. **Site nav** uses `header.site-header` so inner `<header>` elements are not styled with the glass navbar (that had caused a large frosted panel on the catalog).
- Security hardening: env-based DB credentials, CSRF protection on state-changing forms, stricter session cookie settings, and safer error output.
- UI upgrade: redesigned `library/about.php` with rich default content and custom `assets/css/pages/about.css`.
- Visual identity: global classic-library background art with readable overlay tuning in `CSS/style.css`.
- Performance: catalog now loads in batches (`20` per request) via `library/load-books.php` + infinite load behavior in `assets/js/pages/index.js`.
- Filtering fix: category/search filters now query server-side with pagination so each genre returns correct results.
- Added multilingual foundation: six UI languages (`en`, `hy`, `ru`, `fr`, `de`, `it`) via `lang/`, compact language dropdown in `includes/header.php`, translated core pages and footer.
- Categories are localized in the database: `slug` plus `name_i18n` JSON for all six languages; admin manages names in `admin/categories.php`. Display uses `includes/category_i18n.php` helpers.

## User process requirements
- Keep deploying changes to server after important updates.
- Keep pushing project updates to GitHub remotes.
- Maintain this memory file with major user requests and delivered changes.

## Incident note (2026-05-09) — empty catalog + category picker

**User report (summary):** After multilingual categories shipped, the catalog sometimes showed **no books**, and the **category control** in the book form no longer behaved like a clear list of six choices; category labels must follow the **active UI language**.

**Cause:** The catalog search field used the `input` event to call `resetAndReloadBooks()`, which clears the SSR grid. Some browsers/extensions fire `input` on load (e.g. autofill), so the page could replace the initial 20 books with an **empty API result** before the user touched the UI.

**Fix:**
- `assets/js/pages/index.js`: only enable live search after the user **focuses** `#searchInput`; `fetch` uses `credentials: 'same-origin'`.
- `index.php`: `autocomplete="off"` (and related attrs) on search; `ORDER BY books.created_at` qualified.
- `library/book-form.php`: category `<select>` uses `size` so small catalogs (e.g. six categories + placeholder) show as a **visible pick list**; option `selected` compare normalized to string.
- `CSS/style.css`: `select.book-category-select[size]` sizing.

**Deploy routine:** Push `main` to both GitHub remotes (`origin` Samvel10, `sargis` Sargis10); on Hetzner, `git pull` only under `/opt/libery-from-practic`, then `systemctl restart ssk-app`.

**Correction (2026-05-09):** The catalog did **not** delete books or categories. MySQL still held all rows. The UI broke with **PHP Fatal: `mb_strtolower()` undefined** because `php-mbstring` was not installed on the Hetzner image. Fix: `includes/category_i18n.php` adds `sskLower()` fallback; server should install `php8.3-mbstring` for full Unicode support.

## Next planned step
- Keep `main` in sync on both GitHub remotes and redeploy the Hetzner unit after material changes.
