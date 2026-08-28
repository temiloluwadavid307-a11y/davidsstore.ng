<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$product = get_product($id);

if (!$product) {
    set_flash('error', 'Product not found.');
    redirect(APP_URL . '/products.php');
}

$page_title = e($product['name']) . ' — ' . STORE_NAME;
$discount = (int) ($product['discount_percent'] ?? 0);
$features = array_filter(explode('|', $product['features'] ?? ''));
$specs = array_filter(explode('|', $product['specifications'] ?? ''));
$images = $product['images'] ?? [];
$main_image = image_url($product['image_primary']);

// Related products
$related = get_products(['category' => $product['category_slug'], 'limit' => 4]);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <nav class="breadcrumb" style="padding-top: 20px;" aria-label="Breadcrumb">
        <a href="<?= APP_URL ?>/index.php">Home</a> &rsaquo;
        <a href="<?= APP_URL ?>/products.php">Products</a> &rsaquo;
        <?php if ($product['category_slug']): ?>
        <a href="<?= APP_URL ?>/products.php?category=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a> &rsaquo;
        <?php endif; ?>
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
        <div class="product-gallery">
            <div class="main-image">
                <img src="<?= e($main_image) ?>" alt="<?= e($product['name']) ?>" id="mainProductImage">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="thumbnail-list">
                <?php foreach ($images as $i => $img): ?>
                <div class="thumbnail <?= $i === 0 ? 'active' : '' ?>">
                    <img src="<?= e(image_url($img['image_url'])) ?>" alt="<?= e($img['alt_text'] ?? $product['name']) ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="product-detail-info">
            <p class="product-detail-brand"><?= e($product['brand_name'] ?? '') ?></p>
            <h1><?= e($product['name']) ?></h1>

            <div class="product-detail-rating">
                <?= render_stars((float) $product['rating']) ?>
                <span><?= (float) $product['rating'] ?> (<?= (int) $product['reviews_count'] ?> reviews)</span>
            </div>

            <div>
                <span class="product-detail-price"><?= format_price((float) $product['price']) ?></span>
                <?php if ($discount > 0 && !empty($product['old_price'])): ?>
                <span class="product-detail-old-price"><?= format_price((float) $product['old_price']) ?></span>
                <span class="product-detail-discount">-<?= $discount ?>%</span>
                <?php endif; ?>
            </div>

            <?php
            $avail = $product['availability'] ?? 'in_stock';
            $avail_labels = ['in_stock' => 'In Stock', 'low_stock' => 'Low Stock — Order Soon', 'out_of_stock' => 'Out of Stock'];
            ?>
            <div class="availability-badge <?= e($avail) ?>">
                <i class="fas fa-<?= $avail === 'in_stock' ? 'check-circle' : ($avail === 'low_stock' ? 'exclamation-circle' : 'times-circle') ?>"></i>
                <?= $avail_labels[$avail] ?? 'In Stock' ?>
            </div>

            <p style="font-size: 14px; color: #555; line-height: 1.7; margin-bottom: 8px;">
                <?= e(mb_substr($product['description'], 0, 200)) ?>...
            </p>

            <div class="product-meta">
                <span>SKU: <strong><?= e($product['sku']) ?></strong></span>
                <span>Category: <strong><?= e($product['category_name'] ?? '') ?></strong></span>
                <span>Brand: <strong><?= e($product['brand_name'] ?? '') ?></strong></span>
            </div>

            <?php if ($avail !== 'out_of_stock') : ?>
            <form method="post" action="<?= APP_URL ?>/actions/cart.php" class="product-actions">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                <div class="qty-selector">
                    <button type="button" class="qty-btn qty-minus" aria-label="Decrease quantity" data-tooltip="Decrease quantity">−</button>
                    <input type="number" name="quantity" class="qty-input" value="1" min="1" max="<?= (int) $product['stock_quantity'] ?>" aria-label="Quantity" data-tooltip="Set quantity">
                    <button type="button" class="qty-btn qty-plus" aria-label="Increase quantity" data-tooltip="Increase quantity">+</button>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" style="flex:1;" data-tooltip="Add this product to your cart">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </form>
            <?php else : ?>
            <button class="btn btn-secondary btn-lg" disabled style="width:100%;" data-tooltip="This product is out of stock">Out of Stock</button>
            <?php endif; ?>

            <div class="product-tabs">
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="tab-description">Description</button>
                    <button class="tab-btn" data-tab="tab-features">Features</button>
                    <button class="tab-btn" data-tab="tab-specs">Specifications</button>
                </div>
                <div class="tab-panel active" id="tab-description">
                    <p><?= nl2br(e($product['description'])) ?></p>
                </div>
                <div class="tab-panel" id="tab-features">
                    <?php if ($features): ?>
                    <ul class="feature-list">
                        <?php foreach ($features as $f): ?>
                        <li><?= e($f) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p>Premium quality craftsmanship with attention to every detail.</p>
                    <?php endif; ?>
                </div>
                <div class="tab-panel" id="tab-specs">
                    <?php if ($specs): ?>
                    <table class="specs-table">
                        <?php foreach ($specs as $spec): ?>
                        <?php $parts = explode(':', $spec, 2); ?>
                        <tr>
                            <td><?= e(trim($parts[0] ?? $spec)) ?></td>
                            <td><?= e(trim($parts[1] ?? '')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($related): ?>
    <section class="section-container" style="margin-bottom: 40px;">
        <div class="section-header">
            <div class="section-title">You May Also Like</div>
        </div>
        <div class="product-grid">
            <?php foreach ($related as $rel): ?>
                <?php if ($rel['id'] != $product['id']): ?>
                    <?= render_product_card($rel) ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
