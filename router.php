<?php

/**
 * Front controller for PHP built-in server (production: php -S ... router.php).
 * Usage: php -S 0.0.0.0:8090 -t /path/to/project /path/to/project/router.php
 */
declare(strict_types=1);

$docroot = __DIR__;
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

require_once $docroot . '/includes/routes.php';

// 301 from legacy *.php URLs to opaque routes (reduces fingerprinting).
$legacy = sskLegacyRedirects();
if (isset($legacy[$uri])) {
    $target = $legacy[$uri];
    $q = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    if (is_string($q) && $q !== '') {
        $target .= (str_contains($target, '?') ? '&' : '?') . $q;
    }
    header('Location: ' . $target, true, 301);
    exit;
}

// Let the built-in server handle existing static files and directories.
$fsPath = $docroot . $uri;
if ($uri !== '/' && is_file($fsPath)) {
    return false;
}
if ($uri !== '/' && is_dir($fsPath)) {
    return false;
}

$handlers = sskPublicPathHandlers();
if (!isset($handlers[$uri])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not Found';
    return true;
}

chdir($docroot);
require $handlers[$uri];
return true;
