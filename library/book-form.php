<?php
require_once __DIR__ . '/../includes/db.php';

if (!isLoggedIn()) {
    header('Location: ' . sskUrl('sign_in'));
    exit;
}

if (!isAdmin()) {
    header('Location: ' . sskUrl('home'));
    exit;
}

$user_id = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $routeAction = $_POST['action'] ?? '';
    if ($routeAction === 'prepare_create') {
        verifyCsrfTokenOrFail();
        unset($_SESSION['ssk_edit_book_id']);
        header('Location: ' . sskUrl('write'));
        exit;
    }
    if ($routeAction === 'prepare_edit') {
        verifyCsrfTokenOrFail();
        $bid = (int)($_POST['book_id'] ?? 0);
        if ($bid > 0) {
            $stmt = $pdo->prepare("SELECT id FROM books WHERE id = ? AND user_id = ?");
            $stmt->execute([$bid, $user_id]);
            if ($stmt->fetch()) {
                $_SESSION['ssk_edit_book_id'] = $bid;
            }
        }
        header('Location: ' . sskUrl('write'));
        exit;
    }
}

$book_id = $_SESSION['ssk_edit_book_id'] ?? null;

$title = '';
$author = '';
$description = '';
$cover_url = '';
$category_id = '';
$isbn = '';
$publisher = '';
$publish_year = '';
$language = 'English';
$page_count = '';
$author_bio = '';
$format = '';
$edition = '';
$error = '';
$pageScripts = ['assets/js/pages/book-form.js'];

// Fetch all categories for the dropdown (same source as catalog filters; labels follow current UI language)
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
// List up to 6 category rows plus the placeholder option (native pick list, no UX regression on small catalogs).
$categorySelectSize = count($categories) > 0 ? min(7, count($categories) + 1) : 1;

// Load existing book for editing (owner only — matches book-details edit/delete rules)
if ($book_id) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND user_id = ?");
    $stmt->execute([$book_id, $user_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        unset($_SESSION['ssk_edit_book_id']);
        header('Location: ' . sskUrl('write'));
        exit;
    }

    $title = $book['title'];
    $author = $book['author'];
    $description = $book['description'];
    $cover_url = sskNormalizeStoredCoverUrl($book['cover_url'] ?? null);
    $category_id = $book['category_id'];
    $isbn = $book['isbn'] ?? '';
    $publisher = $book['publisher'] ?? '';
    $publish_year = $book['publish_year'] ?? '';
    $language = $book['language'] ?? 'English';
    $page_count = $book['page_count'] ?? '';
    $author_bio = $book['author_bio'] ?? '';
    $format = $book['format'] ?? '';
    $edition = $book['edition'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfTokenOrFail();
    if (($_POST['action'] ?? '') !== '') {
        header('Location: ' . sskUrl('write'));
        exit;
    }
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publishYearRaw = $_POST['publish_year'] ?? null;
    $publish_year = ($publishYearRaw === '' || $publishYearRaw === null)
        ? null
        : (filter_var($publishYearRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== false
            ? (int)$publishYearRaw
            : null);

    $language = trim($_POST['language'] ?? 'English');

    $pageCountRaw = $_POST['page_count'] ?? null;
    $page_count = ($pageCountRaw === '' || $pageCountRaw === null)
        ? null
        : (filter_var($pageCountRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== false
            ? (int)$pageCountRaw
            : null);
    $author_bio = trim($_POST['author_bio'] ?? '');
    $format = trim($_POST['format'] ?? '');
    $edition = trim($_POST['edition'] ?? '');
    
    // Process File Upload (type + size validated; extension never taken from user filename)
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $maxBytes = 5 * 1024 * 1024;
        if ((int)$_FILES['cover']['size'] > $maxBytes) {
            $error = 'Cover image is too large (max 5 MB).';
        } else {
            $tmp_name = $_FILES['cover']['tmp_name'];
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp_name);
            $mimeToExt = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];
            if (!isset($mimeToExt[$mime])) {
                $error = 'Cover must be a JPEG, PNG, WebP, or GIF image.';
            } elseif (!is_uploaded_file($tmp_name)) {
                $error = 'Invalid upload.';
            } else {
                $safeBase = $user_id . '_' . time() . '_' . bin2hex(random_bytes(4));
                $destination = $upload_dir . $safeBase . '.' . $mimeToExt[$mime];
                if (move_uploaded_file($tmp_name, $destination)) {
                    $cover_url = $destination;
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }
    }

    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    if (empty($title) || empty($author) || $category_id <= 0) {
        $error = "Title, Author, and Category are required.";
    } else {
        $catCheck = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
        $catCheck->execute([$category_id]);
        if (!$catCheck->fetchColumn()) {
            $error = 'Invalid category selected.';
        }
    }

    if (!$error) {
        if ($book_id) {
            // Preservation logic: if no new cover was uploaded, keep the old one
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                // $cover_url already set by the upload logic above
            } else {
                // Fetch the existing cover_url to preserve it
                $stmt_fetch = $pdo->prepare("SELECT cover_url FROM books WHERE id = ? AND user_id = ?");
                $stmt_fetch->execute([$book_id, $user_id]);
                $old_cover = $stmt_fetch->fetchColumn();
                $cover_url = sskNormalizeStoredCoverUrl($old_cover !== false ? (string)$old_cover : null);
            }

            $stmt = $pdo->prepare("UPDATE books SET title=?, author=?, description=?, cover_url=?, category_id=?, isbn=?, publisher=?, publish_year=?, language=?, page_count=?, author_bio=?, format=?, edition=?, updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
            $stmt->execute([$title, $author, $description, $cover_url, $category_id, $isbn, $publisher, $publish_year, $language, $page_count, $author_bio, $format, $edition, $book_id, $user_id]);
            // Note: MySQL may report 0 rows "changed" when values are identical; WHERE still enforced owner id.
            unset($_SESSION['ssk_edit_book_id']);
            $_SESSION['ssk_view_book_id'] = (int)$book_id;
            header('Location: ' . sskUrl('read'));
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO books (user_id, title, author, description, cover_url, category_id, isbn, publisher, publish_year, language, page_count, author_bio, format, edition) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $author, $description, $cover_url, $category_id, $isbn, $publisher, $publish_year, $language, $page_count, $author_bio, $format, $edition]);
            $newId = (int)$pdo->lastInsertId();
            unset($_SESSION['ssk_edit_book_id']);
            $_SESSION['ssk_view_book_id'] = $newId;
            header('Location: ' . sskUrl('read'));
            exit;
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="max-width: 800px;">
    <div style="margin-bottom: 32px;">
        <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:8px;color:var(--muted-color);text-decoration:none;font-size:14px;font-weight:500;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back
        </a>
    </div>

    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 36px; margin-bottom: 8px;"><?= $book_id ? 'Edit Book' : 'Add New Book' ?></h1>
        <p style="color:var(--muted-color);">Fill in the details to update your library.</p>
    </div>

    <?php if ($error): ?>
        <p class="error-text" style="margin-bottom: 24px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr; gap: 40px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 40px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <label>Book Cover</label>
                <div style="aspect-ratio: 2/3; background: #e5e5e5; border-radius: 8px; border: 2px dashed #d6d3d1; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; cursor: pointer; max-width: 200px;">
                    <?php if ($cover_url): ?>
                        <img id="previewImage" src="<?= htmlspecialchars(sskPublicCoverImgSrc($cover_url)) ?>" style="width:100%; height:100%; object-fit: cover;">
                    <?php else: ?>
                        <img id="previewImage" style="width:100%; height:100%; object-fit: cover; display:none;">
                    <?php endif; ?>
                    <div id="uploadPlaceholder" style="display: <?= $cover_url ? 'none' : 'flex' ?>; flex-direction: column; align-items: center; color: #a8a29e; position: absolute;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span style="font-size: 12px; font-weight: 500;">Upload</span>
                    </div>
                    <input type="file" name="cover" class="book-cover-input" accept="image/*" style="opacity: 0; position: absolute; inset: 0; cursor: pointer;">
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($title) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" class="form-input" value="<?= htmlspecialchars($author) ?>" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="bookCategorySelect" class="form-input book-category-select" required size="<?= (int)$categorySelectSize ?>">
                        <option value="">Select a Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= (string)$category_id === (string)$cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(sskCategoryDisplayName($cat)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>ISBN</label>
                        <input type="text" name="isbn" class="form-input" value="<?= htmlspecialchars($isbn) ?>" placeholder="e.g. 978-3-16-148410-0">
                    </div>
                    <div class="form-group">
                        <label>Publisher</label>
                        <input type="text" name="publisher" class="form-input" value="<?= htmlspecialchars($publisher) ?>" placeholder="e.g. Penguin Books">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>Publish Year</label>
                        <input type="number" name="publish_year" class="form-input" value="<?= htmlspecialchars($publish_year) ?>" placeholder="2024">
                    </div>
                    <div class="form-group">
                        <label>Language</label>
                        <input type="text" name="language" class="form-input" value="<?= htmlspecialchars($language) ?>">
                    </div>
                    <div class="form-group">
                        <label>Page Count</label>
                        <input type="number" name="page_count" class="form-input" value="<?= htmlspecialchars($page_count) ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>Book Format</label>
                        <input type="text" name="format" class="form-input" value="<?= htmlspecialchars($format) ?>" placeholder="e.g. Hardcover, Paperback">
                    </div>
                    <div class="form-group">
                        <label>Edition</label>
                        <input type="text" name="edition" class="form-input" value="<?= htmlspecialchars($edition) ?>" placeholder="e.g. 1st Edition, Anniversary Edition">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-textarea" placeholder="Tell us about this book..."><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label>About the Author</label>
                    <textarea name="author_bio" class="form-textarea" placeholder="Biography of the author..."><?= htmlspecialchars($author_bio) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 16px; padding: 14px;">
                    <?= $book_id ? 'Save Changes' : 'Add to Library' ?>
                </button>
            </div>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
