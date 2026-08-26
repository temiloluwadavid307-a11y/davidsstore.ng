<?php
// Paystack helper: minimal safe wrappers for creating subaccounts, initializing transactions, and verification.
require_once __DIR__ . '/config.php';

function paystack_request(string $method, string $endpoint, array $data = []) {
    $secret = defined('PAYSTACK_SECRET_KEY') ? PAYSTACK_SECRET_KEY : getenv('PAYSTACK_SECRET_KEY');
    if (empty($secret)) {
        return ['error' => 'Paystack secret key not configured'];
    }

    $url = rtrim('https://api.paystack.co/', '/') . '/' . ltrim($endpoint, '/');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secret,
        'Content-Type: application/json',
        'Cache-Control: no-cache',
    ]);
    if (in_array($method, ['POST','PUT','PATCH'])) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) return ['error' => $err];
    $json = json_decode($resp, true);
    if ($json === null) return ['error' => 'Invalid JSON response from Paystack', 'raw' => $resp, 'http_code' => $code];
    return ['http_code' => $code, 'body' => $json];
}

function paystack_create_subaccount(array $params) {
    // params: business_name, bank_code, account_number, percentage_charge (optional)
    $required = ['business_name','bank_code','account_number'];
    foreach ($required as $r) {
        if (empty($params[$r])) return ['error' => "Missing $r"];
    }
    $payload = [
        'business_name' => $params['business_name'],
        'bank_code' => $params['bank_code'],
        'account_number' => $params['account_number'],
    ];
    if (!empty($params['percentage_charge'])) $payload['percentage_charge'] = $params['percentage_charge'];

    return paystack_request('POST', 'subaccount', $payload);
}

function paystack_initialize_transaction(array $params) {
    // params: email, amount_kobo (integer), reference, metadata (array), subaccount OR split_code, transaction_charge, bearer
    $required = ['email','amount'];
    foreach ($required as $r) if (empty($params[$r])) return ['error' => "Missing $r"];

    $payload = [
        'email' => $params['email'],
        'amount' => (int) $params['amount'],
    ];
    if (!empty($params['reference'])) $payload['reference'] = $params['reference'];
    if (!empty($params['metadata']) && is_array($params['metadata'])) $payload['metadata'] = $params['metadata'];
    if (!empty($params['subaccount'])) $payload['subaccount'] = $params['subaccount'];
    if (!empty($params['split_code'])) $payload['split_code'] = $params['split_code'];
    if (isset($params['transaction_charge'])) $payload['transaction_charge'] = (int) $params['transaction_charge'];
    if (!empty($params['bearer'])) $payload['bearer'] = $params['bearer'];

    return paystack_request('POST', 'transaction/initialize', $payload);
}

function paystack_create_split(array $params) {
    // params: name, type ('percentage'|'flat'), subaccounts => array of ['subaccount'=> 'ACCT_xxx','share'=> number]
    $required = ['name','type','subaccounts'];
    foreach ($required as $r) if (empty($params[$r])) return ['error' => "Missing $r"]; 
    $payload = [
        'name' => $params['name'],
        'type' => $params['type'],
        'subaccounts' => $params['subaccounts']
    ];
    return paystack_request('POST', 'split', $payload);
}

function paystack_verify_transaction(string $reference) {
    if (empty($reference)) return ['error' => 'Missing reference'];
    // Mock mode for local testing: if PAYSTACK_MOCK_VERIFY=1, return a canned successful response
    if (getenv('PAYSTACK_MOCK_VERIFY') === '1') {
        $fixture = __DIR__ . '/../tests/fixtures/verify.json';
        if (file_exists($fixture)) {
            $json = json_decode(file_get_contents($fixture), true);
            return ['http_code' => 200, 'body' => $json];
        }
        $mock = [
            'status' => true,
            'message' => 'Mock verification',
            'data' => [
                'id' => 'MOCK_' . $reference,
                'reference' => $reference,
                'amount' => 10000,
                'status' => 'success',
                'metadata' => new stdClass(),
            ],
        ];
        return ['http_code' => 200, 'body' => $mock];
    }
    return paystack_request('GET', 'transaction/verify/' . urlencode($reference));
}

function paystack_verify_webhook_signature(string $rawPayload, string $signatureHeader) {
    $secret = getenv('PAYSTACK_WEBHOOK_SECRET') ?: (defined('PAYSTACK_WEBHOOK_SECRET') ? PAYSTACK_WEBHOOK_SECRET : '');
    if (empty($secret)) return false;
    $computed = hash_hmac('sha512', $rawPayload, $secret);
    return hash_equals($computed, $signatureHeader);
}

?>
