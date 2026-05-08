<?php
require_once __DIR__ . '/../includes/db.php';

if (!isLoggedIn()) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = currentUserId();

// Query for favorite categories (Read the most)
$stats_stmt = $pdo->prepare("
    SELECT categories.name, categories.name_i18n, COUNT(*) as count 
    FROM user_books 
    JOIN books ON user_books.book_id = books.id 
    JOIN categories ON books.category_id = categories.id 
    WHERE user_books.user_id = ? AND user_books.status = 'read'
    GROUP BY categories.id 
    ORDER BY count DESC 
    LIMIT 3
");
$stats_stmt->execute([$user_id]);
$fav_categories = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get reading summary
$summary_stmt = $pdo->prepare("
    SELECT 
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'reading' THEN 1 ELSE 0 END) as reading_count,
        SUM(CASE WHEN status = 'want_to_read' THEN 1 ELSE 0 END) as want_count
    FROM user_books 
    WHERE user_id = ?
");
$summary_stmt->execute([$user_id]);
$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container" style="max-width: 900px; padding: 60px 24px;">
    <div style="margin-bottom: 48px; text-align: center;">
        <h1 style="font-size: 42px; font-family: var(--font-serif); margin-bottom: 12px;">Your Reading Insights</h1>
        <p style="color: var(--muted-color); font-size: 18px;">Track your journey through the world of books.</p>
    </div>

    <!-- SUMMARY CARDS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 48px;">
        <div style="background: white; padding: 32px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; border: 1px solid rgba(0,0,0,0.05);">
            <div style="color: var(--muted-color); font-size: 13px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Completed</div>
            <div style="font-size: 36px; font-weight: 700; color: var(--accent-color);"><?= $summary['read_count'] ?: 0 ?></div>
        </div>
        <div style="background: white; padding: 32px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; border: 1px solid rgba(0,0,0,0.05);">
            <div style="color: var(--muted-color); font-size: 13px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Currently Reading</div>
            <div style="font-size: 36px; font-weight: 700; color: #3b82f6;"><?= $summary['reading_count'] ?: 0 ?></div>
        </div>
        <div style="background: white; padding: 32px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; border: 1px solid rgba(0,0,0,0.05);">
            <div style="color: var(--muted-color); font-size: 13px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Wishlist</div>
            <div style="font-size: 36px; font-weight: 700; color: #8b5cf6;"><?= $summary['want_count'] ?: 0 ?></div>
        </div>
    </div>

    <!-- MOST READ CATEGORIES -->
    <section style="background: #1A1A1A; color: white; border-radius: 24px; padding: 48px; position: relative; overflow: hidden;">
        <div style="position: relative; z-index: 2;">
            <h2 style="font-size: 28px; font-family: var(--font-serif); margin-bottom: 32px;">Top Genres Read</h2>
            
            <?php if (empty($fav_categories)): ?>
                <p style="color: rgba(255,255,255,0.6);">Mark some books as "Read" to see your genre distribution!</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 24px;">
                    <?php foreach($fav_categories as $index => $cat): ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; font-weight: 500;">
                                <span><?= htmlspecialchars(sskCategoryDisplayName(['name' => $cat['name'], 'name_i18n' => $cat['name_i18n'] ?? null])) ?></span>
                                <span style="opacity: 0.6;"><?= $cat['count'] ?> books</span>
                            </div>
                            <div style="height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; width: 100%;">
                                <?php 
                                    $max = $fav_categories[0]['count'];
                                    $percent = ($cat['count'] / $max) * 100;
                                ?>
                                <div style="height: 100%; width: <?= $percent ?>%; background: var(--accent-color); border-radius: 4px;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Decorative subtle element -->
        <div style="position: absolute; right: -50px; bottom: -50px; width: 200px; height: 200px; background: var(--accent-color); filter: blur(100px); opacity: 0.2;"></div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
