<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = sanitize($_POST['to'] ?? '');
    $subject = sanitize($_POST['subject'] ?? 'Test email from ' . APP_NAME);
    $body = sanitize($_POST['body'] ?? 'This is a test email to verify SMTP configuration.');

    $html = '<p>' . e($body) . '</p>';
    $text = strip_tags($body);

    $ok = send_email_notification(null, $to, $subject, $html, $text);
    if ($ok) {
        set_flash('success', 'Test email sent successfully to ' . e($to));
    } else {
        set_flash('error', 'Failed to send test email. Check server logs and SMTP settings.');
    }
    redirect(APP_URL . '/admin/smtp-test.php');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<main class="dashboard-content">
    <div class="dashboard-grid">
        <div class="dashboard-card" style="grid-column:1/-1;">
            <h2>SMTP Test (Admin only)</h2>
            <p>Use this page to send a test email using the configured SMTP settings. Saved credentials are read from <strong>config/smtp.php</strong>.</p>
            <?php if ($flash = get_flash()): ?>
                <div class="flash-message <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <form method="post" style="display:grid; gap:12px; max-width:600px;">
                <label>To Email</label>
                <input type="email" name="to" required value="<?= e($_SESSION['user_email'] ?? '') ?>">
                <label>Subject</label>
                <input type="text" name="subject" value="Test email from <?= e(APP_NAME) ?>">
                <label>Body</label>
                <textarea name="body">This is a test message from <?= e(APP_NAME) ?></textarea>
                <?= csrf_field() ?>
                <button type="submit" class="dashboard-btn dashboard-btn-primary">Send Test Email</button>
            </form>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
