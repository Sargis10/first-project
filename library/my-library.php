<?php
require_once __DIR__ . '/../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ' . sskUrl('sign_in'));
    exit;
}

$user_id = currentUserId();

// 1. Fetch Quick Stats
$stats_stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'reading' THEN 1 ELSE 0 END) as reading_count,
        SUM(CASE WHEN is_favorite = 1 THEN 1 ELSE 0 END) as fav_count
    FROM user_books 
    WHERE user_id = ?
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// 2. Fetch Different Sections
// FAVORITES
$fav_stmt = $pdo->prepare("
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM user_books 
    JOIN books ON user_books.book_id = books.id 
    LEFT JOIN categories ON books.category_id = categories.id
    WHERE user_books.user_id = ? AND user_books.is_favorite = 1
");
$fav_stmt->execute([$user_id]);
$favorites = $fav_stmt->fetchAll(PDO::FETCH_ASSOC);

// CURRENTLY READING
$reading_stmt = $pdo->prepare("
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM user_books 
    JOIN books ON user_books.book_id = books.id 
    LEFT JOIN categories ON books.category_id = categories.id
    WHERE user_books.user_id = ? AND user_books.status = 'reading'
");
$reading_stmt->execute([$user_id]);
$reading = $reading_stmt->fetchAll(PDO::FETCH_ASSOC);

// WANT TO READ
$want_stmt = $pdo->prepare("
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM user_books 
    JOIN books ON user_books.book_id = books.id 
    LEFT JOIN categories ON books.category_id = categories.id
    WHERE user_books.user_id = ? AND user_books.status = 'want_to_read'
");
$want_stmt->execute([$user_id]);
$want_to_read = $want_stmt->fetchAll(PDO::FETCH_ASSOC);

// COMPLETED (READ)
$completed_stmt = $pdo->prepare("
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM user_books 
    JOIN books ON user_books.book_id = books.id 
    LEFT JOIN categories ON books.category_id = categories.id
    WHERE user_books.user_id = ? AND user_books.status = 'read'
");
$completed_stmt->execute([$user_id]);
$completed = $completed_stmt->fetchAll(PDO::FETCH_ASSOC);

// YOUR UPLOADS
$uploads_stmt = $pdo->prepare("
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM books 
    LEFT JOIN categories ON books.category_id = categories.id
    WHERE books.user_id = ? 
    ORDER BY books.created_at DESC
");
$uploads_stmt->execute([$user_id]);
$my_uploads = $uploads_stmt->fetchAll(PDO::FETCH_ASSOC);
$pageStyles = ['assets/css/pages/user-dashboard.css'];
$pageScripts = ['assets/js/pages/user-dashboard.js'];

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="padding: 60px 24px;">
    
    <header style="margin-bottom: 56px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 42px; font-family: var(--font-serif); margin-bottom: 8px;">My Library</h1>
            <p style="color: var(--muted-color); font-size: 18px;">Your personal universe of curated stories.</p>
        </div>
        <form method="POST" action="<?= htmlspecialchars(sskUrl('write')) ?>" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="prepare_create">
            <button type="submit" class="btn btn-primary" style="padding: 14px 28px; border-radius: 12px; box-shadow: 0 10px 20px -5px rgba(90, 90, 64, 0.3);">
                <svg style="margin-right: 8px;" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add New Book
            </button>
        </form>
    </header>

    <div class="library-tabs" id="libraryTabs">
        <button class="tab-btn active" data-target="all">All Uploads (<?= count($my_uploads) ?>)</button>
        <button class="tab-btn" data-target="favorites">❤ Favorites (<?= $stats['fav_count'] ?: 0 ?>)</button>
        <button class="tab-btn" data-target="reading">Reading (<?= $stats['reading_count'] ?: 0 ?>)</button>
        <button class="tab-btn" data-target="want">Want to Read (<?= count($want_to_read) ?>)</button>
        <button class="tab-btn" data-target="read">Completed (<?= $stats['read_count'] ?: 0 ?>)</button>
    </div>

    <!-- TAB CONTENTS -->
    <div id="tabContents">
        <!-- ALL UPLOADS -->
        <div class="tab-content active" id="all">
            <?php renderBookGrid($my_uploads, "You haven't uploaded any books yet."); ?>
        </div>

        <!-- FAVORITES -->
        <div class="tab-content" id="favorites">
            <?php renderBookGrid($favorites, "No favorites yet. Click the heart on a book to add it here!"); ?>
        </div>

        <!-- READING -->
        <div class="tab-content" id="reading">
            <?php renderBookGrid($reading, "Nothing in progress. What's your next adventure?"); ?>
        </div>

        <!-- WANT TO READ -->
        <div class="tab-content" id="want">
            <?php renderBookGrid($want_to_read, "Your wishlist is empty. Explore the catalog!"); ?>
        </div>

        <!-- READ -->
        <div class="tab-content" id="read">
            <?php renderBookGrid($completed, "You haven't finished any books yet. Keep reading!"); ?>
        </div>
    </div>

</main>

<?php
function renderBookGrid($books, $emptyMsg) {
    if (empty($books)) {
        echo '
        <div style="padding: 100px 40px; background: #fafafa; border-radius: 24px; border: 2px dashed #e5e7eb; text-align: center;">
            <p style="color: var(--muted-color); font-size: 16px;">' . $emptyMsg . '</p>
        </div>';
    } else {
        echo '<div class="book-grid">';
        foreach ($books as $b) {
            $myPh = 'https://placehold.co/400x600/1a1a1a/ffffff?text=' . rawurlencode((string)($b['title'] ?? ''));
            $myCover = sskSafePublicCoverPath($b['cover_url'] ?? null);
            $myImgSrc = $myCover !== '' ? $myCover : $myPh;
            $myImgPhAttr = $myCover !== '' ? ' data-ssk-placeholder="' . htmlspecialchars($myPh, ENT_QUOTES, 'UTF-8') . '"' : '';
            echo '
            <div class="book-card" style="position: relative;">
                <form method="POST" action="' . htmlspecialchars(sskUrl('read'), ENT_QUOTES, 'UTF-8') . '" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">
                    <input type="hidden" name="action" value="open_book">
                    <input type="hidden" name="book_id" value="' . (int)$b['id'] . '">
                    <button type="submit" style="all: unset; cursor: pointer; display: block; width: 100%; text-align: left; color: inherit;">
                    <div style="aspect-ratio: 2/3; border-radius: 16px; overflow: hidden; background: #f1f5f9; box-shadow: 0 15px 35px -12px rgba(0,0,0,0.15); transition: transform 0.3s; margin-bottom: 20px;">
                        <img src="' . htmlspecialchars($myImgSrc) . '" 
                             loading="lazy"
                             decoding="async"
                             fetchpriority="low"
                             style="width: 100%; height: 100%; object-fit: cover;"' . $myImgPhAttr . '>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--accent-color); margin-bottom: 6px;">' . htmlspecialchars(sskBookCategoryDisplay($b)) . '</div>
                        <h4 style="font-size: 17px; font-weight: 700; margin-bottom: 4px; line-height: 1.3;">' . htmlspecialchars($b['title']) . '</h4>
                        <p style="font-size: 14px; color: var(--muted-color);">' . htmlspecialchars($b['author']) . '</p>
                    </div>
                    </button>
                </form>
            </div>';
        }
        echo '</div>';
    }
}
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
