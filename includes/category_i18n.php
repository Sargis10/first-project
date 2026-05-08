<?php

declare(strict_types=1);

require_once __DIR__ . '/i18n.php';

/** Lowercase for slugs and filters; works without mbstring (Hetzner default missed php-mbstring once). */
function sskLower(string $value): string
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
}

function sskSlugifyCategory(string $label): string
{
    $label = trim($label);
    if ($label === '') {
        return '';
    }
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
    if ($ascii === false) {
        $ascii = $label;
    }
    $s = strtolower($ascii);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s;
}

function sskCategoryNamesDecode(?string $json): array
{
    if ($json === null || $json === '') {
        return [];
    }
    $d = json_decode($json, true);
    return is_array($d) ? $d : [];
}

/** @param array<string, string> $postedByLang keys: en, hy, … */
function sskCategoryNamesFromPost(array $postedByLang): array
{
    $out = [];
    foreach (SSK_LANGUAGES as $code) {
        $v = isset($postedByLang[$code]) ? trim((string)$postedByLang[$code]) : '';
        $out[$code] = $v;
    }
    return $out;
}

/** @param array<string, string> $namesByLang */
function sskCategoryNamesJson(array $namesByLang): string
{
    $ordered = [];
    foreach (SSK_LANGUAGES as $code) {
        $ordered[$code] = $namesByLang[$code] ?? '';
    }
    return json_encode($ordered, JSON_UNESCAPED_UNICODE);
}

/** @param array<string, string> $namesByLang */
function sskCategoryPrimaryName(array $namesByLang): string
{
    if (($namesByLang['en'] ?? '') !== '') {
        return $namesByLang['en'];
    }
    foreach (SSK_LANGUAGES as $code) {
        if (($namesByLang[$code] ?? '') !== '') {
            return $namesByLang[$code];
        }
    }
    return '';
}

function sskCategoryLabelFromParts(?string $nameI18nJson, ?string $legacyName = null): string
{
    $map = sskCategoryNamesDecode($nameI18nJson);
    $lang = sskCurrentLang();
    if (($map[$lang] ?? '') !== '') {
        return $map[$lang];
    }
    $order = array_unique(array_merge(['en'], SSK_LANGUAGES));
    foreach ($order as $try) {
        if (($map[$try] ?? '') !== '') {
            return $map[$try];
        }
    }
    if ($legacyName !== null && $legacyName !== '') {
        return $legacyName;
    }
    return t('books.uncategorized');
}

/** @param array{name?: string, name_i18n?: string|null, slug?: string|null} $cat */
function sskCategoryDisplayName(array $cat): string
{
    return sskCategoryLabelFromParts($cat['name_i18n'] ?? null, $cat['name'] ?? null);
}

/** Book row after JOIN: category_name, category_name_i18n, category_id */
function sskBookCategoryDisplay(array $book): string
{
    if (empty($book['category_id'])) {
        return t('books.uncategorized');
    }
    return sskCategoryLabelFromParts(
        $book['category_name_i18n'] ?? null,
        $book['category_name'] ?? null
    );
}

/** @param array{name?: string, slug?: string|null} $cat */
function sskCategorySlugForFilter(array $cat): string
{
    $slug = isset($cat['slug']) ? trim((string)$cat['slug']) : '';
    if ($slug !== '') {
        return sskLower($slug);
    }
    $fallback = sskSlugifyCategory((string)($cat['name'] ?? ''));
    return $fallback !== '' ? $fallback : 'uncategorized';
}

function sskEnsureUniqueCategorySlug(PDO $pdo, string $baseSlug, ?int $exceptId = null): string
{
    $slug = $baseSlug !== '' ? $baseSlug : 'category';
    $unique = $slug;
    $n = 0;
    while (true) {
        if ($exceptId === null) {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
            $stmt->execute([$unique]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1');
            $stmt->execute([$unique, $exceptId]);
        }
        if (!$stmt->fetch()) {
            return $unique;
        }
        $unique = $slug . '-' . (++$n);
    }
}
