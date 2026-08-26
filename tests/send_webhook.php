<?php
// Usage: php tests/send_webhook.php [webhook_url] [fixture_json]
// Example: php tests/send_webhook.php http://localhost/server/paystack_webhook.php tests/fixtures/webhook_charge_success.json

$webhook = $argv[1] ?? 'http://localhost/server/paystack_webhook.php';
$fixture = $argv[2] ?? __DIR__ . '/fixtures/webhook_charge_success.json';

$secret = getenv('PAYSTACK_WEBHOOK_SECRET') ?: '';
if (!$secret) {
    echo "PAYSTACK_WEBHOOK_SECRET not set in environment.\n";
    exit(1);
}

if (!file_exists($fixture)) {
    echo "Fixture not found: $fixture\n";
    exit(1);
}

$payload = file_get_contents($fixture);
$signature = hash_hmac('sha512', $payload, $secret);

$ch = curl_init($webhook);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Paystack-Signature: ' . $signature,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$resp = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo "Request error: $err\n";
    exit(1);
}

echo "HTTP $code\n";
echo $resp . "\n";

?>
