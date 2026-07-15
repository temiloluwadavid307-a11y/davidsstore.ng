<?php
require_once 'db.php';

function seedCategories($conn) {
    $categories = [
        ['name' => 'Electronics', 'slug' => 'electronics'],
        ['name' => 'Fashion', 'slug' => 'fashion'],
        ['name' => 'Home & Kitchen', 'slug' => 'home-kitchen'],
        ['name' => 'Food & Snacks', 'slug' => 'food-snacks']
    ];
    foreach ($categories as $cat) {
        $name = $conn->real_escape_string($cat['name']);
        $slug = $conn->real_escape_string($cat['slug']);
        $conn->query("INSERT IGNORE INTO categories (name, slug) VALUES ('$name', '$slug')");
    }
}

function seedBrands($conn) {
    $brands = [
        ['name' => 'Apple', 'slug' => 'apple'],
        ['name' => 'Samsung', 'slug' => 'samsung'],
        ['name' => 'Sony', 'slug' => 'sony'],
        ['name' => 'Canon', 'slug' => 'canon'],
        ['name' => 'Dell', 'slug' => 'dell'],
        ['name' => 'Nintendo', 'slug' => 'nintendo']
    ];
    foreach ($brands as $brand) {
        $name = $conn->real_escape_string($brand['name']);
        $slug = $conn->real_escape_string($brand['slug']);
        $conn->query("INSERT IGNORE INTO brands (name, slug) VALUES ('$name', '$slug')");
    }
}

function seedDemoUser($conn, $email, $password, $role, $extra = []) {
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $email = $conn->real_escape_string($email);
    
    $conn->begin_transaction();
    try {
        $conn->query("INSERT IGNORE INTO users (email, password_hash, status) VALUES ('$email', '$passwordHash', 'active')");
        $result = $conn->query("SELECT id FROM users WHERE email = '$email'");
        $user = $result->fetch_assoc();
        $userId = $user['id'];

        $firstName = $conn->real_escape_string($extra['first_name'] ?? 'Demo');
        $lastName = $conn->real_escape_string($extra['last_name'] ?? 'User');
        $conn->query("INSERT IGNORE INTO profiles (user_id, first_name, last_name) VALUES ($userId, '$firstName', '$lastName')");

        $roleResult = $conn->query("SELECT id FROM roles WHERE name = '$role'");
        $roleData = $roleResult->fetch_assoc();
        $roleId = $roleData['id'];
        $conn->query("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES ($userId, $roleId)");

        if ($role === 'vendor') {
            $storeName = $conn->real_escape_string($extra['store_name'] ?? 'Demo Store');
            $slug = $conn->real_escape_string($extra['slug'] ?? 'demo-store');
            $conn->query("INSERT IGNORE INTO vendors (user_id, store_name, slug, verification_status) VALUES ($userId, '$storeName', '$slug', 'verified')");
        } elseif ($role === 'customer') {
            $conn->query("INSERT IGNORE INTO customers (user_id) VALUES ($userId)");
        } elseif ($role === 'admin') {
            $conn->query("INSERT IGNORE INTO admins (user_id) VALUES ($userId)");
        }

        $conn->commit();
        return $userId;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function seedProducts($conn) {
    $vendorResult = $conn->query("SELECT * FROM vendors LIMIT 1");
    $vendor = $vendorResult->fetch_assoc();
    if (!$vendor) return;

    $categoryResult = $conn->query("SELECT id FROM categories WHERE slug = 'electronics'");
    $cat = $categoryResult->fetch_assoc();
    $electronicsId = $cat['id'];

    $brandResult = $conn->query("SELECT id FROM brands WHERE slug = 'apple'");
    $apple = $brandResult->fetch_assoc();
    $appleId = $apple['id'];

    $products = [
        [
            'name' => 'iPhone 14 Pro Max',
            'slug' => 'iphone-14-pro-max',
            'description' => 'Latest Apple smartphone with advanced features',
            'price' => 1299.99,
            'stock_quantity' => 50,
            'status' => 'published',
            'category_id' => $electronicsId,
            'brand_id' => $appleId,
            'media' => [['url' => '/assets/images/14pm.jpg', 'alt_text' => 'iPhone 14 Pro Max']]
        ],
        [
            'name' => 'AirPods Pro',
            'slug' => 'airpods-pro',
            'description' => 'Wireless earbuds with noise cancellation',
            'price' => 249.99,
            'stock_quantity' => 100,
            'status' => 'published',
            'category_id' => $electronicsId,
            'brand_id' => $appleId,
            'media' => [['url' => '/assets/images/airpod.jpg', 'alt_text' => 'AirPods Pro']]
        ],
        [
            'name' => 'MacBook Pro',
            'slug' => 'macbook-pro',
            'description' => 'Powerful laptop for professionals',
            'price' => 1999.99,
            'stock_quantity' => 30,
            'status' => 'published',
            'category_id' => $electronicsId,
            'brand_id' => $appleId,
            'media' => [['url' => '/assets/images/macbook.jpg', 'alt_text' => 'MacBook Pro']]
        ]
    ];

    foreach ($products as $product) {
        $name = $conn->real_escape_string($product['name']);
        $slug = $conn->real_escape_string($product['slug']);
        $desc = $conn->real_escape_string($product['description']);
        $price = floatval($product['price']);
        $stock = intval($product['stock_quantity']);
        $status = $conn->real_escape_string($product['status']);
        $catId = intval($product['category_id']);
        $brandId = intval($product['brand_id']);

        $conn->query("
            INSERT IGNORE INTO products (vendor_id, name, slug, description, price, stock_quantity, status, category_id, brand_id)
            VALUES ({$vendor['id']}, '$name', '$slug', '$desc', $price, $stock, '$status', $catId, $brandId)
        ");
        $productId = $conn->insert_id;

        foreach ($product['media'] as $index => $media) {
            $url = $conn->real_escape_string($media['url']);
            $alt = $conn->real_escape_string($media['alt_text']);
            $conn->query("INSERT IGNORE INTO product_images (product_id, image_url, alt_text, sort_order) VALUES ($productId, '$url', '$alt', $index)");
        }
    }
}

seedCategories($conn);
seedBrands($conn);

$adminId = seedDemoUser($conn, 'admin@example.com', 'admin123', 'admin', ['first_name' => 'Admin', 'last_name' => 'User']);
$vendorId = seedDemoUser($conn, 'vendor@example.com', 'vendor123', 'vendor', ['first_name' => 'Vendor', 'last_name' => 'User', 'store_name' => 'Tech Store', 'slug' => 'tech-store']);
$customerId = seedDemoUser($conn, 'customer@example.com', 'customer123', 'customer', ['first_name' => 'Customer', 'last_name' => 'User']);

seedProducts($conn);

echo "Database seeded successfully!\n";
echo "Admin login: admin@example.com / admin123\n";
echo "Vendor login: vendor@example.com / vendor123\n";
echo "Customer login: customer@example.com / customer123\n";
?>
