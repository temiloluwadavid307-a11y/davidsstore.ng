<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$page_title = 'Analytics - Vendor Dashboard — ' . APP_NAME;
$page_name = 'Analytics';
$user_role = 'vendor';
$user = $_SESSION['user'] ?? null;
$active_page = 'analytics';
$logout_url = APP_URL . '/actions/logout.php';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-stat-card">
        <div class="icon blue"><i class="fas fa-shopping-bag"></i></div>
        <div class="info">
            <h3>156</h3>
            <p>Total Orders</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon green"><i class="fas fa-dollar-sign"></i></div>
        <div class="info">
            <h3>₦ 1,250,000</h3>
            <p>Total Sales</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon orange"><i class="fas fa-box"></i></div>
        <div class="info">
            <h3>28</h3>
            <p>Total Products</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon purple"><i class="fas fa-star"></i></div>
        <div class="info">
            <h3>4.8</h3>
            <p>Average Rating</p>
        </div>
    </div>
</div>
<div class="dashboard-grid" style="margin-top:24px;">
    <div class="dashboard-card" style="grid-column:span 2;">
        <h2 style="margin-bottom:16px;">Sales Overview</h2>
        <div style="background:#f9fafb; height:300px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#6b7280;">
            <i class="fas fa-chart-line" style="font-size:64px; color:#f59e0b; margin-right:16px; opacity:0.4;"></i>
            <div>
                <h4 style="margin:0;">Sales Chart</h4>
                <p style="margin:4px 0 0;">Visual sales data would appear here</p>
            </div>
        </div>
    </div>
    <div class="dashboard-card">
        <h2 style="margin-bottom:16px;">Top Selling Products</h2>
        <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #f3f4f6;">
            <img src="../assets/images/14pm.jpg" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
            <div style="flex:1;">
                <strong>iPhone 14 Pro Max</strong><br>
                <span style="font-size:13px; color:#6b7280;">45 units sold</span>
            </div>
            <span style="font-weight:600;">₦ 950,000</span>
        </div>
        <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #f3f4f6;">
            <img src="../assets/images/ps5.jpg" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
            <div style="flex:1;">
                <strong>Sony PlayStation 5</strong><br>
                <span style="font-size:13px; color:#6b7280;">32 units sold</span>
            </div>
            <span style="font-weight:600;">₦ 520,000</span>
        </div>
        <div style="display:flex; align-items:center; gap:12px; padding:12px 0;">
            <img src="../assets/images/s23u.jpg" style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
            <div style="flex:1;">
                <strong>Samsung Galaxy S23 Ultra</strong><br>
                <span style="font-size:13px; color:#6b7280;">25 units sold</span>
            </div>
            <span style="font-weight:600;">₦ 850,000</span>
        </div>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
