<?php
/**
 * Temporary cPanel-ready config for live deployment.
 * Copy this content into the app's config.php on the live server if needed.
 */

define('APP_NAME', "Swagbag");
define('APP_TAGLINE', 'Premium Fashion & Lifestyle');
define('APP_CURRENCY', 'NGN');
define('APP_CURRENCY_SYMBOL', '₦');

define('ROOT_PATH', dirname(__DIR__));

define('APP_URL', 'https://swagbag.com.ng');

define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL', APP_URL . '/uploads');

define('APP_ENV', 'production');

define('DB_HOST', 'localhost');
define('DB_USER', 'davidsst_daviddb');
define('DB_PASS', '12345');
define('DB_NAME', 'davidsst_codesbyd4');

define('SESSION_NAME', 'swagbag_session_' . md5(APP_URL));
define('ADMIN_EMAIL', 'admin@swagbag.ng');

// SMTP config placeholders - fill in with your real mail credentials on cPanel
if (!defined('MAIL_HOST')) define('MAIL_HOST', '');
if (!defined('MAIL_PORT')) define('MAIL_PORT', '587');
if (!defined('MAIL_USERNAME')) define('MAIL_USERNAME', '');
if (!defined('MAIL_PASSWORD')) define('MAIL_PASSWORD', '');
if (!defined('MAIL_ENCRYPTION')) define('MAIL_ENCRYPTION', 'tls');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', APP_NAME);
if (!defined('MAIL_FROM_EMAIL')) define('MAIL_FROM_EMAIL', 'noreply@swagbag.com.ng');

if (!defined('PAYSTACK_SECRET_KEY')) define('PAYSTACK_SECRET_KEY', '');
if (!defined('PAYSTACK_PUBLIC_KEY')) define('PAYSTACK_PUBLIC_KEY', 'pk_live_1c45b8f902c231e57dfcc260f64bab680a6d4d88');
if (!defined('API_JWT_SECRET')) define('API_JWT_SECRET', 'dev-api-secret-change-me');

define('PRODUCTS_PER_PAGE', 12);

error_reporting(0);
ini_set('display_errors', 0);

date_default_timezone_set('Africa/Lagos');

if (!is_dir(UPLOAD_PATH)) {
    @mkdir(UPLOAD_PATH, 0755, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
