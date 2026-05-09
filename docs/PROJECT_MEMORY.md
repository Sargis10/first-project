# SSK Project Memory

This file is the persistent working memory for this project and should be updated when core behavior changes.

## Project Type
- Stack: PHP + MySQL (no framework)
- **Public HTTP entry:** `router.php` (front controller). The built-in server and production PHP process must be started with the router script as the last argument (see `router.php` header comment). Real pages remain as `index.php`, `auth/*.php`, `library/*.php`, `admin/*.php` on disk.
- User-visible paths are **opaque** (no `.php` in the address bar): `/`, `/sign-in`, `/sign-up`, `/sign-out`, `/about`, `/contact`, `/shelf`, `/activity`, `/read`, `/write`, `/list`, `/manage`, `/manage/topics`, `/manage/content`. Mapping lives in `includes/routes.php` (`sskRoutes()`, `sskUrl()`, `sskPublicPathHandlers()`, `sskLegacyRedirects()`).
- Shared DB/bootstrap: `includes/db.php` (also loads `includes/routes.php`).
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
- **Filesystem (internal):** `auth/login.php`, `auth/register.php`, `auth/logout.php`; `admin/dashboard.php`, `admin/categories.php`, `admin/settings.php`; `library/catalog.php`, `library/my-library.php`, `library/book-form.php`, `library/book-details.php`, `library/load-books.php`, `library/stats.php`, `library/about.php`, `library/contact.php`; `scripts/seed.php`.
- **Public URLs:** see `sskRoutes()` in `includes/routes.php`. Legacy `*.php` paths return **301** to the opaque paths; query strings are preserved (e.g. admin category edit `?edit=`).
- Book detail/edit still avoid numeric ids in the URL; session keys `ssk_view_book_id` / `ssk_edit_book_id` after CSRF POST handoff.

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

## Latest updates (2026-05-09) — CSP + NAT-friendly catalog limits + edge deploy hints

**User request:** Address the three “umbrella” gaps: (1) strong browser policy / XSS surface, (2) volumetric abuse beyond app code, (3) corporate NAT sharing one public IP.

**Delivered:**
- **Content-Security-Policy** in `sskSendSecurityHeaders()` with per-request **`sskCspNonce()`**; `script-src 'self' 'nonce-…' https://cdn.jsdelivr.net`; `style-src 'self' 'unsafe-inline'` (full removal of inline styles deferred); tight `default-src`, `object-src 'none'`, `form-action 'self'`, `frame-ancestors 'self'`, `upgrade-insecure-requests`, `img-src` includes `https:` for placehold.co and `blob:` for local previews.
- **No inline event handlers** site-wide: `assets/js/ssk-ui.js` (defer, loaded from `includes/header.php`) handles `data-ssk-placeholder` image fallbacks, `data-ssk-confirm` delete dialogs, and `.ssk-autosubmit` selects; book cover file input uses `book-cover-input` + `book-form.js` listener; admin dashboard Chart block uses **nonce** on the one remaining inline script with `JSON_HEX_*`-safe `json_encode`.
- **Router volumetric cap:** `router.php` applies `sskRateLimitExceeded('global_dynamic', …)` before dynamic handlers; tune with env **`SSK_GLOBAL_RL_MAX`** (default `480` per window) and **`SSK_GLOBAL_RL_WINDOW`** seconds (default `60`). Set `SSK_GLOBAL_RL_MAX=0` to disable.
- **Catalog API / NAT:** `library/load-books.php` uses **two buckets**: per-user `catalog_uid:{id}` (300/min) and shared IP `catalog_ip` (1500/min).
- **Deploy examples (optional edge layer):** `deploy/nginx-limit-req.conf.example`, `deploy/apache-mod-ratelimit.conf.example` — real DDoS still needs provider/WAF; these document reverse-proxy knobs.

---

## Latest updates (2026-05-09) — security hardening pass (rate limits, session sync, covers)

**User request:** Deep security review: assume attackers run many requests and varied techniques; fix what breaks and prevent recurrence.

**Delivered:**
- **`includes/rate_limit.php`:** per-IP fixed-window limits using `sys_get_temp_dir()/ssk-rate` + `flock`; `sskClientIp()` prefers real client when `REMOTE_ADDR` is loopback (uses `X-Forwarded-For` / `X-Real-IP` first hop).
- **Login:** after CSRF, max **25** failed attempts per **15 minutes** per IP → `auth.too_many_attempts` (all 6 UI languages); **constant-time** `password_verify` path via `SSK_PASSWORD_PLACEHOLDER_HASH` when user missing; small random `usleep` on failures.
- **Register:** max **15** posts per **hour** per IP (same throttle helper).
- **Catalog API** (`library/load-books.php`): max **180** JSON requests per **minute** per IP → **429** + `Retry-After: 60` (mitigates scroll/search abuse).
- **Session:** `sskSyncSessionWithDatabase()` runs on each web request after DB init if logged in — role matches DB; deleted users lose session keys (admin demotion effective immediately).
- **Cover URLs:** `sskSafePublicCoverPath()` allowlists only `uploads/…` paths without `..`, `\\`, or NUL; used in catalog cards, load-more HTML, book detail, my-library grid, and safe unlink on delete; **bugfix:** `library/my-library.php` `renderBookGrid` form `action` was broken (literal `<?=` inside string) — now correct `sskUrl('read')` concatenation.
- **Admin settings:** cap stored value length (**120k** chars) per key; show `$error` with escaping; success message escaped.

---

## Latest updates (2026-05-09) — opaque URLs + router

**User request (summary):** Reduce fingerprinting in the browser (avoid visible `login.php`, `book-details.php`, etc.); route everything through short paths; keep defense in depth; sync work to **both** GitHub accounts (`origin` Samvel10, `sargis` Sargis10); deploy to server and verify.

**Delivered in repo:**
- New `includes/routes.php` and root `router.php` (front controller for `php -S` and equivalent production startup).
- All internal links, form `action`s, and `header('Location: …')` redirects updated to use `sskUrl(...)` where applicable; catalog infinite scroll fetches `/list` instead of `/library/load-books.php`.
- `library/load-books.php` JSON fragments: form `action` must use PHP concatenation with `sskUrl('read')` (not `<?=` inside a string).
- Legacy redirects extended (`/library/index.php`, `/library/catalog.php` → `/`; `auth/index.php`, `admin/index.php` stubs point to opaque targets).
- `includes/db.php`: `header_remove('X-Powered-By')` inside existing security headers helper (when headers are sent from PHP).

**Git remotes (2026-05-09):** Commit `850dd88` on `main` pushed successfully to `https://github.com/Samvel10/libery-from-practic.git` and `https://github.com/Sargis10/first-project.git`.

**Server deploy (operator action):** This development environment could not SSH into `5.223.92.226` (no authorized key). On the Hetzner host, as the user that owns `/opt/libery-from-practic`:
1. `cd /opt/libery-from-practic && git pull` (ensure `main` includes `850dd88`).
2. Update the app unit so PHP uses the router, e.g. `ExecStart=/usr/bin/php -S 127.0.0.1:8090 -t /opt/libery-from-practic /opt/libery-from-practic/router.php` (adjust PHP binary path if needed).
3. `sudo systemctl daemon-reload` (if unit changed), then `sudo systemctl restart ssk-app`.
4. Smoke test: `curl -I https://armenianlibery.duckdns.org/sign-in` (200); `curl -I https://armenianlibery.duckdns.org/auth/login.php` (301 to `/sign-in`); logged-in catalog load-more hits `/list`.

---

## Latest updates (2026-05-09)
- HTTPS + performance request handled: user asked to secure `armenianlibery.duckdns.org` with TLS and speed up global background image loading.
- Root cause identified for slow background paint: static image responses had no explicit cache headers from proxied app (`library-reading-room-bg.webp` served without `Cache-Control`), causing repeat fetch/decode; desktop `background-attachment: fixed` also increased repaint cost.
- Mitigation applied in app code: `includes/header.php` now preloads the background image; `CSS/style.css` uses `scroll` attachment on desktop too to reduce jank.
- Proxy-aware HTTPS detection added in `includes/db.php` (`HTTP_X_FORWARDED_PROTO`) so session cookies stay `Secure` when TLS is terminated by Apache reverse proxy.
- User visual feedback after perf tweak: restore the original background visual behavior (desktop `background-attachment: fixed`) because the temporary full-scroll variant made the image composition look enlarged/cropped; keep performance gains from preload + Apache static caching.
- Follow-up UX tweak: improve perceived cover speed without changing image dimensions by adding native image loading hints (`loading`, `decoding`, `fetchpriority`) in catalog/library/detail templates; improve readability by darkening muted text tone and long description/body text colors.
- Search reliability fix (catalog): `assets/js/pages/index.js` now enables live search on real user interaction paths (`focus`, typing, paste, search event) to avoid the edge case where typing did not trigger reload; `library/load-books.php` keeps category filtering active while searching and uses Unicode-aware `LIKE` matching (`utf8mb4_unicode_ci`) so partial title/author searches behave correctly.
- Search incident follow-up: MySQL/PDO with native prepares rejected reusing a single named placeholder twice in `library/load-books.php` (`:q` in both title/author predicates), causing `SQLSTATE[HY093] Invalid parameter number` and 500 responses on non-empty queries. Fix: bind separate placeholders (`:q_title`, `:q_author`) with the same wildcard value.
- Cache coherency hardening: because Apache serves `/assets` with long-lived immutable caching, introduced `sskAssetHref()` in `includes/header.php` and reused it in `includes/footer.php` to append filemtime version query params to local JS/CSS/image asset URLs so hotfixes (e.g., catalog search JS) are picked up immediately after deploy.
- Search performance tuning: `assets/js/pages/index.js` now deduplicates and optimizes live queries with request aborts, stale-response protection, LRU-style in-memory response cache (category+query+offset+limit key), and a calmer debounce (`320ms`). `library/load-books.php` also uses plain `LIKE` for title/author predicates (no explicit per-expression collation) to reduce query overhead.
- Domain activation request processed: user connected `armenianlibery.duckdns.org` -> `5.223.92.226` and requested full activation without `:8090`.
- Delivery plan recorded and executed order: (1) update local memory/docs, (2) configure server reverse proxy on port `80`, (3) keep both GitHub remotes (`origin`, `sargis`) in sync, (4) redeploy/verify service.
- Category i18n backfill CLI: `scripts/backfill-category-translations.php` writes six-language JSON for known genre slugs; docs updated (`README.md`, `database/tables.txt`, `database_schema.sql` column comment).
- Catalog search UX: index page has no duplicate H1/subtitle block; only `index.search_label` + search field + category chips. **Site nav** uses `header.site-header` so inner `<header>` elements are not styled with the glass navbar (that had caused a large frosted panel on the catalog).
- Security hardening: env-based DB credentials, CSRF protection on state-changing forms, stricter session cookie settings, and safer error output.
- UI upgrade: redesigned `library/about.php` with rich default content and custom `assets/css/pages/about.css`.
- Visual identity: global classic-library background art with readable overlay tuning in `CSS/style.css`.
- Performance: catalog now loads in batches (`20` per request) via `library/load-books.php` + infinite load behavior in `assets/js/pages/index.js`.
- Filtering fix: category/search filters now query server-side with pagination so each genre returns correct results.
- Added multilingual foundation: six UI languages (`en`, `hy`, `ru`, `fr`, `de`, `it`) via `lang/`, compact language dropdown in `includes/header.php`, translated core pages and footer.
- Categories are localized in the database: `slug` plus `name_i18n` JSON for all six languages; admin manages names in `admin/categories.php`. Display uses `includes/category_i18n.php` helpers.
- **Security hardening (deep pass):** Code review + fixes: (1) **IDOR** — `library/book-form.php` now scopes `prepare_edit`, edit load, cover preservation SELECT, and UPDATE to `books.user_id = current user` (aligned with `book-details` edit UI). (2) **Uploads** — MIME allowlist via `finfo`, 5 MB cap, `is_uploaded_file`, randomized filename; extension derived from detected MIME only (mitigates PHP/webshell uploads in `uploads/`). (3) **Session fixation** — `session_regenerate_id(true)` after successful login and registration. (4) **Auth input** — `FILTER_VALIDATE_EMAIL` on login/register; new registrations require **10+** character passwords (i18n strings updated). (5) **Admin settings** — `admin/settings.php` whitelists allowed `setting_key` values so arbitrary keys cannot be injected via crafted POST. (6) **Reading status** — `library/book-details.php` allowlists `status` values before DB writes. (7) **HTTP headers** — `sskSendSecurityHeaders()` in `includes/db.php` sets `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` (no full CSP yet due to pervasive inline styles). (8) **Apache uploads** — `uploads/.htaccess` denies execution of `.php`/script-like extensions when served by Apache.

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
- On the server: `git pull` in `/opt/libery-from-practic`, switch `ssk-app` to start PHP with `router.php`, restart service, verify opaque routes and legacy 301s.
- Keep `main` in sync on both GitHub remotes after material changes.
- Keep domain proxy (`armenianlibery.duckdns.org` on `:80`) active and mapped to local app service `127.0.0.1:8090`.
