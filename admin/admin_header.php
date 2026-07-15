<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../includes/config.php';
}
$activePage = basename($_SERVER['PHP_SELF']);
$navItems = [
    'index.php' => ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'tooltip' => 'View admin dashboard'],
    'products.php' => ['label' => 'Products', 'icon' => 'fa-boxes-stacked', 'tooltip' => 'Manage products'],
    'categories.php' => ['label' => 'Categories', 'icon' => 'fa-tags', 'tooltip' => 'Manage product categories'],
    'orders.php' => ['label' => 'Orders', 'icon' => 'fa-basket-shopping', 'tooltip' => 'View and manage orders'],
    'messages.php' => ['label' => 'Messages', 'icon' => 'fa-envelope', 'tooltip' => 'View customer messages'],
    'users.php' => ['label' => 'Users', 'icon' => 'fa-users', 'tooltip' => 'Manage site users'],
    'statistics.php' => ['label' => 'Statistics', 'icon' => 'fa-chart-simple', 'tooltip' => 'View sales statistics'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Admin') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f5f7; color: #1f2937; }
        .admin-shell { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #111827; color: #e5e7eb; position: fixed; inset: 0 auto 0 0; overflow-y: auto; }
        .admin-sidebar .brand { padding: 24px; display: flex; align-items: center; gap: 12px; font-size: 1.1rem; font-weight: 800; letter-spacing: -0.5px; }
        .admin-sidebar .brand span { color: #f59e0b; }
        .admin-sidebar nav a { display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: inherit; transition: background 0.2s; }
        .admin-sidebar nav a.active, .admin-sidebar nav a:hover { background: rgba(244, 115, 20, 0.16); color: #ffffff; }
        .admin-sidebar nav a i { width: 20px; text-align: center; }
        .admin-main { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }
        .admin-topbar { background: #ffffff; border-bottom: 1px solid #e5e7eb; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; }
        .admin-topbar .breadcrumbs { font-size: 13px; color: #6b7280; }
        .admin-topbar .admin-actions button { border: none; background: #f59e0b; color: #fff; padding: 10px 18px; border-radius: 10px; cursor: pointer; }
        .admin-content { padding: 24px; width: 100%; }
        .admin-card { background: #ffffff; border-radius: 18px; box-shadow: 0 16px 40px rgba(15,23,42,0.08); padding: 24px; }
        .flash-banner { padding: 16px 24px; margin: 18px 24px 0; border-radius: 14px; font-weight: 600; }
        .flash-banner.success { background: #d1fae5; color: #065f46; }
        .flash-banner.error { background: #fee2e2; color: #991b1b; }
        .admin-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
        .admin-card h2 { margin-top: 0; }
        @media(max-width: 992px) { .admin-shell { flex-direction: column; } .admin-sidebar { position: relative; width: 100%; } .admin-main { margin-left: 0; } .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar" aria-label="Admin navigation">
        <div class="brand"><i class="fas fa-store"></i><span>David's Admin</span></div>
        <nav>
            <?php foreach ($navItems as $file => $item): ?>
                <a href="<?= APP_URL ?>/admin/<?= $file ?>" class="<?= $activePage === $file ? 'active' : '' ?>" data-tooltip="<?= e($item['tooltip']) ?>">
                    <i class="fas <?= $item['icon'] ?>"></i><span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
            <a href="<?= APP_URL ?>/index.php" data-tooltip="Return to main storefront"><i class="fas fa-arrow-left"></i><span>Back to Store</span></a>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <div>
                <div class="breadcrumbs">Admin Panel / <?= e($navItems[$activePage]['label'] ?? 'Dashboard') ?></div>
                <h1 style="margin: 0; font-size: 24px; font-weight: 700;"><?= e($page_title ?? 'Admin') ?></h1>
            </div>
            <div class="admin-actions">
                <a href="<?= APP_URL ?>/actions/logout.php"><button type="button">Logout</button></a>
            </div>
        </header>
        <?php if ($flash = get_flash()): ?>
            <div class="flash-banner <?= e($flash['type']) ?>" role="status">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        <main class="admin-content">
