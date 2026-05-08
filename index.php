<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = currentUserId();
$stmt = $pdo->query("
    SELECT books.*, categories.name as category_name 
    FROM books 
    LEFT JOIN categories ON books.category_id = categories.id 
    ORDER BY created_at DESC
");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$pageScripts = ['assets/js/pages/index.js'];

?>
<?php include 'includes/header.php'; ?>

<main class="container">
    <div class="page-header">
        <div class="page-title">
            <h1>My Library</h1>
            <p>Explore the full library catalog.</p>
        </div>
        
        <div class="search-container">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" id="searchInput" placeholder="Search books...">
        </div>
    </div>

    <!-- CATEGORY FILTERS -->
    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 32px;" id="categoryFilters">
        <button class="btn btn-primary cat-filter" data-category="all" style="border-radius: 99px;">All Books</button>
        <?php foreach($categories as $cat): ?>
            <button class="btn btn-outline cat-filter" data-category="<?= htmlspecialchars(strtolower($cat['name'])) ?>" style="border-radius: 99px; font-size: 13px;">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php if (count($books) > 0): ?>
        <div class="grid" id="booksGrid">
            <?php foreach ($books as $book): ?>
                <div class="card-item" 
                     data-title="<?= htmlspecialchars(strtolower($book['title'])) ?>" 
                     data-author="<?= htmlspecialchars(strtolower($book['author'])) ?>" 
                     data-category="<?= htmlspecialchars(strtolower($book['category_name'] ?? 'uncategorized')) ?>">
                    <a href="/library/book-details.php?id=<?= $book['id'] ?>" class="card-link">
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
                                <?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?>
                            </div>
                            <h3 class="card-title"><?= htmlspecialchars($book['title']) ?></h3>
                            <p class="card-subtitle"><?= htmlspecialchars($book['author']) ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 0; text-align: center; gap: 16px;">
            <div style="background: #f5f5f4; border-radius: 50%; padding: 24px; color: #a8a29e;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
            </div>
            <div>
                <h2 style="font-size: 24px; margin-bottom: 4px;">No books found</h2>
                <p style="color: var(--muted-color);">Start your collection by adding your first book.</p>
            </div>
            <?php if (isAdmin()): ?>
            <a href="/library/book-form.php" class="btn btn-primary" style="margin-top: 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add First Book
            </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>