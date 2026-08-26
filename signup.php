<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(APP_URL . '/index.php');
}

$page_title = 'Sign Up — ' . APP_NAME;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($first_name)) $errors[] = 'First name is required.';
    if (empty($last_name)) $errors[] = 'Last name is required.';
    if (!is_valid_email($email)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare('INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, "customer")');
            $stmt->bind_param('sssss', $first_name, $last_name, $email, $phone, $hash);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                handle_new_user_registration($_SESSION['user_id'], $first_name, $last_name, $email, 'customer');
                set_flash('success', 'Account created successfully! Welcome to David\'s Store.');
                redirect(APP_URL . '/index.php');
            } else {
                $errors[] = 'Registration failed. Please try again.';
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
            <h1>Create Account</h1>
            <p class="subtitle">Join David's Store for exclusive access and faster checkout</p>
            <p style="margin:-6px 0 18px; color:#6b7280; font-size:14px;">A verification email will be sent to your inbox so you can complete registration and start shopping.</p>
            <p style="margin:-6px 0 18px; color:#6b7280; font-size:14px;">Planning to sell? Create your account first, then apply to become a vendor from your customer dashboard.</p>

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
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required value="<?= e($_POST['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required value="<?= e($_POST['last_name'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="08012345678" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Create Account</button>
            </form>

            <p class="form-footer">
                Already have an account? <a href="<?= APP_URL ?>/login.php">Sign in</a>
            </p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
