CREATE DATABASE IF NOT EXISTS `ssk` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ssk`;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) DEFAULT 'user',
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- categories.name_i18n: ONE TEXT column holding UTF-8 JSON (not six MySQL columns).
-- Keys must match app languages: en, hy, ru, fr, de, it. Empty string = "use another language fallback" (see includes/category_i18n.php).
-- Example: {"en":"Business","hy":"Բիզնես","ru":"Бизнес","fr":"Affaires","de":"Wirtschaft","it":"Business"}
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(191) NULL,
    name_i18n TEXT NULL COMMENT 'JSON: en, hy, ru, fr, de, it — localized display names'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(255) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
