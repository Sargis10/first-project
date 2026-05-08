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

## Database Map (authoritative)
- `users`: id, role, email, password, created_at
- `categories`: id, name
- `books`: id, user_id, title, author, description, cover_url, created_at, updated_at, category_id, isbn, publisher, publish_year, language, page_count, author_bio, format, edition
- `user_books`: id, user_id, book_id, status, is_favorite, created_at, updated_at, unique(user_id, book_id)
- `site_settings`: setting_key, setting_value, updated_at

## Important Runtime Assumptions
- Database name: `ssk`
- Host: `127.0.0.1`
- App user: `ssk_app`
- App password in code: `ssk_app_2026`
- `includes/db.php` auto-creates DB/tables and applies basic column migrations.

## Startup Dependencies
- PHP runtime with MySQL extension (`php`, `php-mysql`)
- Apache or built-in PHP server
- MySQL/MariaDB server

## Next planned step
- Continue layout cleanup by moving remaining inline style attributes from PHP templates to page CSS files, then publish to GitHub.
