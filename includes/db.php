<?php
$forwardedProtoRaw = (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '');
$forwardedProto = strtolower(trim(explode(',', $forwardedProtoRaw)[0] ?? '', " \t\n\r\0\x0B\"'"));
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($forwardedProto === 'https');
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('ssk_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/category_i18n.php';
sskSetLangFromRequest();

function loadEnvFile($filePath) {
    if (!is_readable($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function envValue($key, $default = null) {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

loadEnvFile(__DIR__ . '/../.env');

$host = envValue('DB_HOST', '127.0.0.1');
$db = envValue('DB_NAME', 'ssk');
$user = envValue('DB_USER', 'ssk_app');
$charset = 'utf8mb4';

$passwordCandidates = [];
$mainPassword = envValue('DB_PASSWORD', null);
if ($mainPassword !== null) {
    $passwordCandidates[] = $mainPassword;
}
$fallbackCsv = envValue('DB_PASSWORD_FALLBACKS', '');
if ($fallbackCsv !== '') {
    foreach (explode(',', $fallbackCsv) as $fallbackPassword) {
        $fallbackPassword = trim($fallbackPassword);
        if ($fallbackPassword !== '') {
            $passwordCandidates[] = $fallbackPassword;
        }
    }
}
if (count($passwordCandidates) === 0) {
    throw new RuntimeException('DB_PASSWORD is not configured. Add it in environment or .env file.');
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function connectPdo($dsn, $user, $passwordCandidates, $options) {
    $lastException = null;
    foreach ($passwordCandidates as $pass) {
        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $lastException = $e;
        }
    }
    throw $lastException;
}

function tableExists($pdo, $db, $table) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = ? AND table_name = ?
    ");
    $stmt->execute([$db, $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists($pdo, $db, $table, $column) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = ? AND table_name = ? AND column_name = ?
    ");
    $stmt->execute([$db, $table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    try {
        $pdo = connectPdo("mysql:host=$host;dbname=$db;charset=$charset", $user, $passwordCandidates, $options);
    } catch (PDOException $e) {
        // If DB does not exist yet, connect to server and create it.
        $serverPdo = connectPdo("mysql:host=$host;charset=$charset", $user, $passwordCandidates, $options);
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = connectPdo("mysql:host=$host;dbname=$db;charset=$charset", $user, $passwordCandidates, $options);
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) DEFAULT 'user',
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            slug VARCHAR(191) NULL,
            name_i18n TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            description TEXT NULL,
            cover_url VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            category_id INT NULL,
            isbn VARCHAR(20) NULL,
            publisher VARCHAR(255) NULL,
            publish_year INT NULL,
            language VARCHAR(100) DEFAULT 'English',
            page_count INT NULL,
            author_bio TEXT NULL,
            format VARCHAR(100) NULL,
            edition VARCHAR(100) NULL,
            CONSTRAINT fk_books_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            status ENUM('want_to_read', 'reading', 'read') DEFAULT 'want_to_read',
            is_favorite TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_book (user_id, book_id),
            CONSTRAINT fk_user_books_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_user_books_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(255) PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Safety migrations for older DB snapshots.
    if (!columnExists($pdo, $db, 'users', 'role')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user'");
    }

    $bookColumns = [
        'category_id' => "ALTER TABLE books ADD COLUMN category_id INT NULL",
        'isbn' => "ALTER TABLE books ADD COLUMN isbn VARCHAR(20) NULL",
        'publisher' => "ALTER TABLE books ADD COLUMN publisher VARCHAR(255) NULL",
        'publish_year' => "ALTER TABLE books ADD COLUMN publish_year INT NULL",
        'language' => "ALTER TABLE books ADD COLUMN language VARCHAR(100) DEFAULT 'English'",
        'page_count' => "ALTER TABLE books ADD COLUMN page_count INT NULL",
        'author_bio' => "ALTER TABLE books ADD COLUMN author_bio TEXT NULL",
        'format' => "ALTER TABLE books ADD COLUMN format VARCHAR(100) NULL",
        'edition' => "ALTER TABLE books ADD COLUMN edition VARCHAR(100) NULL",
    ];

    foreach ($bookColumns as $column => $sql) {
        if (tableExists($pdo, $db, 'books') && !columnExists($pdo, $db, 'books', $column)) {
            $pdo->exec($sql);
        }
    }

    if (tableExists($pdo, $db, 'categories')) {
        if (!columnExists($pdo, $db, 'categories', 'slug')) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN slug VARCHAR(191) NULL");
        }
        if (!columnExists($pdo, $db, 'categories', 'name_i18n')) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN name_i18n TEXT NULL");
        }

        $pending = $pdo->query("
            SELECT id, name, slug, name_i18n FROM categories
            WHERE slug IS NULL OR slug = '' OR name_i18n IS NULL OR name_i18n = ''
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pending as $c) {
            $name = (string)$c['name'];
            // Only English in JSON; other locales stay empty so the UI can fall back to English
            // until an admin adds real translations (see sskCategoryLabelFromParts).
            $names = [];
            foreach (SSK_LANGUAGES as $lc) {
                $names[$lc] = ($lc === 'en') ? $name : '';
            }
            $json = sskCategoryNamesJson($names);
            $baseSlug = sskSlugifyCategory($name);
            if ($baseSlug === '') {
                $baseSlug = 'cat-' . (int)$c['id'];
            }
            $slug = sskEnsureUniqueCategorySlug($pdo, $baseSlug, (int)$c['id']);
            $upd = $pdo->prepare('UPDATE categories SET slug = ?, name_i18n = ? WHERE id = ?');
            $upd->execute([$slug, $json, (int)$c['id']]);
        }

        try {
            $chk = $pdo->prepare("
                SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = ? AND table_name = 'categories' AND index_name = 'categories_slug_unique'
            ");
            $chk->execute([$db]);
            if ((int)$chk->fetchColumn() === 0) {
                $pdo->exec('ALTER TABLE categories ADD UNIQUE KEY categories_slug_unique (slug)');
            }
        } catch (Throwable $e) {
            // Older snapshots may still have duplicate slugs; app layer enforces uniqueness on write.
        }

        // One-time cleanup: older app versions copied the same label into every language key, so
        // Armenian (etc.) never fell back — sskCategoryLabelFromParts returned the English word from map['hy'].
        // If every filled language has the identical string, keep it only under `en` and clear the rest.
        $normKey = 'ssk_category_i18n_uniform_cleared_v1';
        $normChk = $pdo->prepare('SELECT 1 FROM site_settings WHERE setting_key = ? LIMIT 1');
        $normChk->execute([$normKey]);
        if (!$normChk->fetchColumn()) {
            $allCats = $pdo->query('SELECT id, name, name_i18n FROM categories')->fetchAll(PDO::FETCH_ASSOC);
            $pdo->beginTransaction();
            try {
                foreach ($allCats as $row) {
                    $map = sskCategoryNamesDecode($row['name_i18n'] ?? null);
                    foreach (SSK_LANGUAGES as $lc) {
                        if (!array_key_exists($lc, $map)) {
                            $map[$lc] = '';
                        }
                    }
                    $nonEmpty = [];
                    foreach (SSK_LANGUAGES as $lc) {
                        $t = trim((string)($map[$lc] ?? ''));
                        if ($t !== '') {
                            $nonEmpty[$lc] = $t;
                        }
                    }
                    if (count($nonEmpty) < 2) {
                        continue;
                    }
                    $uniqueVals = array_values(array_unique(array_values($nonEmpty)));
                    if (count($uniqueVals) !== 1) {
                        continue;
                    }
                    $canonical = trim((string)($row['name']));
                    if ($canonical === '') {
                        $canonical = $uniqueVals[0];
                    }
                    $newNames = [];
                    foreach (SSK_LANGUAGES as $lc) {
                        $newNames[$lc] = ($lc === 'en') ? $canonical : '';
                    }
                    $updNorm = $pdo->prepare('UPDATE categories SET name_i18n = ? WHERE id = ?');
                    $updNorm->execute([sskCategoryNamesJson($newNames), (int)$row['id']]);
                }
                $insNorm = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
                $insNorm->execute([$normKey, '1']);
                $pdo->commit();
            } catch (Throwable $eNorm) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Category i18n normalization: ' . $eNorm->getMessage());
            }
        }
    }
} catch (Throwable $e) {
    error_log('Database initialization error: ' . $e->getMessage());
    http_response_code(500);
    die("Database initialization failed. Contact administrator.");
}

/**
 * Baseline security headers for HTML responses (safe defaults; CSP omitted due to inline styles).
 */
function sskSendSecurityHeaders(): void {
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// Helper to check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfTokenOrFail() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid security token. Please refresh and try again.');
    }
}

// Helper to get current user ID
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper to check if current user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

if (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
    sskSendSecurityHeaders();
}
?>
