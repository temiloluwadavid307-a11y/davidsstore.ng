<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$page_title = 'Orders - Vendor Dashboard — ' . APP_NAME;
$page_name = 'Orders';
$user_role = 'vendor';
$user = $_SESSION['user'] ?? null;
$active_page = 'orders';
$logout_url = '../actions/logout.php';
$vendor = ensure_current_vendor();
if (!$vendor) {
    redirect(APP_URL . '/index.php');
}

// Fetch orders that include products from the logged-in vendor
$stmt = $conn->prepare("SELECT o.id, o.order_number, o.total, o.status, o.created_at, o.customer_name, o.customer_email
          FROM orders o
          JOIN order_items oi ON oi.order_id = o.id
          JOIN products p ON p.id = oi.product_id
          WHERE p.vendor_id = ?
          GROUP BY o.id
          ORDER BY o.created_at DESC");
$stmt->bind_param('i', $vendor['id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-table">
    <div class="dashboard-table-header">
        <h2>Orders</h2>
    </div>
    <div class="dashboard-table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" style="padding:18px;text-align:center;">No orders yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= e($order['order_number'] ?? $order['id']) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= format_price((float)$order['total']) ?></td>
                        <td><span class="status <?= e($order['status'] ?? 'pending') ?>"><?= e(ucfirst($order['status'] ?? 'pending')) ?></span></td>
                        <td><?= e(date('M d, Y', strtotime($order['created_at']))) ?></td>
                        <td>
                            <form method="post" action="../actions/dashboard_actions.php" style="display:inline-block;">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <input type="hidden" name="status" value="processing">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                <button type="submit" class="action-btn primary">Process</button>
                            </form>
                            <form method="post" action="../actions/dashboard_actions.php" style="display:inline-block;">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                <input type="hidden" name="status" value="shipped">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                <button type="submit" class="action-btn view">Ship</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
