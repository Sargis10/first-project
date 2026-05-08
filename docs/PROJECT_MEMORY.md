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
- `categories`: id, name (canonical English label, unique), slug (stable ASCII key for filters, unique), name_i18n (JSON object: `en`, `hy`, `ru`, `fr`, `de`, `it`)
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
- Apache or built-in PHP server
- MySQL/MariaDB server

## Latest updates (2026-05-09)
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

## Next planned step
- Mirror current project state to additional repository `https://github.com/Sargis10/first-project.git` and continue syncing future updates there.
