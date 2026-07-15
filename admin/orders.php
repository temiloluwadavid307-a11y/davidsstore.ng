<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Orders — ' . APP_NAME;
$page_name = 'Orders';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'orders';
$logout_url = '../actions/logout.php';

$query = "SELECT o.id, o.order_number, o.total, o.status, o.created_at, o.customer_name, o.customer_email, o.shipping_city, o.shipping_state, o.payment_method
          FROM orders o
          ORDER BY o.created_at DESC";
$orders = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-table">
    <div class="dashboard-table-header">
        <h2>Recent Orders</h2>
    </div>
    <div class="dashboard-table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" style="padding:18px;text-align:center;">No orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= e($order['order_number'] ?? $order['id']) ?></td>
                        <td><?= e($order['customer_name']) ?> <br><small style="color:#6b7280;"><?= e($order['customer_email']) ?></small></td>
                        <td><?= format_price((float)$order['total']) ?></td>
                        <td><span class="status <?= e($order['status'] ?? 'pending') ?>"><?= e(ucfirst($order['status'] ?? 'pending')) ?></span></td>
                        <td><?= e(date('M d, Y', strtotime($order['created_at']))) ?></td>
                        <td><a href="order_detail.php?id=<?= (int)$order['id'] ?>" class="action-btn view">View details</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
