<?php

/**
 * User-visible URL paths (no .php, minimal structure disclosure).
 * Real scripts are mapped in /router.php for `php -S`; production uses the same router behind Apache.
 */
function sskRoutes(): array
{
    return [
        'home' => '/',
        'sign_in' => '/sign-in',
        'sign_up' => '/sign-up',
        'sign_out' => '/sign-out',
        'about' => '/about',
        'contact' => '/contact',
        'shelf' => '/shelf',
        'activity' => '/activity',
        'read' => '/read',
        'write' => '/write',
        'list' => '/list',
        'manage' => '/manage',
        'manage_topics' => '/manage/topics',
        'manage_content' => '/manage/content',
    ];
}

/** @param array<string, scalar> $query */
function sskUrl(string $key, array $query = []): string
{
    $routes = sskRoutes();
    $path = $routes[$key] ?? '/';
    if ($query === []) {
        return $path;
    }
    return $path . '?' . http_build_query($query);
}

/**
 * Map public path -> filesystem script for the front controller (router.php).
 *
 * @return array<string, string> path => absolute file path
 */
function sskPublicPathHandlers(): array
{
    $root = dirname(__DIR__);
    return [
        '/' => $root . '/index.php',
        '/sign-in' => $root . '/auth/login.php',
        '/sign-up' => $root . '/auth/register.php',
        '/sign-out' => $root . '/auth/logout.php',
        '/about' => $root . '/library/about.php',
        '/contact' => $root . '/library/contact.php',
        '/shelf' => $root . '/library/my-library.php',
        '/activity' => $root . '/library/stats.php',
        '/read' => $root . '/library/book-details.php',
        '/write' => $root . '/library/book-form.php',
        '/list' => $root . '/library/load-books.php',
        '/manage' => $root . '/admin/dashboard.php',
        '/manage/topics' => $root . '/admin/categories.php',
        '/manage/content' => $root . '/admin/settings.php',
    ];
}

/** @return array<string, string> legacy .php path => new public path */
function sskLegacyRedirects(): array
{
    return [
        '/index.php' => '/',
        '/auth/login.php' => '/sign-in',
        '/auth/register.php' => '/sign-up',
        '/auth/logout.php' => '/sign-out',
        '/library/about.php' => '/about',
        '/library/contact.php' => '/contact',
        '/library/my-library.php' => '/shelf',
        '/library/stats.php' => '/activity',
        '/library/book-details.php' => '/read',
        '/library/book-form.php' => '/write',
        '/library/load-books.php' => '/list',
        '/admin/dashboard.php' => '/manage',
        '/admin/categories.php' => '/manage/topics',
        '/admin/settings.php' => '/manage/content',
        '/auth/index.php' => '/sign-in',
        '/admin/index.php' => '/manage',
        '/library/index.php' => '/',
        '/library/catalog.php' => '/',
    ];
}
