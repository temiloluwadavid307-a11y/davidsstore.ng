<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Order Details — ' . APP_NAME;
$page_name = 'Orders';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'orders';
$logout_url = APP_URL . '/actions/logout.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    redirect(APP_URL . '/admin/orders.php');
}

$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    redirect(APP_URL . '/admin/orders.php');
}

$itemsStmt = $conn->prepare('SELECT product_name, product_sku, quantity, unit_price, line_total FROM order_items WHERE order_id = ?');
$itemsStmt->bind_param('i', $order_id);
$itemsStmt->execute();
$order_items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-card" style="margin-bottom:20px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
            <h2>Order #<?= e($order['order_number'] ?? $order['id']) ?></h2>
            <p style="margin:6px 0; color:#6b7280;">Placed on <?= e(date('M d, Y', strtotime($order['created_at']))) ?> · Status: <strong><?= e(ucfirst($order['status'])) ?></strong></p>
        </div>
        <div style="text-align:right;">
            <p style="margin:0; font-size:1.1rem; font-weight:700;">Total: <?= format_price((float)$order['total']) ?></p>
            <p style="margin:6px 0 0; color:#6b7280;">Payment: <?= e(ucfirst($order['payment_method'] ?? 'N/A')) ?></p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3>Customer</h3>
        <p><strong><?= e($order['customer_name']) ?></strong></p>
        <p><a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></p>
        <?php if (!empty($order['customer_phone'])): ?>
            <p><?= e($order['customer_phone']) ?></p>
        <?php endif; ?>
    </div>
    <div class="dashboard-card">
        <h3>Shipping Address</h3>
        <p><?= nl2br(e($order['shipping_address'])) ?></p>
        <p><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?></p>
    </div>
</div>

<div class="dashboard-table" style="margin-top:20px;">
    <div class="dashboard-table-header">
        <h2>Items</h2>
    </div>
    <div class="dashboard-table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($order_items)): ?>
                <tr><td colspan="5" style="padding:18px; text-align:center;">No order items found.</td></tr>
                <?php else: ?>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td><?= e($item['product_sku']) ?></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td><?= format_price((float)$item['unit_price']) ?></td>
                            <td><?= format_price((float)$item['line_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="dashboard-card" style="margin-top:20px; display:flex; justify-content:flex-end; gap:24px; flex-wrap:wrap;">
    <div style="min-width:180px;">
        <p style="margin:0; color:#6b7280;">Subtotal</p>
        <p style="font-weight:700; font-size:1.1rem; margin:4px 0 0;"><?= format_price((float)$order['subtotal']) ?></p>
    </div>
    <div style="min-width:180px;">
        <p style="margin:0; color:#6b7280;">Shipping</p>
        <p style="font-weight:700; font-size:1.1rem; margin:4px 0 0;"><?= format_price((float)($order['shipping_fee'] ?? 0)) ?></p>
    </div>
    <div style="min-width:180px;">
        <p style="margin:0; color:#6b7280;">Total</p>
        <p style="font-weight:700; font-size:1.3rem; margin:4px 0 0; color:#1f2937;"><?= format_price((float)$order['total']) ?></p>
    </div>
</div>

<div style="margin-top:24px;">
    <a href="orders.php" class="dashboard-btn" style="background:#f3f4f6; color:#1f2937;">Back to Orders</a>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
