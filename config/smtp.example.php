<?php
// Copy this file to config/smtp.php and set your real SMTP credentials there.

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
}

if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 587);
}

if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', 'your-email@gmail.com');
}

if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', 'your-smtp-app-password');
}

if (!defined('SMTP_ENCRYPTION')) {
    define('SMTP_ENCRYPTION', 'tls');
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', "David's Store");
}

if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', 'noreply@davidsstore.ng');
}

if (!defined('PAYSTACK_SECRET_KEY')) {
    define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxx');
}
