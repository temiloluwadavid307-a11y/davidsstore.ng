<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$page_title = 'Reset Password — ' . APP_NAME;
$errors = [];
$success = null;
$token = $_GET['token'] ?? '';
$user_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf($csrf_token)) {
        $errors[] = 'Invalid request token.';
    }
    if (empty($token) || !is_string($token)) {
        $errors[] = 'Invalid password reset link.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $user_id = consume_password_reset_token($token);
        if ($user_id === null) {
            $errors[] = 'This reset link is invalid or has expired.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $user_id);
            if ($stmt->execute()) {
                $success = 'Your password has been reset successfully. You may now sign in.';
            } else {
                $errors[] = 'Unable to update password. Please try again later.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main>
    <div class="form-page">
        <div class="form-card">
            <h1>Reset your password</h1>
            <p class="subtitle">Choose a new password for your account.</p>

            <?php if ($errors): ?>
            <div style="background:#fef2f2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                <ul style="margin:0;padding-left:16px;">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div style="background:#ecfdf5;color:#166534;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                <?= e($success) ?>
            </div>
            <p><a href="<?= APP_URL ?>/login.php">Return to login</a></p>
            <?php else: ?>
            <form method="post" data-validate>
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Reset password</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
