<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_customer();

$user = current_user();

$page_key = $_GET['page'] ?? 'orders';
$pages = [
    'orders' => [
        'title' => 'My Orders',
        'heading' => 'Your order history',
        'subtitle' => 'Track recent purchases and delivery progress from one place.',
        'body' => '',
    ],
    'wishlist' => [
        'title' => 'Wishlist',
        'heading' => 'Saved favorites',
        'subtitle' => 'Keep your favorite pieces handy for later.',
        'body' => '<div class="card border-0 shadow-sm p-4"><h5 class="mb-2">Wishlist is ready</h5><p class="text-muted mb-3">Save your favorite hoodies, denim, streetwear, and accessories to come back to anytime.</p><a href="' . APP_URL . '/products.php" class="btn btn-primary">Explore the latest drops</a></div>',
    ],
    'addresses' => [
        'title' => 'Addresses',
        'heading' => 'Saved addresses',
        'subtitle' => 'Manage delivery details for faster checkout.',
        'body' => '<div class="alert alert-info mb-0">No saved addresses yet. Add your preferred delivery location to make checkout quicker.</div>',
    ],
    'settings' => [
        'title' => 'Account Settings',
        'heading' => 'Account settings',
        'subtitle' => 'Keep your account information current and secure.',
        'body' => '<div class="card border-0 shadow-sm p-4"><h5 class="mb-2">Secure account overview</h5><p class="text-muted mb-3">Your profile, contact details, and preferences are protected and ready to manage.</p><a href="' . APP_URL . '/customer/index.php" class="btn btn-outline-secondary">Back to dashboard</a></div>',
    ],
];

$active = $pages[$page_key] ?? $pages['orders'];
$page_title = $active['title'] . ' - Customer Dashboard - ' . APP_NAME;
$customer_name = $user['name'] ?? 'Customer';
$customer_email = $user['email'] ?? 'customer@example.com';

$content_html = $active['body'];
if ($page_key === 'orders') {
    $status_map = [
        'pending' => ['label' => 'Pending', 'class' => 'badge-warning'],
        'processing' => ['label' => 'Processing', 'class' => 'badge-info'],
        'shipped' => ['label' => 'Shipped', 'class' => 'badge-primary'],
        'delivered' => ['label' => 'Delivered', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'badge-danger'],
    ];

    $stmt = $conn->prepare('SELECT id, order_number, subtotal, shipping_fee, total, status, payment_method, shipping_city, shipping_state, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($orders as &$order) {
        $item_stmt = $conn->prepare('SELECT product_name, quantity, unit_price, line_total FROM order_items WHERE order_id = ? ORDER BY id');
        $item_stmt->bind_param('i', $order['id']);
        $item_stmt->execute();
        $order['items'] = $item_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    unset($order);

    ob_start();
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="tracking-card">
                <div class="tracking-card-label">Total orders</div>
                <div class="tracking-card-value"><?= count($orders) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tracking-card">
                <div class="tracking-card-label">In progress</div>
                <div class="tracking-card-value"><?= count(array_filter($orders, fn($order) => in_array($order['status'], ['pending', 'processing', 'shipped'], true))) ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="tracking-card">
                <div class="tracking-card-label">Delivered</div>
                <div class="tracking-card-value"><?= count(array_filter($orders, fn($order) => $order['status'] === 'delivered')) ?></div>
            </div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
    <div class="alert alert-info mb-0">
        <strong>No recent orders yet.</strong> Once you place an order, it will appear here with live status updates and related items.
    </div>
    <?php else: ?>
    <div class="order-list">
        <?php foreach ($orders as $order): 
            $meta = $status_map[$order['status']] ?? ['label' => ucfirst($order['status']), 'class' => 'badge-secondary'];
            $payment_label = $order['payment_method'] === 'paystack' ? 'Card payment' : 'Pay on delivery';
        ?>
        <div class="order-card">
            <div class="order-card-head">
                <div>
                    <div class="order-number"><?= e($order['order_number']) ?></div>
                    <div class="order-date">Placed on <?= e(date('M d, Y', strtotime($order['created_at']))) ?></div>
                </div>
                <span class="badge <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span>
            </div>
            <div class="order-meta">
                <span><i class="fa-solid fa-credit-card"></i> <?= e($payment_label) ?></span>
                <span><i class="fa-solid fa-location-dot"></i> <?= e(trim(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_state'] ?? ''))) ?></span>
            </div>
            <div class="order-summary">
                <div>
                    <strong><?= count($order['items']) ?> item(s)</strong>
                    <div class="text-muted small">Your order includes the products below.</div>
                </div>
                <div class="text-end">
                    <div class="order-total"><?= format_price((float) $order['total']) ?></div>
                    <div class="text-muted small">Shipping: <?= format_price((float) ($order['shipping_fee'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="order-items">
                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item-row">
                    <div>
                        <div class="fw-semibold"><?= e($item['product_name']) ?></div>
                        <div class="text-muted small">Qty: <?= (int) $item['quantity'] ?></div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold"><?= format_price((float) $item['line_total']) ?></div>
                        <div class="text-muted small">Unit: <?= format_price((float) $item['unit_price']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php
    $content_html = ob_get_clean();
}
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
        body { background: #f5f5f5; }
        .customer-shell { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: #282828; color: #fff; padding: 24px 0; }
        .sidebar .brand { padding: 0 24px 20px; font-size: 1.1rem; font-weight: 700; letter-spacing: 0.08em; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.78); padding: 13px 24px; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background: #F68B1E; color: #fff; }
        .main-content { flex: 1; padding: 24px; }
        .top-bar { background: #fff; border-radius: 16px; padding: 20px 24px; box-shadow: 0 6px 20px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .content-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 6px 20px rgba(0,0,0,0.05); }
        .tracking-card { background: linear-gradient(135deg, #111827, #4f46e5); color: #fff; border-radius: 16px; padding: 16px 18px; }
        .tracking-card-label { font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.16em; opacity: 0.8; }
        .tracking-card-value { font-size: 1.6rem; font-weight: 700; margin-top: 6px; }
        .order-list { display: grid; gap: 16px; }
        .order-card { border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px; background: #fcfcfd; }
        .order-card-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 10px; }
        .order-number { font-weight: 700; font-size: 1rem; }
        .order-date { color: #6b7280; font-size: 0.9rem; margin-top: 3px; }
        .order-meta { display: flex; flex-wrap: wrap; gap: 14px; color: #4b5563; font-size: 0.92rem; margin-bottom: 12px; }
        .order-summary { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6; margin-bottom: 10px; }
        .order-total { font-size: 1.05rem; font-weight: 700; }
        .order-items { display: grid; gap: 10px; }
        .order-item-row { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .order-item-row:last-child { border-bottom: 0; padding-bottom: 0; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1d4ed8; }
        .badge-primary { background: #e0e7ff; color: #4338ca; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-secondary { background: #f3f4f6; color: #374151; }
        @media (max-width: 900px) { .customer-shell { flex-direction: column; } .sidebar { width: 100%; } .order-summary, .order-item-row { flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
<div class="customer-shell">
    <aside class="sidebar">
        <div class="brand">David's Store</div>
        <nav>
            <a href="<?= APP_URL ?>/customer/index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            <a class="<?= $page_key === 'orders' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/section.php?page=orders"><i class="fa-solid fa-basket-shopping"></i> My Orders</a>
            <a class="<?= $page_key === 'wishlist' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/section.php?page=wishlist"><i class="fa-solid fa-heart"></i> Wishlist</a>
            <a class="<?= $page_key === 'addresses' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/section.php?page=addresses"><i class="fa-solid fa-location-dot"></i> Addresses</a>
            <a class="<?= $page_key === 'settings' ? 'active' : '' ?>" href="<?= APP_URL ?>/customer/section.php?page=settings"><i class="fa-solid fa-gear"></i> Account Settings</a>
            <a href="<?= APP_URL ?>/index.php"><i class="fa-solid fa-house"></i> Return to Site</a>
            <a href="<?= APP_URL ?>/actions/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <div>
                <h4 class="mb-1">Welcome back, <?= e($customer_name) ?></h4>
                <p class="text-muted mb-0">Your David's Store account is ready for effortless shopping.</p>
            </div>
            <div class="text-end">
                <div class="fw-bold"><?= e($customer_email) ?></div>
                <small class="text-muted">Customer account • Premium shopping</small>
            </div>
        </div>

        <div class="content-card">
            <h3 class="mb-1"><?= e($active['heading']) ?></h3>
            <p class="text-muted"><?= e($active['subtitle']) ?></p>
            <?= $content_html ?>
        </div>
    </main>
</div>
</body>
</html>
