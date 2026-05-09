<?php
require_once __DIR__ . '/../includes/db.php';

if (!isAdmin()) {
    header('Location: ' . sskUrl('home'));
    exit;
}

$error = '';
$success = '';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId > 0) {
    $es = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $es->execute([$editId]);
    $editRow = $es->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$editRow) {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfTokenOrFail();
    $action = (string)$_POST['action'];

    if ($action === 'add' || $action === 'update') {
        $names = sskCategoryNamesFromPost($_POST['name_i18n'] ?? []);
        $primary = sskCategoryPrimaryName($names);
        if ($primary === '') {
            $error = t('admin.categories.require_one');
        } else {
            $nameCol = ($names['en'] ?? '') !== '' ? $names['en'] : $primary;
            $slugInput = trim((string)($_POST['slug'] ?? ''));
            $desiredSlug = $slugInput !== ''
                ? sskSlugifyCategory($slugInput)
                : sskSlugifyCategory(($names['en'] ?? '') !== '' ? $names['en'] : $primary);
            if ($desiredSlug === '') {
                $error = t('admin.categories.slug_required');
            } else {
                $exceptId = $action === 'update' ? (int)($_POST['id'] ?? 0) : null;
                if ($action === 'update' && $exceptId <= 0) {
                    $error = 'Invalid category.';
                } else {
                    $slug = sskEnsureUniqueCategorySlug($pdo, $desiredSlug, $exceptId);
                    if ($slugInput !== '' && $slug !== $desiredSlug) {
                        $error = t('admin.categories.slug_in_use');
                    }
                }
                if ($error === '') {
                    $json = sskCategoryNamesJson($names);
                    try {
                        if ($action === 'add') {
                            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, name_i18n) VALUES (?, ?, ?)');
                            $stmt->execute([$nameCol, $slug, $json]);
                            $success = t('admin.categories.added');
                        } else {
                            $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, name_i18n = ? WHERE id = ?');
                            $stmt->execute([$nameCol, $slug, $json, $exceptId]);
                            $success = t('admin.categories.updated');
                            $editId = 0;
                            $editRow = null;
                        }
                    } catch (PDOException $e) {
                        $msg = $e->getMessage();
                        if (str_contains($msg, 'Duplicate') && str_contains($msg, 'name')) {
                            $error = t('admin.categories.name_english_unique');
                        } elseif (str_contains($msg, 'Duplicate') && str_contains($msg, 'slug')) {
                            $error = t('admin.categories.slug_in_use');
                        } else {
                            $error = $msg;
                        }
                    }
                }
            }
        }
    }

    if ($action === 'delete') {
        $cat_id = (int)($_POST['id'] ?? 0);
        $check = $pdo->prepare('SELECT COUNT(*) FROM books WHERE category_id = ?');
        $check->execute([$cat_id]);
        if ($check->fetchColumn() > 0) {
            $error = 'Cannot delete: Some books are still using this category.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$cat_id]);
            $success = 'Category deleted successfully!';
        }
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
$langMeta = sskLanguageMeta();
$editNames = $editRow ? sskCategoryNamesDecode($editRow['name_i18n'] ?? null) : [];

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="max-width: 920px; padding: 40px 24px;">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 32px; font-family: var(--font-serif); margin-bottom: 8px;"><?= htmlspecialchars(t('admin.categories.title')) ?></h1>
        <p style="color: var(--muted-color);"><?= htmlspecialchars(t('admin.categories.subtitle')) ?></p>
    </div>

    <?php if ($error): ?>
        <div style="margin-bottom: 24px; padding: 12px 16px; background: #fee2e2; color: #b91c1c; border-radius: 8px; font-size: 14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="margin-bottom: 24px; padding: 12px 16px; background: #ecfdf5; color: #047857; border-radius: 8px; font-size: 14px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); margin-bottom: 40px;">
        <h2 style="font-size: 18px; font-family: var(--font-serif); margin-bottom: 8px;"><?= htmlspecialchars($editRow ? t('admin.categories.edit_title') : t('admin.categories.add_title')) ?></h2>
        <p style="color: var(--muted-color); font-size: 14px; margin-bottom: 24px;"><?= htmlspecialchars(t('admin.categories.names_hint')) ?></p>

        <form method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'add' ?>">
            <?php if ($editRow): ?>
                <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
            <?php endif; ?>

            <?php foreach (SSK_LANGUAGES as $code): ?>
                <?php $lm = $langMeta[$code] ?? ['flag' => '', 'label' => $code]; ?>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px;">
                        <?= $lm['flag'] ?> <?= htmlspecialchars($lm['label']) ?>
                    </label>
                    <input type="text" name="name_i18n[<?= htmlspecialchars($code) ?>]" class="form-input"
                           value="<?= htmlspecialchars($editNames[$code] ?? '') ?>"
                           placeholder="<?= htmlspecialchars($lm['label']) ?>">
                </div>
            <?php endforeach; ?>

            <div>
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px;"><?= htmlspecialchars(t('admin.categories.slug_label')) ?></label>
                <input type="text" name="slug" class="form-input"
                       value="<?= htmlspecialchars($editRow['slug'] ?? '') ?>"
                       placeholder="e.g. science-fiction">
                <p style="font-size: 12px; color: var(--muted-color); margin-top: 8px;"><?= htmlspecialchars(t('admin.categories.slug_hint')) ?></p>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($editRow ? t('admin.categories.save') : t('admin.categories.add_btn')) ?></button>
                <?php if ($editRow): ?>
                    <a href="<?= htmlspecialchars(sskUrl('manage_topics')) ?>" class="btn btn-outline"><?= htmlspecialchars(t('admin.categories.cancel')) ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05);">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.05); background: #fafafa; color: var(--muted-color); font-weight: 500;">
                    <th style="padding: 16px 24px;"><?= htmlspecialchars(t('admin.categories.col_category')) ?></th>
                    <th style="padding: 16px 24px;"><?= htmlspecialchars(t('admin.categories.col_slug')) ?></th>
                    <th style="padding: 16px 24px; text-align: right;"><?= htmlspecialchars(t('admin.categories.col_actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <td style="padding: 16px 24px; font-weight: 500;"><?= htmlspecialchars(sskCategoryDisplayName($cat)) ?></td>
                    <td style="padding: 16px 24px; color: var(--muted-color); font-family: ui-monospace, monospace; font-size: 13px;"><?= htmlspecialchars($cat['slug'] ?? '') ?></td>
                    <td style="padding: 16px 24px; text-align: right; white-space: nowrap;">
                        <a href="<?= htmlspecialchars(sskUrl('manage_topics', ['edit' => (int)$cat['id']])) ?>" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px;"><?= htmlspecialchars(t('admin.categories.edit_btn')) ?></a>
                        <form method="POST" style="margin: 0; display: inline;" onsubmit="return confirm('<?= htmlspecialchars(t('admin.categories.delete_confirm')) ?>');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-danger" style="padding: 6px 12px; font-size: 12px;"><?= htmlspecialchars(t('admin.categories.delete_btn')) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
