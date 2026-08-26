<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Manage Products — ' . APP_NAME;
$page_name = 'Products';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'products';
$logout_url = APP_URL . '/actions/logout.php';

// Fetch products
$stmt = $conn->prepare("SELECT p.id, p.name, p.sku, p.price, p.old_price, p.stock_quantity, p.is_active, p.image_primary, c.name AS category_name, b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-table">
    <div class="dashboard-table-header">
        <h2>Products</h2>
        <a href="product_form.php" class="dashboard-btn dashboard-btn-primary">
            <i class="fas fa-plus"></i> Add Product
        </a>
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
                <tr><td colspan="8" style="padding:24px;text-align:center;">No products yet.</td></tr>
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
                            <a href="product_form.php?id=<?= (int)$p['id'] ?>" class="action-btn edit">Edit</a>
                            <form method="post" action="product_delete.php" style="display:inline-block;" onsubmit="return confirm('Delete this product?');">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="action-btn delete">Delete</button>
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
