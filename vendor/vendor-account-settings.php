<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$page_title = 'Account Settings - Vendor Dashboard — ' . APP_NAME;
$page_name = 'Account Settings';
$user_role = 'vendor';
$user = current_user();
$active_page = 'account';
$logout_url = APP_URL . '/actions/logout.php';
$vendor = ensure_current_vendor();
if (!$vendor) {
    redirect(APP_URL . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect(APP_URL . '/vendor/vendor-account-settings.php');
    }
    $action = $_POST['action'] ?? 'profile';
    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !password_verify($current, $row['password_hash'])) {
            set_flash('error', 'Current password is incorrect.');
        } elseif ($new !== $confirm || strlen($new) < 6) {
            set_flash('error', 'New password must match and be at least 6 characters.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $upd->bind_param('si', $hash, $user['id']);
            $upd->execute();
            set_flash('success', 'Password updated successfully.');
        }
    } else {
        $store_name = sanitize($_POST['store_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $stmt = $conn->prepare('UPDATE vendors SET name = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('ssi', $store_name, $phone, $vendor['id']);
        $stmt->execute();
        set_flash('success', 'Store profile updated.');
    }
    redirect(APP_URL . '/vendor/vendor-account-settings.php');
}

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-card" style="grid-column:span 2;">
        <h2 style="margin-bottom:16px;">Store Profile</h2>
        <form method="post" style="display:grid; grid-template-columns:repeat(2,1fr); gap:20px;">
            <input type="hidden" name="action" value="profile">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Store Name</label>
                <input type="text" name="store_name" value="<?= e($_POST['store_name'] ?? $vendor['name'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;" placeholder="Your store name">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Email</label>
                <input type="email" value="<?= e($vendor['email'] ?? '') ?>" disabled style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Phone Number</label>
                <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? $vendor['phone'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;" placeholder="+234 800 000 0000">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Store Address</label>
                <input type="text" name="address" value="<?= e($_POST['address'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;" placeholder="123 Main St, Lagos">
            </div>
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Store Description</label>
                <textarea name="bio" rows="3" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;" placeholder="Tell us about your store..."><?= e($_POST['bio'] ?? '') ?></textarea>
            </div>
            <div style="grid-column:span 2; display:flex; justify-content:flex-end;">
                <button type="submit" class="dashboard-btn dashboard-btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
    <div class="dashboard-card">
        <h2 style="margin-bottom:16px;">Change Password</h2>
        <form method="post" style="display:flex; flex-direction:column; gap:16px;">
            <input type="hidden" name="action" value="password">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Current Password</label>
                <input type="password" name="current_password" required style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">New Password</label>
                <input type="password" name="new_password" required style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Confirm New Password</label>
                <input type="password" name="confirm_password" required style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <button type="submit" class="dashboard-btn dashboard-btn-primary" style="width:100%;">
                <i class="fas fa-key"></i> Update Password
            </button>
        </form>
    </div>
    <div class="dashboard-card">
        <h2 style="margin-bottom:16px;">Account Status</h2>
        <div style="padding:16px; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <strong>Verification</strong>
                <span class="dashboard-badge" style="background:#fef3c7; color:#92400e;">Pending</span>
            </div>
            <p style="font-size:14px; color:#6b7280; margin:0;">Your account is pending verification</p>
        </div>
        <button class="dashboard-btn" style="width:100%; background:#fee2e2; color:#991b1b; border:none;">
            <i class="fas fa-power-off"></i> Deactivate Account
        </button>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
