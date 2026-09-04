<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Change Password — ' . APP_NAME;
$page_name = 'Change Password';
$user_role = 'admin';
$active_page = 'dashboard';
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf($csrf_token)) {
        $errors[] = 'Invalid request token.';
    }

    if (empty($current_password)) {
        $errors[] = 'Your current password is required.';
    }

    if (empty($new_password) || empty($confirm_password)) {
        $errors[] = 'Please enter and confirm your new password.';
    }

    if ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match.';
    }

    $policy_errors = validate_password_policy($new_password);
    foreach ($policy_errors as $policy_error) {
        $errors[] = $policy_error;
    }

    if (empty($errors)) {
        $user_id = (int) ($_SESSION['user_id'] ?? 0);
        if ($user_id <= 0) {
            $errors[] = 'You are not authenticated.';
        } else {
            if (!change_user_password($user_id, $current_password, $new_password)) {
                $errors[] = 'Current password is incorrect or the password update failed.';
            } else {
                session_regenerate_id(true);
                $success = 'Password updated successfully. Please sign in again with your new password on your next session.';
                $_SESSION['user_password_changed'] = true;
            }
        }
    }
}

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>

<div class="dashboard-card" style="max-width:640px; margin:0 auto;">
    <h2 style="margin-top:0;">Change Admin Password</h2>
    <p style="color:#6b7280; margin-top:0;">Use your current password to set a new one. Passwords are stored using PHP's secure password hashing.</p>

    <?php if ($errors): ?>
        <div style="background:#fef2f2;color:#991b1b;padding:12px 14px;border-radius:10px;margin-bottom:16px;">
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background:#ecfdf5;color:#166534;padding:12px 14px;border-radius:10px;margin-bottom:16px;">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" style="display:grid; gap:16px;">
        <?= csrf_field() ?>

        <div>
            <label for="current_password" style="display:block; margin-bottom:8px; font-weight:600;">Current Password</label>
            <input type="password" id="current_password" name="current_password" required style="width:100%; padding:12px 14px; border-radius:10px; border:1px solid #d1d5db;">
        </div>

        <div>
            <label for="new_password" style="display:block; margin-bottom:8px; font-weight:600;">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="8" style="width:100%; padding:12px 14px; border-radius:10px; border:1px solid #d1d5db;">
        </div>

        <div>
            <label for="confirm_password" style="display:block; margin-bottom:8px; font-weight:600;">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" style="width:100%; padding:12px 14px; border-radius:10px; border:1px solid #d1d5db;">
        </div>

        <button type="submit" class="dashboard-btn dashboard-btn-primary">Update Password</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
