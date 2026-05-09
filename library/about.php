<?php
require_once __DIR__ . '/../includes/db.php';

$raw_settings = $pdo->query("SELECT * FROM site_settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($raw_settings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$pick = static function (string $key, string $fallback) use ($settings): string {
    $v = trim($settings[$key] ?? '');
    return $v !== '' ? $v : $fallback;
};

$defaults = [
    'about_title' => t('about.default_title'),
    'about_hero' => t('about.default_hero'),
    'about_story' => t('about.default_story'),
    'about_mission' => t('about.default_mission'),
    'about_vision' => t('about.default_vision'),
];

$about_title = $pick('about_title', $defaults['about_title']);
$about_hero = $pick('about_hero', $defaults['about_hero']);
$about_story = $pick('about_story', $defaults['about_story']);
$about_mission = $pick('about_mission', $defaults['about_mission']);
$about_vision = $pick('about_vision', $defaults['about_vision']);

$books_count = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$categories_count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$readers_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

$pageStyles = ['assets/css/pages/about.css'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="about-page">
    <section class="about-hero" aria-labelledby="about-heading">
        <div class="about-hero-visual" aria-hidden="true">
            <div class="about-book-sculpture">
                <span class="spine-a"></span>
                <span class="spine-b"></span>
                <span class="spine-c"></span>
            </div>
        </div>
        <div>
            <p class="about-hero-eyebrow"><?= htmlspecialchars(t('about.eyebrow')) ?></p>
            <h1 id="about-heading"><?= htmlspecialchars($about_title) ?></h1>
            <div class="about-hero-lead">
                <blockquote><?= htmlspecialchars($about_hero) ?></blockquote>
            </div>
        </div>
    </section>

    <section class="about-stats" aria-label="<?= htmlspecialchars(t('about.community_snapshot')) ?>">
        <div class="about-stat">
            <strong><?= number_format($books_count) ?></strong>
            <span><?= htmlspecialchars(t('about.stat_titles')) ?></span>
        </div>
        <div class="about-stat">
            <strong><?= number_format($categories_count) ?></strong>
            <span><?= htmlspecialchars(t('about.stat_categories')) ?></span>
        </div>
        <div class="about-stat">
            <strong><?= number_format($readers_count) ?></strong>
            <span><?= htmlspecialchars(t('about.stat_readers')) ?></span>
        </div>
    </section>

    <section class="about-grid-2" aria-labelledby="story-heading">
        <div>
            <h2 class="about-section-title" id="story-heading"><?= htmlspecialchars(t('about.our_story')) ?></h2>
            <div class="about-prose">
                <?php foreach (preg_split("/\n+/", $about_story) as $para): ?>
                    <?php $para = trim($para); ?>
                    <?php if ($para !== ''): ?>
                        <p><?= nl2br(htmlspecialchars($para)) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div class="about-card-dark">
                <h3><?= htmlspecialchars(t('about.mission')) ?></h3>
                <p><?= htmlspecialchars($about_mission) ?></p>
            </div>
            <div class="about-card-light">
                <h3><?= htmlspecialchars(t('about.vision')) ?></h3>
                <p><?= htmlspecialchars($about_vision) ?></p>
            </div>
        </div>
    </section>

    <section class="about-features" aria-labelledby="features-heading">
        <h2 class="about-section-title" id="features-heading"><?= htmlspecialchars(t('about.features_title')) ?></h2>
        <div class="about-features-grid">
            <article class="about-feature">
                <h4><?= htmlspecialchars(t('about.feature1_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.feature1_text')) ?></p>
            </article>
            <article class="about-feature">
                <h4><?= htmlspecialchars(t('about.feature2_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.feature2_text')) ?></p>
            </article>
            <article class="about-feature">
                <h4><?= htmlspecialchars(t('about.feature3_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.feature3_text')) ?></p>
            </article>
            <article class="about-feature">
                <h4><?= htmlspecialchars(t('about.feature4_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.feature4_text')) ?></p>
            </article>
            <article class="about-feature">
                <h4><?= htmlspecialchars(t('about.feature5_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.feature5_text')) ?></p>
            </article>
            <article class="about-feature">
                <h4><?= htmlspecialchars(t('about.feature6_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.feature6_text')) ?></p>
            </article>
        </div>
    </section>

    <section class="about-steps" aria-labelledby="steps-heading">
        <h2 class="about-section-title" id="steps-heading"><?= htmlspecialchars(t('about.steps_title')) ?></h2>
        <ol>
            <li>
                <h4><?= htmlspecialchars(t('about.step1_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.step1_text')) ?></p>
            </li>
            <li>
                <h4><?= htmlspecialchars(t('about.step2_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.step2_text')) ?></p>
            </li>
            <li>
                <h4><?= htmlspecialchars(t('about.step3_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.step3_text')) ?></p>
            </li>
            <li>
                <h4><?= htmlspecialchars(t('about.step4_title')) ?></h4>
                <p><?= htmlspecialchars(t('about.step4_text')) ?></p>
            </li>
        </ol>
    </section>

    <section class="about-cta" aria-label="Call to action">
        <p><?= htmlspecialchars(t('about.cta_text')) ?></p>
        <div class="about-cta-actions">
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars(sskUrl('home')) ?>"><?= htmlspecialchars(t('about.cta_browse')) ?></a>
                <a class="btn btn-outline" href="<?= htmlspecialchars(sskUrl('shelf')) ?>"><?= htmlspecialchars(t('about.cta_my_library')) ?></a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars(sskUrl('sign_up')) ?>"><?= htmlspecialchars(t('about.cta_create')) ?></a>
                <a class="btn btn-outline" href="<?= htmlspecialchars(sskUrl('sign_in')) ?>"><?= htmlspecialchars(t('about.cta_signin')) ?></a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
