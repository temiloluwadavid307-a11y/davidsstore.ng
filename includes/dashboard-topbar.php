<?php
/**
 * Shared Dashboard Topbar
 */
$user_role = $user_role ?? 'admin';
$page_name = $page_name ?? 'Dashboard';
$user = $user ?? null;
$logout_url = $logout_url ?? '../actions/logout.php';

// Get user initials
$user_initials = 'U';
$user_name = 'User';
$user = $user ?? current_user();
if ($user) {
    $user_name = trim(($user['name'] ?? '') ?: (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
    $user_name = $user_name !== '' ? $user_name : 'User';
    $user_initials = 'U';
    $name_parts = preg_split('/\s+/', trim($user_name));
    if (!empty($name_parts)) {
        $first = strtoupper(substr($name_parts[0], 0, 1));
        $last = isset($name_parts[1]) ? strtoupper(substr($name_parts[1], 0, 1)) : '';
        $user_initials = $first . $last;
    }
}
$role_display = $user_role === 'admin' ? 'Administrator' : 'Vendor';
?>
        <div class="dashboard-main">
            <header class="dashboard-topbar">
                <div class="left">
                    <button class="dashboard-toggle" type="button" data-dashboard-toggle aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1><?php echo htmlspecialchars($page_name); ?></h1>
                        <p>Welcome back, <?php echo htmlspecialchars($user_name); ?></p>
                    </div>
                </div>
                <div class="right">
                    <div class="notif-icon">
                        <i class="fas fa-bell"></i>
                        <span>3</span>
                    </div>
                    <div class="user">
                        <div class="avatar"><?php echo $user_initials; ?></div>
                        <div class="user-info">
                            <div class="name"><?php echo $user_name; ?></div>
                            <div class="role"><?php echo $role_display; ?></div>
                        </div>
                        <a href="<?php echo htmlspecialchars($logout_url); ?>" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </header>
            <main class="dashboard-content">
                <!-- Flash messages -->
                <?php if ($flash = get_flash()): ?>
                    <div class="flash-message <?= e($flash['type']) ?>">
                        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                        <span><?= e($flash['message']) ?></span>
                    </div>
                <?php endif; ?>
