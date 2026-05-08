<?php

const SSK_LANGUAGES = ['en', 'hy', 'ru', 'fr'];
const SSK_DEFAULT_LANG = 'en';

function sskCurrentLang(): string
{
    $lang = $_SESSION['lang'] ?? SSK_DEFAULT_LANG;
    return in_array($lang, SSK_LANGUAGES, true) ? $lang : SSK_DEFAULT_LANG;
}

function sskSetLangFromRequest(): void
{
    if (!isset($_GET['lang'])) {
        return;
    }

    $requested = strtolower(trim((string)$_GET['lang']));
    if (in_array($requested, SSK_LANGUAGES, true)) {
        $_SESSION['lang'] = $requested;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $params = $_GET;
    unset($params['lang']);
    $query = http_build_query($params);
    header('Location: ' . $path . ($query ? ('?' . $query) : ''));
    exit;
}

function sskLoadTranslations(): array
{
    static $cache = [];
    $lang = sskCurrentLang();
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }

    $baseFile = __DIR__ . '/../lang/en.php';
    $langFile = __DIR__ . '/../lang/' . $lang . '.php';

    $base = is_file($baseFile) ? require $baseFile : [];
    $local = is_file($langFile) ? require $langFile : [];
    $cache[$lang] = array_merge($base, $local);
    return $cache[$lang];
}

function t(string $key, array $params = []): string
{
    $map = sskLoadTranslations();
    $text = $map[$key] ?? $key;
    foreach ($params as $paramKey => $value) {
        $text = str_replace('{' . $paramKey . '}', (string)$value, $text);
    }
    return $text;
}

function sskLanguageMeta(): array
{
    return [
        'en' => ['flag' => '🇬🇧', 'label' => 'English'],
        'hy' => ['flag' => '🇦🇲', 'label' => 'Հայերեն'],
        'ru' => ['flag' => '🇷🇺', 'label' => 'Русский'],
        'fr' => ['flag' => '🇫🇷', 'label' => 'Français'],
    ];
}

function sskLanguageHref(string $langCode): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $params = $_GET;
    $params['lang'] = $langCode;
    return $path . '?' . http_build_query($params);
}

