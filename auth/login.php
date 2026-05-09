<?php
require_once __DIR__ . '/../includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . sskUrl('home'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfTokenOrFail();
    if (sskRateLimitExceeded('login', 25, 900)) {
        usleep(random_int(100_000, 350_000));
        $error = t('auth.too_many_attempts');
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!empty($email) && !empty($password)) {
            $email = trim((string)$email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = t('auth.invalid_email');
            } else {
                $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $storedHash = ($user && !empty($user['password']))
                    ? (string)$user['password']
                    : SSK_PASSWORD_PLACEHOLDER_HASH;
                $passwordOk = password_verify((string)$password, $storedHash);

                if ($user && $passwordOk) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    header('Location: ' . sskUrl('home'));
                    exit;
                }
                usleep(random_int(80_000, 220_000));
                $error = t('auth.invalid_credentials');
            }
        } else {
            $error = t('auth.fill_all_fields');
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="auth-wrapper">
    <div class="auth-form">
        <div style="text-align: center; margin-bottom: 32px;">
            <h1 style="font-size: 36px; font-family: var(--font-serif); margin-bottom: 8px;"><?= htmlspecialchars(t('auth.login.title')) ?></h1>
            <p style="color: var(--muted-color);"><?= htmlspecialchars(t('auth.login.subtitle')) ?></p>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <div class="form-group">
                <label><?= htmlspecialchars(t('auth.email')) ?></label>
                <input type="email" name="email" class="form-input" required>
            </div>

            <div class="form-group">
                <label><?= htmlspecialchars(t('auth.password')) ?></label>
                <input type="password" name="password" class="form-input" required>
            </div>

            <?php if ($error): ?>
                <p class="error-text" style="text-align: center; margin-bottom: 16px;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 16px;"><?= htmlspecialchars(t('auth.sign_in')) ?></button>
        </form>

        <p style="text-align: center; color: var(--muted-color); font-size: 14px; margin-top: 24px;">
            <?= htmlspecialchars(t('auth.no_account')) ?>
            <a href="<?= htmlspecialchars(sskUrl('sign_up')) ?>" style="color: var(--ink-color); font-weight: 600; text-decoration: none;"><?= htmlspecialchars(t('auth.register_here')) ?></a>
        </p>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
