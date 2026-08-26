<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? APP_URL . '/cart.php';

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash('error', 'Invalid request. Please try again.');
        redirect($redirect);
    }
}

switch ($action) {
    case 'add':
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if (add_to_cart($product_id, $quantity)) {
            set_flash('success', 'Product added to cart!');
        } else {
            set_flash('error', 'Could not add product to cart. It may be out of stock.');
        }
        break;

    case 'update':
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        update_cart_item($product_id, $quantity);
        set_flash('success', 'Cart updated.');
        $redirect = APP_URL . '/cart.php';
        break;

    case 'remove':
        $product_id = (int) ($_POST['product_id'] ?? 0);
        remove_from_cart($product_id);
        set_flash('success', 'Item removed from cart.');
        $redirect = APP_URL . '/cart.php';
        break;

    case 'clear':
        clear_cart();
        set_flash('success', 'Cart cleared.');
        $redirect = APP_URL . '/cart.php';
        break;
}

redirect($redirect);
