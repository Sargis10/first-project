<?php

declare(strict_types=1);

/**
 * CLI: fill categories.name_i18n with six-language labels for known genres.
 *
 * Matching: lowercase slug first, then slugified categories.name, then exact
 * lowercase categories.name against English keys.
 *
 * Rows with no dictionary match are left unchanged (prints a notice).
 *
 * Usage (from project root, with .env DB credentials):
 *   php scripts/backfill-category-translations.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("This script is CLI-only.\n");
}

require_once __DIR__ . '/../includes/db.php';

/** @return array<string, array<string, string>> keyed by slug (ASCII lowercase) */
function sskCategoryTranslationDictionary(): array
{
    $defs = [
        'business' => [
            'en' => 'Business',
            'hy' => 'Բիզնես',
            'ru' => 'Бизнес',
            'fr' => 'Affaires',
            'de' => 'Wirtschaft',
            'it' => 'Business',
        ],
        'fantasy' => [
            'en' => 'Fantasy',
            'hy' => 'Ֆենթեզի',
            'ru' => 'Фэнтези',
            'fr' => 'Fantasy',
            'de' => 'Fantasy',
            'it' => 'Fantasy',
        ],
        'history' => [
            'en' => 'History',
            'hy' => 'Պատմություն',
            'ru' => 'История',
            'fr' => 'Histoire',
            'de' => 'Geschichte',
            'it' => 'Storia',
        ],
        'mystery' => [
            'en' => 'Mystery',
            'hy' => 'Դետեկտիվ',
            'ru' => 'Детектив',
            'fr' => 'Policier',
            'de' => 'Krimi',
            'it' => 'Giallo',
        ],
        'romance' => [
            'en' => 'Romance',
            'hy' => 'Սիրավեպ',
            'ru' => 'Любовный роман',
            'fr' => 'Romance',
            'de' => 'Liebesroman',
            'it' => 'Rosa',
        ],
        'science-fiction' => [
            'en' => 'Science Fiction',
            'hy' => 'Գիտական ֆանտաստիկա',
            'ru' => 'Научная фантастика',
            'fr' => 'Science-fiction',
            'de' => 'Science-Fiction',
            'it' => 'Fantascienza',
        ],
    ];

    $aliases = [
        'sci-fi' => 'science-fiction',
        'scifi' => 'science-fiction',
        'sciencefiction' => 'science-fiction',
        'science_fiction' => 'science-fiction',
        'sf' => 'science-fiction',
    ];

    $out = [];
    foreach ($defs as $slug => $row) {
        $out[$slug] = $row;
    }
    foreach ($aliases as $alias => $target) {
        if (isset($defs[$target])) {
            $out[$alias] = $defs[$target];
        }
    }

    // Match plain English titles (lowercase) when slug differs or is missing.
    $nameForms = [
        'science fiction' => 'science-fiction',
        'sci fi' => 'science-fiction',
        'sciencefiction' => 'science-fiction',
    ];
    foreach ($nameForms as $nk => $target) {
        if (isset($defs[$target])) {
            $out[$nk] = $defs[$target];
        }
    }

    return $out;
}

$dict = sskCategoryTranslationDictionary();
$stmt = $pdo->query('SELECT id, name, slug, name_i18n FROM categories ORDER BY id ASC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$skipped = 0;

$upd = $pdo->prepare('UPDATE categories SET name = ?, name_i18n = ? WHERE id = ?');

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $name = trim((string)$row['name']);
    $slugRaw = trim((string)($row['slug'] ?? ''));
    $slugKey = $slugRaw !== '' ? sskLower($slugRaw) : '';
    if ($slugKey === '') {
        $slugKey = sskLower(sskSlugifyCategory($name));
    }

    $labels = null;
    if ($slugKey !== '' && isset($dict[$slugKey])) {
        $labels = $dict[$slugKey];
    } else {
        $nameKey = sskLower($name);
        if (isset($dict[$nameKey])) {
            $labels = $dict[$nameKey];
        }
    }

    if ($labels === null) {
        fwrite(STDERR, "Skip id={$id} name=" . json_encode($name, JSON_UNESCAPED_UNICODE) . " slug=" . json_encode($slugRaw, JSON_UNESCAPED_UNICODE) . " (no dictionary entry)\n");
        $skipped++;
        continue;
    }

    $json = sskCategoryNamesJson($labels);
    $canonicalEn = $labels['en'] ?? $name;
    $upd->execute([$canonicalEn, $json, $id]);
    $updated++;
    fwrite(STDOUT, "OK id={$id} -> " . $canonicalEn . "\n");
}

fwrite(STDOUT, "\nDone. Updated: {$updated}, skipped: {$skipped}\n");
