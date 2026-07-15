<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';
$pathParts = explode('/', trim($path, '/'));

if ($path === '/' && $method === 'POST') {
    $user = get_current_user($conn);
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $customerResult = $conn->query("SELECT * FROM customers WHERE user_id = " . $user['id']);
    $customer = $customerResult->fetch_assoc();
    if (!$customer) {
        json_response(['error' => 'Only customers can create orders'], 403);
    }

    $cartResult = $conn->query("SELECT * FROM carts WHERE customer_id = {$customer['id']} AND status = 'active'");
    $cart = $cartResult->fetch_assoc();
    if (!$cart) {
        json_response(['error' => 'No active cart'], 400);
    }

    $itemsResult = $conn->query("SELECT * FROM cart_items WHERE cart_id = {$cart['id']}");
    $items = [];
    $subtotal = 0;
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
        $subtotal += $item['unit_price'] * $item['quantity'];
    }

    if (empty($items)) {
        json_response(['error' => 'Cart is empty'], 400);
    }

    $conn->begin_transaction();
    try {
        foreach ($items as $item) {
            $productResult = $conn->query("SELECT * FROM products WHERE id = {$item['product_id']}");
            $product = $productResult->fetch_assoc();
            $vendorId = $product['vendor_id'];

            $totalAmount = $subtotal;
            $conn->query("
                INSERT INTO orders (customer_id, vendor_id, status, payment_status, subtotal, total_amount)
                VALUES ({$customer['id']}, $vendorId, 'pending', 'pending', $subtotal, $totalAmount)
            ");
            $orderId = $conn->insert_id;

            $lineTotal = $item['unit_price'] * $item['quantity'];
            $conn->query("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total)
                VALUES ($orderId, {$item['product_id']}, {$item['quantity']}, {$item['unit_price']}, $lineTotal)
            ");
        }

        $conn->query("UPDATE carts SET status = 'converted' WHERE id = {$cart['id']}");
        $conn->commit();
        json_response(['message' => 'Order created', 'order_id' => $orderId], 201);
    } catch (Exception $e) {
        $conn->rollback();
        json_response(['error' => 'Order failed', 'details' => $e->getMessage()], 500);
    }
} elseif ($path === '/' && $method === 'GET') {
    $user = get_current_user($conn);
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $roleResult = $conn->query("SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = " . $user['id']);
    $roleData = $roleResult->fetch_assoc();
    $role = $roleData['name'];

    if ($role === 'customer') {
        $customerResult = $conn->query("SELECT * FROM customers WHERE user_id = " . $user['id']);
        $customer = $customerResult->fetch_assoc();
        $ordersResult = $conn->query("SELECT * FROM orders WHERE customer_id = {$customer['id']} ORDER BY created_at DESC");
    } elseif ($role === 'vendor') {
        $vendorResult = $conn->query("SELECT * FROM vendors WHERE user_id = " . $user['id']);
        $vendor = $vendorResult->fetch_assoc();
        $ordersResult = $conn->query("SELECT * FROM orders WHERE vendor_id = {$vendor['id']} ORDER BY created_at DESC");
    } else {
        $ordersResult = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
    }

    $orders = [];
    while ($order = $ordersResult->fetch_assoc()) {
        $orderId = $order['id'];
        $itemsResult = $conn->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $orderId");
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
        $order['items'] = $items;
        $orders[] = $order;
    }

    json_response($orders);
} elseif (count($pathParts) === 1 && $method === 'GET') {
    $orderId = intval($pathParts[0]);
    $result = $conn->query("SELECT * FROM orders WHERE id = $orderId");
    $order = $result->fetch_assoc();
    if (!$order) {
        json_response(['error' => 'Order not found'], 404);
    }
    $itemsResult = $conn->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $orderId");
    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    $order['items'] = $items;
    json_response($order);
} else {
    json_response(['error' => 'Not found'], 404);
}
?>
