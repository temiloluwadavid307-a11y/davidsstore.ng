<?php
require_once 'config.php';
session_start();

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';
$pathParts = explode('/', trim($path, '/'));

function get_or_create_cart($conn) {
    $user = get_current_user($conn);
    $customer = null;
    $cart = null;

    if ($user) {
        $customerResult = $conn->query("SELECT * FROM customers WHERE user_id = " . $user['id']);
        $customer = $customerResult->fetch_assoc();
        if ($customer) {
            $cartResult = $conn->query("SELECT * FROM carts WHERE customer_id = {$customer['id']} AND status = 'active'");
            $cart = $cartResult->fetch_assoc();
        }
    }

    if (!$cart) {
        $sessionId = session_id();
        $cartResult = $conn->query("SELECT * FROM carts WHERE session_id = '$sessionId' AND status = 'active'");
        $cart = $cartResult->fetch_assoc();
    }

    if (!$cart) {
        $sessionId = session_id();
        $customerId = $customer ? $customer['id'] : 'NULL';
        $expiresAt = date('Y-m-d H:i:s', time() + 3600 * 24 * 7);
        $conn->query("INSERT INTO carts (customer_id, session_id, status, expires_at) VALUES ($customerId, '$sessionId', 'active', '$expiresAt')");
        $cartId = $conn->insert_id;
        $cartResult = $conn->query("SELECT * FROM carts WHERE id = $cartId");
        $cart = $cartResult->fetch_assoc();
    }

    return $cart;
}

if ($path === '/' && $method === 'GET') {
    $cart = get_or_create_cart($conn);
    $cartId = $cart['id'];

    $itemsResult = $conn->query("
        SELECT ci.*, p.name, p.price 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.cart_id = $cartId
    ");
    $items = [];
    $subtotal = 0;
    while ($row = $itemsResult->fetch_assoc()) {
        $items[] = $row;
        $subtotal += $row['unit_price'] * $row['quantity'];
    }

    json_response(['cart' => $cart, 'items' => $items, 'subtotal' => $subtotal]);
} elseif ($path === '/items' && $method === 'POST') {
    $cart = get_or_create_cart($conn);
    $cartId = $cart['id'];
    $input = get_input();
    $productId = intval($input['product_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);

    $productResult = $conn->query("SELECT * FROM products WHERE id = $productId");
    $product = $productResult->fetch_assoc();
    if (!$product) {
        json_response(['error' => 'Product not found'], 404);
    }

    $existingResult = $conn->query("SELECT * FROM cart_items WHERE cart_id = $cartId AND product_id = $productId");
    $existing = $existingResult->fetch_assoc();

    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        $conn->query("UPDATE cart_items SET quantity = $newQty WHERE id = {$existing['id']}");
    } else {
        $conn->query("INSERT INTO cart_items (cart_id, product_id, quantity, unit_price) VALUES ($cartId, $productId, $quantity, {$product['price']})");
    }

    json_response(['message' => 'Item added to cart']);
} elseif (count($pathParts) === 2 && $pathParts[0] === 'items' && $method === 'DELETE') {
    $cart = get_or_create_cart($conn);
    $cartId = $cart['id'];
    $itemId = intval($pathParts[1]);
    $conn->query("DELETE FROM cart_items WHERE id = $itemId AND cart_id = $cartId");
    json_response(['message' => 'Item removed']);
} elseif (count($pathParts) === 2 && $pathParts[0] === 'items' && $method === 'PUT') {
    $cart = get_or_create_cart($conn);
    $cartId = $cart['id'];
    $itemId = intval($pathParts[1]);
    $input = get_input();
    $quantity = intval($input['quantity'] ?? 1);
    $conn->query("UPDATE cart_items SET quantity = $quantity WHERE id = $itemId AND cart_id = $cartId");
    json_response(['message' => 'Item updated']);
} else {
    json_response(['error' => 'Not found'], 404);
}
?>
