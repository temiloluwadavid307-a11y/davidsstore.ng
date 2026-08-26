<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

require_customer();

$user = current_user();

$page_title = 'Customer Dashboard - ' . APP_NAME;
$page_name = 'Dashboard';
$user_role = 'customer';
$active_page = 'dashboard';
$logout_url = APP_URL . '/actions/logout.php';
$customer_name = $user['name'] ?? 'Customer';
$customer_email = $user['email'] ?? 'customer@example.com';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-hero">
    <h2>Your account is now beautifully organized.</h2>
    <p>Track orders, save favorites, and pick up where you left off with a cleaner checkout experience.</p>
    <p style="margin-top: 15px;"><a class="dashboard-btn dashboard-btn-primary" href="<?= APP_URL ?>/products.php">Continue shopping</a> <a class="dashboard-btn" href="<?= APP_URL ?>/customer/section.php?page=wishlist" style="background: rgba(255,255,255,0.16); color:#fff; border:1px solid rgba(255,255,255,0.3);">View wishlist</a></p>
</div>
<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3 style="margin-top:0;">Order tracking</h3>
        <p style="margin-top:10px;color:#6b7280;">Review every recent purchase, track delivery status, and follow the items that matter most.</p>
        <p style="margin-top:12px;"><a class="dashboard-btn dashboard-btn-primary" href="<?= APP_URL ?>/customer/section.php?page=orders">View all orders</a></p>
    </div>
    <div class="dashboard-card">
        <h3 style="margin-top:0;">Saved favorites</h3>
        <p style="margin-top:10px;color:#6b7280;">Save your favorite hoodies, denim, and fashion essentials for later.</p>
    </div>
    <div class="dashboard-card">
        <h3 style="margin-top:0;">Saved addresses</h3>
        <p style="margin-top:10px;color:#6b7280;">Store delivery details for faster checkout and a smoother shopping experience.</p>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
