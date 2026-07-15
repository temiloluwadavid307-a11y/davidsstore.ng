<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$page_title = 'My Products - Vendor Dashboard — ' . APP_NAME;
$page_name = 'My Products';
$user_role = 'vendor';
$user = $_SESSION['user'] ?? null;
$active_page = 'my-products';
$logout_url = '../actions/logout.php';
$vendor = ensure_current_vendor();
if (!$vendor) {
    redirect(APP_URL . '/index.php');
}

// Fetch only products belonging to the logged-in vendor
$stmt = $conn->prepare("SELECT p.id, p.name, p.sku, p.price, p.old_price, p.stock_quantity, p.is_active, p.image_primary, c.name AS category_name, b.name AS brand_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN brands b ON p.brand_id = b.id
          WHERE p.vendor_id = ?
          ORDER BY p.created_at DESC");
$stmt->bind_param('i', $vendor['id']);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-table">
    <div class="dashboard-table-header">
        <h2>My Products</h2>
        <a href="vendor-add-product.php" class="dashboard-btn dashboard-btn-primary"><i class="fas fa-plus"></i> Add Product</a>
    </div>
    <div class="dashboard-table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="8" style="padding:18px;text-align:center;">No products yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <div class="dashboard-product-row">
                                <img src="<?= e(image_url($p['image_primary'] ?? '')) ?>" alt="" onerror="this.style.display='none'">
                                <span><?= e($p['name']) ?></span>
                            </div>
                        </td>
                        <td><?= e($p['sku']) ?></td>
                        <td><?= format_price((float)$p['price']) ?></td>
                        <td><?= (int)$p['stock_quantity'] ?></td>
                        <td><?= e($p['category_name']) ?></td>
                        <td><?= e($p['brand_name']) ?></td>
                        <td><span class="dashboard-badge <?= $p['is_active'] ? 'green' : 'yellow' ?>"><?= $p['is_active'] ? 'Active' : 'Hidden' ?></span></td>
                        <td>
                            <a href="vendor-add-product.php?id=<?= (int)$p['id'] ?>" class="action-btn edit">Edit</a>
                            <a href="../product.php?id=<?= (int)$p['id'] ?>" class="action-btn view">View</a>
                            <form method="post" action="../actions/dashboard_actions.php" style="display:inline-block;" onsubmit="return confirm('Update this product visibility?');">
                                <input type="hidden" name="action" value="toggle_product_status">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="redirect" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                <button type="submit" class="action-btn <?= $p['is_active'] ? 'delete' : 'primary' ?>"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
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
