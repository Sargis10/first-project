<?php
require_once __DIR__ . '/../includes/db.php';

if (!isAdmin()) {
    header("Location: /index.php");
    exit;
}

$error = '';
$success = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfTokenOrFail();
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $error = "Category name is required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);
                $success = "Category '$name' added successfully!";
            } catch (PDOException $e) {
                $error = "Category already exists or database error.";
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $cat_id = $_POST['id'] ?? null;
        // Check if category is used
        $check = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
        $check->execute([$cat_id]);
        if ($check->fetchColumn() > 0) {
            $error = "Cannot delete: Some books are still using this category.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$cat_id]);
            $success = "Category deleted successfully!";
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="max-width: 800px; padding: 40px 24px;">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 32px; font-family: var(--font-serif); margin-bottom: 8px;">Manage Categories</h1>
        <p style="color: var(--muted-color);">Add or remove book categories for the library.</p>
    </div>

    <!-- ADD CATEGORY FORM -->
    <div style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); margin-bottom: 40px;">
        <form method="POST" style="display: flex; gap: 12px; align-items: flex-end;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="add">
            <div style="flex: 1;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px;">New Category Name</label>
                <input type="text" name="name" class="form-input" placeholder="e.g. Mystery, Fantasy" required>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 48px;">Add Category</button>
        </form>
        
        <?php if($error): ?>
            <div style="margin-top: 16px; padding: 12px; background: #fee2e2; color: #b91c1c; border-radius: 8px; font-size: 14px;"> <?= $error ?> </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div style="margin-top: 16px; padding: 12px; background: #ecfdf5; color: #047857; border-radius: 8px; font-size: 14px;"> <?= $success ?> </div>
        <?php endif; ?>
    </div>

    <!-- LIST CATEGORIES -->
    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.05); background: #fafafa; color: var(--muted-color); font-weight: 500;">
                    <th style="padding: 16px 24px;">Name</th>
                    <th style="padding: 16px 24px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($categories as $cat): ?>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <td style="padding: 16px 24px; font-weight: 500;"><?= htmlspecialchars($cat['name']) ?></td>
                    <td style="padding: 16px 24px; text-align: right;">
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-danger" style="padding: 6px 12px; font-size: 12px;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
