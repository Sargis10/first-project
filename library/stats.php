<?php
require_once __DIR__ . '/../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ' . sskUrl('sign_in'));
    exit;
}

/**
 * @param array<int, array<string, mixed>> $books
 */
function activity_render_book_grid(array $books): void {
    echo '<div class="activity-book-grid">';
    foreach ($books as $b) {
        $ph = 'https://placehold.co/400x600/1a1a1a/ffffff?text=' . rawurlencode((string)($b['title'] ?? ''));
        $src = sskPublicCoverImgSrc($b['cover_url'] ?? null);
        $phAttr = '';
        if ($src === '') {
            $src = $ph;
        } else {
            $phAttr = ' data-ssk-placeholder="' . htmlspecialchars($ph, ENT_QUOTES, 'UTF-8') . '"';
        }
        $alt = htmlspecialchars((string)($b['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<div class="activity-book-card">';
        echo '<form method="POST" action="' . htmlspecialchars(sskUrl('read'), ENT_QUOTES, 'UTF-8') . '" style="margin:0;">';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
        echo '<input type="hidden" name="action" value="open_book">';
        echo '<input type="hidden" name="book_id" value="' . (int)$b['id'] . '">';
        echo '<button type="submit" class="activity-book-hit">';
        echo '<div class="activity-book-card__cover"><img src="' . htmlspecialchars($src) . '" alt="' . $alt . '" loading="lazy" decoding="async"' . $phAttr . '></div>';
        echo '<div class="activity-book-card__meta">' . htmlspecialchars(sskBookCategoryDisplay($b)) . '</div>';
        echo '<div class="activity-book-card__title">' . htmlspecialchars((string)($b['title'] ?? '')) . '</div>';
        echo '<div class="activity-book-card__author">' . htmlspecialchars((string)($b['author'] ?? '')) . '</div>';
        echo '</button></form></div>';
    }
    echo '</div>';
}

$user_id = (int)currentUserId();

$listSql = "
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM user_books ub
    JOIN books ON ub.book_id = books.id
    LEFT JOIN categories ON books.category_id = categories.id
    WHERE ub.user_id = ? AND ub.status = ?
    ORDER BY ub.updated_at DESC, books.title ASC
";

$listStmt = $pdo->prepare($listSql);
$listStmt->execute([$user_id, 'read']);
$books_read = $listStmt->fetchAll(PDO::FETCH_ASSOC);
$listStmt->execute([$user_id, 'reading']);
$books_reading = $listStmt->fetchAll(PDO::FETCH_ASSOC);
$listStmt->execute([$user_id, 'want_to_read']);
$books_want = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$n_read = count($books_read);
$n_reading = count($books_reading);
$n_want = count($books_want);

$stats_stmt = $pdo->prepare("
    SELECT categories.name, categories.name_i18n, COUNT(*) as count
    FROM user_books
    JOIN books ON user_books.book_id = books.id
    JOIN categories ON books.category_id = categories.id
    WHERE user_books.user_id = ? AND user_books.status = 'read'
    GROUP BY categories.id
    ORDER BY count DESC
    LIMIT 3
");
$stats_stmt->execute([$user_id]);
$fav_categories = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

$pageStyles = ['assets/css/pages/activity.css'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container activity-page" style="max-width: 960px;">
    <div class="activity-hero">
        <h1><?= htmlspecialchars(t('activity.title')) ?></h1>
        <p><?= htmlspecialchars(t('activity.subtitle')) ?></p>
    </div>

    <div class="activity-panels">
        <details class="activity-panel activity-panel--read" id="activity-read">
            <summary>
                <div class="activity-panel__lead">
                    <div class="activity-panel__label"><?= htmlspecialchars(t('activity.completed')) ?></div>
                    <div class="activity-panel__count"><?= (int)$n_read ?></div>
                    <div class="activity-panel__hint"><?= htmlspecialchars(t('activity.expand_hint')) ?></div>
                </div>
                <span class="activity-chevron" aria-hidden="true"></span>
            </summary>
            <div class="activity-panel__body">
                <?php if ($n_read === 0): ?>
                    <p class="activity-empty"><?= htmlspecialchars(t('activity.empty_completed')) ?></p>
                <?php else: ?>
                    <?php activity_render_book_grid($books_read); ?>
                <?php endif; ?>
            </div>
        </details>

        <details class="activity-panel activity-panel--reading" id="activity-reading">
            <summary>
                <div class="activity-panel__lead">
                    <div class="activity-panel__label"><?= htmlspecialchars(t('activity.reading')) ?></div>
                    <div class="activity-panel__count"><?= (int)$n_reading ?></div>
                    <div class="activity-panel__hint"><?= htmlspecialchars(t('activity.expand_hint')) ?></div>
                </div>
                <span class="activity-chevron" aria-hidden="true"></span>
            </summary>
            <div class="activity-panel__body">
                <?php if ($n_reading === 0): ?>
                    <p class="activity-empty"><?= htmlspecialchars(t('activity.empty_reading')) ?></p>
                <?php else: ?>
                    <?php activity_render_book_grid($books_reading); ?>
                <?php endif; ?>
            </div>
        </details>

        <details class="activity-panel activity-panel--want" id="activity-want">
            <summary>
                <div class="activity-panel__lead">
                    <div class="activity-panel__label"><?= htmlspecialchars(t('activity.wishlist')) ?></div>
                    <div class="activity-panel__count"><?= (int)$n_want ?></div>
                    <div class="activity-panel__hint"><?= htmlspecialchars(t('activity.expand_hint')) ?></div>
                </div>
                <span class="activity-chevron" aria-hidden="true"></span>
            </summary>
            <div class="activity-panel__body">
                <?php if ($n_want === 0): ?>
                    <p class="activity-empty"><?= htmlspecialchars(t('activity.empty_wishlist')) ?></p>
                <?php else: ?>
                    <?php activity_render_book_grid($books_want); ?>
                <?php endif; ?>
            </div>
        </details>
    </div>

    <section class="activity-genres" aria-labelledby="activity-genres-heading">
        <h2 id="activity-genres-heading"><?= htmlspecialchars(t('activity.top_genres')) ?></h2>
        <?php if (empty($fav_categories)): ?>
            <p class="activity-genres__empty"><?= htmlspecialchars(t('activity.top_genres_empty')) ?></p>
        <?php else: ?>
            <div class="activity-genres__list">
                <?php foreach ($fav_categories as $cat): ?>
                    <?php
                    $max = (int)$fav_categories[0]['count'];
                    $cnt = (int)$cat['count'];
                    $percent = $max > 0 ? min(100, round(($cnt / $max) * 100)) : 0;
                    ?>
                    <div class="activity-genre-row">
                        <div class="activity-genre-row__head">
                            <span><?= htmlspecialchars(sskCategoryDisplayName(['name' => $cat['name'], 'name_i18n' => $cat['name_i18n'] ?? null])) ?></span>
                            <span><?= (int)$cnt ?></span>
                        </div>
                        <div class="activity-genre-bar">
                            <div class="activity-genre-bar__fill" style="width: <?= (int)$percent ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="activity-genres__glow" aria-hidden="true"></div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
