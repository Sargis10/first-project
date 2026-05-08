<?php
require_once __DIR__ . '/../includes/db.php';

if (isLoggedIn()) {
    header("Location: /index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfTokenOrFail();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email is already in use.';
            } else {
                // Insert user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                if ($stmt->execute([$email, $hash])) {
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['role'] = 'user';
                    header("Location: /index.php");
                    exit;
                } else {
                    $error = 'Registration failed. Try again.';
                }
            }
        }
    } else {
        $error = 'Please fill all fields';
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="auth-wrapper">
    <div class="auth-form">
        <div style="text-align: center; margin-bottom: 32px;">
            <h1 style="font-size: 36px; font-family: var(--font-serif); margin-bottom: 8px;">Join Libris</h1>
            <p style="color: var(--muted-color);">Create an account to start your collection.</p>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-input" required minlength="6">
            </div>

            <?php if ($error): ?>
                <p class="error-text" style="text-align: center; margin-bottom: 16px;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 16px;">Create Account</button>
        </form>

        <p style="text-align: center; color: var(--muted-color); font-size: 14px; margin-top: 24px;">
            Already have an account? 
            <a href="/auth/login.php" style="color: var(--ink-color); font-weight: 600; text-decoration: none;">Login here</a>
        </p>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
