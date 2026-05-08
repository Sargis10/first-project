<?php
require_once __DIR__ . '/../includes/db.php';

if (!isLoggedIn()) {
    header("Location: /auth/login.php");
    exit;
}

if (!isAdmin()) {
    header("Location: /index.php");
    exit;
}

$user_id = currentUserId();
$book_id = $_GET['id'] ?? null;

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

// Fetch all categories for the dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Load existing book for editing
if ($book_id) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        die("Book not found or unauthorized.");
    }

    $title = $book['title'];
    $author = $book['author'];
    $description = $book['description'];
    $cover_url = $book['cover_url'];
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
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $isbn = trim($_POST['isbn'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publish_year = $_POST['publish_year'] ?? null;
    $language = trim($_POST['language'] ?? 'English');
    $page_count = $_POST['page_count'] ?? null;
    $author_bio = trim($_POST['author_bio'] ?? '');
    $format = trim($_POST['format'] ?? '');
    $edition = trim($_POST['edition'] ?? '');
    
    // Process File Upload
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['cover']['tmp_name'];
        $name = basename($_FILES['cover']['name']);
        
        // Ensure uploads dir
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
        
        $new_name = $user_id . '_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $name);
        $destination = $upload_dir . $new_name;
        
        if (move_uploaded_file($tmp_name, $destination)) {
            $cover_url = $destination;
        } else {
            $error = "Failed to upload image.";
        }
    }

    if (empty($title) || empty($author) || empty($category_id)) {
        $error = "Title, Author, and Category are required.";
    }

    if (!$error) {
        if ($book_id) {
            // Preservation logic: if no new cover was uploaded, keep the old one
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                // $cover_url already set by the upload logic above
            } else {
                // Fetch the existing cover_url to preserve it
                $stmt_fetch = $pdo->prepare("SELECT cover_url FROM books WHERE id = ?");
                $stmt_fetch->execute([$book_id]);
                $old_cover = $stmt_fetch->fetchColumn();
                $cover_url = $old_cover;
            }

            $stmt = $pdo->prepare("UPDATE books SET title=?, author=?, description=?, cover_url=?, category_id=?, isbn=?, publisher=?, publish_year=?, language=?, page_count=?, author_bio=?, format=?, edition=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$title, $author, $description, $cover_url, $category_id, $isbn, $publisher, $publish_year, $language, $page_count, $author_bio, $format, $edition, $book_id]);
            header("Location: /library/book-details.php?id=" . $book_id);
            exit;
        } else {
            $stmt = $pdo->prepare("INSERT INTO books (user_id, title, author, description, cover_url, category_id, isbn, publisher, publish_year, language, page_count, author_bio, format, edition) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $author, $description, $cover_url, $category_id, $isbn, $publisher, $publish_year, $language, $page_count, $author_bio, $format, $edition]);
            header("Location: /library/my-library.php");
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
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 40px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <label>Book Cover</label>
                <div style="aspect-ratio: 2/3; background: #e5e5e5; border-radius: 8px; border: 2px dashed #d6d3d1; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; cursor: pointer; max-width: 200px;">
                    <?php if ($cover_url): ?>
                        <img id="previewImage" src="<?= htmlspecialchars($cover_url) ?>" style="width:100%; height:100%; object-fit: cover;">
                    <?php else: ?>
                        <img id="previewImage" style="width:100%; height:100%; object-fit: cover; display:none;">
                    <?php endif; ?>
                    <div id="uploadPlaceholder" style="display: <?= $cover_url ? 'none' : 'flex' ?>; flex-direction: column; align-items: center; color: #a8a29e; position: absolute;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        <span style="font-size: 12px; font-weight: 500;">Upload</span>
                    </div>
                    <input type="file" name="cover" accept="image/*" style="opacity: 0; position: absolute; inset: 0; cursor: pointer;" onchange="previewFile()">
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
                    <select name="category_id" class="form-input" required>
                        <option value="">Select a Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
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
