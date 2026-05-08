<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = currentUserId();
$limit = 20;
$offset = 0;

$stmt = $pdo->prepare("
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM books 
    LEFT JOIN categories ON books.category_id = categories.id 
    ORDER BY books.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$pageScripts = ['assets/js/pages/index.js'];

?>
<?php include 'includes/header.php'; ?>

<main class="container catalog-page">
    <header class="catalog-hero">
        <div class="page-title">
            <h1><?= htmlspecialchars(t('index.title')) ?></h1>
            <p><?= htmlspecialchars(t('index.subtitle')) ?></p>
        </div>
    </header>

    <section class="catalog-search-panel" aria-label="<?= htmlspecialchars(t('index.search_placeholder')) ?>">
        <div class="search-container catalog-search-field">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="search" id="searchInput" name="q" autocomplete="off" autocapitalize="off" spellcheck="false" enterkeyhint="search" placeholder="<?= htmlspecialchars(t('index.search_placeholder')) ?>">
        </div>
    </section>

    <div class="category-filters" id="categoryFilters">
        <button class="btn btn-primary cat-filter" data-category="all" style="border-radius: 99px;"><?= htmlspecialchars(t('index.all_books')) ?></button>
        <?php foreach($categories as $cat): ?>
            <button class="btn btn-outline cat-filter" data-category="<?= htmlspecialchars(sskCategorySlugForFilter($cat)) ?>" style="border-radius: 99px; font-size: 13px;">
                <?= htmlspecialchars(sskCategoryDisplayName($cat)) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php if (count($books) > 0): ?>
        <div class="grid" id="booksGrid" data-offset="<?= (int)count($books) ?>" data-limit="<?= (int)$limit ?>">
            <?php foreach ($books as $book): ?>
                <div class="card-item" 
                     data-title="<?= htmlspecialchars(strtolower($book['title'])) ?>" 
                     data-author="<?= htmlspecialchars(strtolower($book['author'])) ?>" 
                     data-category="<?= htmlspecialchars(!empty($book['category_id']) ? sskCategorySlugForFilter(['slug' => $book['category_slug'] ?? '', 'name' => $book['category_name'] ?? '']) : 'uncategorized') ?>">
                    <form method="POST" action="/library/book-details.php" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                        <input type="hidden" name="action" value="open_book">
                        <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                        <button type="submit" class="card-link">
                            <div class="card-image-wrap">
                                <?php if ($book['cover_url']): ?>
                                    <img src="<?= htmlspecialchars($book['cover_url']) ?>"
                                         alt="Cover"
                                         onerror="this.onerror=null; this.src='https://placehold.co/400x600/1a1a1a/ffffff?text=<?= urlencode($book['title']) ?>';">
                                <?php else: ?>
                                    <svg class="placeholder-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="card-content">
                                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-color); margin-bottom: 4px;">
                                    <?= htmlspecialchars(sskBookCategoryDisplay($book)) ?>
                                </div>
                                <h3 class="card-title"><?= htmlspecialchars($book['title']) ?></h3>
                                <p class="card-subtitle"><?= htmlspecialchars($book['author']) ?></p>
                            </div>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="dynamicNoResults" style="display:none; text-align:center; padding:64px 0; color:var(--muted-color);">
            <?= htmlspecialchars(t('index.no_results_filter')) ?>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 0; text-align: center; gap: 16px;">
            <div style="background: #f5f5f4; border-radius: 50%; padding: 24px; color: #a8a29e;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
            </div>
            <div>
                <h2 style="font-size: 24px; margin-bottom: 4px;"><?= htmlspecialchars(t('index.no_books_title')) ?></h2>
                <p style="color: var(--muted-color);"><?= htmlspecialchars(t('index.no_books_subtitle')) ?></p>
            </div>
            <?php if (isAdmin()): ?>
            <form method="POST" action="/library/book-form.php" style="margin-top: 16px; display: inline-block;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="action" value="prepare_create">
                <button type="submit" class="btn btn-primary" style="margin: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <?= htmlspecialchars(t('index.add_first_book')) ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (count($books) > 0): ?>
        <div style="display: flex; justify-content: center; margin: 40px 0 12px;">
            <button id="loadMoreBtn" type="button" class="btn btn-outline" style="padding: 12px 18px;">
                <?= htmlspecialchars(t('index.load_more')) ?>
            </button>
        </div>
        <div id="loadMoreSentinel" style="height: 1px;"></div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>