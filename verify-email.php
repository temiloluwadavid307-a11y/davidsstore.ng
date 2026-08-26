<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Verify Email — ' . APP_NAME;
$message = '';
$status = 'success';
$token = $_GET['token'] ?? '';

if (empty($token) || !is_string($token)) {
    $message = 'Invalid verification link. Please check your email and try again.';
    $status = 'error';
} else {
    $user_id = consume_email_verification_token($token);
    if ($user_id === null) {
        $message = 'This verification link is invalid or has expired. Please request a new link.';
        $status = 'error';
    } else {
        mark_user_email_verified($user_id);
        $message = 'Your email address has been verified successfully. You can now sign in to your account.';
        $status = 'success';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main>
    <div class="form-page">
        <div class="form-card">
            <h1>Email Verification</h1>
            <p class="subtitle"><?= $status === 'success' ? 'Verification complete' : 'Verification failed' ?></p>
            <div style="background:<?= $status === 'success' ? '#ecfdf5' : '#fef2f2' ?>; color:<?= $status === 'success' ? '#166534' : '#991b1b' ?>; padding:16px; border-radius:10px; margin-top:16px;">
                <?= e($message) ?>
            </div>
            <div style="margin-top:24px; display:flex; gap:12px; flex-wrap:wrap;">
                <a href="<?= APP_URL ?>/login.php" class="btn btn-primary">Sign in</a>
                <a href="<?= APP_URL ?>/resend-verification.php" class="btn" style="background:#f3f4f6; color:#111827;">Resend verification</a>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
