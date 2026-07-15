<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    redirect(APP_URL . '/login.php');
}

$user = current_user();
if (($user['role'] ?? 'customer') !== 'vendor') {
    redirect(APP_URL . '/index.php');
}

$page_key = $_GET['page'] ?? 'products';
$pages = [
    'products' => [
        'title' => 'Products',
        'heading' => 'Your product catalogue',
        'subtitle' => 'Curate your assortment and keep inventory fresh for customers.',
        'body' => '<div class="card-list"><div class="content-box"><h6>Featured collection</h6><p class="text-muted mb-0">Premium dresses, accessories and seasonal essentials are ready to showcase.</p></div><div class="content-box"><h6>Inventory watch</h6><p class="text-muted mb-0">12 items need attention due to low stock and pricing updates.</p></div><div class="content-box"><h6>Quick link</h6><p class="mb-0"><a class="text-orange" href="'.APP_URL.'/vendor/section.php?page=add-product">Add another product</a></p></div></div>',
    ],
    'add-product' => [
        'title' => 'Add Product',
        'heading' => 'Create a new product listing',
        'subtitle' => 'Add rich details for your store so buyers can discover and purchase with confidence.',
        'body' => '<div class="content-box"><h6>Product setup checklist</h6><ul class="mb-0"><li>Upload high-quality images</li><li>Set a clear price and stock level</li><li>Add a short description and category</li><li>Publish to make it visible in store</li></ul></div>',
    ],
    'orders' => [
        'title' => 'Orders',
        'heading' => 'Recent orders',
        'subtitle' => 'Stay on top of every purchase and fulfillment milestone.',
        'body' => '<div class="table-wrap"><table class="table table-hover"><thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th></tr></thead><tbody><tr><td>#DS-1001</td><td>Ada</td><td>₦ 92,500</td><td><span class="status-chip pending">Pending</span></td></tr><tr><td>#DS-1002</td><td>Grace</td><td>₦ 64,000</td><td><span class="status-chip shipped">Shipped</span></td></tr><tr><td>#DS-1003</td><td>Daniel</td><td>₦ 118,000</td><td><span class="status-chip delivered">Delivered</span></td></tr></tbody></table></div>',
    ],
    'earnings' => [
        'title' => 'Earnings',
        'heading' => 'Sales overview',
        'subtitle' => 'Review your revenue performance and payout readiness.',
        'body' => '<div class="stats-row"><div class="content-box"><h6>Month to date</h6><p class="display-6 mb-0">₦ 1.2M</p></div><div class="content-box"><h6>Pending payout</h6><p class="display-6 mb-0">₦ 250K</p></div><div class="content-box"><h6>Commission</h6><p class="display-6 mb-0">₦ 96K</p></div></div>',
    ],
    'analytics' => [
        'title' => 'Analytics',
        'heading' => 'Store analytics',
        'subtitle' => 'Track product performance, visitor engagement, and sales momentum.',
        'body' => '<div class="stats-row"><div class="content-box"><h6>Visitors</h6><p class="display-6 mb-0">3,820</p></div><div class="content-box"><h6>Best seller</h6><p class="display-6 mb-0">Luxury Bag</p></div><div class="content-box"><h6>Retention</h6><p class="display-6 mb-0">68%</p></div></div>',
    ],
    'account-settings' => [
        'title' => 'Account Settings',
        'heading' => 'Vendor account settings',
        'subtitle' => 'Keep your business profile, delivery preferences, and payment details up to date.',
        'body' => '<div class="content-box"><h6>Business profile</h6><p class="text-muted mb-0">Update your store name, contact info, payout method and shipping preferences from one place.</p></div>',
    ],
];

$active = $pages[$page_key] ?? $pages['products'];
$page_title = $active['title'] . ' - Vendor Dashboard - ' . APP_NAME;
$vendor_name = $user['name'] ?: 'Vendor';
$vendor_email = $user['email'] ?: 'vendor@example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/platform.css">
    <style>
        body { background: #f4f6fb; color: #1f2937; }
        .vendor-shell { display: flex; min-height: 100vh; }
        .sidebar { width: 270px; background: linear-gradient(180deg, #111827, #1f2937); color: #fff; padding: 24px 0; }
        .sidebar .brand { padding: 0 24px 24px; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.8); padding: 13px 24px; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background: rgba(246, 139, 30, 0.18); color: #fff; border-left: 3px solid #F68B1E; }
        .main-content { flex: 1; padding: 24px; }
        .top-bar { background: #fff; border-radius: 18px; padding: 20px 24px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .content-card { background: #fff; border-radius: 18px; padding: 24px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
        .card-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .content-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .table-wrap { overflow-x: auto; }
        .status-chip { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .status-chip.pending { background: #fff7ed; color: #c2410c; }
        .status-chip.shipped { background: #eff6ff; color: #1d4ed8; }
        .status-chip.delivered { background: #ecfdf5; color: #047857; }
        .text-orange { color: #F68B1E; }
        @media (max-width: 900px) { .vendor-shell { flex-direction: column; } .sidebar { width: 100%; } }
    </style>
</head>
<body>
<div class="vendor-shell">
    <aside class="sidebar">
        <div class="brand">Vendor Hub</div>
        <nav>
            <a href="<?= APP_URL ?>/vendor/index.php"><i class="fa-solid fa-gauge-high"></i> Overview</a>
            <a class="<?= $page_key === 'products' ? 'active' : '' ?>" href="<?= APP_URL ?>/vendor/section.php?page=products"><i class="fa-solid fa-boxes-stacked"></i> Products</a>
            <a class="<?= $page_key === 'add-product' ? 'active' : '' ?>" href="<?= APP_URL ?>/vendor/section.php?page=add-product"><i class="fa-solid fa-circle-plus"></i> Add Product</a>
            <a class="<?= $page_key === 'orders' ? 'active' : '' ?>" href="<?= APP_URL ?>/vendor/section.php?page=orders"><i class="fa-solid fa-basket-shopping"></i> Orders</a>
            <a class="<?= $page_key === 'earnings' ? 'active' : '' ?>" href="<?= APP_URL ?>/vendor/section.php?page=earnings"><i class="fa-solid fa-sack-dollar"></i> Earnings</a>
            <a class="<?= $page_key === 'analytics' ? 'active' : '' ?>" href="<?= APP_URL ?>/vendor/section.php?page=analytics"><i class="fa-solid fa-chart-simple"></i> Analytics</a>
            <a class="<?= $page_key === 'account-settings' ? 'active' : '' ?>" href="<?= APP_URL ?>/vendor/section.php?page=account-settings"><i class="fa-solid fa-gear"></i> Account Settings</a>
            <a href="<?= APP_URL ?>/index.php"><i class="fa-solid fa-house"></i> Back to Store</a>
            <a href="<?= APP_URL ?>/actions/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <div>
                <h4 class="mb-1">Vendor Dashboard</h4>
                <p class="text-muted mb-0">Welcome back, <?= e($vendor_name) ?></p>
            </div>
            <div class="text-end">
                <div class="fw-bold"><?= e($vendor_email) ?></div>
                <small class="text-muted">Verified vendor account</small>
            </div>
        </div>

        <div class="content-card">
            <h3 class="mb-1"><?= e($active['heading']) ?></h3>
            <p class="text-muted"><?= e($active['subtitle']) ?></p>
            <?= $active['body'] ?>
        </div>
    </main>
</div>
</body>
</html>
