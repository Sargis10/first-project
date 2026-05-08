<?php
require_once __DIR__ . '/../includes/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Seed script is CLI-only for security reasons.');
}

$email = getenv('SEED_ADMIN_EMAIL') ?: '';
$password = getenv('SEED_ADMIN_PASSWORD') ?: '';
if ($email === '' || $password === '') {
    die("Missing SEED_ADMIN_EMAIL or SEED_ADMIN_PASSWORD environment variables.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->execute([$email, $hash]);
    echo "Admin user successfully created.\n";
} else {
    echo "Admin user already exists.\n";
}
echo "Seed complete for: {$email}\n";
?>
