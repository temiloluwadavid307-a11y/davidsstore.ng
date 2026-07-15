<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/admin/products.php');
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Invalid product id.');
    redirect(APP_URL . '/admin/products.php');
}

// Delete images from filesystem
$imgStmt = $conn->prepare('SELECT image_url FROM product_images WHERE product_id = ?');
$imgStmt->bind_param('i', $id);
$imgStmt->execute();
$imgs = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($imgs as $img) {
    $path = ROOT_PATH . DIRECTORY_SEPARATOR . ltrim($img['image_url'], '/');
    if (file_exists($path)) @unlink($path);
}

// Delete primary image file
$pStmt = $conn->prepare('SELECT image_primary FROM products WHERE id = ?');
$pStmt->bind_param('i', $id);
$pStmt->execute();
$prow = $pStmt->get_result()->fetch_assoc();
if (!empty($prow['image_primary'])) {
    $pPath = ROOT_PATH . DIRECTORY_SEPARATOR . ltrim($prow['image_primary'], '/');
    if (file_exists($pPath)) @unlink($pPath);
}

// Delete DB records
$delImgs = $conn->prepare('DELETE FROM product_images WHERE product_id = ?');
$delImgs->bind_param('i', $id);
$delImgs->execute();

$del = $conn->prepare('DELETE FROM products WHERE id = ?');
$del->bind_param('i', $id);
$del->execute();

set_flash('success', 'Product deleted.');
redirect(APP_URL . '/admin/products.php');
