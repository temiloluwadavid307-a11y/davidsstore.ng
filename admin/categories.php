<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Categories — ' . APP_NAME;
$page_name = 'Categories';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'categories';
$logout_url = APP_URL . '/actions/logout.php';

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedCategory = null;
if ($id) {
    $stmt = $conn->prepare('SELECT id, name, is_active FROM categories WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $selectedCategory = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $name = sanitize($_POST['name'] ?? '');
    $is_active = !empty($_POST['is_active']) ? 1 : 0;
    $category_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'delete' && $category_id) {
        $delete = $conn->prepare('DELETE FROM categories WHERE id = ?');
        $delete->bind_param('i', $category_id);
        $delete->execute();
        set_flash('success', 'Category deleted.');
        redirect(APP_URL . '/admin/categories.php');
    }

    if (empty($name)) {
        $errors[] = 'Category name is required.';
    }

    if (empty($errors)) {
        if ($category_id) {
            $update = $conn->prepare('UPDATE categories SET name = ?, is_active = ? WHERE id = ?');
            $update->bind_param('sii', $name, $is_active, $category_id);
            $update->execute();
            set_flash('success', 'Category updated.');
        } else {
            $insert = $conn->prepare('INSERT INTO categories (name, is_active, created_at) VALUES (?, ?, NOW())');
            $insert->bind_param('si', $name, $is_active);
            $insert->execute();
            set_flash('success', 'Category created.');
        }
        redirect(APP_URL . '/admin/categories.php');
    }
}

$categories = $conn->query('SELECT id, name, is_active, created_at FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-table" style="grid-column:span 2;">
        <div class="dashboard-table-header">
            <h2>Active Categories</h2>
        </div>
        <div class="dashboard-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="4" style="padding:18px;text-align:center;">No categories found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><span class="dashboard-badge" style="<?= $row['is_active'] ? 'background:#dcfce7;color:#166534;' : 'background:#fef3c7;color:#92400e;' ?>"><?= $row['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td><?= e(date('M d, Y', strtotime($row['created_at']))) ?></td>
                            <td>
                                <a href="categories.php?id=<?= (int)$row['id'] ?>" class="action-btn">Edit</a>
                                <form method="post" style="display:inline-block;" onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="action-btn" style="background:#dc2626; color:white; border:none;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dashboard-card" style="grid-column:span 1;">
        <h2><?= $selectedCategory ? 'Edit Category' : 'Add Category' ?></h2>
        <?php if (!empty($errors)): ?>
            <div class="flash-message error" style="margin-bottom:16px;">
                <i class="fas fa-exclamation-circle"></i>
                <ul style="margin:0; padding-left:20px;">
                    <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" style="display:flex; flex-direction:column; gap:14px;">
            <input type="hidden" name="id" value="<?= (int)($selectedCategory['id'] ?? 0) ?>">
            <div>
                <label style="display:block; font-weight:600; margin-bottom:6px;">Category name</label>
                <input type="text" name="name" value="<?= e($_POST['name'] ?? $selectedCategory['name'] ?? '') ?>" required style="width:100%; padding:10px 14px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
            </div>
            <label style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="is_active" <?= !empty($_POST['is_active']) || (!isset($_POST['is_active']) && !empty($selectedCategory['is_active'])) ? 'checked' : '' ?>>
                Active
            </label>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <button type="submit" class="dashboard-btn dashboard-btn-primary">Save Category</button>
                <?php if ($selectedCategory): ?>
                    <a href="categories.php" class="dashboard-btn" style="background:#f3f4f6; color:#1f2937;">Cancel</a>
                    <button type="submit" name="action" value="delete" class="dashboard-btn" style="background:#dc2626; color:#fff;" onclick="return confirm('Delete this category?');">Delete</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
