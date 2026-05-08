# Project Structure

This project now uses feature directories as the main structure.

## Directories

- `admin/`
  - `dashboard.php`
  - `categories.php`
  - `settings.php`
- `auth/`
  - `login.php`
  - `register.php`
  - `logout.php`
- `library/`
  - `catalog.php`
  - `my-library.php`
  - `book-form.php`
  - `book-details.php`
  - `stats.php`
  - `about.php`
- `includes/` (shared bootstrap and layout)
- `assets/css/pages/` (page-level CSS)
- `assets/js/pages/` (page-level JS)
- `CSS/` (global stylesheet)
- `uploads/` (user-uploaded covers)

## Notes

- Main feature pages are inside `admin/`, `auth/`, and `library/`.
- The specific legacy root files requested by the user were removed to keep the root cleaner.
- Authentication pages are now only under `auth/` (no root `login.php`/`logout.php`).
