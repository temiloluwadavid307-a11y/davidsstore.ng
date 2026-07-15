<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../includes/config.php';
}
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$user = current_user();
$activePage = basename($_SERVER['PHP_SELF']);
$navItems = [
    'index.php' => ['label' => 'Overview', 'icon' => 'fa-gauge-high', 'tooltip' => 'View vendor dashboard overview'],
    'vendor-my-products.php' => ['label' => 'Products', 'icon' => 'fa-boxes-stacked', 'tooltip' => 'Manage your products'],
    'vendor-add-product.php' => ['label' => 'Add Product', 'icon' => 'fa-circle-plus', 'tooltip' => 'Add a new product'],
    'vendor-orders.php' => ['label' => 'Orders', 'icon' => 'fa-basket-shopping', 'tooltip' => 'View and manage your orders'],
    'vendor-earnings.php' => ['label' => 'Earnings', 'icon' => 'fa-sack-dollar', 'tooltip' => 'View your earnings history'],
    'vendor-analytics.php' => ['label' => 'Analytics', 'icon' => 'fa-chart-simple', 'tooltip' => 'View sales analytics'],
    'vendor-account-settings.php' => ['label' => 'Account Settings', 'icon' => 'fa-gear', 'tooltip' => 'Update your account settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Vendor Dashboard') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg:#f4f6fb; --surface:#fff; --border:#e5e7eb; --text:#111827; --muted:#6b7280; --primary:#f59e0b; --primary-dark:#d97706; --accent:#111827; }
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { margin:0; background:var(--bg); color:var(--text); }
        .vendor-shell { display:flex; min-height:100vh; }
        .vendor-sidebar { width:270px; background:linear-gradient(180deg, var(--accent), #1f2937); color:#f9fafb; position:sticky; top:0; min-height:100vh; }
        .vendor-sidebar .brand { padding:24px; display:flex; align-items:center; gap:12px; font-weight:800; font-size:1.05rem; letter-spacing:-0.02em; }
        .vendor-sidebar .brand span { color:#fbbf24; }
        .vendor-sidebar nav a { display:flex; align-items:center; gap:12px; padding:13px 24px; color:inherit; text-decoration:none; transition:background .2s ease; }
        .vendor-sidebar nav a.active, .vendor-sidebar nav a:hover { background:rgba(245,158,11,.16); color:#fff; border-left:3px solid var(--primary); }
        .vendor-sidebar nav a i { width:18px; text-align:center; }
        .vendor-main { flex:1; display:flex; flex-direction:column; }
        .vendor-topbar { background:var(--surface); border-bottom:1px solid var(--border); padding:18px 24px; display:flex; justify-content:space-between; align-items:center; }
        .vendor-topbar .breadcrumbs { color:var(--muted); font-size:13px; margin-bottom:4px; }
        .vendor-topbar .meta { text-align:right; }
        .vendor-topbar .meta .name { font-weight:700; }
        .vendor-content { padding:24px; }
        .vendor-card { background:var(--surface); border:1px solid var(--border); border-radius:18px; box-shadow:0 12px 28px rgba(15,23,42,.05); padding:24px; }
        .vendor-grid { display:grid; gap:20px; grid-template-columns:repeat(3,minmax(0,1fr)); }
        .stats-grid { display:grid; gap:16px; grid-template-columns:repeat(4,minmax(0,1fr)); margin-bottom:20px; }
        .stat-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:18px; }
        .stat-card .value { font-size:1.4rem; font-weight:700; margin-top:6px; }
        .btn-primary { border:none; background:var(--primary); color:#fff; padding:10px 16px; border-radius:10px; text-decoration:none; display:inline-block; }
        .btn-primary:hover { background:var(--primary-dark); color:#fff; }
        .btn-outline { border:1px solid var(--border); background:#fff; color:var(--text); padding:10px 16px; border-radius:10px; text-decoration:none; display:inline-block; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 10px; border-bottom:1px solid #f1f5f9; text-align:left; }
        .pill { display:inline-block; padding:6px 10px; background:#fff7ed; color:#c2410c; border-radius:999px; font-size:12px; font-weight:700; }
        .hero { background:linear-gradient(135deg,#111827,#1f2937); color:#fff; border-radius:24px; padding:24px; margin-bottom:20px; }
        .hero .btn { margin-top:10px; margin-right:10px; }
        .muted { color:var(--muted); }
        @media(max-width:992px){ .vendor-shell{flex-direction:column;} .vendor-sidebar{width:100%; min-height:auto;} .vendor-grid{grid-template-columns:1fr;} .stats-grid{grid-template-columns:1fr 1fr;} }
        @media(max-width:640px){ .stats-grid{grid-template-columns:1fr;} .vendor-topbar{flex-direction:column; align-items:flex-start; gap:10px;} }
    </style>
</head>
<body>
<div class="vendor-shell">
    <aside class="vendor-sidebar" aria-label="Vendor navigation">
        <div class="brand"><i class="fas fa-store"></i><span>Vendor Panel</span></div>
        <nav>
            <?php foreach ($navItems as $file => $item): ?>
                <a href="<?= APP_URL ?>/vendor/<?= $file ?>" class="<?= $activePage === $file ? 'active' : '' ?>" data-tooltip="<?= e($item['tooltip']) ?>">
                    <i class="fas <?= $item['icon'] ?>"></i><span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <a href="<?= APP_URL ?>/index.php" data-tooltip="Return to main storefront"><i class="fas fa-arrow-left"></i><span>Back to Store</span></a>
        </nav>
    </aside>
    <div class="vendor-main">
        <header class="vendor-topbar">
            <div>
                <div class="breadcrumbs">Vendor Dashboard / <?= e($navItems[$activePage]['label'] ?? 'Overview') ?></div>
                <h1 style="margin:0; font-size:24px; font-weight:700;"><?= e($page_title ?? 'Vendor Dashboard') ?></h1>
            </div>
            <div class="meta" style="display:flex; gap:12px; align-items:center;">
                <div style="text-align:right;">
                    <div class="name"><?= e($user['name'] ?? 'Vendor') ?></div>
                    <div class="muted"><?= e($user['email'] ?? '') ?></div>
                </div>
                <a href="<?= APP_URL ?>/actions/logout.php"><button type="button" style="border: none; background: var(--primary); color: #fff; padding: 10px 18px; border-radius: 10px; cursor: pointer;">Logout</button></a>
            </div>
        </header>
        <?php if ($flash = get_flash()): ?>
            <div class="flash-banner <?= e($flash['type']) ?>" role="status" style="padding: 16px 24px; margin: 18px 24px 0; border-radius: 14px; font-weight: 600; <?= $flash['type'] === 'success' ? 'background: #d1fae5; color: #065f46;' : 'background: #fee2e2; color: #991b1b;' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        <main class="vendor-content">
