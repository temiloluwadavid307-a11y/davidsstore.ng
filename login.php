<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$page_title = 'Login — ' . STORE_NAME;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (empty($password)) {
        $errors[] = 'Password is required.';
    } else {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            $update = $conn->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
            $update->bind_param('i', $user['id']);
            $update->execute();

            set_flash('success', 'Welcome back, ' . $user['first_name'] . '!');
            if ($user['role'] === 'admin') {
                $redirect = APP_URL . '/admin/index.php';
            } elseif ($user['role'] === 'vendor') {
                $redirect = APP_URL . '/vendor/index.php';
            } else {
                $redirect = APP_URL . '/customer/index.php';
            }
            redirect($redirect);
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main>
    <div class="form-page">
        <div class="form-card">
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to your <?= STORE_NAME ?> account</p>
            <p style="margin:-6px 0 18px; color:#6b7280; font-size:14px;">Need to manage your own store? Sign in and start a vendor application from the dashboard.</p>

            <?php if ($errors): ?>
            <div style="background:#fef2f2;color:#991b1b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;">
                <?= e($errors[0]) ?>
            </div>
            <?php endif; ?>

            <form method="post" data-validate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div style="text-align:right; margin-bottom:16px;"><a href="<?= APP_URL ?>/forgot-password.php" style="color:#2563eb; text-decoration:none; font-size:14px;">Forgot password?</a></div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
            </form>

            <p class="form-footer">
                Don't have an account? <a href="<?= APP_URL ?>/signup.php">Create one</a>
            </p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
