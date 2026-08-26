<?php
/**
 * Shared helper functions
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail.php';

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
 * Generate CSRF token
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Render CSRF hidden input
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Add a notification record
 */
function add_notification(int $user_id, string $title, string $message, string $type = 'info'): void
{
    global $conn;
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $user_id, $title, $message, $type);
    $stmt->execute();
}

/**
 * Create an audit entry
 */
function add_audit_log(int $user_id, string $action, string $details): void
{
    global $conn;
    $stmt = $conn->prepare('INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $user_id, $action, $details);
    $stmt->execute();
}

/**
 * Read a simple settings key from the settings table
 */
function get_setting(string $key, $default = null) {
    global $conn;
    // If the settings table doesn't exist yet (migrations not run), return default
    $check = $conn->query("SHOW TABLES LIKE 'settings'");
    if (!$check || $check->num_rows < 1) {
        return $default;
    }
    $stmt = $conn->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1');
    if (!$stmt) return $default;
    $stmt->bind_param('s', $key);
    if ($stmt->execute()) {
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && isset($res['value'])) return $res['value'];
    }
    return $default;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Check if user is super admin
 */
function is_super_admin(): bool
{
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin';
}

/**
 * Check if user is admin
 */
function is_admin(): bool
{
    return !empty($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'super_admin');
}

/**
 * Check if user is vendor
 */
function is_vendor(): bool
{
    return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendor';
}

/**
 * Require user login
 */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect(APP_URL . '/login.php');
    }
}

function redirect_to_role_dashboard(string $role): void
{
    switch ($role) {
        case 'customer':
            redirect(APP_URL . '/customer/index.php');
            break;
        case 'vendor':
            redirect(APP_URL . '/vendor/index.php');
            break;
        case 'admin':
        case 'super_admin':
            redirect(APP_URL . '/admin/index.php');
            break;
        default:
            redirect(APP_URL . '/login.php');
            break;
    }
}

/**
 * Require admin login
 */
function require_admin(): void
{
    require_login();
    $user = current_user();
    if (!$user) {
        redirect(APP_URL . '/login.php');
    }

    if (!in_array($user['role'] ?? '', ['admin', 'super_admin'], true)) {
        redirect_to_role_dashboard($user['role'] ?? '');
    }
}

/**
 * Require super admin login
 */
function require_super_admin(): void
{
    require_login();
    $user = current_user();
    if (!$user) {
        redirect(APP_URL . '/login.php');
    }

    if (($user['role'] ?? '') !== 'super_admin') {
        redirect_to_role_dashboard($user['role'] ?? '');
    }
}

/**
 * Require customer login and verified email
 */
function require_customer(): void
{
    require_login();
    require_email_verified();
    $user = current_user();
    if (!$user) {
        redirect(APP_URL . '/login.php');
    }

    if (($user['role'] ?? '') !== 'customer') {
        redirect_to_role_dashboard($user['role'] ?? '');
    }
}

/**
 * Require vendor login and verified email
 */
function require_vendor(): void
{
    require_login();
    require_email_verified();

    $user = current_user();
    if (!$user) {
        redirect(APP_URL . '/login.php');
    }

    if (($user['role'] ?? '') !== 'vendor') {
        redirect_to_role_dashboard($user['role'] ?? '');
    }

    global $conn;
    // Check if the `status` column exists in `vendors`. If not, assume approved to avoid fatal errors
    $hasStatus = false;
    $res = $conn->query("SHOW COLUMNS FROM vendors LIKE 'status'");
    if ($res && $res->num_rows > 0) {
        $hasStatus = true;
    }

    if ($hasStatus) {
        $stmt = $conn->prepare('SELECT id, status FROM vendors WHERE user_id = ? LIMIT 1');
    } else {
        $stmt = $conn->prepare('SELECT id FROM vendors WHERE user_id = ? LIMIT 1');
    }
    $user_id = (int) $user['id'];
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $vendor = $stmt->get_result()->fetch_assoc();

    if (!$vendor) {
        redirect(APP_URL . '/vendor/pending.php');
    }

    if ($hasStatus && ($vendor['status'] ?? '') !== 'approved') {
        redirect(APP_URL . '/vendor/pending.php');
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

    global $conn;
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $stmt = $conn->prepare('SELECT id, first_name, last_name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || empty($user['is_active'])) {
        return null;
    }

    $_SESSION['user_role'] = $user['role'] ?? 'customer';
    $_SESSION['user_email'] = $user['email'] ?? $_SESSION['user_email'] ?? '';
    $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $_SESSION['user_name'] ?? 'User';

    return [
        'id' => $user['id'],
        'name' => $_SESSION['user_name'],
        'email' => $user['email'] ?? '',
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
    $user_id = (int) ($user['id'] ?? 0);

    $stmt = $conn->prepare('SELECT id, name, email, phone, store_name, business_name, verification_status FROM vendors WHERE (user_id = ? OR email = ?) AND (verification_status = "verified" OR is_active = 1) LIMIT 1');
    $stmt->bind_param('is', $user_id, $email);
    $stmt->execute();
    $vendor = $stmt->get_result()->fetch_assoc();

    if ($vendor) {
        return $vendor;
    }

    return null;
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
 * Generate a secure random token
 */
function generate_secure_token(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Log email delivery attempts
 */
function log_email_delivery(?int $user_id, string $recipient, string $subject, bool $success, string $message = null): void
{
    global $conn;
    try {
        $stmt = $conn->prepare('INSERT INTO email_logs (user_id, recipient, subject, success, message) VALUES (?, ?, ?, ?, ?)');
        $user_id_param = $user_id !== null ? $user_id : 0;
        $stmt->bind_param('issis', $user_id_param, $recipient, $subject, $success, $message);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log('Email delivery log failed: ' . $e->getMessage());
    }
}

/**
 * Queue failed email deliveries for retry
 */
function queue_email_message(?int $user_id, string $recipient, string $subject, string $html, string $text = ''): void
{
    global $conn;
    try {
        $stmt = $conn->prepare('INSERT INTO email_queue (user_id, recipient, subject, html, text, status, attempts) VALUES (?, ?, ?, ?, ?, "pending", 0)');
        $user_id_param = $user_id !== null ? $user_id : 0;
        $stmt->bind_param('issss', $user_id_param, $recipient, $subject, $html, $text);
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log('Email queue insert failed: ' . $e->getMessage());
    }
}

/**
 * Send mail and queue if it fails
 */
function send_email_notification(?int $user_id, string $recipient, string $subject, string $html, string $text = ''): bool
{
    $recipient = filter_var($recipient, FILTER_VALIDATE_EMAIL);
    if (!$recipient) {
        return false;
    }

    $success = send_email_message($recipient, $subject, $html, $text);
    log_email_delivery($user_id, $recipient, $subject, $success, $success ? 'Delivered' : 'Send failed');

    if (!$success) {
        queue_email_message($user_id, $recipient, $subject, $html, $text);
    }

    return $success;
}

/**
 * Create an email verification token.
 */
function create_email_verification_token(int $user_id): string
{
    global $conn;
    $token = generate_secure_token(24);
    $expires = date('Y-m-d H:i:s', time() + 3600 * 24);
    $stmt = $conn->prepare('INSERT INTO email_verifications (user_id, token, expires_at, used) VALUES (?, ?, ?, 0)');
    $stmt->bind_param('iss', $user_id, $token, $expires);
    $stmt->execute();
    return $token;
}

/**
 * Create a password reset token.
 */
function create_password_reset_token(int $user_id): string
{
    global $conn;
    $token = generate_secure_token(24);
    $expires = date('Y-m-d H:i:s', time() + 3600);
    $stmt = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at, used) VALUES (?, ?, ?, 0)');
    $stmt->bind_param('iss', $user_id, $token, $expires);
    $stmt->execute();
    return $token;
}

/**
 * Mark an email verification token as used.
 */
function consume_email_verification_token(string $token): ?int
{
    global $conn;
    $stmt = $conn->prepare('SELECT id, user_id FROM email_verifications WHERE token = ? AND used = 0 AND expires_at >= NOW() LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $verification = $stmt->get_result()->fetch_assoc();
    if (!$verification) {
        return null;
    }
    $stmt = $conn->prepare('UPDATE email_verifications SET used = 1 WHERE id = ?');
    $stmt->bind_param('i', $verification['id']);
    $stmt->execute();
    return (int) $verification['user_id'];
}

/**
 * Consume a password reset token and return the user ID.
 */
function consume_password_reset_token(string $token): ?int
{
    global $conn;
    $stmt = $conn->prepare('SELECT id, user_id FROM password_resets WHERE token = ? AND used = 0 AND expires_at >= NOW() LIMIT 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    if (!$reset) {
        return null;
    }
    $stmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
    $stmt->bind_param('i', $reset['id']);
    $stmt->execute();
    return (int) $reset['user_id'];
}

/**
 * Generate a branded email wrapper
 */
function build_email_template(string $subject, string $body, string $actionUrl = '', string $actionLabel = ''): string
{
    $logoUrl = APP_URL . '/images for coding/logo.png';
    $supportEmail = ADMIN_EMAIL;
    $appUrl = APP_URL;
    $year = date('Y');

    $buttonHtml = '';
    if ($actionUrl && $actionLabel) {
        $buttonHtml = '<tr><td align="center" style="padding:24px 0 16px;"><a href="' . e($actionUrl) . '" style="background:#111827;color:#ffffff;padding:14px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">' . e($actionLabel) . '</a></td></tr>';
    }

    $socialLinksHtml = '<tr><td align="center" style="padding:18px 40px 0; font-size:14px; color:#6b7280;"><a href="https://facebook.com" style="margin:0 8px; color:#111827; text-decoration:none;">Facebook</a><span style="margin:0 8px; color:#d1d5db;">•</span><a href="https://instagram.com" style="margin:0 8px; color:#111827; text-decoration:none;">Instagram</a><span style="margin:0 8px; color:#d1d5db;">•</span><a href="https://twitter.com" style="margin:0 8px; color:#111827; text-decoration:none;">Twitter</a></td></tr>';

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($subject) . '</title></head><body style="margin:0; padding:0; font-family:Arial, Helvetica, sans-serif; background:#f4f5f7; color:#111827;"><table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding:24px 16px;"><table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 20px 60px rgba(15,23,42,0.08);"><tr><td style="padding:32px; text-align:center; background:#111827;"><img src="' . e($logoUrl) . '" alt="' . e(APP_NAME) . '" width="120" style="display:block; margin:0 auto 18px;" /><h1 style="color:#ffffff; font-size:24px; margin:0;">' . e(APP_NAME) . '</h1><p style="color:#cbd5e1; margin:8px 0 0;">' . e(APP_TAGLINE) . '</p></td></tr><tr><td style="padding:32px 40px 28px;">' . $body . '</td></tr>' . $buttonHtml . $socialLinksHtml . '<tr><td style="padding:0 40px 32px; color:#6b7280; font-size:14px; line-height:1.7;"><p style="margin:0 0 10px;">Need help? Contact us at <a href="mailto:' . e($supportEmail) . '" style="color:#111827; text-decoration:none;">' . e($supportEmail) . '</a>.</p><p style="margin:0;">' . e(APP_NAME) . ' • <a href="' . e($appUrl) . '" style="color:#111827; text-decoration:none;">' . e($appUrl) . '</a></p></td></tr><tr><td style="padding:20px 40px; background:#f8fafc; color:#9ca3af; font-size:12px; text-align:center;">© ' . e($year) . ' ' . e(APP_NAME) . '. All rights reserved.</td></tr></table></td></tr></table></body></html>';
}

/**
 * Send the customer welcome email
 */
function send_customer_welcome_email(int $user_id, string $first_name, string $last_name, string $email): bool
{
    $fullName = trim($first_name . ' ' . $last_name);
    $subject = 'Welcome to ' . APP_NAME;
    $body = '<h2 style="margin-top:0;">Welcome, ' . e($fullName) . '!</h2>';
    $body .= '<p>Thank you for joining ' . e(APP_NAME) . '. Your customer account has been created successfully.</p>';
    $body .= '<p><strong>Account type:</strong> Customer</p>';
    $body .= '<p><strong>Registration date:</strong> ' . e(date('F j, Y H:i')) . '</p>';
    $body .= '<p>Explore products, save favorites, and manage your orders from your account dashboard.</p>';
    $text = 'Welcome to ' . APP_NAME . '! Your customer account has been created successfully.';
    $loginUrl = APP_URL . '/login.php';
    $html = build_email_template($subject, $body, $loginUrl, 'Sign in to your account');
    return send_email_notification($user_id, $email, $subject, $html, $text);
}

/**
 * Send the vendor registration acknowledgment email
 */
function send_vendor_registration_email(int $user_id, string $fullName, string $email): bool
{
    $subject = 'Vendor Registration Received';
    $body = '<h2 style="margin-top:0;">Thank you for registering as a vendor, ' . e($fullName) . '.</h2>';
    $body .= '<p>Your vendor application has been received successfully and is currently under review by our Marketplace Administration team.</p>';
    $body .= '<p>Once your application is approved, you will receive another email with access instructions and vendor dashboard details.</p>';
    $body .= '<p><strong>Account type:</strong> Vendor applicant</p>';
    $body .= '<p>Until approval, vendor features are not yet available.</p>';
    $actionUrl = APP_URL . '/login.php';
    $html = build_email_template($subject, $body, $actionUrl, 'Visit the Store');
    $text = 'Your vendor application has been received successfully and is under review. You will be notified once it is approved.';
    return send_email_notification($user_id, $email, $subject, $html, $text);
}

/**
 * Notify the store super-admin / admin team about a new vendor application
 */
function send_vendor_application_admin_notification(int $user_id, string $fullName, string $storeName, string $businessName, string $businessEmail, string $location): bool
{
    global $conn;

    $subject = 'New vendor application submitted';
    $body = '<h2 style="margin-top:0;">New vendor application received</h2>';
    $body .= '<p>A new vendor application has been submitted for review.</p>';
    $body .= '<p><strong>Applicant:</strong> ' . e($fullName) . '</p>';
    $body .= '<p><strong>Store name:</strong> ' . e($storeName) . '</p>';
    $body .= '<p><strong>Business name:</strong> ' . e($businessName) . '</p>';
    $body .= '<p><strong>Business email:</strong> ' . e($businessEmail) . '</p>';
    $body .= '<p><strong>Location:</strong> ' . e($location) . '</p>';
    $body .= '<p>Please review it in the admin vendor applications section.</p>';
    $actionUrl = APP_URL . '/admin/vendor-applications.php';
    $html = build_email_template($subject, $body, $actionUrl, 'Review Vendor Application');
    $text = 'A new vendor application has been submitted. Applicant: ' . $fullName . '. Store: ' . $storeName . '. Business: ' . $businessName . '. Email: ' . $businessEmail . '. Location: ' . $location . ' Please review it in the admin vendor applications section.';

    $recipients = [];
    $adminRes = $conn->query("SELECT email FROM users WHERE role IN ('admin', 'super_admin') AND is_active = 1 AND email IS NOT NULL AND email != ''");
    if ($adminRes) {
        while ($row = $adminRes->fetch_assoc()) {
            $email = trim((string) ($row['email'] ?? ''));
            if ($email !== '') {
                $recipients[] = $email;
            }
        }
    }

    if (empty($recipients)) {
        $recipients[] = ADMIN_EMAIL;
    }

    $sent = false;
    $seen = [];
    foreach ($recipients as $recipient) {
        $recipient = trim((string) $recipient);
        if ($recipient === '' || isset($seen[$recipient])) {
            continue;
        }
        $seen[$recipient] = true;
        if (send_email_notification($user_id, $recipient, $subject, $html, $text)) {
            $sent = true;
        }
    }

    return $sent;
}

/**
 * Send the vendor approval email
 */
function send_vendor_approval_email(int $user_id, string $fullName, string $businessName, string $storeName, string $email): bool
{
    $subject = 'Congratulations! Your Vendor Account Has Been Approved';
    $body = '<h2 style="margin-top:0;">Congratulations, ' . e($fullName) . '!</h2>';
    $body .= '<p>Your vendor account has been approved.</p>';
    $body .= '<p><strong>Business name:</strong> ' . e($businessName) . '</p>';
    $body .= '<p><strong>Store name:</strong> ' . e($storeName) . '</p>';
    $body .= '<p><strong>Approval date:</strong> ' . e(date('F j, Y H:i')) . '</p>';
    $body .= '<p>You can now access your vendor dashboard and start listing products.</p>';
    $dashboardUrl = APP_URL . '/vendor/index.php';
    $html = build_email_template($subject, $body, $dashboardUrl, 'Go to Vendor Dashboard');
    $text = 'Your vendor account has been approved. You can now access your vendor dashboard at ' . $dashboardUrl;
    return send_email_notification($user_id, $email, $subject, $html, $text);
}

/**
 * Send the vendor rejection email
 */
function send_vendor_rejection_email(int $user_id, string $fullName, string $email, string $reason = ''): bool
{
    $subject = 'Vendor Application Update';
    $body = '<h2 style="margin-top:0;">Thank you for your application, ' . e($fullName) . '.</h2>';
    $body .= '<p>We reviewed your vendor application and it was not approved at this time.</p>';
    if ($reason !== '') {
        $body .= '<p><strong>Reason:</strong> ' . e($reason) . '</p>';
    }
    $body .= '<p>You may revise your application and submit again after addressing the feedback.</p>';
    $html = build_email_template($subject, $body, APP_URL . '/vendor/become-vendor.php', 'Review vendor application');
    $text = 'Your vendor application was not approved. Reason: ' . ($reason ?: 'Not specified') . '. You may reapply after making improvements.';
    return send_email_notification($user_id, $email, $subject, $html, $text);
}

/**
 * Send password reset email
 */
function send_password_reset_email(int $user_id, string $first_name, string $email, string $token): bool
{
    $subject = 'Password Reset Request';
    $resetUrl = APP_URL . '/reset-password.php?token=' . urlencode($token);
    $body = '<h2 style="margin-top:0;">Hi ' . e($first_name) . ',</h2>';
    $body .= '<p>We received a request to reset your password. Click the button below to create a new password.</p>';
    $body .= '<p>This link will expire in 60 minutes. If you did not request a password reset, you can ignore this email.</p>';
    $html = build_email_template($subject, $body, $resetUrl, 'Reset your password');
    $text = 'Reset your password using this link: ' . $resetUrl;
    return send_email_notification($user_id, $email, $subject, $html, $text);
}

/**
 * Send verification email
 */
function send_email_verification_email(int $user_id, string $first_name, string $email, string $token): bool
{
    $subject = 'Verify Your Email Address';
    $verifyUrl = APP_URL . '/verify-email.php?token=' . urlencode($token);
    $body = '<h2 style="margin-top:0;">Welcome to ' . e(APP_NAME) . ', ' . e($first_name) . '!</h2>';
    $body .= '<p>Please verify your email address to complete your registration.</p>';
    $body .= '<p>Once verified, you will be able to access your account and receive order notifications.</p>';
    $html = build_email_template($subject, $body, $verifyUrl, 'Verify your email');
    $text = 'Please verify your email by visiting: ' . $verifyUrl;
    return send_email_notification($user_id, $email, $subject, $html, $text);
}

/**
 * Send order confirmation email
 */
function send_order_confirmation_email(int $order_id): bool
{
    global $conn;
    $stmt = $conn->prepare('SELECT o.order_number, o.customer_name, o.customer_email, o.created_at, o.subtotal, o.shipping_fee, o.total, o.payment_method FROM orders o WHERE o.id = ? LIMIT 1');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) return false;

    $items_stmt = $conn->prepare('SELECT product_name, quantity, unit_price, line_total FROM order_items WHERE order_id = ?');
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $subject = 'Order Confirmation #' . $order['order_number'];
    $body = '<h2>Thank you, ' . e($order['customer_name']) . '!</h2>';
    $body .= '<p>Your order <strong>' . e($order['order_number']) . '</strong> was received on ' . e(date('F j, Y H:i', strtotime($order['created_at']))) . '.</p>';
    $body .= '<table style="width:100%; border-collapse:collapse;">';
    $body .= '<thead><tr><th align="left">Product</th><th align="center">Qty</th><th align="right">Price</th><th align="right">Total</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $body .= '<tr><td>' . e($it['product_name']) . '</td><td align="center">' . (int)$it['quantity'] . '</td><td align="right">' . e(format_price((float)$it['unit_price'])) . '</td><td align="right">' . e(format_price((float)$it['line_total'])) . '</td></tr>';
    }
    $body .= '</tbody></table>';
    $body .= '<p style="margin-top:12px;">Subtotal: ' . e(format_price((float)$order['subtotal'])) . '<br>Delivery: ' . e(format_price((float)$order['shipping_fee'])) . '<br><strong>Total: ' . e(format_price((float)$order['total'])) . '</strong></p>';
    $body .= '<p>Payment method: ' . e(ucwords(str_replace('_', ' ', $order['payment_method']))) . '</p>';
    $viewUrl = APP_URL . '/customer/section.php?page=order&order=' . urlencode($order['order_number']);
    $html = build_email_template($subject, $body, $viewUrl, 'View Order');
    $text = 'Order ' . $order['order_number'] . ' placed. Total: ' . format_price((float)$order['total']);

    return send_email_notification(null, $order['customer_email'], $subject, $html, $text);
}

/**
 * Send payment confirmation email
 */
function send_payment_confirmation_email(int $order_id): bool
{
    global $conn;
    $stmt = $conn->prepare('SELECT order_number, customer_name, customer_email, total FROM orders WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) return false;

    $subject = 'Payment Confirmation for Order #' . $order['order_number'];
    $body = '<h2>Payment received</h2><p>Hi ' . e($order['customer_name']) . ',</p>';
    $body .= '<p>We have received payment of <strong>' . e(format_price((float)$order['total'])) . '</strong> for your order <strong>' . e($order['order_number']) . '</strong>.</p>';
    $html = build_email_template($subject, $body, APP_URL . '/customer/section.php?page=order&order=' . urlencode($order['order_number']), 'View Order');
    $text = 'Payment received for order ' . $order['order_number'] . '. Amount: ' . format_price((float)$order['total']);
    return send_email_notification(null, $order['customer_email'], $subject, $html, $text);
}

/**
 * Send order status update email
 */
function send_order_status_email(int $order_id, string $new_status): bool
{
    global $conn;
    $stmt = $conn->prepare('SELECT order_number, customer_name, customer_email FROM orders WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) return false;

    $subject = 'Your Order #' . $order['order_number'] . ' status updated';
    $body = '<p>Hi ' . e($order['customer_name']) . ',</p>';
    $body .= '<p>Your order <strong>' . e($order['order_number']) . '</strong> status is now <strong>' . e(ucfirst($new_status)) . '</strong>.</p>';
    $html = build_email_template($subject, $body, APP_URL . '/customer/section.php?page=order&order=' . urlencode($order['order_number']), 'View Order');
    $text = 'Order ' . $order['order_number'] . ' status: ' . $new_status;
    return send_email_notification(null, $order['customer_email'], $subject, $html, $text);
}

/**
 * Require verified email before allowing access
 */
function has_user_column(string $column): bool
{
    static $cache = [];
    if (isset($cache[$column])) {
        return $cache[$column];
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    global $conn;
    $escapedColumn = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM users LIKE '" . $escapedColumn . "'");
    $exists = $result && $result->num_rows > 0;
    $cache[$column] = $exists;
    return $exists;
}

function is_email_verified(): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    if (!has_user_column('email_verified_at')) {
        return true;
    }

    global $conn;
    $stmt = $conn->prepare('SELECT email_verified_at FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return !empty($row['email_verified_at']);
}

function require_email_verified(): void
{
    $user = current_user();
    if (!$user) {
        redirect(APP_URL . '/login.php');
    }

    if (!has_user_column('email_verified_at')) {
        return;
    }

    if (!is_email_verified()) {
        redirect(APP_URL . '/resend-verification.php');
    }
}

/**
 * Process pending email queue items
 */
function process_email_queue(int $maxItems = 20): int
{
    global $conn;

    try {
        $stmt = $conn->prepare('SELECT id, user_id, recipient, subject, html, text, attempts FROM email_queue WHERE status = "pending" ORDER BY created_at ASC LIMIT ?');
        $stmt->bind_param('i', $maxItems);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (mysqli_sql_exception $e) {
        error_log('Email queue processing failed: ' . $e->getMessage());
        return 0;
    }

    $processed = 0;

    foreach ($rows as $row) {
        $success = send_email_message($row['recipient'], $row['subject'], $row['html'], $row['text']);
        if ($success) {
            $update = $conn->prepare('UPDATE email_queue SET status = "sent", attempts = attempts + 1, last_attempt_at = NOW() WHERE id = ?');
            $update->bind_param('i', $row['id']);
            $update->execute();
            log_email_delivery((int)$row['user_id'], $row['recipient'], $row['subject'], true, 'Queued email delivered');
        } else {
            $update = $conn->prepare('UPDATE email_queue SET attempts = attempts + 1, last_attempt_at = NOW(), status = CASE WHEN attempts + 1 >= 5 THEN "failed" ELSE "pending" END WHERE id = ?');
            $update->bind_param('i', $row['id']);
            $update->execute();
            log_email_delivery((int)$row['user_id'], $row['recipient'], $row['subject'], false, 'Queued email retry failed');
        }
        $processed++;
    }

    return $processed;
}

/**
 * Restrict resend verification rate
 */
function can_resend_verification(int $user_id): bool
{
    global $conn;
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM email_logs WHERE user_id = ? AND subject = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $subject = 'Verify Your Email Address';
    $stmt->bind_param('is', $user_id, $subject);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int) ($row['total'] ?? 0) < 3;
}

/**
 * Send verification reminder email with rate limiting
 */
function resend_verification_email(int $user_id, string $first_name, string $email): bool
{
    if (!can_resend_verification($user_id)) {
        return false;
    }
    $token = create_email_verification_token($user_id);
    return send_email_verification_email($user_id, $first_name, $email, $token);
}

/**
 * Get a user record by email
 */
function get_user_by_email(string $email): ?array
{
    global $conn;
    if (function_exists('has_user_column') && has_user_column('email_verified_at')) {
        $stmt = $conn->prepare('SELECT id, first_name, last_name, email, role, is_active, email_verified_at FROM users WHERE email = ? LIMIT 1');
    } else {
        $stmt = $conn->prepare('SELECT id, first_name, last_name, email, role, is_active FROM users WHERE email = ? LIMIT 1');
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && !isset($user['email_verified_at'])) {
        $user['email_verified_at'] = null;
    }
    return $user;
}

/**
 * Get a user record by ID
 */
function get_user_by_id(int $user_id): ?array
{
    global $conn;
    if (function_exists('has_user_column') && has_user_column('email_verified_at')) {
        $stmt = $conn->prepare('SELECT id, first_name, last_name, email, role, is_active, email_verified_at FROM users WHERE id = ? LIMIT 1');
    } else {
        $stmt = $conn->prepare('SELECT id, first_name, last_name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && !isset($user['email_verified_at'])) {
        $user['email_verified_at'] = null;
    }
    return $user;
}

/**
 * Mark a user as email verified
 */
function mark_user_email_verified(int $user_id): void
{
    global $conn;
    $stmt = $conn->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
}

/**
 * Send verification and welcome email after registration
 */
function handle_new_user_registration(int $user_id, string $first_name, string $last_name, string $email, string $role = 'customer'): void
{
    if ($role === 'vendor') {
        $send = send_vendor_registration_email($user_id, trim($first_name . ' ' . $last_name), $email);
    } else {
        $send = send_customer_welcome_email($user_id, $first_name, $last_name, $email);
    }
    $token = create_email_verification_token($user_id);
    send_email_verification_email($user_id, $first_name, $email, $token);
}

/**
 * Send vendor application acknowledgment email
 */
function handle_vendor_application_submission(int $user_id, string $first_name, string $last_name, string $email): void
{
    send_vendor_registration_email($user_id, trim($first_name . ' ' . $last_name), $email);
}

/**
 * Get active product categories for site navigation.
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
