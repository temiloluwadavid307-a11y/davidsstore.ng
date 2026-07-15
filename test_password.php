<?php
// Test what the known hash corresponds to
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing common passwords...<br><br>";

$test_passwords = ['password', 'password123', 'admin123', 'customer123', 'vendor123', '123456', 'secret'];

foreach ($test_passwords as $pw) {
    $result = password_verify($pw, $hash) ? "<strong style='color:green'>MATCH!</strong>" : "no";
    echo "Testing '$pw' → $result<br>";
}
?>
