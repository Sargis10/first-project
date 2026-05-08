<?php
require_once __DIR__ . '/../includes/db.php';

// Fetch all settings into a helper array
$raw_settings = $pdo->query("SELECT * FROM site_settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="max-width: 1000px; padding: 80px 24px;">
    
    <!-- Hero Section -->
    <header style="text-align: center; margin-bottom: 100px;">
        <div style="font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.2em; color: var(--accent-color); margin-bottom: 16px;">Our Story</div>
        <h1 style="font-size: 64px; font-family: var(--font-serif); line-height: 1.1; margin-bottom: 32px; color: var(--ink-color);">
            <?= htmlspecialchars($settings['about_title'] ?? 'The Libris Narrative') ?>
        </h1>
        <p style="font-size: 22px; color: var(--muted-color); max-width: 700px; margin: 0 auto; line-height: 1.6; font-style: italic;">
            "<?= htmlspecialchars($settings['about_hero'] ?? '') ?>"
        </p>
    </header>

    <!-- Content Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 80px; margin-bottom: 100px; align-items: start;">
        <section>
            <h2 style="font-size: 24px; font-family: var(--font-serif); margin-bottom: 24px; border-bottom: 2px solid var(--accent-color); display: inline-block; padding-bottom: 8px;">Our Genesis</h2>
            <div style="font-size: 18px; line-height: 1.8; color: #444;">
                <?= nl2br(htmlspecialchars($settings['about_story'] ?? '')) ?>
            </div>
        </section>

        <div style="display: flex; flex-direction: column; gap: 48px;">
            <div style="background: #1A1A1A; color: white; padding: 40px; border-radius: 24px; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.2);">
                <h3 style="font-size: 18px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--accent-color); margin-bottom: 16px;">The Mission</h3>
                <p style="font-size: 16px; line-height: 1.6; opacity: 0.8;">
                    <?= htmlspecialchars($settings['about_mission'] ?? '') ?>
                </p>
            </div>

            <div style="background: white; border: 1px solid rgba(0,0,0,0.05); padding: 40px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                <h3 style="font-size: 18px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink-color); margin-bottom: 16px;">The Vision</h3>
                <p style="font-size: 16px; line-height: 1.6; color: var(--muted-color);">
                    <?= htmlspecialchars($settings['about_vision'] ?? '') ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Decorative Callout -->
    <div style="text-align: center; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 80px;">
        <div style="width: 60px; height: 1px; background: var(--accent-color); margin: 0 auto 32px;"></div>
        <p style="font-family: var(--font-serif); font-size: 28px; color: var(--ink-color); line-height: 1.4; max-width: 600px; margin: 0 auto;">
            Building the future of reading, one structured record at a time.
        </p>
    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
