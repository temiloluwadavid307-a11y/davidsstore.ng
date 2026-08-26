<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/index.php');
}

if (!validate_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid request. Please try again.');
    redirect(APP_URL . '/index.php');
}

$redirect = $_POST['redirect'] ?? APP_URL . '/index.php';
$action = $_POST['action'] ?? '';
$user = current_user();
$vendor = null;
if (($user['role'] ?? '') === 'vendor') {
    $vendor = ensure_current_vendor();
}

switch ($action) {
    case 'toggle_product_status':
        if (($user['role'] ?? '') !== 'vendor' && !is_admin()) {
            set_flash('error', 'You are not allowed to perform this action.');
            redirect($redirect);
        }
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $stmt = $conn->prepare('SELECT is_active, vendor_id FROM products WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($vendor && (!empty($row['vendor_id']) && (int)$row['vendor_id'] !== (int)$vendor['id'])) {
            set_flash('error', 'You can only update products from your own store.');
            redirect($redirect);
        }
        $new_status = empty($row['is_active']) ? 1 : 0;
        $upd = $conn->prepare('UPDATE products SET is_active = ? WHERE id = ?');
        $upd->bind_param('ii', $new_status, $product_id);
        $upd->execute();
        set_flash('success', 'Product status updated.');
        break;

    case 'update_order_status':
        if (($user['role'] ?? '') !== 'vendor' && !is_admin()) {
            set_flash('error', 'You are not allowed to perform this action.');
            redirect($redirect);
        }
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'pending');
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }
        if ($vendor) {
            $check = $conn->prepare('SELECT 1 FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ? AND p.vendor_id = ? LIMIT 1');
            $check->bind_param('ii', $order_id, $vendor['id']);
            $check->execute();
            if ($check->get_result()->num_rows < 1) {
                set_flash('error', 'You can only manage orders for your own products.');
                redirect($redirect);
            }
        }
        $stmt = $conn->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('si', $status, $order_id);
        $stmt->execute();
        // Notify customer about status change
        send_order_status_email($order_id, $status);
        set_flash('success', 'Order status updated.');
        break;

    case 'update_vendor_profile':
        if (($user['role'] ?? '') !== 'vendor') {
            set_flash('error', 'Vendor access required.');
            redirect($redirect);
        }
        $store_name = sanitize($_POST['store_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        if (!$vendor) {
            set_flash('error', 'Vendor account not found.');
            redirect($redirect);
        }
        $stmt = $conn->prepare('UPDATE vendors SET name = ?, phone = ? WHERE id = ?');
        $stmt->bind_param('ssi', $store_name, $phone, $vendor['id']);
        $stmt->execute();
        set_flash('success', 'Store profile updated.');
        break;

    default:
        set_flash('error', 'Unknown action.');
}

redirect($redirect);
