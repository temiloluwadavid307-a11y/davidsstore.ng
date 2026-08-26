<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/paystack.php';
require_admin();

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $kyc_id = (int) ($_POST['kyc_id'] ?? 0);
    $vendor_id = (int) ($_POST['vendor_id'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    if (!validate_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid token');
        redirect(APP_URL . '/admin/kyc_review.php');
    }
    if ($action === 'approve') {
        $u = $conn->prepare('UPDATE vendor_kyc SET status = "verified", reviewed_at = NOW(), reviewed_by = ? WHERE id = ?');
        $u->bind_param('ii', $_SESSION['user_id'], $kyc_id);
        $u->execute();
        $conn->query('UPDATE vendors SET kyc_status = "verified" WHERE id = ' . intval($vendor_id));
        add_audit_log($_SESSION['user_id'] ?? 0, 'vendor_kyc_approved', 'Approved KYC ' . $kyc_id);

        // Attempt subaccount creation if missing
        $vstmt = $conn->prepare('SELECT id, business_name, store_name, bank_account_number, bank_code, paystack_subaccount_code FROM vendors WHERE id = ? LIMIT 1');
        $vstmt->bind_param('i', $vendor_id);
        $vstmt->execute();
        $v = $vstmt->get_result()->fetch_assoc();
        if ($v && empty($v['paystack_subaccount_code']) && !empty($v['bank_account_number']) && !empty($v['bank_code'])) {
            $createParams = ['business_name' => $v['business_name'] ?: $v['store_name'], 'bank_code' => $v['bank_code'], 'account_number' => $v['bank_account_number']];
            $resp = paystack_create_subaccount($createParams);
            if (empty($resp['error']) && !empty($resp['body']['status']) && $resp['body']['status'] === true) {
                $sub = $resp['body']['data']['subaccount_code'] ?? null;
                if ($sub) {
                    $u2 = $conn->prepare('UPDATE vendors SET paystack_subaccount_code = ?, paystack_subaccount_status = ?, paystack_subaccount_created_at = NOW(), paystack_subaccount_updated_at = NOW() WHERE id = ?');
                    $stat = 'created';
                    $u2->bind_param('ssi', $sub, $stat, $vendor_id);
                    $u2->execute();
                    add_audit_log($_SESSION['user_id'] ?? 0, 'paystack_subaccount_created', 'Created subaccount ' . $sub . ' for vendor ' . $vendor_id);
                }
            } else {
                add_audit_log($_SESSION['user_id'] ?? 0, 'paystack_subaccount_failed', 'Subaccount creation failed for vendor ' . $vendor_id . ' resp: ' . json_encode($resp));
            }
        }

        set_flash('success', 'KYC approved.');
        redirect(APP_URL . '/admin/kyc_review.php');
    }
    if ($action === 'reject') {
        $u = $conn->prepare('UPDATE vendor_kyc SET status = "rejected", reviewed_at = NOW(), reviewed_by = ?, rejection_reason = ? WHERE id = ?');
        $u->bind_param('iss', $_SESSION['user_id'], $notes, $kyc_id);
        $u->execute();
        $conn->query('UPDATE vendors SET kyc_status = "rejected" WHERE id = ' . intval($vendor_id));
        add_audit_log($_SESSION['user_id'] ?? 0, 'vendor_kyc_rejected', 'Rejected KYC ' . $kyc_id . ' reason: ' . $notes);
        set_flash('success', 'KYC rejected.');
        redirect(APP_URL . '/admin/kyc_review.php');
    }
}

$rows = $conn->query('SELECT vk.*, v.store_name, v.user_id FROM vendor_kyc vk JOIN vendors v ON v.id = vk.vendor_id WHERE vk.status IN ("submitted","under_review") ORDER BY vk.submitted_at DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-card" style="grid-column:1/-1;">
        <h2>Vendor KYC Review</h2>
        <?php if ($rows): ?>
            <?php foreach ($rows as $r): ?>
                <div style="border:1px solid #e5e7eb;padding:12px;margin-bottom:12px;border-radius:10px;">
                    <strong><?= e($r['store_name']) ?></strong>
                    <div><?= e($r['legal_name']) ?> — <?= e($r['phone']) ?></div>
                    <div style="margin-top:8px;">Submitted: <?= e($r['submitted_at']) ?></div>
                    <form method="post" style="margin-top:10px;display:flex;gap:8px;align-items:center;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kyc_id" value="<?= (int)$r['id'] ?>">
                        <input type="hidden" name="vendor_id" value="<?= (int)$r['vendor_id'] ?>">
                        <input type="text" name="notes" placeholder="Optional notes/reason">
                        <button name="action" value="approve" class="dashboard-btn dashboard-btn-primary">Approve</button>
                        <button name="action" value="reject" class="dashboard-btn">Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div>No KYC submissions pending review.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
