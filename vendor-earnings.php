<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_vendor();

$vendor = ensure_current_vendor();
if (!$vendor) redirect(APP_URL . '/');

// Totals
$vid = (int) $vendor['id'];
$totalSalesRes = $conn->prepare('SELECT SUM(gross_amount) AS total_sales FROM order_vendor_payouts WHERE vendor_id = ?');
$totalSalesRes->bind_param('i', $vid);
$totalSalesRes->execute();
$totalSales = $totalSalesRes->get_result()->fetch_assoc()['total_sales'] ?? 0.00;

$vendorEarningsRes = $conn->prepare('SELECT SUM(vendor_amount) AS vendor_earnings, SUM(marketplace_commission) AS marketplace_commission FROM order_vendor_payouts WHERE vendor_id = ?');
$vendorEarningsRes->bind_param('i', $vid);
$vendorEarningsRes->execute();
$vendorEarnings = $vendorEarningsRes->get_result()->fetch_assoc();

$paidCountRes = $conn->prepare('SELECT COUNT(*) AS paid_count FROM order_vendor_payouts WHERE vendor_id = ? AND status = "paid"');
$paidCountRes->bind_param('i', $vid);
$paidCountRes->execute();
$paidCount = $paidCountRes->get_result()->fetch_assoc()['paid_count'] ?? 0;

$pendingCountRes = $conn->prepare('SELECT COUNT(*) AS pending_count FROM order_vendor_payouts WHERE vendor_id = ? AND status = "pending"');
$pendingCountRes->bind_param('i', $vid);
$pendingCountRes->execute();
$pendingCount = $pendingCountRes->get_result()->fetch_assoc()['pending_count'] ?? 0;

$subaccount = $conn->prepare('SELECT paystack_subaccount_code, paystack_subaccount_status, kyc_status FROM vendors WHERE id = ? LIMIT 1');
$subaccount->bind_param('i', $vid);
$subaccount->execute();
$sub = $subaccount->get_result()->fetch_assoc();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>
<main class="container" style="padding:28px 20px;">
    <h1>My Earnings</h1>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:18px;">
        <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;min-width:200px;">
            <div style="font-size:12px;color:#6b7280">Total Sales</div>
            <div style="font-size:20px;font-weight:700;"><?= format_price($totalSales) ?></div>
        </div>
        <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;min-width:200px;">
            <div style="font-size:12px;color:#6b7280">Marketplace Commission</div>
            <div style="font-size:20px;font-weight:700;"><?= format_price($vendorEarnings['marketplace_commission'] ?? 0) ?></div>
        </div>
        <div style="padding:16px;border:1px solid #e5e7eb;border-radius:10px;min-width:200px;">
            <div style="font-size:12px;color:#6b7280">Vendor Earnings</div>
            <div style="font-size:20px;font-weight:700;"><?= format_price($vendorEarnings['vendor_earnings'] ?? 0) ?></div>
        </div>
    </div>

    <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:320px;padding:16px;border:1px solid #e5e7eb;border-radius:10px;">
            <h3>Payout Status</h3>
            <p>KYC Status: <strong><?= e($sub['kyc_status'] ?? 'not_started') ?></strong></p>
            <p>Paystack Subaccount: <strong><?= e($sub['paystack_subaccount_code'] ?? 'Not connected') ?></strong></p>
            <p>Subaccount Status: <strong><?= e($sub['paystack_subaccount_status'] ?? 'none') ?></strong></p>
            <?php if (($sub['kyc_status'] ?? '') !== 'verified'): ?>
                <div style="margin-top:8px;color:#92400e;background:#fff7ed;padding:10px;border-radius:6px;">Complete KYC to become eligible for automatic payouts. <a href="<?= APP_URL ?>/vendor/kyc.php">Complete KYC</a></div>
            <?php endif; ?>
        </div>

        <div style="flex:2;min-width:320px;padding:16px;border:1px solid #e5e7eb;border-radius:10px;">
            <h3>Recent Payouts</h3>
            <?php
                $rows = $conn->prepare('SELECT ovp.*, o.order_number FROM order_vendor_payouts ovp LEFT JOIN orders o ON o.id = ovp.order_id WHERE ovp.vendor_id = ? ORDER BY ovp.created_at DESC LIMIT 20');
                $rows->bind_param('i', $vid);
                $rows->execute();
                $res = $rows->get_result();
            ?>
            <?php if ($res->num_rows): ?>
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr><th>Order</th><th>Gross</th><th>Vendor</th><th>Commission</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($r['order_id'] ? $r['order_id'] : '-') ?></td>
                            <td><?= format_price($r['gross_amount']) ?></td>
                            <td><?= format_price($r['vendor_amount']) ?></td>
                            <td><?= format_price($r['marketplace_commission']) ?></td>
                            <td><?= e($r['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No payouts yet.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
