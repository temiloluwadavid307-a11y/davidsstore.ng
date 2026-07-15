<?php
/**
 * Shared helper functions
 */

/**
 * Escape HTML output
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format Nigerian Naira currency
 */
function format_price(float $amount): string
{
    return APP_CURRENCY_SYMBOL . number_format($amount, 0, '.', ',');
}

/**
 * Calculate discount percentage
 */
function calc_discount(float $price, float $old_price): int
{
    if ($old_price <= 0 || $price >= $old_price) {
        return 0;
    }
    return (int) round((($old_price - $price) / $old_price) * 100);
}

/**
 * Generate URL slug
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Resolve image path
 */
function image_url(?string $path, string $fallback = ''): string
{
    if (empty($path)) {
        return $fallback ?: 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=400&h=400&fit=crop';
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Get current page name for active nav
 */
function current_page(): string
{
    return basename($_SERVER['PHP_SELF'], '.php');
}

/**
 * Check if nav item is active
 */
function is_active(string $page): string
{
    return current_page() === $page ? 'active' : '';
}

/**
 * Redirect helper
 */
function redirect(string $url): void
{
    if (headers_sent()) {
        echo '<script>window.location.replace(' . json_encode($url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ');</script>';
    } else {
        header('Location: ' . $url, true, 302);
    }
    exit;
}

/**
 * Set flash message
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin(): bool
{
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require admin login
 */
function require_admin(): void
{
    if (!is_admin()) {
        redirect(APP_URL . '/admin/login.php');
    }
}

/**
 * Require vendor login
 */
function require_vendor(): void
{
    if (!is_logged_in()) {
        redirect(APP_URL . '/login.php');
    }

    $user = current_user();
    if (($user['role'] ?? 'customer') !== 'vendor') {
        redirect(APP_URL . '/index.php');
    }
}

/**
 * Get logged-in user
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'customer',
    ];
}

/**
 * Ensure a vendor record exists for the current logged-in user
 */
function ensure_current_vendor(): ?array
{
    global $conn;

    $user = current_user();
    if (!$user || ($user['role'] ?? 'customer') !== 'vendor') {
        return null;
    }

    $email = $user['email'] ?? '';
    $name = $user['name'] ?? 'Vendor';

    $stmt = $conn->prepare('SELECT id, name, email, phone FROM vendors WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $vendor = $stmt->get_result()->fetch_assoc();

    if ($vendor) {
        return $vendor;
    }

    $insert = $conn->prepare('INSERT INTO vendors (name, email, phone, is_active, created_at) VALUES (?, ?, ?, 1, NOW())');
    $phone = '';
    $insert->bind_param('sss', $name, $email, $phone);
    $insert->execute();

    return [
        'id' => $conn->insert_id,
        'name' => $name,
        'email' => $email,
        'phone' => '',
    ];
}

/**
 * Initialize cart in session
 */
function init_cart(): void
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/**
 * Get cart items count
 */
function cart_count(): int
{
    init_cart();
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int) ($item['quantity'] ?? 0);
    }
    return $count;
}

/**
 * Get cart total
 */
function cart_total(): float
{
    init_cart();
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }
    return (float) $total;
}

/**
 * Add product to session cart
 */
function add_to_cart(int $product_id, int $quantity = 1): bool
{
    global $conn;
    init_cart();

    $stmt = $conn->prepare('SELECT id, name, price, stock_quantity, sku, image_primary FROM products WHERE id = ? AND is_active = 1');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product || $product['stock_quantity'] < 1) {
        return false;
    }

    $qty = min($quantity, (int) $product['stock_quantity']);

    if (isset($_SESSION['cart'][$product_id])) {
        $new_qty = $_SESSION['cart'][$product_id]['quantity'] + $qty;
        $_SESSION['cart'][$product_id]['quantity'] = min($new_qty, (int) $product['stock_quantity']);
    } else {
        $_SESSION['cart'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => (float) $product['price'],
            'sku' => $product['sku'],
            'image' => $product['image_primary'],
            'quantity' => $qty,
            'max_stock' => (int) $product['stock_quantity'],
        ];
    }
    return true;
}

/**
 * Update cart item quantity
 */
function update_cart_item(int $product_id, int $quantity): bool
{
    init_cart();
    if (!isset($_SESSION['cart'][$product_id])) {
        return false;
    }
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    $max = $_SESSION['cart'][$product_id]['max_stock'] ?? 99;
    $_SESSION['cart'][$product_id]['quantity'] = min($quantity, $max);
    return true;
}

/**
 * Remove item from cart
 */
function remove_from_cart(int $product_id): void
{
    init_cart();
    unset($_SESSION['cart'][$product_id]);
}

/**
 * Clear cart
 */
function clear_cart(): void
{
    $_SESSION['cart'] = [];
}

/**
 * Render star rating HTML
 */
function render_stars(float $rating): string
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($rating >= $i - 0.5) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    return $html;
}

/**
 * Sanitize input
 */
function sanitize(string $input): string
{
    return trim(strip_tags($input));
}

/**
 * Validate email
 */
function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get all categories
 */
function get_categories(): array
{
    global $conn;
    $result = $conn->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name');
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get product by ID with images
 */
function get_product(int $id): ?array
{
    global $conn;
    $stmt = $conn->prepare('
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.id = ? AND p.is_active = 1
    ');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        return null;
    }

    $img_stmt = $conn->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order');
    $img_stmt->bind_param('i', $id);
    $img_stmt->execute();
    $product['images'] = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($product['images']) && !empty($product['image_primary'])) {
        $product['images'] = [['image_url' => $product['image_primary'], 'alt_text' => $product['name']]];
    }

    return $product;
}

/**
 * Build products query with filters
 */
function get_products(array $filters = []): array
{
    global $conn;

    $where = ['p.is_active = 1'];
    $params = [];
    $types = '';

    if (!empty($filters['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = $filters['category'];
        $types .= 's';
    }

    if (!empty($filters['search'])) {
        $where[] = '(p.name LIKE ? OR p.description LIKE ? OR b.name LIKE ?)';
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }

    if (!empty($filters['brand'])) {
        $where[] = 'b.slug = ?';
        $params[] = $filters['brand'];
        $types .= 's';
    }

    if (!empty($filters['min_price'])) {
        $where[] = 'p.price >= ?';
        $params[] = (float) $filters['min_price'];
        $types .= 'd';
    }

    if (!empty($filters['max_price'])) {
        $where[] = 'p.price <= ?';
        $params[] = (float) $filters['max_price'];
        $types .= 'd';
    }

    $order = 'p.created_at DESC';
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc': $order = 'p.price ASC'; break;
            case 'price_desc': $order = 'p.price DESC'; break;
            case 'rating': $order = 'p.rating DESC'; break;
            case 'newest': $order = 'p.created_at DESC'; break;
            case 'popular': $order = 'p.reviews_count DESC'; break;
            case 'discount': $order = 'p.discount_percent DESC'; break;
        }
    }

    $limit = (int) ($filters['limit'] ?? PRODUCTS_PER_PAGE);
    $offset = (int) ($filters['offset'] ?? 0);

    $sql = "
        SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $order
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $conn->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Count products with filters
 */
function count_products(array $filters = []): int
{
    global $conn;

    $where = ['p.is_active = 1'];
    $params = [];
    $types = '';

    if (!empty($filters['category'])) {
        $where[] = 'c.slug = ?';
        $params[] = $filters['category'];
        $types .= 's';
    }

    if (!empty($filters['search'])) {
        $where[] = '(p.name LIKE ? OR p.description LIKE ? OR b.name LIKE ?)';
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }

    $sql = "
        SELECT COUNT(*) AS total
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE " . implode(' AND ', $where);

    $stmt = $conn->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

/**
 * Render product card HTML
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    if (!is_string($token) || empty($token)) {
        return false;
    }
    return hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token);
}

/**
 * Render product card HTML
 */
function render_product_card(array $product): string
{
    $discount = (int) ($product['discount_percent'] ?? 0);
    $image = image_url($product['image_primary'] ?? '');
    $price = format_price((float) $product['price']);
    $old_price = !empty($product['old_price']) ? format_price((float) $product['old_price']) : '';
    $rating = render_stars((float) ($product['rating'] ?? 0));
    $reviews = (int) ($product['reviews_count'] ?? 0);
    $badge = $discount > 0 ? '<span class="discount-badge">-' . $discount . '%</span>' : '';
    $old_price_html = $old_price ? '<div class="old-price">' . $old_price . '</div>' : '';
    $avail = $product['availability'] ?? 'in_stock';
    $is_out_of_stock = $avail === 'out_of_stock' || $product['stock_quantity'] < 1;

    return '
    <article class="product-card" data-product-id="' . (int) $product['id'] . '">
        <a href="' . APP_URL . '/product.php?id=' . (int) $product['id'] . '" class="product-card-link" data-tooltip="View product details">
            <div class="product-image">
                <img src="' . e($image) . '" alt="' . e($product['name']) . '" loading="lazy">
                ' . $badge . '
            </div>
            <div class="product-info">
                <span class="product-brand">' . e($product['brand_name'] ?? '') . '</span>
                <h3 class="product-name">' . e($product['name']) . '</h3>
                <div class="product-rating">' . $rating . ' <span class="reviews-count">(' . $reviews . ')</span></div>
                <div class="product-price">
                    ' . $price . '
                    ' . $old_price_html . '
                </div>
            </div>
        </a>
        ' . ($is_out_of_stock ? '
        <button class="btn btn-secondary btn-sm" disabled style="width: 100%; margin-top: 8px;" data-tooltip="This product is temporarily unavailable">Out of Stock</button>
        ' : '
        <form method="post" action="' . APP_URL . '/actions/cart.php" style="width: 100%;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">
            <input type="hidden" name="product_id" value="' . (int) $product['id'] . '">
            <input type="hidden" name="redirect" value="' . e($_SERVER['REQUEST_URI']) . '">
            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; margin-top: 8px;" data-tooltip="Add item to shopping cart">
                <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
        </form>
        ') . '
    </article>
    ';
}
