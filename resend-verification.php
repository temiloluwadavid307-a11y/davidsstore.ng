<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$user = current_user();
if (!$user) {
    redirect(APP_URL . '/login.php');
}

$page_title = 'Resend Verification — ' . APP_NAME;
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request token.';
    }

    if (empty($errors)) {
        if (resend_verification_email($user['id'], $user['name'], $user['email'])) {
            $success = 'A new verification link has been sent to your email address.';
        } else {
            $errors[] = 'Unable to send a new verification email right now. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main>
    <div class="form-page">
        <div class="form-card">
            <h1>Resend Verification Email</h1>
            <p class="subtitle">We will send a fresh verification link to the email on your account.</p>

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
            <?php endif; ?>

            <form method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary" style="width:100%;">Resend verification email</button>
            </form>

            <p class="form-footer">
                Already verified? <a href="<?= APP_URL ?>/login.php">Sign in</a>
            </p>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
