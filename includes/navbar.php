<?php
/**
 * Site navigation bar
 */
if (!function_exists('cart_count')) {
    require_once __DIR__ . '/functions.php';
}
$user = current_user();
$categories = get_storefront_categories();
$search_query = $_GET['q'] ?? '';
?>
<header>
    <div class="container">
        <nav class="main-nav" aria-label="Main navigation">
            <a href="<?= APP_URL ?>/index.php" class="logo" aria-label="<?= STORE_NAME ?>">
                <img src="<?= APP_URL ?>/assets/images/swagbag-logo.svg" alt="<?= STORE_NAME ?>" style="height:40px; display:inline-block; vertical-align:middle;">
            </a>

            <form class="search-container" action="<?= APP_URL ?>/products.php" method="get" role="search">
                <span class="search-icon"><i class="fas fa-search" aria-hidden="true"></i></span>
                <input type="search" name="q" value="<?= e($search_query) ?>" placeholder="Search products, brands and categories" aria-label="Search products" data-tooltip="Search our product catalog">
                <button type="submit" class="search-btn" data-tooltip="Search for products">Search</button>
            </form>

            <div class="nav-icons">
                <?php if ($user): ?>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= APP_URL ?>/admin/index.php" class="nav-item" data-tooltip="Go to Admin Dashboard">
                    <i class="far fa-user" aria-hidden="true"></i>
                    <span><?= e($user['name']) ?></span>
                </a>
                <?php elseif ($user['role'] === 'vendor'): ?>
                <a href="<?= APP_URL ?>/vendor/index.php" class="nav-item" data-tooltip="Go to Vendor Dashboard">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    <span>Vendor</span>
                </a>
                <?php else: ?>
                <a href="<?= APP_URL ?>/customer/index.php" class="nav-item" data-tooltip="View your account">
                    <i class="far fa-user" aria-hidden="true"></i>
                    <span><?= e($user['name']) ?></span>
                </a>
                <a href="<?= APP_URL ?>/vendor/become-vendor.php" class="nav-item" data-tooltip="Apply to become a vendor">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    <span>Become a Vendor</span>
                </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/actions/logout.php" class="nav-item" data-tooltip="Sign out of your account">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span>Logout</span>
                </a>
                <?php else: ?>
                <a href="<?= APP_URL ?>/login.php" class="nav-item <?= is_active('login') ?>" data-tooltip="Sign in to your account">
                    <i class="far fa-user" aria-hidden="true"></i>
                    <span>Login</span>
                </a>
                <a href="<?= APP_URL ?>/signup.php" class="nav-item <?= is_active('signup') ?>" data-tooltip="Create a new account">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    <span>Sign Up</span>
                </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/cart.php" class="nav-item <?= is_active('cart') ?>" data-tooltip="View your shopping cart">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    <span>Cart</span>
                    <span class="cart-count" id="cartCount"><?= cart_count() ?></span>
                </a>
            </div>

            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle menu" aria-expanded="false" data-tooltip="Toggle mobile menu">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </div>

    <nav class="category-nav" id="categoryNav" aria-label="Categories">
        <div class="container">
            <ul class="category-nav-list">
                <li><a href="<?= APP_URL ?>/products.php" class="<?= empty($_GET['category'] ?? '') && current_page() === 'products' ? 'active' : '' ?>">All Products</a></li>
                <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="<?= APP_URL ?>/products.php?category=<?= e($cat['slug']) ?>"
                       class="<?= ($_GET['category'] ?? '') === $cat['slug'] ? 'active' : '' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <li><a href="<?= APP_URL ?>/about.php" class="<?= is_active('about') ?>">About</a></li>
                <li><a href="<?= APP_URL ?>/contact.php" class="<?= is_active('contact') ?>">Contact</a></li>
            </ul>
        </div>
    </nav>
</header>
