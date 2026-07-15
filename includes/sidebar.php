<?php
/**
 * Shared Sidebar (Role-based)
 */
$user_role = $user_role ?? 'admin';
$active_page = $active_page ?? 'dashboard';
$nav_items = [];

if ($user_role === 'admin') {
    $nav_items = [
        ['id' => 'dashboard', 'name' => 'Dashboard', 'url' => 'index.php', 'icon' => 'fas fa-home'],
        ['id' => 'products', 'name' => 'Products', 'url' => 'products.php', 'icon' => 'fas fa-box'],
        ['id' => 'categories', 'name' => 'Categories', 'url' => 'categories.php', 'icon' => 'fas fa-list'],
        ['id' => 'orders', 'name' => 'Orders', 'url' => 'orders.php', 'icon' => 'fas fa-shopping-cart'],
        ['id' => 'users', 'name' => 'Users', 'url' => 'users.php', 'icon' => 'fas fa-users'],
        ['id' => 'statistics', 'name' => 'Statistics', 'url' => 'statistics.php', 'icon' => 'fas fa-chart-line'],
        ['id' => 'messages', 'name' => 'Messages', 'url' => 'messages.php', 'icon' => 'fas fa-envelope'],
    ];
} else if ($user_role === 'vendor') {
    $nav_items = [
        ['id' => 'dashboard', 'name' => 'Dashboard', 'url' => 'index.php', 'icon' => 'fas fa-home'],
        ['id' => 'my-products', 'name' => 'My Products', 'url' => 'vendor-my-products.php', 'icon' => 'fas fa-box'],
        ['id' => 'add-product', 'name' => 'Add Product', 'url' => 'vendor-add-product.php', 'icon' => 'fas fa-plus'],
        ['id' => 'orders', 'name' => 'Orders', 'url' => 'vendor-orders.php', 'icon' => 'fas fa-shopping-cart'],
        ['id' => 'earnings', 'name' => 'Earnings', 'url' => 'vendor-earnings.php', 'icon' => 'fas fa-wallet'],
        ['id' => 'analytics', 'name' => 'Analytics', 'url' => 'vendor-analytics.php', 'icon' => 'fas fa-chart-pie'],
        ['id' => 'account', 'name' => 'Account Settings', 'url' => 'vendor-account-settings.php', 'icon' => 'fas fa-cog'],
    ];
}
?>
        <aside class="dashboard-sidebar" data-dashboard-sidebar>
            <div class="brand">
                <i class="fas fa-crown"></i>
                <span>CodesbyDavid</span>
                <button class="dashboard-close" type="button" data-dashboard-close aria-label="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav>
                <?php foreach ($nav_items as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $active_page === $item['id'] ? 'active' : ''; ?>">
                        <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <?php echo htmlspecialchars($item['name']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
