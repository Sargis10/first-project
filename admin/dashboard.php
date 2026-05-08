<?php
require_once __DIR__ . '/../includes/db.php';

if (!isAdmin()) {
    header("Location: /index.php");
    exit;
}

// Get stats
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$books_count = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();

// Data for User Growth Chart (Cumulative - Last 14 days)
$raw_user_data = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate cumulative growth
$cumulative_users = 0;
// We need the total count BEFORE these 14 days to start correctly
$start_count = $pdo->query("
    SELECT COUNT(*) 
    FROM users 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 14 DAY)
")->fetchColumn();

$cumulative_users = (int)$start_count;
$user_growth_data = [];
foreach ($raw_user_data as $row) {
    $cumulative_users += $row['count'];
    $user_growth_data[] = [
        'date' => $row['date'],
        'count' => $cumulative_users
    ];
}

// Data for Book Uploads Chart (Daily - Last 14 days)
$book_uploads_data = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM books 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Data for Top Uploaders (Top 5 users by book count)
$top_uploaders_data = $pdo->query("
    SELECT users.email, COUNT(books.id) as count 
    FROM users 
    JOIN books ON users.id = books.user_id 
    GROUP BY users.id 
    ORDER BY count DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Data for Category Distribution (Top 5 categories)
$category_distribution_data = $pdo->query("
    SELECT categories.name as category_name, categories.name_i18n as category_name_i18n,
           COUNT(books.id) as count 
    FROM categories 
    JOIN books ON categories.id = books.category_id 
    GROUP BY categories.id 
    ORDER BY count DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($category_distribution_data as &$catRow) {
    $catRow['category_label'] = sskCategoryDisplayName([
        'name' => $catRow['category_name'],
        'name_i18n' => $catRow['category_name_i18n'] ?? null,
    ]);
}
unset($catRow);

// Get recent books (Title, Author, Created_at) max 10
$recent_books = $pdo->query("
    SELECT books.*, users.email as uploader_email 
    FROM books 
    JOIN users ON books.user_id = users.id 
    ORDER BY books.created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get all users (sorted by most recent)
$all_users = $pdo->query("
    SELECT id, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 48px;">
        <div class="page-title">
            <h1 style="font-size: 36px; font-family: var(--font-serif); font-weight: 700; margin-bottom: 8px;">Admin Dashboard</h1>
            <p style="color: var(--muted-color); font-size: 16px;">Welcome back, Administrator. Here is your control center.</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="/admin/categories.php" class="btn btn-outline" style="font-size: 13px; padding: 10px 16px;">
                <svg style="margin-right: 8px;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Manage Categories
            </a>
            <a href="/admin/settings.php" class="btn btn-outline" style="font-size: 13px; padding: 10px 16px;">
                <svg style="margin-right: 8px;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Site Settings
            </a>
            <form method="POST" action="/library/book-form.php" style="margin: 0; display: inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                <input type="hidden" name="action" value="prepare_create">
                <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 10px 16px;">
                    <svg style="margin-right: 8px;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Add New Book
                </button>
            </form>
        </div>
    </div>

    <!-- STATS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 24px;">
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; color: var(--muted-color); font-size: 14px; font-weight: 600; text-transform: uppercase;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Total Users
            </div>
            <div style="font-size: 36px; font-weight: 700; font-family: var(--font-serif);"><?= $users_count ?></div>
        </div>

        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); display: flex; flex-direction: column; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; color: var(--muted-color); font-size: 14px; font-weight: 600; text-transform: uppercase;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                Total Books
            </div>
            <div style="font-size: 36px; font-weight: 700; font-family: var(--font-serif);"><?= $books_count ?></div>
        </div>
    </div>

    <!-- CHARTS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 48px;">
        <!-- Chart 1: User Growth -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); min-height: 350px;">
            <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--muted-color); font-weight: 500;">Total Registered Users</h3>
            <div style="height: 250px;"><canvas id="userGrowthChart"></canvas></div>
        </div>

        <!-- Chart 2: Book Uploads -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); min-height: 350px;">
            <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--muted-color); font-weight: 500;">Book Upload Activity</h3>
            <div style="height: 250px;"><canvas id="bookUploadsChart"></canvas></div>
        </div>

        <!-- Chart 3: Top Contributors -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); min-height: 350px;">
            <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--muted-color); font-weight: 500;">Top Contributors (Uploads)</h3>
            <div style="height: 250px;"><canvas id="topUploadersChart"></canvas></div>
        </div>

        <!-- Chart 4: Category Distribution -->
        <div style="background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); min-height: 350px;">
            <h3 style="font-size: 16px; margin-bottom: 20px; color: var(--muted-color); font-weight: 500;">Category Distribution</h3>
            <div style="height: 250px;"><canvas id="categoryDistributionChart"></canvas></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prepare data from PHP with fallback to empty arrays
            const userGrowthData = <?= json_encode($user_growth_data ?? []) ?>;
            const bookUploadsData = <?= json_encode($book_uploads_data ?? []) ?>;
            const topUploadersData = <?= json_encode($top_uploaders_data ?? []) ?>;
            const categoryDistributionData = <?= json_encode($category_distribution_data ?? []) ?>;

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1A1A1A',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { family: 'Inter', size: 12 },
                        bodyFont: { family: 'Inter', size: 14 }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' }, ticks: { font: { size: 11 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            };

            // Helper to safe map
            const safeMap = (arr, fn) => (arr || []).map(fn);

            try {
                // User Growth Chart
                new Chart(document.getElementById('userGrowthChart'), {
                    type: 'line',
                    data: {
                        labels: safeMap(userGrowthData, d => d.date),
                        datasets: [{
                            label: 'Total Users',
                            data: safeMap(userGrowthData, d => d.count),
                            borderColor: '#1A1A1A',
                            backgroundColor: 'rgba(26, 26, 26, 0.05)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#1A1A1A'
                        }]
                    },
                    options: commonOptions
                });

                // Book Uploads Chart
                new Chart(document.getElementById('bookUploadsChart'), {
                    type: 'bar',
                    data: {
                        labels: safeMap(bookUploadsData, d => d.date),
                        datasets: [{
                            label: 'Books Added',
                            data: safeMap(bookUploadsData, d => d.count),
                            backgroundColor: '#5A5A40',
                            borderRadius: 4
                        }]
                    },
                    options: commonOptions
                });

                // Top Uploaders Chart (Horizontal Bar)
                new Chart(document.getElementById('topUploadersChart'), {
                    type: 'bar',
                    data: {
                        labels: safeMap(topUploadersData, d => (d.email || 'Unknown').split('@')[0]),
                        datasets: [{
                            label: 'Books Uploaded',
                            data: safeMap(topUploadersData, d => d.count),
                            backgroundColor: '#2D3748',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        ...commonOptions,
                        indexAxis: 'y',
                    }
                });

                // Category Distribution Chart (Doughnut)
                new Chart(document.getElementById('categoryDistributionChart'), {
                    type: 'doughnut',
                    data: {
                        labels: safeMap(categoryDistributionData, d => d.category_label || d.category_name || 'Unknown'),
                        datasets: [{
                            data: safeMap(categoryDistributionData, d => d.count),
                            backgroundColor: ['#1A1A1A', '#5A5A40', '#717171', '#A8A29E', '#E5E5E5'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        ...commonOptions,
                        plugins: { ...commonOptions.plugins, legend: { display: true, position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                        scales: { y: { display: false }, x: { display: false } }
                    }
                });
            } catch (err) {
                console.error("Chart Initialization Error:", err);
            }
        });
    </script>

    <div style="display: grid; grid-template-columns: 1fr; gap: 48px;">
        
        <!-- RECENT BOOKS -->
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 24px;">Recent Books</h2>
                <a href="/index.php" style="color: var(--ink-color); font-size: 14px; font-weight: 500; text-decoration: none;">View All &rarr;</a>
            </div>
            
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05); background: #fafafa; color: var(--muted-color); font-weight: 500;">
                            <th style="padding: 16px 24px;">Title & Author</th>
                            <th style="padding: 16px 24px;">Added By</th>
                            <th style="padding: 16px 24px;">Date</th>
                            <th style="padding: 16px 24px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_books as $b): ?>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <td style="padding: 16px 24px;">
                                <div style="font-weight: 600; color: var(--ink-color);"><?= htmlspecialchars($b['title']) ?></div>
                                <div style="color: var(--muted-color); font-size: 12px;"><?= htmlspecialchars($b['author']) ?></div>
                            </td>
                            <td style="padding: 16px 24px; color: var(--muted-color);"><?= htmlspecialchars($b['uploader_email']) ?></td>
                            <td style="padding: 16px 24px; color: var(--muted-color);"><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                            <td style="padding: 16px 24px; text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <form method="POST" action="/library/book-form.php" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="prepare_edit">
                                        <input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">Edit</button>
                                    </form>
                                    <form method="POST" action="/library/book-details.php" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="open_book">
                                        <input type="hidden" name="book_id" value="<?= (int)$b['id'] ?>">
                                        <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">View</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_books)): ?>
                        <tr><td colspan="4" style="padding: 24px; text-align: center; color: var(--muted-color);">No books found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- REGISTERED USERS -->
        <section>
            <h2 style="font-size: 24px; margin-bottom: 24px;">All Registered Users</h2>
            
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05); background: #fafafa; color: var(--muted-color); font-weight: 500;">
                            <th style="padding: 16px 24px;">ID</th>
                            <th style="padding: 16px 24px;">Email</th>
                            <th style="padding: 16px 24px;">Role</th>
                            <th style="padding: 16px 24px;">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_users as $u): ?>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <td style="padding: 16px 24px; color: var(--muted-color);">#<?= $u['id'] ?></td>
                            <td style="padding: 16px 24px; font-weight: 500;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="padding: 16px 24px;">
                                <?php if($u['role'] === 'admin'): ?>
                                    <span style="background: #1A1A1A; color: white; padding: 4px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; text-transform: uppercase;">Admin</span>
                                <?php else: ?>
                                    <span style="background: #f5f5f4; color: var(--muted-color); padding: 4px 8px; border-radius: 99px; font-size: 11px; font-weight: 600; text-transform: uppercase;">User</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 16px 24px; color: var(--muted-color);"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
