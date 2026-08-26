<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$page_title = 'Vendor Dashboard — ' . APP_NAME;
$page_name = 'Dashboard';
$user_role = 'vendor';
$user = $_SESSION['user'] ?? null;
$active_page = 'dashboard';
$logout_url = APP_URL . '/actions/logout.php';
$vendor = ensure_current_vendor();
if (!$vendor) {
    redirect(APP_URL . '/index.php');
}

$vendor_kyc_status = strtolower((string) ($vendor['kyc_status'] ?? 'not_started'));
$kyc_alert = null;
if (in_array($vendor_kyc_status, ['not_started', 'rejected', 'requires_update'], true)) {
    $kyc_alert = [
        'type' => 'warning',
        'title' => 'Complete your KYC to unlock payouts',
        'message' => 'Your store is active, but payouts and Paystack payouts remain locked until your verification is approved.'
    ];
} elseif (in_array($vendor_kyc_status, ['submitted', 'under_review'], true)) {
    $kyc_alert = [
        'type' => 'info',
        'title' => 'KYC submitted for review',
        'message' => 'Your documents are under review. We’ll notify you once the verification is approved.'
    ];
}

$stmt = $conn->prepare('SELECT COUNT(*) AS product_count FROM products WHERE vendor_id = ?');
$stmt->bind_param('i', $vendor['id']);
$stmt->execute();
$product_count = (int) $stmt->get_result()->fetch_assoc()['product_count'];

$orders_stmt = $conn->prepare('SELECT COUNT(DISTINCT o.id) AS order_count FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN products p ON p.id = oi.product_id WHERE p.vendor_id = ?');
$orders_stmt->bind_param('i', $vendor['id']);
$orders_stmt->execute();
$order_count = (int) $orders_stmt->get_result()->fetch_assoc()['order_count'];

$revenue_stmt = $conn->prepare('SELECT COALESCE(SUM(oi.line_total), 0) AS revenue FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN products p ON p.id = oi.product_id WHERE p.vendor_id = ? AND o.created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")');
$revenue_stmt->bind_param('i', $vendor['id']);
$revenue_stmt->execute();
$revenue_total = (float) $revenue_stmt->get_result()->fetch_assoc()['revenue'];

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<?php if ($kyc_alert): ?>
    <div style="margin: 0 0 20px; padding: 16px 18px; border-radius: 14px; border: 1px solid <?= $kyc_alert['type'] === 'warning' ? '#f59e0b' : '#3b82f6' ?>; background: <?= $kyc_alert['type'] === 'warning' ? '#fff7ed' : '#eff6ff' ?>; color: <?= $kyc_alert['type'] === 'warning' ? '#9a5b00' : '#1d4ed8' ?>; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div>
            <div style="font-weight: 700; margin-bottom: 4px;"><?= e($kyc_alert['title']) ?></div>
            <div style="font-size: 14px; opacity: 0.9;"><?= e($kyc_alert['message']) ?></div>
        </div>
        <a href="kyc.php" class="dashboard-btn dashboard-btn-primary" style="white-space: nowrap;">
            <i class="fas fa-id-card"></i> <?= $kyc_alert['type'] === 'warning' ? 'Complete KYC' : 'View KYC status' ?>
        </a>
    </div>
<?php endif; ?>
<div class="dashboard-hero">
    <h2>Run your e-commerce business from a polished control center.</h2>
    <p>Create listings, track orders, review earnings, and manage your profile from one place.</p>
    <p style="margin-top: 15px;">
        <a class="dashboard-btn dashboard-btn-primary" href="vendor-add-product.php">
            <i class="fas fa-plus"></i> Add new product
        </a>
        <a class="dashboard-btn" href="vendor-analytics.php" style="background: transparent; color: #fff; border: 2px solid #fff; margin-left: 10px;">
            <i class="fas fa-chart-pie"></i> Open analytics
        </a>
    </p>
</div>
<div class="dashboard-grid">
    <div class="dashboard-stat-card">
        <div class="icon blue">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="info">
            <h3><?= (int) $order_count ?></h3>
            <p>Orders tracked</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon green">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="info">
            <h3><?= e(format_price($revenue_total)) ?></h3>
            <p>Revenue this month</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon orange">
            <i class="fas fa-box"></i>
        </div>
        <div class="info">
            <h3><?= (int) $product_count ?></h3>
            <p>Active products</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon purple">
            <i class="fas fa-percent"></i>
        </div>
        <div class="info">
            <h3>4.8%</h3>
            <p>Conversion rate</p>
        </div>
    </div>
</div>
<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3 style="margin-top:0;">Quick actions</h3>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:15px;">
            <a class="dashboard-btn dashboard-btn-primary" href="vendor-add-product.php"><i class="fas fa-plus me-2"></i>Add Product</a>
            <a class="dashboard-btn" href="vendor-orders.php" style="background: #f3f4f6; color:#1f2937;"><i class="fas fa-shopping-cart me-2"></i>Manage Orders</a>
            <a class="dashboard-btn" href="vendor-earnings.php" style="background: #f3f4f6; color:#1f2937;"><i class="fas fa-money-bill me-2"></i>Review Earnings</a>
            <a class="dashboard-btn" href="vendor-account-settings.php" style="background: #f3f4f6; color:#1f2937;"><i class="fas fa-gear me-2"></i>Update Settings</a>
        </div>
    </div>
    <div class="dashboard-card">
        <h3 style="margin-top:0;">Recent activity</h3>
        <ul style="padding-left:18px; margin:0; color:#374151; margin-top:15px;">
            <li style="margin-bottom:15px;"><strong>New order received</strong><div style="font-size:13px; color:#6b7280; margin-top:4px;">Customer placed an order for two items.</div></li>
            <li style="margin-bottom:15px;"><strong>Inventory update</strong><div style="font-size:13px; color:#6b7280; margin-top:4px;">Product stock was refreshed for your premium range.</div></li>
            <li style="margin-bottom:0;"><strong>Store profile updated</strong><div style="font-size:13px; color:#6b7280; margin-top:4px;">Business details and delivery settings are current.</div></li>
        </ul>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
