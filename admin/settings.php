<?php
require_once __DIR__ . '/../includes/db.php';

if (!isAdmin()) {
    header("Location: /index.php");
    exit;
}

$success = '';
$error = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $pdo->commit();
        $success = "Site settings updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Update failed: " . $e->getMessage();
    }
}

// Fetch current
$settings = $pdo->query("SELECT * FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="max-width: 800px; padding: 40px 24px;">
    <header style="margin-bottom: 40px; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 32px; font-family: var(--font-serif);">Site Settings</h1>
            <p style="color: var(--muted-color);">Configure the information displayed on the About Us page.</p>
        </div>
        <a href="/library/about.php" class="btn btn-outline" target="_blank">View Live Page →</a>
    </header>

    <?php if($success): ?>
        <div style="background: #ecfdf5; color: #047857; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500;"> <?= $success ?> </div>
    <?php endif; ?>

    <form method="POST" style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05);">
        <div style="display: flex; flex-direction: column; gap: 32px;">
            
            <div class="form-group">
                <label>Page Title</label>
                <input type="text" name="settings[about_title]" class="form-input" value="<?= htmlspecialchars($settings['about_title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Hero Quote / Tagline</label>
                <textarea name="settings[about_hero]" class="form-textarea" style="height: 100px;"><?= htmlspecialchars($settings['about_hero'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Our Story (Genesis)</label>
                <textarea name="settings[about_story]" class="form-textarea" style="height: 200px;"><?= htmlspecialchars($settings['about_story'] ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div class="form-group">
                    <label>Mission Statement</label>
                    <textarea name="settings[about_mission]" class="form-textarea" style="height: 120px;"><?= htmlspecialchars($settings['about_mission'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Vision Statement</label>
                    <textarea name="settings[about_vision]" class="form-textarea" style="height: 120px;"><?= htmlspecialchars($settings['about_vision'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 16px; width: 100%; justify-content: center; font-size: 15px; font-weight: 700;">Update Content</button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
