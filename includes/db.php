<?php
/**
 * Shared database connection using mysqli.
 *
 * Database credentials are read only from config.php so this application can
 * be moved between localhost and hosted environments without updating code.
 */
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    if ($conn->connect_error) {
        throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
    }
    $conn->set_charset(DB_CHARSET);
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (APP_ENV !== 'production') {
        echo '<pre>Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    exit('Database connection failed. Please check the database configuration.');
}
