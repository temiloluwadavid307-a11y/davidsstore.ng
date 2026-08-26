<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Product Form — ' . APP_NAME;
$page_name = 'Products';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'products';
$logout_url = APP_URL . '/actions/logout.php';

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load categories and brands
$cats = $conn->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$brands = $conn->query('SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name')->fetch_all(MYSQLI_ASSOC);

$product = null;
if ($id) {
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    $imgStmt = $conn->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order');
    $imgStmt->bind_param('i', $id);
    $imgStmt->execute();
    $product_images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $product_images = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $sku = sanitize($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $old_price = $_POST['old_price'] !== '' ? floatval($_POST['old_price']) : null;
    $stock = intval($_POST['stock_quantity'] ?? 0);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $description = sanitize($_POST['description'] ?? '');
    $features = sanitize($_POST['features'] ?? '');
    $specs = sanitize($_POST['specifications'] ?? '');
    $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
    $is_active = !empty($_POST['is_active']) ? 1 : 0;

    if (empty($name)) $errors[] = 'Product name is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than zero.';

    if (empty($errors)) {
        $sku = trim($sku);
        if ($sku === '') {
            $sku = 'SKU-' . time();
        }

        $skuCheck = $id
            ? $conn->prepare('SELECT id FROM products WHERE sku = ? AND id != ? LIMIT 1')
            : $conn->prepare('SELECT id FROM products WHERE sku = ? LIMIT 1');
        if ($id) {
            $skuCheck->bind_param('si', $sku, $id);
        } else {
            $skuCheck->bind_param('s', $sku);
        }
        $skuCheck->execute();
        $skuCheck->store_result();

        if ($skuCheck->num_rows > 0) {
            $errors[] = 'SKU already exists. Please choose a different SKU.';
        }
    }

    if (empty($errors)) {
        // determine vendor id (use first vendor or 1)
        $vRes = $conn->query('SELECT id FROM vendors LIMIT 1');
        $vRow = $vRes ? $vRes->fetch_assoc() : null;
        $vendor_id = $vRow['id'] ?? 1;

        $slug = slugify($name) . '-' . time();

        try {
            if ($id) {
                $update = $conn->prepare('UPDATE products SET category_id = ?, brand_id = ?, name = ?, sku = ?, description = ?, features = ?, specifications = ?, price = ?, old_price = ?, stock_quantity = ?, is_featured = ?, is_active = ? WHERE id = ?');
                $update->bind_param('iisssssddiiii', $category_id, $brand_id, $name, $sku, $description, $features, $specs, $price, $old_price, $stock, $is_featured, $is_active, $id);
                $update->execute();
                $product_id = $id;
            } else {
                $insert = $conn->prepare('INSERT INTO products (vendor_id, category_id, brand_id, name, slug, sku, description, features, specifications, price, old_price, stock_quantity, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $insert->bind_param('iiissssssddiii', $vendor_id, $category_id, $brand_id, $name, $slug, $sku, $description, $features, $specs, $price, $old_price, $stock, $is_featured, $is_active);
                $insert->execute();
                $product_id = $conn->insert_id;
            }
        } catch (mysqli_sql_exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'for key sku')) {
                $errors[] = 'SKU already exists. Please choose a different SKU.';
            } else {
                throw $e;
            }
        }

        // Handle primary image upload
        if (!empty($_FILES['image_primary']['tmp_name'])) {
            $file = $_FILES['image_primary'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array(strtolower($ext), $allowed)) {
                $errors[] = 'Primary image must be JPG/PNG/WEBP.';
            } else {
                $filename = uniqid('img_') . '.' . $ext;
                $dest = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $image_path = 'uploads/' . $filename;
                    $upd = $conn->prepare('UPDATE products SET image_primary = ? WHERE id = ?');
                    $upd->bind_param('si', $image_path, $product_id);
                    $upd->execute();
                }
            }
        }

        // Handle additional images
        if (!empty($_FILES['images'])) {
            foreach ($_FILES['images']['tmp_name'] as $idx => $tmp) {
                if (empty($tmp)) continue;
                $name_orig = $_FILES['images']['name'][$idx];
                $ext = pathinfo($name_orig, PATHINFO_EXTENSION);
                $allowed = ['jpg','jpeg','png','webp'];
                if (!in_array(strtolower($ext), $allowed)) continue;
                $filename = uniqid('img_') . '.' . $ext;
                $dest = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;
                if (move_uploaded_file($tmp, $dest)) {
                    $img_url = 'uploads/' . $filename;
                    $alt = $conn->real_escape_string(pathinfo($name_orig, PATHINFO_FILENAME));
                    $conn->query("INSERT INTO product_images (product_id, image_url, alt_text, sort_order) VALUES ($product_id, '$img_url', '$alt', 0)");
                }
            }
        }

        set_flash('success', $id ? 'Product updated.' : 'Product created.');
        redirect(APP_URL . '/admin/products.php');
    }
}

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <h2><?= $id ? 'Edit Product' : 'Add Product' ?></h2>
        <a href="products.php" class="dashboard-btn" style="background:#f3f4f6; color:#1f2937;">Back</a>
    </div>

    <?php if ($errors): ?>
        <div class="flash-message error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin:0; padding-left:20px;">
                <?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:16px;">
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Product Name</label>
                <input type="text" name="name" required value="<?= e($_POST['name'] ?? $product['name'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">SKU</label>
                <input type="text" name="sku" value="<?= e($_POST['sku'] ?? $product['sku'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Category</label>
                <select name="category_id" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
                    <option value="">-- Select --</option>
                    <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ((int)($product['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Brand</label>
                <select name="brand_id" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
                    <option value="">-- Select --</option>
                    <?php foreach ($brands as $b): ?>
                    <option value="<?= (int)$b['id'] ?>" <?= ((int)($product['brand_id'] ?? 0) === (int)$b['id']) ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Price</label>
                <input type="number" step="0.01" name="price" required value="<?= e($_POST['price'] ?? $product['price'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Old Price</label>
                <input type="number" step="0.01" name="old_price" value="<?= e($_POST['old_price'] ?? $product['old_price'] ?? '') ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Stock Quantity</label>
                <input type="number" name="stock_quantity" value="<?= e($_POST['stock_quantity'] ?? $product['stock_quantity'] ?? 0) ?>" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Primary Image</label>
                <input type="file" name="image_primary" accept="image/*" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
                <?php if (!empty($product['image_primary'])): ?>
                    <div style="margin-top:8px;">
                        <img src="<?= e(image_url($product['image_primary'])) ?>" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                    </div>
                <?php endif; ?>
            </div>
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Additional Images (multiple)</label>
                <input type="file" name="images[]" accept="image/*" multiple style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
                <?php if (!empty($product_images)): ?>
                    <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
                        <?php foreach ($product_images as $img): ?>
                            <div style="width:80px; height:80px; overflow:hidden; border-radius:6px; border:1px solid #eee;">
                                <img src="<?= e(image_url($img['image_url'])) ?>" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="grid-column:span 2;">
                <label style="display:block; font-weight:600; margin-bottom:6px;">Description</label>
                <textarea name="description" rows="5" style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;"><?= e($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
            </div>
            <div style="grid-column:span 2; display:flex; gap:20px; align-items:center; margin-top:10px;">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="is_featured" <?= !empty($_POST['is_featured']) || (!empty($product['is_featured']) && $product['is_featured']) ? 'checked' : '' ?>> Featured
                </label>
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="is_active" <?= isset($_POST['is_active']) ? 'checked' : ((!isset($_POST['is_active']) && !empty($product['is_active'])) ? 'checked' : '') ?>> Active
                </label>
            </div>
            <div style="grid-column:span 2; display:flex; justify-content:flex-end; margin-top:16px;">
                <button type="submit" class="dashboard-btn dashboard-btn-primary">Save Product</button>
            </div>
        </div>
    </form>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
