<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in() && is_admin()) {
    redirect(APP_URL . '/admin/index.php');
}

$page_title = 'Admin Login — ' . APP_NAME;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (empty($password)) {
        $errors[] = 'Password is required.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 AND role IN ('admin','super_admin')");
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
            redirect(APP_URL . '/admin/index.php');
        } else {
            $errors[] = 'Invalid credentials or admin access denied.';
        }
    }
}

// If logged in but not admin, redirect
if (is_logged_in() && !is_admin()) {
    redirect(APP_URL . '/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #111827;
        }
        .admin-login-container {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            padding: 36px 32px;
            border: 1px solid #e5e7eb;
        }
        .admin-login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .admin-login-header .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            border-radius: 16px;
            background: linear-gradient(135deg, #111827, #4f46e5);
            color: white;
            font-size: 24px;
            margin-bottom: 14px;
        }
        .admin-login-header h1 {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 700;
        }
        .admin-login-header p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .admin-login-form { display: flex; flex-direction: column; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 14px; font-weight: 600; color: #374151; }
        .form-group input {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }
        .admin-login-button {
            margin-top: 4px;
            border: none;
            border-radius: 12px;
            padding: 13px 16px;
            background: linear-gradient(135deg, #111827, #4f46e5);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .admin-login-button:hover { transform: translateY(-1px); box-shadow: 0 12px 24px rgba(79, 70, 229, 0.2); }
        .error-message {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            font-size: 14px;
        }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #4f46e5; text-decoration: none; font-weight: 600; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-header">
            <div class="brand-badge"><i class="fas fa-shield-alt"></i></div>
            <h1>Admin Login</h1>
            <p>Secure access to your store dashboard</p>
        </div>

        <?php if ($errors): ?>
        <div class="error-message">
            <?= e($errors[0]) ?>
        </div>
        <?php endif; ?>

        <form method="post" class="admin-login-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@example.com">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="admin-login-button">Sign In to Admin Panel</button>
        </form>

        <div class="back-link">
            <a href="<?= APP_URL ?>/index.php">← Back to Store</a>
        </div>
    </div>
</body>
</html>
