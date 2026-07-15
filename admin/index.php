<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Admin Dashboard — ' . APP_NAME;
$page_name = 'Dashboard';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'dashboard';
$logout_url = '../actions/logout.php';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-card">
        <p>Welcome back, admin. Use the navigation to manage products, orders, users and messages.</p>
    </div>
    <div class="dashboard-card">
        <h2>Quick Links</h2>
        <ul style="padding-left:16px;">
            <li><a href="products.php">Manage Products</a></li>
            <li><a href="orders.php">View Orders</a></li>
            <li><a href="messages.php">Inbox Messages</a></li>
            <li><a href="categories.php">Manage Categories</a></li>
        </ul>
    </div>
    <div class="dashboard-card">
        <h2>Overview</h2>
        <p>Access the latest admin tools and keep the storefront updated.</p>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
