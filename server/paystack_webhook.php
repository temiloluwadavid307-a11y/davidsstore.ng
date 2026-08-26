<?php
// Paystack webhook receiver
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/paystack.php';

// Read raw body and signature
$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? ($_SERVER['HTTP_X_PAYSTACK_SIGN'] ?? '');

// Verify signature if secret configured
if (!paystack_verify_webhook_signature($raw, $sig)) {
    http_response_code(400);
    error_log('Invalid Paystack webhook signature');
    echo json_encode(['status' => false, 'message' => 'Invalid signature']);
    exit;
}

$payload = json_decode($raw, true);
if (!$payload) {
    http_response_code(400);
    echo json_encode(['status' => false, 'message' => 'Invalid payload']);
    exit;
}

$event = $payload['event'] ?? '';
$data = $payload['data'] ?? null;

// Idempotency: check if reference already processed
$reference = $data['reference'] ?? null;
if (!$reference) {
    http_response_code(200);
    echo json_encode(['status' => true]);
    exit;
}

// Fetch existing payment record if any
$check = $conn->prepare('SELECT id, status FROM payments WHERE paystack_reference = ? LIMIT 1');
$check->bind_param('s', $reference);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

// Determine success-like events
$isSuccessEvent = ($event === 'charge.success') || (($data['status'] ?? '') === 'success') || ($event === 'transfer.success') || ($event === 'transfer.received');

if ($isSuccessEvent) {
    // Double-check with Paystack verify
    $verify = paystack_verify_transaction($reference);
    if (!empty($verify['error'])) {
        error_log('Paystack verify error: ' . json_encode($verify));
        http_response_code(500);
        echo json_encode(['status' => false]);
        exit;
    }
    $body = $verify['body'] ?? null;
    if (empty($body['status']) || $body['status'] !== true) {
        error_log('Paystack verify returned non-true status: ' . json_encode($body));
        http_response_code(500);
        echo json_encode(['status' => false]);
        exit;
    }

    $trx = $body['data'];
    $conn->begin_transaction();
    try {
        $order_id = isset($trx['metadata']['order_id']) ? (int)$trx['metadata']['order_id'] : null;
        $user_id = isset($trx['metadata']['user_id']) ? (int)$trx['metadata']['user_id'] : null;
        $amount = isset($trx['amount']) ? ((int)$trx['amount']) / 100.0 : null;
        $metaJson = json_encode($trx);

        if ($existing) {
            $u = $conn->prepare('UPDATE payments SET status = ?, paystack_transaction_id = ?, gross_amount = ?, meta = ?, updated_at = NOW() WHERE id = ?');
            $status = 'success';
            $u->bind_param('ssdis', $status, $trx['id'] ?? '', $amount, $metaJson, $existing['id']);
            $u->execute();
            $payment_id = $existing['id'];
        } else {
            $ins = $conn->prepare('INSERT INTO payments (order_id, user_id, gateway, paystack_reference, paystack_transaction_id, gross_amount, marketplace_commission, status, meta, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
            $marketplace_commission = $trx['metadata']['marketplace_commission'] ?? 0.00;
            $gateway = 'paystack';
            // types: i (order_id), i (user_id), s (gateway), s (reference), s (trx id), d (amount), d (marketplace_commission), s (status), s (meta)
            $ins->bind_param('iissddsss', $order_id, $user_id, $gateway, $reference, $trx['id'] ?? '', $amount, $marketplace_commission, $status = 'success', $metaJson);
            $ins->execute();
            $payment_id = $conn->insert_id;
        }

        // Update order status if order_id present
        if ($order_id) {
            $u2 = $conn->prepare('UPDATE orders SET payment_method = ?, payment_status = "paid", status = "processing", updated_at = NOW() WHERE id = ?');
            $pm = 'paystack';
            $u2->bind_param('si', $pm, $order_id);
            $u2->execute();
        }

        // Attempt to reconcile splits/transfers to order_vendor_payouts
        $reconciled = false;
        // Pattern 1: transaction verify returns 'split' with subaccounts and amounts
        if (!empty($trx['split']) && is_array($trx['split'])) {
            $sa = $trx['split'];
            if (!empty($sa['subaccounts']) && is_array($sa['subaccounts'])) {
                foreach ($sa['subaccounts'] as $s) {
                    $subcode = $s['subaccount_code'] ?? ($s['subaccount'] ?? null);
                    $amount_sub = isset($s['amount']) ? ($s['amount'] / 100.0) : null;
                    if ($subcode && $order_id && $amount_sub !== null) {
                        $upd = $conn->prepare('UPDATE order_vendor_payouts SET status = "paid", paystack_split_reference = ?, vendor_amount = ?, updated_at = NOW() WHERE order_id = ? AND subaccount_code = ? AND status != "paid"');
                        $upd->bind_param('sdis', $trx['id'], $amount_sub, $order_id, $subcode);
                        $upd->execute();
                        $reconciled = true;
                    }
                }
            }
        }

        // Pattern 2: webhook payload includes transfers array
        if (!$reconciled && !empty($payload['data']['transfers']) && is_array($payload['data']['transfers'])) {
            foreach ($payload['data']['transfers'] as $t) {
                $subcode = $t['recipient'] ?? ($t['subaccount'] ?? null);
                $amount_sub = isset($t['amount']) ? ($t['amount'] / 100.0) : null;
                $transfer_ref = $t['id'] ?? ($t['transfer_code'] ?? null);
                if ($subcode && $order_id && $amount_sub !== null) {
                    $upd = $conn->prepare('UPDATE order_vendor_payouts SET status = "paid", paystack_split_reference = ?, vendor_amount = ?, updated_at = NOW() WHERE order_id = ? AND subaccount_code = ? AND status != "paid"');
                    $upd->bind_param('sdis', $transfer_ref, $amount_sub, $order_id, $subcode);
                    $upd->execute();
                    $reconciled = true;
                }
            }
        }

        // Pattern 3: event is transfer.success with recipient/subaccount in $data
        if (!$reconciled && ($event === 'transfer.success' || $event === 'transfer.received')) {
            $subcode = $data['recipient'] ?? ($data['subaccount'] ?? null);
            $amount_sub = isset($data['amount']) ? ($data['amount'] / 100.0) : null;
            $transfer_ref = $data['id'] ?? null;
            if ($subcode && $order_id && $amount_sub !== null) {
                $upd = $conn->prepare('UPDATE order_vendor_payouts SET status = "paid", paystack_split_reference = ?, vendor_amount = ?, updated_at = NOW() WHERE order_id = ? AND subaccount_code = ? AND status != "paid"');
                $upd->bind_param('sdis', $transfer_ref, $amount_sub, $order_id, $subcode);
                $upd->execute();
                $reconciled = true;
            }
        }

        if (!$reconciled) {
            // Could not automatically reconcile per-vendor payouts; mark for manual review
            $note = 'Unable to reconcile split payouts automatically for reference: ' . $reference . ' payload keys: ' . implode(',', array_keys($payload['data'] ?? []));
            add_audit_log($_SESSION['user_id'] ?? 0, 'paystack_reconcile_pending', $note);
            // Optionally set order_vendor_payouts to on_hold
            if ($order_id) {
                $conn->query("UPDATE order_vendor_payouts SET status = 'on_hold' WHERE order_id = " . intval($order_id) . " AND status = 'pending'");
            }
        }

        $conn->commit();
        http_response_code(200);
        echo json_encode(['status' => true]);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Webhook processing error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'processing error']);
        exit;
    }
}

// For other events, acknowledge
http_response_code(200);
echo json_encode(['status' => true]);
exit;

?>
