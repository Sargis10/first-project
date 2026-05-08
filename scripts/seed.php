<?php
require_once __DIR__ . '/../includes/db.php';

$email = 'admin@libris.com';
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->execute([$email, $hash]);
    echo "Admin user successfully created! <br><br>";
} else {
    echo "Admin user already exists! <br><br>";
}

echo "<strong>Email:</strong> admin@libris.com <br>";
echo "<strong>Password:</strong> password123 <br><br>";
echo "<a href='/auth/login.php'>Go to Login</a>";
?>
