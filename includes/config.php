<?php
/**
 * Application configuration for David's Store.
 *
 * All environment-specific settings are centralized in this file so the
 * same codebase can run on XAMPP, InfinityFree, Namecheap, cPanel, or a VPS.
 */

define('APP_NAME', "David's Store");
define('APP_TAGLINE', 'Premium Fashion & Lifestyle');
define('APP_CURRENCY', 'NGN');
define('APP_CURRENCY_SYMBOL', '₦');

define('ROOT_PATH', dirname(__DIR__));

$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$host = preg_replace('/:\d+$/', '', $host);
$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
) ? 'https' : 'http';

$script_name = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$script_dir = str_replace('\\', '/', dirname($script_name));
if ($script_dir === '/' || $script_dir === '\\') {
    $script_dir = '';
}

if (preg_match('#/(admin|vendor|customer|actions|server)$#', $script_dir)) {
    $script_dir = dirname($script_dir);
}

$app_base_path = rtrim($script_dir, '/');
if ($app_base_path === '') {
    $app_base_path = '';
}

define('APP_URL', $scheme . '://' . $host . $app_base_path);

define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL', APP_URL . '/uploads');

define('APP_ENV', getenv('APP_ENV') ?: ((stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) ? 'development' : 'production'));

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'codesbyd4');

define('SESSION_NAME', 'davids_store_session_' . md5(APP_URL));

define('ADMIN_EMAIL', 'admin@davidsstore.ng');

// Load local SMTP overrides from config/smtp.php before default values.
if (file_exists(__DIR__ . '/../config/smtp.php')) {
    @include_once __DIR__ . '/../config/smtp.php';
}

if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', getenv('MAIL_HOST') ?: (defined('SMTP_HOST') ? SMTP_HOST : ''));
}
if (!defined('MAIL_PORT')) {
    define('MAIL_PORT', getenv('MAIL_PORT') ?: (defined('SMTP_PORT') ? SMTP_PORT : '587'));
}
if (!defined('MAIL_USERNAME')) {
    define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: (defined('SMTP_USERNAME') ? SMTP_USERNAME : ''));
}
if (!defined('MAIL_PASSWORD')) {
    define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: (defined('SMTP_PASSWORD') ? SMTP_PASSWORD : ''));
}
if (!defined('MAIL_ENCRYPTION')) {
    define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: (defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls'));
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);
}
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'noreply@' . $host);
}
if (!defined('PAYSTACK_SECRET_KEY')) {
    define('PAYSTACK_SECRET_KEY', getenv('PAYSTACK_SECRET_KEY') ?: '');
}

if (!defined('API_JWT_SECRET')) {
    define('API_JWT_SECRET', getenv('API_JWT_SECRET') ?: 'dev-api-secret-change-me');
}

if (!defined('PAYSTACK_PUBLIC_KEY')) {
    define('PAYSTACK_PUBLIC_KEY', getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_live_1c45b8f902c231e57dfcc260f64bab680a6d4d88');
}

define('PRODUCTS_PER_PAGE', 12);
define('FREE_DELIVERY_THRESHOLD', 10000);

if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

date_default_timezone_set('Africa/Lagos');

if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
