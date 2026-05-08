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
        $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            header("Location: /index.php");
            exit;
        } else {
            $error = 'Invalid email or password';
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
            <h1 style="font-size: 36px; font-family: var(--font-serif); margin-bottom: 8px;">Welcome Back</h1>
            <p style="color: var(--muted-color);">Sign in to manage your personal library.</p>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-input" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>

            <?php if ($error): ?>
                <p class="error-text" style="text-align: center; margin-bottom: 16px;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 16px;">Sign In</button>
        </form>

        <p style="text-align: center; color: var(--muted-color); font-size: 14px; margin-top: 24px;">
            Don't have an account?
            <a href="/auth/register.php" style="color: var(--ink-color); font-weight: 600; text-decoration: none;">Register here</a>
        </p>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
