<?php
require_once __DIR__ . '/../includes/db.php';

if (!isLoggedIn()) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = currentUserId();
$book_id = $_GET['id'] ?? null;

if (!$book_id) {
    header("Location: /index.php");
    exit;
}

// Fetch the book
$stmt = $pdo->prepare("
    SELECT books.*, categories.name as category_name 
    FROM books 
    LEFT JOIN categories ON books.category_id = categories.id 
    WHERE books.id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    die("Book not found or unauthorized to view this book.");
}

// Fetch user status for this book
$status_stmt = $pdo->prepare("SELECT status, is_favorite FROM user_books WHERE user_id = ? AND book_id = ?");
$status_stmt->execute([$user_id, $book_id]);
$row = $status_stmt->fetch(PDO::FETCH_ASSOC);
$current_status = $row['status'] ?? 'none';
$is_favorite = $row['is_favorite'] ?? 0;
$pageStyles = ['assets/css/pages/book-details.css'];

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'] ?? 'none';
        if ($new_status === 'none') {
            $pdo->prepare("DELETE FROM user_books WHERE user_id = ? AND book_id = ?")->execute([$user_id, $book_id]);
        } else {
            $pdo->prepare("INSERT INTO user_books (user_id, book_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?")->execute([$user_id, $book_id, $new_status, $new_status]);
        }
    }
    
    if ($_POST['action'] === 'toggle_favorite') {
        $new_fav = $is_favorite ? 0 : 1;
        $pdo->prepare("INSERT INTO user_books (user_id, book_id, is_favorite) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_favorite = ?")->execute([$user_id, $book_id, $new_fav, $new_fav]);
    }
    
    header("Location: /library/book-details.php?id=" . $book_id);
    exit;
}

// Handle Demetion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ? AND user_id = ?");
    $stmt->execute([$book_id, $user_id]);
    
    if ($book['cover_url'] && file_exists($book['cover_url'])) {
        unlink($book['cover_url']); // Delete the image file
    }

    header("Location: /index.php");
    exit;
}

// Simple markdown parse
function simpleMarkdown($text) {
    $text = htmlspecialchars($text);
    $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
    $text = nl2br($text);
    return $text;
}

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="max-width: 1024px; padding: 40px 24px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <a href="/index.php" style="display:inline-flex;align-items:center;gap:8px;color:var(--muted-color);text-decoration:none;font-size:14px;font-weight:500;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back
        </a>
        
        <div style="display: flex; gap: 8px;">
            <?php if (isAdmin() && $book['user_id'] == $user_id): ?>
            <a href="/library/book-form.php?id=<?= $book['id'] ?>" class="btn btn-ghost" style="padding: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="16 3 21 8 8 21 3 21 3 16 16 3"></polygon></svg>
            </a>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this book?');" style="margin: 0;">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-ghost btn-danger" style="padding: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 48px;">
        <div class="book-details-grid">
            
            <div style="aspect-ratio: 2/3; border-radius: 12px; overflow: hidden; background: #e5e5e5; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center;">
                <?php if ($book['cover_url']): ?>
                    <img src="<?= htmlspecialchars($book['cover_url']) ?>" 
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://placehold.co/400x600/1a1a1a/ffffff?text=<?= urlencode($book['title']) ?>';">
                <?php else: ?>
                    <svg style="color: #a8a29e;" xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                <?php endif; ?>
            </div>

            <div style="display: flex; flex-direction: column; gap: 32px;">
                <div>
                    <div style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent-color); margin-bottom: 12px;">
                        <?= htmlspecialchars($book['category_name'] ?? 'Uncategorized') ?>
                    </div>
                    <h1 style="font-size: 48px; font-family: var(--font-serif); line-height: 1.1; margin-bottom: 8px;"><?= htmlspecialchars($book['title']) ?></h1>
                    <p style="font-size: 24px; color: var(--muted-color); font-style: italic;">by <?= htmlspecialchars($book['author']) ?></p>
                </div>

                <div style="display: flex; align-items: center; gap: 16px;">
                    <form method="POST" style="margin: 0; flex: 1;">
                        <input type="hidden" name="action" value="update_status">
                        <select name="status" onchange="this.form.submit()" class="form-input" style="padding: 10px 16px; font-weight: 600; cursor: pointer; border: 1px solid var(--accent-color); color: var(--accent-color); background: white; border-radius: 8px;">
                            <option value="none" <?= $current_status == 'none' ? 'selected' : '' ?>>+ Add to My Reading List</option>
                            <option value="want_to_read" <?= $current_status == 'want_to_read' ? 'selected' : '' ?>>Want to Read</option>
                            <option value="reading" <?= $current_status == 'reading' ? 'selected' : '' ?>>Reading</option>
                            <option value="read" <?= $current_status == 'read' ? 'selected' : '' ?>>Read</option>
                        </select>
                    </form>
                    
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="toggle_favorite">
                        <button type="submit" style="background: none; border: none; cursor: pointer; padding: 8px; color: <?= $is_favorite ? '#e11d48' : '#cbd5e1' ?>; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="<?= $is_favorite ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>
                    </form>
                </div>

                <div style="height: 1px; background: rgba(26,26,26,0.1); width: 100%;"></div>

                <div>
                    <h3 style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted-color); margin-bottom: 16px;">Description</h3>
                    <div style="font-size: 16px; color: #4b5563; line-height: 1.6;">
                        <?= $book['description'] ? simpleMarkdown($book['description']) : 'No description provided.' ?>
                    </div>
                </div>

                <div style="height: 1px; background: rgba(26,26,26,0.1); width: 100%;"></div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 24px;">
                    <div>
                        <h4 style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted-color); margin-bottom: 4px;">ISBN</h4>
                        <p style="font-size: 14px; color: var(--ink-color);"><?= htmlspecialchars($book['isbn'] ?: 'N/A') ?></p>
                    </div>
                    <div>
                        <h4 style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted-color); margin-bottom: 4px;">Publisher</h4>
                        <p style="font-size: 14px; color: var(--ink-color);"><?= htmlspecialchars($book['publisher'] ?: 'Unknown') ?></p>
                    </div>
                    <div>
                        <h4 style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted-color); margin-bottom: 4px;">Language</h4>
                        <p style="font-size: 14px; color: var(--ink-color);"><?= htmlspecialchars($book['language'] ?: 'English') ?></p>
                    </div>
                    <div>
                        <h4 style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted-color); margin-bottom: 4px;">Pages</h4>
                        <p style="font-size: 14px; color: var(--ink-color);"><?= htmlspecialchars($book['page_count'] ?: 'N/A') ?></p>
                    </div>
                    <div>
                        <h4 style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted-color); margin-bottom: 4px;">Year</h4>
                        <p style="font-size: 14px; color: var(--ink-color);"><?= htmlspecialchars($book['publish_year'] ?: 'N/A') ?></p>
                    </div>
                    <div>
                        <h4 style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted-color); margin-bottom: 4px;">Format</h4>
                        <p style="font-size: 14px; color: var(--ink-color);"><?= htmlspecialchars($book['format'] ?: 'N/A') ?></p>
                    </div>
                    <div>
                        <h4 style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--muted-color); margin-bottom: 4px;">Edition</h4>
                        <p style="font-size: 14px; color: var(--ink-color);"><?= htmlspecialchars($book['edition'] ?: 'Standard') ?></p>
                    </div>
                </div>

                <?php if ($book['author_bio']): ?>
                    <div style="height: 1px; background: rgba(26,26,26,0.1); width: 100%;"></div>
                    <div>
                        <h3 style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted-color); margin-bottom: 16px;">About the Author</h3>
                        <div style="font-size: 15px; color: #4b5563; line-height: 1.6; font-style: italic;">
                            <?= simpleMarkdown($book['author_bio']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; color: var(--muted-color); margin-top: auto; padding-top: 40px;">
                    <span>Added <?= date('M j, Y', strtotime($book['created_at'])) ?></span>
                    <span>•</span>
                    <span>Updated <?= date('M j, Y', strtotime($book['updated_at'])) ?></span>
                </div>
            </div>

        </div>

    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
