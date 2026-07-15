<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';
$pathParts = explode('/', trim($path, '/'));

if ($path === '/' && $method === 'GET') {
    $result = $conn->query("
        SELECT p.*, v.store_name, c.name as category_name, b.name as brand_name 
        FROM products p 
        LEFT JOIN vendors v ON p.vendor_id = v.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE p.status = 'published'
    ");
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $productId = $row['id'];
        $mediaResult = $conn->query("SELECT * FROM product_images WHERE product_id = $productId ORDER BY sort_order");
        $media = [];
        while ($mediaRow = $mediaResult->fetch_assoc()) {
            $media[] = $mediaRow;
        }
        $row['media'] = $media;
        $products[] = $row;
    }
    json_response($products);
} elseif (count($pathParts) === 1 && $method === 'GET') {
    $productId = intval($pathParts[0]);
    $result = $conn->query("
        SELECT p.*, v.store_name, c.name as category_name, b.name as brand_name 
        FROM products p 
        LEFT JOIN vendors v ON p.vendor_id = v.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id 
        WHERE p.id = $productId
    ");
    $product = $result->fetch_assoc();
    if (!$product) {
        json_response(['error' => 'Product not found'], 404);
    }
    $mediaResult = $conn->query("SELECT * FROM product_images WHERE product_id = $productId ORDER BY sort_order");
    $media = [];
    while ($mediaRow = $mediaResult->fetch_assoc()) {
        $media[] = $mediaRow;
    }
    $product['media'] = $media;
    json_response($product);
} elseif ($path === '/' && $method === 'POST') {
    $user = get_current_user($conn);
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $vendorResult = $conn->query("SELECT * FROM vendors WHERE user_id = " . $user['id']);
    $vendor = $vendorResult->fetch_assoc();
    if (!$vendor) {
        json_response(['error' => 'Only vendors can add products'], 403);
    }

    $input = get_input();
    $name = $conn->real_escape_string($input['name'] ?? '');
    $description = $conn->real_escape_string($input['description'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $stockQuantity = intval($input['stock_quantity'] ?? 0);
    $status = $conn->real_escape_string($input['status'] ?? 'draft');
    $categoryId = isset($input['category_id']) ? intval($input['category_id']) : 'NULL';
    $brandId = isset($input['brand_id']) ? intval($input['brand_id']) : 'NULL';
    $slug = strtolower(str_replace(' ', '-', $name)) . '-' . time();

    $conn->query("
        INSERT INTO products (vendor_id, name, slug, description, price, stock_quantity, status, category_id, brand_id)
        VALUES ({$vendor['id']}, '$name', '$slug', '$description', $price, $stockQuantity, '$status', $categoryId, $brandId)
    ");
    $productId = $conn->insert_id;

    if (isset($input['media']) && is_array($input['media'])) {
        foreach ($input['media'] as $index => $mediaItem) {
            $url = $conn->real_escape_string($mediaItem['url'] ?? '');
            $altText = $conn->real_escape_string($mediaItem['alt_text'] ?? '');
            $type = $conn->real_escape_string($mediaItem['type'] ?? 'image');
                $conn->query("
                    INSERT INTO product_images (product_id, image_url, alt_text, sort_order)
                    VALUES ($productId, '$url', '$altText', $index)
                ");
        }
    }

    json_response(['message' => 'Product created', 'product_id' => $productId], 201);
} elseif (count($pathParts) === 1 && $method === 'PUT') {
    $user = get_current_user($conn);
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $productId = intval($pathParts[0]);
    $vendorResult = $conn->query("SELECT * FROM vendors WHERE user_id = " . $user['id']);
    $vendor = $vendorResult->fetch_assoc();

    $checkResult = $conn->query("SELECT * FROM products WHERE id = $productId AND vendor_id = {$vendor['id']}");
    if (!$checkResult->fetch_assoc()) {
        json_response(['error' => 'Not found or not authorized'], 404);
    }

    $input = get_input();
    $fields = [];
    if (isset($input['name'])) $fields[] = "name = '" . $conn->real_escape_string($input['name']) . "'";
    if (isset($input['description'])) $fields[] = "description = '" . $conn->real_escape_string($input['description']) . "'";
    if (isset($input['price'])) $fields[] = "price = " . floatval($input['price']);
    if (isset($input['stock_quantity'])) $fields[] = "stock_quantity = " . intval($input['stock_quantity']);
    if (isset($input['status'])) $fields[] = "status = '" . $conn->real_escape_string($input['status']) . "'";
    if (isset($input['category_id'])) $fields[] = "category_id = " . intval($input['category_id']);
    if (isset($input['brand_id'])) $fields[] = "brand_id = " . intval($input['brand_id']);

    if ($fields) {
        $conn->query("UPDATE products SET " . implode(', ', $fields) . " WHERE id = $productId");
    }

    json_response(['message' => 'Product updated']);
} elseif (count($pathParts) === 1 && $method === 'DELETE') {
    $user = get_current_user($conn);
    if (!$user) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    $productId = intval($pathParts[0]);
    $vendorResult = $conn->query("SELECT * FROM vendors WHERE user_id = " . $user['id']);
    $vendor = $vendorResult->fetch_assoc();

    $conn->query("DELETE FROM products WHERE id = $productId AND vendor_id = {$vendor['id']}");
    json_response(['message' => 'Product deleted']);
} else {
    json_response(['error' => 'Not found'], 404);
}
?>
