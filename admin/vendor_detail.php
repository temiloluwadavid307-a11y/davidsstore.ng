<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/paystack.php';
require_admin();

$vendor_id = (int) ($_GET['id'] ?? 0);
if ($vendor_id <= 0) redirect(APP_URL . '/admin/vendor-applications.php');

$stmt = $conn->prepare('SELECT * FROM vendors WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $vendor_id);
$stmt->execute();
$vendor = $stmt->get_result()->fetch_assoc();
if (!$vendor) redirect(APP_URL . '/admin/vendor-applications.php');

// Handle manual subaccount creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_subaccount') {
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid token');
        redirect(APP_URL . '/admin/vendor_detail.php?id=' . $vendor_id);
    }
    // Ensure KYC verified and bank info present
    if (($vendor['kyc_status'] ?? '') !== 'verified') {
        set_flash('error', 'Vendor KYC not verified');
        redirect(APP_URL . '/admin/vendor_detail.php?id=' . $vendor_id);
    }
    if (empty($vendor['bank_account_number']) || empty($vendor['bank_code'])) {
        set_flash('error', 'Vendor bank information missing');
        redirect(APP_URL . '/admin/vendor_detail.php?id=' . $vendor_id);
    }

    $createParams = ['business_name' => $vendor['business_name'] ?: $vendor['store_name'] ?: $vendor['name'], 'bank_code' => $vendor['bank_code'], 'account_number' => $vendor['bank_account_number']];
    $resp = paystack_create_subaccount($createParams);
    if (!empty($resp['error'])) {
        add_audit_log($_SESSION['user_id'] ?? 0, 'paystack_subaccount_failed', 'Manual create failed for vendor ' . $vendor_id . ': ' . $resp['error']);
        set_flash('error', 'Paystack API error: ' . $resp['error']);
        redirect(APP_URL . '/admin/vendor_detail.php?id=' . $vendor_id);
    }
    $body = $resp['body'] ?? null;
    if (!empty($body['status']) && $body['status'] === true && !empty($body['data']['subaccount_code'])) {
        $subcode = $body['data']['subaccount_code'];
        $u = $conn->prepare('UPDATE vendors SET paystack_subaccount_code = ?, paystack_subaccount_status = ?, paystack_subaccount_created_at = NOW(), paystack_subaccount_updated_at = NOW() WHERE id = ?');
        $stat = 'created';
        $u->bind_param('ssi', $subcode, $stat, $vendor_id);
        $u->execute();
        add_audit_log($_SESSION['user_id'] ?? 0, 'paystack_subaccount_created', 'Manual subaccount ' . $subcode . ' for vendor ' . $vendor_id);
        set_flash('success', 'Subaccount created: ' . $subcode);
    } else {
        add_audit_log($_SESSION['user_id'] ?? 0, 'paystack_subaccount_failed', 'Manual create unexpected response for vendor ' . $vendor_id . ': ' . json_encode($body));
        set_flash('error', 'Unexpected Paystack response.');
    }
    redirect(APP_URL . '/admin/vendor_detail.php?id=' . $vendor_id);
}

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-card" style="grid-column: 1 / -1;">
        <h2>Vendor: <?= e($vendor['store_name'] ?? $vendor['name']) ?></h2>
        <p>Email: <?= e($vendor['email']) ?> | Phone: <?= e($vendor['phone']) ?></p>
        <div style="display:flex;gap:12px;margin-top:12px;">
            <div style="padding:12px;border:1px solid #e5e7eb;border-radius:8px;">
                <strong>KYC Status</strong>
                <div><?= e($vendor['kyc_status'] ?? 'not_started') ?></div>
            </div>
            <div style="padding:12px;border:1px solid #e5e7eb;border-radius:8px;">
                <strong>Paystack Subaccount</strong>
                <div><?= e($vendor['paystack_subaccount_code'] ?? 'Not created') ?></div>
                <div>Status: <?= e($vendor['paystack_subaccount_status'] ?? 'none') ?></div>
            </div>
        </div>

        <div style="margin-top:16px;">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_subaccount">
                <button class="dashboard-btn dashboard-btn-primary">Create Subaccount (manual)</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
