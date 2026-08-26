<?php
/**
 * Shared database connection using mysqli.
 *
 * Configuration is read only from config.php to keep deployments portable.
 */
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    if (APP_ENV === 'development') {
        error_log('Database connection failed: ' . $e->getMessage());
    }
    die('Database connection failed. Please check the database configuration.');
}
