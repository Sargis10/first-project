<?php
require_once __DIR__ . '/db.php';
// We assume session_start() is called inside db.php already.
$pageStyles = $pageStyles ?? [];
$pageScripts = $pageScripts ?? [];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(sskCurrentLang()) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('app.title')) ?></title>
    <link rel="stylesheet" href="/CSS/style.css">
    <?php foreach ($pageStyles as $stylePath): ?>
        <?php
            $resolvedStylePath = $stylePath;
            if (!preg_match('/^https?:\/\//', $resolvedStylePath) && strpos($resolvedStylePath, '/') !== 0) {
                $resolvedStylePath = '/' . ltrim($resolvedStylePath, '/');
            }
        ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($resolvedStylePath) ?>">
    <?php endforeach; ?>
    <script src="/assets/js/lang-menu.js" defer></script>
</head>

<body>
    <header class="site-header">
        <div class="container">
            <a href="/index.php" class="logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20" />
                </svg>
                Libris
            </a>

            <nav class="menu">
                <a href="/library/about.php" class="btn btn-ghost" style="display: flex; align-items: center; gap: 4px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <?= htmlspecialchars(t('nav.about')) ?>
                </a>
                <?php if (isLoggedIn()): ?>
                    <a href="/library/my-library.php" class="btn btn-ghost" style="display: flex; align-items: center; gap: 4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <?= htmlspecialchars(t('nav.my_library')) ?>
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="/admin/dashboard.php" class="btn btn-ghost" style="display: flex; align-items: center; gap: 4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <?= htmlspecialchars(t('nav.dashboard')) ?>
                    </a>
                    <form method="POST" action="/library/book-form.php" style="margin: 0; display: flex;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                        <input type="hidden" name="action" value="prepare_create">
                        <button type="submit" class="btn btn-ghost" style="display: flex; align-items: center; gap: 4px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?= htmlspecialchars(t('nav.add_book')) ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <a href="/library/stats.php" class="btn btn-ghost" title="<?= htmlspecialchars(t('nav.activity')) ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    </a>
                    <a href="/auth/logout.php" class="btn btn-ghost" title="<?= htmlspecialchars(t('nav.logout')) ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="/auth/login.php" class="btn btn-outline"><?= htmlspecialchars(t('nav.login')) ?></a>
                <?php endif; ?>
                <?php $langs = sskLanguageMeta(); $activeLang = sskCurrentLang(); $activeMeta = $langs[$activeLang] ?? $langs['en']; ?>
                <div class="lang-menu" aria-label="<?= htmlspecialchars(t('nav.lang.aria')) ?>">
                    <button type="button" class="lang-menu__trigger" aria-expanded="false" aria-haspopup="true" aria-controls="langMenuPanel" id="langMenuTrigger">
                        <span class="lang-menu__trigger-flag" aria-hidden="true"><?= $activeMeta['flag'] ?></span>
                        <span class="lang-menu__trigger-label"><?= htmlspecialchars($activeMeta['label']) ?></span>
                        <svg class="lang-menu__chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="lang-menu__panel" id="langMenuPanel" role="menu" aria-labelledby="langMenuTrigger">
                        <?php foreach ($langs as $code => $meta): ?>
                            <li role="none">
                                <a role="menuitem" href="<?= htmlspecialchars(sskLanguageHref($code)) ?>"
                                   class="lang-menu__option <?= $activeLang === $code ? 'is-active' : '' ?>"
                                   lang="<?= htmlspecialchars($code) ?>"
                                   hreflang="<?= htmlspecialchars($code) ?>">
                                    <span class="lang-menu__opt-flag" aria-hidden="true"><?= $meta['flag'] ?></span>
                                    <span><?= htmlspecialchars($meta['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </header>