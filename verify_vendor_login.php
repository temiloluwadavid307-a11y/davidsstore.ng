<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Test credentials
$test_email = 'vendor@davidsstore.ng';
$test_password = 'password';

echo "Testing login for $test_email...<br><br>";

$stmt = $conn->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
$stmt->bind_param('s', $test_email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo "User not found!<br>";
} else {
    echo "User found! Role: " . ($user['role'] ?? 'none') . "<br>";
    if (password_verify($test_password, $user['password_hash'])) {
        echo "<strong style='color:green'>LOGIN SUCCESS!</strong> Password matches!<br>";
        echo "You can now log in at " . APP_URL . "/login.php<br>";
    } else {
        echo "<strong style='color:red'>Password mismatch!</strong><br>";
        echo "Hash from DB: " . $user['password_hash'];
    }
}
?>
