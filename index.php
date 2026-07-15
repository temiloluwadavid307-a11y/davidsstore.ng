<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = APP_NAME . ' — Premium Fashion & Lifestyle';
$page_description = 'Shop premium hoodies, sneakers, streetwear and accessories. Free delivery on orders over ' . format_price(FREE_DELIVERY_THRESHOLD);

// Fetch homepage data
$featured = get_products(['limit' => 8, 'sort' => 'popular']);
$flash_sale = get_products(['limit' => 8, 'sort' => 'discount']);
$new_arrivals = get_products(['limit' => 8, 'sort' => 'newest']);
$categories = get_categories();

// Filter featured products
global $conn;
$featured_stmt = $conn->query('
    SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    WHERE p.is_active = 1 AND p.is_featured = 1
    ORDER BY p.rating DESC LIMIT 8
');
$featured_products = $featured_stmt ? $featured_stmt->fetch_all(MYSQLI_ASSOC) : $featured;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <!-- Hero Section -->
    <section class="hero">
        <aside class="category-sidebar" aria-label="Shop categories">
            <ul>
                <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="<?= APP_URL ?>/products.php?category=<?= e($cat['slug']) ?>">
                        <i class="fas <?= e($cat['icon'] ?? 'fa-tshirt') ?>"></i>
                        <?= e($cat['name']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <div class="hero-banner">
            <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=900&h=500&fit=crop" alt="Premium Fashion Collection">
            <div class="hero-content">
                <h1>Premium Streetwear</h1>
                <p>Curated fashion for the modern Nigerian lifestyle</p>
                <a href="<?= APP_URL ?>/products.php" class="cta-btn">Shop Collection</a>
            </div>
        </div>
        <div class="hero-promo-cards">
            <div class="promo-card">
                <img src="https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=400&h=300&fit=crop" alt="Hoodies Collection">
                <span class="promo-card-label">Hoodies</span>
            </div>
            <div class="promo-card">
                <img src="https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&h=300&fit=crop" alt="Sneakers Collection">
                <span class="promo-card-label">Sneakers</span>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <?php if ($featured_products): ?>
    <section class="section-container" style="background: linear-gradient(135deg, #fff 0%, #fff4e6 100%);">
        <div class="section-header" style="border-bottom: none;">
            <div class="section-title" style="color: var(--primary-orange);">
                <i class="fas fa-heart"></i> Handpicked For You
            </div>
            <span style="font-size: 13px; color: #666;">Premium selections</span>
        </div>
        <div class="product-grid">
            <?php foreach ($featured_products as $product): ?>
                <?= render_product_card($product) ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Flash Sales -->
    <section class="section-container">
        <div class="section-header flash-sales-header">
            <div class="section-title">
                <i class="fas fa-bolt" style="color: var(--primary-orange);"></i> FLASH SALE
            </div>
            <div class="countdown" id="flashCountdown">
                Ends In:
                <div class="time-box time-h">04</div>h :
                <div class="time-box time-m">15</div>m :
                <div class="time-box time-s">45</div>s
            </div>
            <a href="<?= APP_URL ?>/products.php?sort=discount" class="section-link" style="color: var(--primary-orange);">SEE ALL DEALS &gt;</a>
        </div>
        <div class="product-grid">
            <?php foreach ($flash_sale as $product): ?>
                <?= render_product_card($product) ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Promo Banner -->
    <section class="promo-banner-full">
        <img src="https://images.unsplash.com/photo-1469334031218-e382a71b716b?w=1200&h=300&fit=crop" alt="New Season Collection">
        <div class="promo-content">
            <h2>New Season Drops</h2>
            <p>Up to 30% off on selected premium pieces</p>
            <a href="<?= APP_URL ?>/products.php" class="cta-btn">SHOP NOW</a>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="section-container">
        <div class="section-header">
            <div class="section-title">New Arrivals</div>
            <a href="<?= APP_URL ?>/products.php?sort=newest" class="section-link">SEE ALL &gt;</a>
        </div>
        <div class="product-grid">
            <?php foreach ($new_arrivals as $product): ?>
                <?= render_product_card($product) ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Category Highlights -->
    <section class="section-container">
        <div class="section-header">
            <div class="section-title">Shop By Category</div>
        </div>
        <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
            <?php foreach ($categories as $cat): ?>
            <a href="<?= APP_URL ?>/products.php?category=<?= e($cat['slug']) ?>" class="product-card" style="text-align: center; padding: 24px 16px;">
                <i class="fas <?= e($cat['icon'] ?? 'fa-tshirt') ?>" style="font-size: 32px; color: var(--primary-orange); margin-bottom: 12px;"></i>
                <h3 class="product-name" style="height: auto;"><?= e($cat['name']) ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter-banner">
        <h2>Stay Ahead of the Drop</h2>
        <p>Get exclusive access to new collections, early sales, and style inspiration.</p>
        <form class="newsletter-inline" action="<?= APP_URL ?>/actions/newsletter.php" method="post">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit">NOTIFY ME</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
