<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$page_title = 'Add Product - Vendor Dashboard — ' . APP_NAME;
$page_name = 'Add Product';
$user_role = 'vendor';
$user = $_SESSION['user'] ?? null;
$active_page = 'add-product';
$logout_url = APP_URL . '/actions/logout.php';
$vendor = ensure_current_vendor();
if (!$vendor) {
    redirect(APP_URL . '/index.php');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = null;
if ($id) {
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if ($product && (int)($product['vendor_id'] ?? 0) !== (int)$vendor['id']) {
        set_flash('error', 'This product does not belong to your store.');
        redirect(APP_URL . '/vendor/vendor-my-products.php');
    }
}
$cats = $conn->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$brands = $conn->query('SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect(APP_URL . '/vendor/vendor-add-product.php' . ($id ? '?id=' . $id : ''));
    }

    $name = sanitize($_POST['name'] ?? '');
    $sku = sanitize($_POST['sku'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $old_price = !empty($_POST['old_price']) ? (float) $_POST['old_price'] : null;
    $stock = max(0, (int) ($_POST['stock_quantity'] ?? 0));
    $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
    $brand_id = !empty($_POST['brand_id']) ? (int) $_POST['brand_id'] : null;
    $description = sanitize($_POST['description'] ?? '');
    $is_active = !empty($_POST['is_active']) ? 1 : 0;
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;

    if (empty($name) || $price <= 0) {
        set_flash('error', 'Please provide a product name and a valid price.');
    } else {
        $sku = $sku !== '' ? $sku : 'SKU-' . time();
        if ($id) {
            $stmt = $conn->prepare('UPDATE products SET category_id = ?, brand_id = ?, name = ?, sku = ?, description = ?, price = ?, old_price = ?, stock_quantity = ?, is_featured = ?, is_active = ? WHERE id = ?');
            $stmt->bind_param('iisssddiiii', $category_id, $brand_id, $name, $sku, $description, $price, $old_price, $stock, $is_featured, $is_active, $id);
            $stmt->execute();
        } else {
            $vendor_id = (int) $vendor['id'];
            $slug = slugify($name) . '-' . time();
            $stmt = $conn->prepare('INSERT INTO products (vendor_id, category_id, brand_id, name, slug, sku, description, price, old_price, stock_quantity, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iiissssddiii', $vendor_id, $category_id, $brand_id, $name, $slug, $sku, $description, $price, $old_price, $stock, $is_featured, $is_active);
            $stmt->execute();
            $id = $conn->insert_id;
        }

        if (!empty($_FILES['image_primary']['tmp_name'])) {
            $file = $_FILES['image_primary'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                set_flash('error', 'Primary image must be JPG, PNG or WebP.');
            } else {
                $filename = uniqid('img_') . '.' . $ext;
                $dest = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $image_path = 'uploads/' . $filename;
                    $img_stmt = $conn->prepare('UPDATE products SET image_primary = ? WHERE id = ?');
                    $img_stmt->bind_param('si', $image_path, $id);
                    $img_stmt->execute();
                }
            }
        }

        set_flash('success', $id ? 'Product saved.' : 'Product created.');
        redirect(APP_URL . '/vendor/vendor-my-products.php');
    }
}

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-card">
    <h2><?= $id ? 'Edit Product' : 'Add New Product' ?></h2>
    <p style="color:#6b7280; margin-bottom:24px;">Create or update a product listing for your store.</p>
    <form method="post" enctype="multipart/form-data" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:20px;">
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Product Name</label>
                <input type="text" name="name" required value="<?= e($_POST['name'] ?? $product['name'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Category</label>
                <select name="category_id" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
                    <option value="">Select Category</option>
                    <?php foreach ($cats as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Brand</label>
                <select name="brand_id" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
                    <option value="">Select Brand</option>
                    <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int)$brand['id'] ?>" <?= ((int)($product['brand_id'] ?? 0) === (int)$brand['id']) ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">SKU</label>
                <input type="text" name="sku" value="<?= e($_POST['sku'] ?? $product['sku'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Price (₦)</label>
                <input type="number" step="0.01" name="price" required value="<?= e($_POST['price'] ?? $product['price'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Sale Price (₦)</label>
                <input type="number" step="0.01" name="old_price" value="<?= e($_POST['old_price'] ?? $product['old_price'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Stock Quantity</label>
                <input type="number" name="stock_quantity" value="<?= e($_POST['stock_quantity'] ?? $product['stock_quantity'] ?? 0) ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Product Description</label>
                <textarea name="description" rows="4" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;"><?= e($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
            </div>
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Primary Image</label>
                <input type="file" name="image_primary" accept="image/*" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div style="grid-column:span 2; display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="is_featured" <?= !empty($_POST['is_featured']) || (!empty($product['is_featured']) && $product['is_featured']) ? 'checked' : '' ?>> Featured</label>
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="is_active" checked> Active</label>
            </div>
            <div style="grid-column:span 2; display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                <a href="vendor-my-products.php" class="dashboard-btn" style="background:#f3f4f6; color:#1f2937;">Cancel</a>
                <button type="submit" class="dashboard-btn dashboard-btn-primary"><i class="fas fa-check"></i> Save Product</button>
            </div>
        </div>
    </form>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
