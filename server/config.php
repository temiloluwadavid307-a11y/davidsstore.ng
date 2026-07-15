<?php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function get_input() {
    return json_decode(file_get_contents('php://input'), true);
}

function generate_token($payload, $secret = 'your-secret-key-change-in-production') {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode(array_merge($payload, ['exp' => time() + 3600 * 24 * 7]));
    $headerBase64 = base64_encode($header);
    $payloadBase64 = base64_encode($payload);
    $signature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secret, true);
    $signatureBase64 = base64_encode($signature);
    return "$headerBase64.$payloadBase64.$signatureBase64";
}

function verify_token($token, $secret = 'your-secret-key-change-in-production') {
    if (!$token) return null;
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    list($headerB64, $payloadB64, $signatureB64) = $parts;
    $signature = hash_hmac('sha256', "$headerB64.$payloadB64", $secret, true);
    if (base64_encode($signature) !== $signatureB64) return null;
    $payload = json_decode(base64_decode($payloadB64), true);
    if (isset($payload['exp']) && $payload['exp'] < time()) return null;
    return $payload;
}

function get_current_user($conn) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    $payload = verify_token($token);
    if (!$payload || !isset($payload['user_id'])) return null;
    $userId = $payload['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id = $userId");
    return $result->fetch_assoc();
}
?>
