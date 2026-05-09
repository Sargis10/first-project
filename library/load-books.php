<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$catUid = (int)($_SESSION['user_id'] ?? 0);
if ($catUid > 0 && sskRateLimitExceeded('catalog_uid:' . $catUid, 300, 60)) {
    http_response_code(429);
    header('Retry-After: 60');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Too many requests']);
    exit;
}
if (sskRateLimitExceeded('catalog_ip', 1500, 60)) {
    http_response_code(429);
    header('Retry-After: 60');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Too many requests']);
    exit;
}

$limit = (int)($_GET['limit'] ?? 20);
$offset = (int)($_GET['offset'] ?? 0);
$category = trim((string)($_GET['category'] ?? 'all'));
$query = trim((string)($_GET['q'] ?? ''));

// Hard clamp to keep the page fast and predictable.
$limit = max(1, min($limit, 20));
$offset = max(0, $offset);

$fetchLimit = $limit + 1; // one extra to detect "has more"

$sql = "
    SELECT books.*, categories.name as category_name, categories.slug as category_slug,
           categories.name_i18n as category_name_i18n
    FROM books
    LEFT JOIN categories ON books.category_id = categories.id
    WHERE 1=1
";
$params = [];

if ($category !== '' && sskLower($category) !== 'all') {
    $sql .= " AND COALESCE(categories.slug, '') = :category";
    $params[':category'] = sskLower($category);
}

if ($query !== '') {
    $sql .= " AND (books.title LIKE :q_title OR books.author LIKE :q_author)";
    $wildcardQuery = '%' . $query . '%';
    $params[':q_title'] = $wildcardQuery;
    $params[':q_author'] = $wildcardQuery;
}

$sql .= " ORDER BY books.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $fetchLimit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasMore = count($rows) > $limit;
$rows = array_slice($rows, 0, $limit);

$html = '';
foreach ($rows as $book) {
    $bookId = (int)$book['id'];
    $title = strtolower((string)($book['title'] ?? ''));
    $author = strtolower((string)($book['author'] ?? ''));
    $slug = isset($book['category_slug']) && $book['category_slug'] !== ''
        ? sskLower((string)$book['category_slug'])
        : 'uncategorized';

    $safeCover = sskSafePublicCoverPath($book['cover_url'] ?? null);
    $placeholderUrl = 'https://placehold.co/400x600/1a1a1a/ffffff?text=' . rawurlencode((string)($book['title'] ?? ''));
    $html .= '
        <div class="card-item" 
             data-title="' . htmlspecialchars($title) . '" 
             data-author="' . htmlspecialchars($author) . '" 
             data-category="' . htmlspecialchars($slug) . '">
            <form method="POST" action="' . htmlspecialchars(sskUrl('read'), ENT_QUOTES, 'UTF-8') . '" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">
                <input type="hidden" name="action" value="open_book">
                <input type="hidden" name="book_id" value="' . $bookId . '">
                <button type="submit" class="card-link">
                    <div class="card-image-wrap">
                        ' . (
                            $safeCover !== ''
                                ? '<img src="' . htmlspecialchars('/' . $safeCover) . '"
                                     alt="Cover"
                                     loading="lazy"
                                     decoding="async"
                                     fetchpriority="low"
                                     data-ssk-placeholder="' . htmlspecialchars($placeholderUrl, ENT_QUOTES, 'UTF-8') . '">'
                                : '<svg class="placeholder-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>'
                        ) . '
                    </div>
                    <div class="card-content">
                        <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-color); margin-bottom: 4px;">
                            ' . htmlspecialchars(sskBookCategoryDisplay($book)) . '
                        </div>
                        <h3 class="card-title">' . htmlspecialchars($book['title'] ?? '') . '</h3>
                        <p class="card-subtitle">' . htmlspecialchars($book['author'] ?? '') . '</p>
                    </div>
                </button>
            </form>
        </div>';
}

echo json_encode([
    'html' => $html,
    'has_more' => $hasMore,
    'next_offset' => $offset + count($rows),
]);
exit;

