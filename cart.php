<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

init_cart();
$page_title = 'Shopping Cart — ' . STORE_NAME;
$cart_items = $_SESSION['cart'] ?? [];
$subtotal = cart_total();
$shipping = 5000;
$total = $subtotal + $shipping;

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <div class="page-header">
        <h1>Shopping Cart</h1>
        <p><?= cart_count() ?> item<?= cart_count() !== 1 ? 's' : '' ?> in your cart</p>
    </div>

    <?php if (empty($cart_items)): ?>
    <div class="empty-state">
        <i class="fas fa-shopping-cart"></i>
        <h2>Your Cart is Empty</h2>
        <p>Looks like you haven't added anything yet. Explore our premium collection.</p>
        <a href="<?= APP_URL ?>/products.php" class="btn btn-primary">Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="cart-layout">
        <div class="cart-items">
            <?php foreach ($cart_items as $item): ?>
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="<?= e(image_url($item['image'] ?? '')) ?>" alt="<?= e($item['name']) ?>">
                </div>
                <div class="cart-item-info">
                    <h3><?= e($item['name']) ?></h3>
                    <span class="cart-item-sku">SKU: <?= e($item['sku'] ?? '') ?></span>
                    <div class="cart-item-price"><?= format_price($item['price'] * $item['quantity']) ?></div>
                    <span style="font-size:12px;color:#888;"><?= format_price($item['price']) ?> each</span>
                </div>
                <div class="cart-item-actions">
<form method="post" action="<?= APP_URL ?>/actions/cart.php">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">


                        <div class="qty-selector">
                            <button type="submit" name="quantity" value="<?= max(1, $item['quantity'] - 1) ?>" class="qty-btn" aria-label="Decrease" data-tooltip="Decrease quantity">−</button>
                            <span class="qty-input" style="display:flex;align-items:center;justify-content:center;border:none;"><?= (int) $item['quantity'] ?></span>
                            <button type="submit" name="quantity" value="<?= min($item['max_stock'] ?? 99, $item['quantity'] + 1) ?>" class="qty-btn" aria-label="Increase" data-tooltip="Increase quantity">+</button>
                        </div>
                    </form>
<form method="post" action="<?= APP_URL ?>/actions/cart.php">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                        <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                        <button type="submit" class="remove-btn" data-tooltip="Remove item from cart"><i class="fas fa-trash-alt"></i> Remove</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <h3>Order Summary</h3>
            <div class="summary-row">
                <span>Subtotal</span>
                <span><?= format_price($subtotal) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span class="<?= $shipping === 0 ? 'free' : '' ?>">
                    <?= $shipping === 0 ? 'FREE' : format_price($shipping) ?>
                </span>
            </div>
            <?php /* Promotional free-delivery text removed per rebrand */ ?>
            <div class="summary-row total">
                <span>Total</span>
                <span><?= format_price($total) ?></span>
            </div>
            <a href="<?= APP_URL ?>/checkout.php" class="btn btn-primary" style="width:100%;margin-top:20px;" data-tooltip="Proceed to checkout and place your order">
                Proceed to Checkout
            </a>
            <a href="<?= APP_URL ?>/products.php" class="btn btn-outline" style="width:100%;margin-top:10px;" data-tooltip="Continue browsing our products">
                Continue Shopping
            </a>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
