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
    'about_title' => 'Where every book gets a thoughtful home',
    'about_hero' => 'Libris is a calm, structured space to explore titles, track your reading life, and keep rich metadata at your fingertips—without losing the warmth of a real bookshelf.',
    'about_story' => "Libris began as a simple idea: digital libraries should feel as intentional as the paper stacks we love. Too many tools treat books as rows in a spreadsheet. We wanted the opposite—a catalog you can browse with pride, personal shelves that reflect your taste, and enough detail (covers, publishers, languages, editions) to satisfy curious readers and careful curators alike.\n\nWhether you are building a classroom collection, a studio reference library, or your own lifetime reading log, Libris keeps the experience grounded in typography, clarity, and respect for the written word.",
    'about_mission' => 'Democratize beautiful library management: make it effortless to add books, organize categories, and understand what your community is reading—while keeping accounts secure and roles clear.',
    'about_vision' => 'A shared standard for small libraries and passionate readers—where discovery, progress tracking, and stewardship of metadata live in one cohesive place.',
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
            <p class="about-hero-eyebrow">About Libris</p>
            <h1 id="about-heading"><?= htmlspecialchars($about_title) ?></h1>
            <div class="about-hero-lead">
                <blockquote><?= htmlspecialchars($about_hero) ?></blockquote>
            </div>
        </div>
    </section>

    <section class="about-stats" aria-label="Community snapshot">
        <div class="about-stat">
            <strong><?= number_format($books_count) ?></strong>
            <span>Titles catalogued with covers, authors, and metadata ready for your shelves.</span>
        </div>
        <div class="about-stat">
            <strong><?= number_format($categories_count) ?></strong>
            <span>Curated categories that keep browsing focused and friendly.</span>
        </div>
        <div class="about-stat">
            <strong><?= number_format($readers_count) ?></strong>
            <span>Readers and curators sharing the same structured library experience.</span>
        </div>
    </section>

    <section class="about-grid-2" aria-labelledby="story-heading">
        <div>
            <h2 class="about-section-title" id="story-heading">Our story</h2>
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
                <h3>Mission</h3>
                <p><?= htmlspecialchars($about_mission) ?></p>
            </div>
            <div class="about-card-light">
                <h3>Vision</h3>
                <p><?= htmlspecialchars($about_vision) ?></p>
            </div>
        </div>
    </section>

    <section class="about-features" aria-labelledby="features-heading">
        <h2 class="about-section-title" id="features-heading">What you can do here</h2>
        <div class="about-features-grid">
            <article class="about-feature">
                <h4>Browse the catalog</h4>
                <p>Search and filter the full collection by category. Each card opens a detail view with rich information so you can decide what to read next.</p>
            </article>
            <article class="about-feature">
                <h4>Track your reading</h4>
                <p>Mark books as want to read, reading, or finished. Keep favorites in one place and revisit your progress from your personal library dashboard.</p>
            </article>
            <article class="about-feature">
                <h4>Curate with care</h4>
                <p>Admins can add and edit books with ISBN, publisher, year, language, format, and edition fields—so the catalog stays accurate and professional.</p>
            </article>
            <article class="about-feature">
                <h4>Organize categories</h4>
                <p>Category management keeps genres tidy. A consistent taxonomy helps everyone browse faster and keeps the homepage filters meaningful.</p>
            </article>
            <article class="about-feature">
                <h4>Insights for admins</h4>
                <p>The dashboard surfaces activity and growth metrics so you can see how the library evolves and where to focus your next curation sprint.</p>
            </article>
            <article class="about-feature">
                <h4>Built for trust</h4>
                <p>Passwords are hashed, database access is configured via environment variables, and state-changing actions use CSRF protection—security without sacrificing usability.</p>
            </article>
        </div>
    </section>

    <section class="about-steps" aria-labelledby="steps-heading">
        <h2 class="about-section-title" id="steps-heading">Get started in minutes</h2>
        <ol>
            <li>
                <h4>Create your account</h4>
                <p>Register with your email, sign in, and you are ready to explore the catalog and personalize your shelves.</p>
            </li>
            <li>
                <h4>Discover and open a book</h4>
                <p>Use categories and search on the home page, then open any title to read the synopsis, see metadata, and add it to your reading list.</p>
            </li>
            <li>
                <h4>Make it yours</h4>
                <p>Toggle favorites, update your status, and visit My Library to see uploads, favorites, and completed books in one glance.</p>
            </li>
            <li>
                <h4>Admins: grow the collection</h4>
                <p>If you have an admin role, use Add Book to upload covers, assign categories, and keep the catalog worthy of your community.</p>
            </li>
        </ol>
    </section>

    <section class="about-cta" aria-label="Call to action">
        <p>Stories deserve more than a forgotten tab—give them a shelf that feels as considered as the prose inside.</p>
        <div class="about-cta-actions">
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-primary" href="/index.php">Browse catalog</a>
                <a class="btn btn-outline" href="/library/my-library.php">My Library</a>
            <?php else: ?>
                <a class="btn btn-primary" href="/auth/register.php">Create account</a>
                <a class="btn btn-outline" href="/auth/login.php">Sign in</a>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
