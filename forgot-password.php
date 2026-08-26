<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$page_title = 'Forgot Password — ' . APP_NAME;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf($csrf_token)) {
        $errors[] = 'Invalid request token.';
    }

    if (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $user = get_user_by_email($email);
        if ($user && !empty($user['is_active'])) {
            $token = create_password_reset_token((int) $user['id']);
            send_password_reset_email((int) $user['id'], $user['first_name'], $user['email'], $token);
        }

        set_flash('success', 'If that address exists in our system, a password reset link has been sent to your email.');
        redirect(APP_URL . '/login.php');
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main>
    <div class="form-page">
        <div class="form-card">
            <h1>Forgot your password?</h1>
            <p class="subtitle">Enter your email address and we will send you a secure reset link.</p>

            <?php if ($errors): ?>
            <div style="background:#fef2f2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                <ul style="margin:0;padding-left:16px;">
                    <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="post" data-validate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Send reset link</button>
            </form>

            <p class="form-footer">
                Remembered your password? <a href="<?= APP_URL ?>/login.php">Sign in</a>
            </p>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
