<?php
/**
 * Application configuration for David's Store.
 *
 * All environment-specific settings are centralized in this file so the same
 * codebase can run on XAMPP, InfinityFree, Namecheap, cPanel, or a VPS.
 */

define('APP_NAME', "David's Store");
define('APP_TAGLINE', 'Premium Fashion & Lifestyle');
define('APP_CURRENCY', 'NGN');
define('APP_CURRENCY_SYMBOL', '₦');

define('ROOT_PATH', realpath(dirname(__DIR__)) ?: dirname(__DIR__));

$host = getenv('HTTP_HOST') ?: ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
$host = preg_replace('/:\\d+$/', '', $host);

$isSecure = (
    (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
);
$scheme = $isSecure ? 'https' : 'http';

$appBasePath = '';
if (!empty($_SERVER['SCRIPT_NAME'])) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if ($scriptDir !== '/' && $scriptDir !== '\\') {
        $appBasePath = rtrim($scriptDir, '/');
    }
}
$appBasePath = preg_replace('#/(admin|vendor|customer|server|actions)$#', '', $appBasePath);
$appBasePath = rtrim($appBasePath, '/');

// When running internal scripts such as server/seed.php or actions/*, the app URL
// should still resolve to the root application path rather than the utility path.
define('APP_URL', getenv('APP_URL') ?: ($scheme . '://' . $host . ($appBasePath ? $appBasePath : '')));

define('APP_ENV', getenv('APP_ENV') ?: ((stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false || stripos($host, '::1') !== false) ? 'development' : 'production'));

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'codesbyd4');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL', APP_URL . '/uploads');

define('SESSION_NAME', 'davids_store_session_' . substr(md5(APP_URL), 0, 16));
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);
define('SESSION_SECURE', APP_ENV === 'production');
define('SESSION_SAMESITE', 'Lax');

define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@davidsstore.ng');
define('API_JWT_SECRET', getenv('API_JWT_SECRET') ?: 'dev-api-secret-change-me');
define('PAYSTACK_PUBLIC_KEY', getenv('PAYSTACK_PUBLIC_KEY') ?: 'pk_test_ed483154365e840a5b0bda0047c4e4bd1ba61a28');
define('PRODUCTS_PER_PAGE', 12);
define('FREE_DELIVERY_THRESHOLD', 10000);

if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
ini_set('session.cookie_samesite', SESSION_SAMESITE);

session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => $host,
    'secure' => SESSION_SECURE,
    'httponly' => true,
    'samesite' => SESSION_SAMESITE,
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}
