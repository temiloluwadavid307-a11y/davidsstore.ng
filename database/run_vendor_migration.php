<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'codesbyd4';
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    fwrite(STDERR, "DB connection failed: {$conn->connect_error}\n");
    exit(1);
}

$conn->set_charset('utf8mb4');
$sql = file_get_contents(__DIR__ . '/add_vendor_applications.sql');
if ($sql === false) {
    fwrite(STDERR, "Unable to read migration file.\n");
    exit(1);
}

if (!$conn->multi_query($sql)) {
    fwrite(STDERR, "Migration failed: {$conn->error}\n");
    exit(1);
}

do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

echo "migration-applied\n";
