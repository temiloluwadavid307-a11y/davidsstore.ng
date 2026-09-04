<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/paystack.php';
require_admin();

$page_title = 'Vendor Applications — ' . APP_NAME;
$page_name = 'Vendor Applications';
$user_role = 'admin';
$active_page = 'vendor-applications';
$logout_url = APP_URL . '/actions/logout.php';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = (int) ($_POST['application_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $review_notes = sanitize($_POST['review_notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!validate_csrf($csrf_token)) {
        $errors[] = 'Invalid request token.';
    }

    if ($application_id <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
        $errors[] = 'Invalid review request.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT * FROM vendor_applications WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $application_id);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc();

        if (!$application) {
            $errors[] = 'Application not found.';
        } else {
            $reviewed_by = (int) ($_SESSION['user_id'] ?? 0);
            $status = $decision;
            $conn->begin_transaction();
            try {
                $update_app = $conn->prepare('UPDATE vendor_applications SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?');
                $update_app->bind_param('ssii', $status, $review_notes, $reviewed_by, $application_id);
                if (!$update_app->execute()) {
                    throw new Exception('Unable to update application.');
                }

                $user_stmt = $conn->prepare('SELECT id, first_name, last_name, email FROM users WHERE id = ? LIMIT 1');
                $user_stmt->bind_param('i', $application['user_id']);
                $user_stmt->execute();
                $user = $user_stmt->get_result()->fetch_assoc();

                if ($status === 'approved') {
                    if (!$user) {
                        throw new Exception('Applicant user not found.');
                    }

                    $vendor_name = $application['store_name'] ?: $application['business_name'];
                    if (empty($vendor_name)) {
                        $vendor_name = 'Vendor ' . $application['user_id'];
                    }
                    $slug = slugify($vendor_name);
                    $slug_base = $slug;
                    $attempt = 1;
                    while (true) {
                        $slug_check = $conn->prepare('SELECT id FROM vendors WHERE store_slug = ? AND user_id != ? LIMIT 1');
                        $slug_check->bind_param('si', $slug, $application['user_id']);
                        $slug_check->execute();
                        if ($slug_check->get_result()->num_rows === 0) {
                            break;
                        }
                        $slug = $slug_base . '-' . $attempt;
                        $attempt++;
                    }

                    $vendor_check = $conn->prepare('SELECT id FROM vendors WHERE email = ? OR user_id = ? LIMIT 1');
                    $vendor_check->bind_param('si', $application['business_email'], $application['user_id']);
                    $vendor_check->execute();
                    $existing_vendor = $vendor_check->get_result()->fetch_assoc();

                    if ($existing_vendor) {
                        $vendor_update = $conn->prepare('UPDATE vendors SET name = ?, store_name = ?, business_name = ?, business_email = ?, phone = ?, location = ?, business_type = ?, website = ?, description = ?, store_slug = ?, status = "approved", approval_date = NOW(), approved_by = ?, rejection_reason = NULL, is_active = 1, verification_status = "verified", updated_at = NOW() WHERE id = ?');
                        $vendor_update->bind_param('sssssssssiii', $vendor_name, $application['store_name'], $application['business_name'], $application['business_email'], $application['contact_phone'], $application['location'], $application['business_type'], $application['website'], $application['description'], $slug, $reviewed_by, $existing_vendor['id']);
                        if (!$vendor_update->execute()) {
                            throw new Exception('Unable to update vendor record.');
                        }
                        $created_vendor_id = $existing_vendor['id'];
                    } else {
                        $vendor_insert = $conn->prepare('INSERT INTO vendors (user_id, name, store_name, business_name, email, business_email, phone, location, business_type, website, description, store_slug, status, approval_date, approved_by, is_active, verification_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "approved", NOW(), ?, 1, "verified", NOW(), NOW())');
                        $vendor_insert->bind_param('isssssssssssi', $application['user_id'], $vendor_name, $application['store_name'], $application['business_name'], $application['business_email'], $application['business_email'], $application['contact_phone'], $application['location'], $application['business_type'], $application['website'], $application['description'], $slug, $reviewed_by);
                        if (!$vendor_insert->execute()) {
                            throw new Exception('Unable to create vendor record.');
                        }
                        $created_vendor_id = $conn->insert_id;
                    }

                    $user_update = $conn->prepare('UPDATE users SET role = "vendor" WHERE id = ?');
                    $user_update->bind_param('i', $application['user_id']);
                    if (!$user_update->execute()) {
                        throw new Exception('Unable to grant vendor role.');
                    }

                    add_notification($application['user_id'], 'Vendor application approved', 'Your vendor application has been approved. You can now access the vendor dashboard.', 'success');
                    send_vendor_approval_email((int) $user['id'], $user['first_name'] . ' ' . $user['last_name'], $application['business_name'], $application['store_name'], $user['email']);
                    add_audit_log($reviewed_by, 'vendor_application_approved', 'Approved vendor application #' . $application_id . ' for user ' . $application['user_id']);
                } else {
                    add_notification($application['user_id'], 'Vendor application rejected', 'Your vendor application was rejected. Please contact support for more details.', 'warning');

                    $reject_user_id = (int) ($user['id'] ?? 0);
                    if ($reject_user_id > 0 && !empty($user['email'])) {
                        send_vendor_rejection_email($reject_user_id, ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''), $user['email'], $review_notes);
                    } else {
                        add_audit_log($reviewed_by, 'vendor_application_rejected_no_user', 'Rejected vendor application #' . $application_id . ' for user ' . $application['user_id'] . ' but no user record/email was available for notification.');
                    }

                    add_audit_log($reviewed_by, 'vendor_application_rejected', 'Rejected vendor application #' . $application_id . ' for user ' . $application['user_id']);
                }

                $conn->commit();
                $success = 'Application reviewed successfully.';

                // After successful commit, attempt to create Paystack subaccount if vendor has provided bank info and KYC verified.
                try {
                    if (!empty($created_vendor_id)) {
                        $vstmt = $conn->prepare('SELECT id, name, store_name, business_name, bank_account_number, bank_code, bank_account_name, kyc_status, paystack_subaccount_code FROM vendors WHERE id = ? LIMIT 1');
                        $vstmt->bind_param('i', $created_vendor_id);
                        $vstmt->execute();
                        $vendor_row = $vstmt->get_result()->fetch_assoc();
                        if ($vendor_row && ($vendor_row['kyc_status'] ?? '') === 'verified' && empty($vendor_row['paystack_subaccount_code']) && !empty($vendor_row['bank_account_number']) && !empty($vendor_row['bank_code'])) {
                            $createParams = [
                                'business_name' => $vendor_row['business_name'] ?: $vendor_row['store_name'] ?: $vendor_row['name'],
                                'bank_code' => $vendor_row['bank_code'],
                                'account_number' => $vendor_row['bank_account_number'],
                                'percentage_charge' => 0
                            ];
                            $resp = paystack_create_subaccount($createParams);
                            if (!empty($resp['error'])) {
                                add_audit_log($reviewed_by, 'paystack_subaccount_failed', 'Subaccount creation error for vendor ' . $created_vendor_id . ': ' . $resp['error']);
                            } else {
                                $body = $resp['body'] ?? null;
                                if (!empty($body['status']) && $body['status'] === true && !empty($body['data']['subaccount_code'])) {
                                    $subcode = $body['data']['subaccount_code'];
                                    $u = $conn->prepare('UPDATE vendors SET paystack_subaccount_code = ?, paystack_subaccount_status = ?, paystack_subaccount_created_at = NOW(), paystack_subaccount_updated_at = NOW() WHERE id = ?');
                                    $status_val = 'created';
                                    $u->bind_param('ssi', $subcode, $status_val, $created_vendor_id);
                                    $u->execute();
                                    add_audit_log($reviewed_by, 'paystack_subaccount_created', 'Subaccount created for vendor ' . $created_vendor_id . ' code ' . $subcode);
                                } else {
                                    add_audit_log($reviewed_by, 'paystack_subaccount_failed', 'Subaccount creation returned unexpected response for vendor ' . $created_vendor_id . ': ' . json_encode($body));
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    add_audit_log($reviewed_by, 'paystack_subaccount_exception', 'Exception creating subaccount: ' . $e->getMessage());
                }
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = $e->getMessage();
            }
        }
    }
}

$applications = $conn->query('SELECT va.*, u.first_name, u.last_name, u.email AS applicant_email FROM vendor_applications va LEFT JOIN users u ON u.id = va.user_id ORDER BY va.created_at DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-card" style="grid-column: 1 / -1;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
            <div>
                <h2 style="margin:0;">Vendor applications</h2>
                <p style="margin:6px 0 0; color:#6b7280;">Review customer requests and approve or reject them before they can access the vendor dashboard.</p>
            </div>
        </div>

        <?php if ($errors): ?>
            <div style="background:#fef2f2;color:#991b1b;padding:12px 14px;border-radius:10px;margin-bottom:16px;">
                <ul style="margin:0; padding-left:18px;">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="background:#ecfdf3;color:#166534;padding:12px 14px;border-radius:10px;margin-bottom:16px;"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($applications): ?>
            <div style="display:grid; gap:16px;">
                <?php foreach ($applications as $application): ?>
                    <div style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
                        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
                            <div>
                                <h3 style="margin:0 0 4px; font-size:18px;"><?= e($application['store_name']) ?></h3>
                                <p style="margin:0; color:#374151;">Applicant: <?= e($application['first_name'] . ' ' . $application['last_name']) ?> (<?= e($application['applicant_email']) ?>)</p>
                                <p style="margin:4px 0 0; color:#6b7280;">Business: <?= e($application['business_name']) ?> • <?= e($application['location']) ?> • <?= e($application['business_type']) ?></p>
                            </div>
                            <span style="padding:6px 10px; border-radius:999px; background:#f3f4f6; color:#374151; text-transform:capitalize; font-size:13px; font-weight:600;"><?= e($application['status']) ?></span>
                        </div>
                        <div style="margin-top:12px; display:grid; gap:8px; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); color:#4b5563; font-size:14px;">
                            <div><strong>Phone:</strong> <?= e($application['contact_phone']) ?></div>
                            <div><strong>Email:</strong> <?= e($application['business_email']) ?></div>
                            <div><strong>Website:</strong> <?= e($application['website'] ?: '—') ?></div>
                        </div>
                        <div style="margin-top:14px; padding:12px 14px; border-radius:12px; background:#f9fafb; color:#374151;">
                            <strong>Business summary</strong>
                            <p style="margin:6px 0 0;"><?= e($application['description']) ?></p>
                        </div>
                        <?php if (!empty($application['review_notes'])): ?>
                            <div style="margin-top:12px; padding:12px 14px; border-radius:12px; background:#fef3c7; color:#92400e;">
                                <strong>Review notes</strong>
                                <p style="margin:6px 0 0;"><?= e($application['review_notes']) ?></p>
                            </div>
                        <?php endif; ?>
                        <form method="post" style="margin-top:16px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                            <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                        <?= csrf_field() ?>
                            <div style="flex:1; min-width:240px;">
                                <label for="review_notes_<?= (int) $application['id'] ?>">Review notes</label>
                                <textarea id="review_notes_<?= (int) $application['id'] ?>" name="review_notes" rows="2" style="width:100%;"></textarea>
                            </div>
                            <button type="submit" name="decision" value="approved" class="dashboard-btn dashboard-btn-primary">Approve</button>
                            <button type="submit" name="decision" value="rejected" class="dashboard-btn" style="background:#f3f4f6; color:#111827;">Reject</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="padding:20px; background:#f9fafb; border-radius:12px; color:#4b5563;">No vendor applications have been submitted yet.</div>
        <?php endif; ?>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
