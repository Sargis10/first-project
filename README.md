# Libris (SSK) - Full Project Documentation

Libris is a PHP + MySQL book-library management system with role-based access control, admin analytics, user reading tracking, category management, and configurable site content.

This document is the main technical guide for developers, operators, and maintainers.

## 1) Project Overview

### Goals
- Manage a catalog of books with rich metadata.
- Let users track their reading lifecycle: `want_to_read`, `reading`, `read`.
- Support favorites and personal dashboards.
- Provide an admin control panel with metrics and settings.

### Tech Stack
- Backend: PHP (plain PHP, no framework)
- Database: MySQL / MariaDB
- Frontend: Server-rendered PHP templates + CSS + vanilla JavaScript
- Charts: Chart.js via CDN for admin analytics

### Current Architecture Style
- Feature-based folders:
  - `admin/`
  - `auth/`
  - `library/`
  - `scripts/`
  - `database/`
  - `docs/`
- Shared components:
  - `includes/db.php` (bootstrapping + DB + helpers)
  - `includes/header.php` / `includes/footer.php`

---

## 2) Directory Layout

```text
Booking center(SSK)/
  admin/
    dashboard.php
    categories.php
    settings.php
    index.php
  auth/
    login.php
    register.php
    logout.php
    index.php
  library/
    catalog.php
    my-library.php
    book-form.php
    book-details.php
    stats.php
    about.php
    contact.php
    index.php
  assets/
    css/pages/
    js/pages/
  CSS/
    style.css
  includes/
    db.php
    header.php
    footer.php
  scripts/
    seed.php
    backfill-category-translations.php
  database/
    database_schema.sql
    tables.txt
  docs/
    PROJECT_MEMORY.md
    STRUCTURE.md
  index.php
  uploads/
```

### Why this structure
- Reduces root clutter.
- Improves discoverability of business logic.
- Makes role/function boundaries obvious (`admin`, `auth`, `library`).
- Simplifies onboarding for new developers.

---

## 3) Functional Modules

### Authentication (`auth/`)
- `auth/login.php`: email/password sign-in, session creation.
- `auth/register.php`: user registration with validation and hashing.
- `auth/logout.php`: session destruction and redirect.

### Public/Main Catalog
- `index.php`: global library catalog; requires authenticated user.
- Search and category filter handled client-side.

### User Library (`library/`)
- `library/my-library.php`: tabbed personal view:
  - uploads
  - favorites
  - reading
  - want-to-read
  - completed
- `library/book-details.php`: per-book details, status/favorite toggles.
- `library/book-form.php`: add/edit book form for admin.
- `library/stats.php`: reading insight cards + genre distribution.
- `library/about.php`: content page driven by DB settings.
- `library/contact.php`: multilingual contact page (hero, channels, checklist, shortcuts); inbox from `CONTACT_EMAIL` env or placeholder.

### Admin (`admin/`)
- `admin/dashboard.php`: high-level metrics, charts, recent books/users.
- `admin/categories.php`: CRUD-like management for categories.
- `admin/settings.php`: editable `site_settings` values.

### Data/Bootstrap (`includes/`)
- `includes/db.php`:
  - starts session
  - connects to DB
  - auto-creates DB/tables if missing
  - applies safety migrations for legacy snapshots
  - exposes helpers:
    - `isLoggedIn()`
    - `currentUserId()`
    - `isAdmin()`

---

## 4) Routing and Access Rules

### Main route groups
- `/auth/*` -> auth pages
- `/admin/*` -> admin-only pages
- `/library/*` -> authenticated user pages
- `/index.php` -> authenticated catalog page

### Book pages and privacy-friendly URLs
- `/library/book-details.php` and `/library/book-form.php` intentionally avoid `?id=` query strings in the address bar after navigation.
- Opening a book or starting an edit uses a CSRF-protected POST; the active numeric id is kept in the session (`ssk_view_book_id`, `ssk_edit_book_id`). Direct GET access without a session context redirects back to the catalog.

### Access control behavior
- Guest user:
  - can access login/register
  - is redirected from protected pages to `/auth/login.php`
- Authenticated regular user:
  - can browse catalog, personal library, stats, about
  - cannot access admin pages
- Admin user:
  - full access including admin dashboard/settings/categories and book form

---

## 5) Database Documentation

Canonical SQL is in:
- `database/database_schema.sql`
- `database/tables.txt` (human-readable explanations)

### Tables
- `users`
  - auth + role (`user`/`admin`)
- `categories`
  - category dictionary (`id`, `name`, `slug`, `name_i18n`)
- `books`
  - book entity + metadata + ownership (`user_id`)
- `user_books`
  - many-to-many relation between users and books for status/favorite
- `site_settings`
  - key/value storage for About page content

### Key Relations
- `books.user_id -> users.id` (CASCADE on delete)
- `books.category_id -> categories.id` (SET NULL on delete)
- `user_books.user_id -> users.id` (CASCADE)
- `user_books.book_id -> books.id` (CASCADE)
- Unique logical pair: `(user_id, book_id)` in `user_books`

### Categories: six languages vs database columns

The UI supports six languages (`en`, `hy`, `ru`, `fr`, `de`, `it`). Category labels are **not** stored as six separate MySQL columns. They are stored in **one** column, `categories.name_i18n`, as **UTF-8 JSON** with exactly these keys:

| JSON key | Meaning |
|----------|---------|
| `en` | English label |
| `hy` | Armenian |
| `ru` | Russian |
| `fr` | French |
| `de` | German |
| `it` | Italian |

When an admin saves **Կատեգորիաներ / Categories**, the form posts `name_i18n[en]`, `name_i18n[hy]`, … PHP builds one JSON string and executes `UPDATE categories SET name = ?, slug = ?, name_i18n = ? WHERE id = ?`. The `name` column stays the canonical English string for uniqueness checks and sorting; the JSON holds per-language display strings.

At runtime, `includes/category_i18n.php` (`sskCategoryDisplayName`, `sskBookCategoryDisplay`) chooses the string for the active session language and falls back to English (then `name`) when a translation is empty.

**Bulk fill** the built-in genre names (Business, Fantasy, History, Mystery, Romance, Science Fiction and common slug variants) in all six languages:

```bash
cd "Booking center(SSK)"
php scripts/backfill-category-translations.php
```

The script only updates rows it recognizes (by `slug` or English `name`); it prints `Skip` lines for anything else so you can add those manually in the admin UI.

---

## 6) Setup Guide (Local)

### Prerequisites
- PHP 8.x with `php-mysql`
- MySQL Server (or MariaDB)
- Apache or PHP built-in server

### Database setup
```bash
cd "Booking center(SSK)"
sudo mysql < database/database_schema.sql
```

### App DB user (example)
```sql
CREATE USER IF NOT EXISTS 'ssk_app'@'127.0.0.1' IDENTIFIED BY '<strong-password>';
GRANT ALL PRIVILEGES ON ssk.* TO 'ssk_app'@'127.0.0.1';
FLUSH PRIVILEGES;
```

### Seed admin (secure mode)
```bash
SEED_ADMIN_EMAIL="admin@example.com" \
SEED_ADMIN_PASSWORD="change-this-password" \
php scripts/seed.php
```

### Optional: six-language labels for default genres
After categories exist in MySQL (from normal use or imports), you can push curated translations into `name_i18n`:

```bash
php scripts/backfill-category-translations.php
```

Requires the same `.env` / DB credentials as the web app (`includes/db.php`).

### Run app
```bash
php -S 127.0.0.1:8080
```

Open:
- `http://127.0.0.1:8080/`

---

## 7) Configuration Notes

Runtime DB configuration comes from environment variables (`.env` for local dev):
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- Optional: `DB_PASSWORD_FALLBACKS` (comma-separated)

### Recommendation
Keep `.env` out of git and rotate DB credentials regularly.

Optional public inbox for the Contact page:

- `CONTACT_EMAIL` — shown on `/library/contact.php` and used in the `mailto:` link (defaults to `support@libris.local` if unset).

---

## 8) Security Notes

### What is already done
- Password hashing via `password_hash()`
- Password verification via `password_verify()`
- Prepared statements for queries
- Basic role checks using session role
- Session cookie hardening (`HttpOnly`, `SameSite`, strict mode)
- CSRF protection for state-changing forms

### Gaps to improve
- No brute-force/rate-limit protection on auth
- No centralized input validation layer
- Inline styles/scripts still exist in some templates

---

## 9) Assets and Frontend Organization

- Global style: `CSS/style.css`
- Page-specific style: `assets/css/pages/*`
- Page-specific JS: `assets/js/pages/*`
- Shared layout wrapper: header/footer includes

Current refactor status:
- major inline `<script>`/`<style>` blocks already extracted
- remaining inline style attributes can still be migrated further

---

## 10) Common Maintenance Tasks

### Add a new admin page
1. Create `admin/<page>.php`
2. Require `includes/db.php`
3. Guard with `isAdmin()`
4. Add nav link if needed in `includes/header.php`

### Add a new user page
1. Create under `library/`
2. Guard with `isLoggedIn()`
3. Add route link in header/user dashboard as needed

### Add DB field safely
1. Update `database/database_schema.sql`
2. Add migration logic in `includes/db.php` (`columnExists` check + ALTER)
3. Update form/view pages

---

## 11) Troubleshooting

### `Forbidden` on phpMyAdmin
- Ensure package installed and Apache config enabled.
- Verify `http://localhost/phpmyadmin/` responds `200`.

### `Access denied for user root@localhost`
- Ubuntu often uses socket auth for root.
- Use app user (recommended) for web app connections.

### Page redirects unexpectedly
- Usually auth guard behavior:
  - guest -> `/auth/login.php`
  - non-admin -> `/index.php` on admin pages

### Missing images
- Check files in `uploads/`
- Verify relative paths and file permissions

---

## 12) Documentation Map

- Main guide: `README.md` (this file)
- Structure quick-reference: `docs/STRUCTURE.md`
- Project memory/state: `docs/PROJECT_MEMORY.md`
- DB SQL + table notes:
  - `database/database_schema.sql`
  - `database/tables.txt`

---

## 13) Current Known Improvements Backlog

- Extract remaining inline style attributes to CSS modules.
- Add CSRF protection.
- Move secrets to env-based configuration.
- Add automated tests (integration + route smoke tests).
- Add CI workflow for lint + basic PHP syntax checks.