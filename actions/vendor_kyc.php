<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request');
    redirect(APP_URL . '/vendor/kyc.php');
}

$action = $_POST['action'] ?? '';
if ($action !== 'submit_kyc') {
    set_flash('error', 'Invalid action');
    redirect(APP_URL . '/vendor/kyc.php');
}

if (!validate_csrf($_POST['csrf_token'] ?? '')) {
    set_flash('error', 'Invalid request token');
    redirect(APP_URL . '/vendor/kyc.php');
}

require_vendor();
$vendor = ensure_current_vendor();
if (!$vendor) {
    set_flash('error', 'Vendor not found');
    redirect(APP_URL . '/vendor/kyc.php');
}

$legal_name = sanitize($_POST['legal_name'] ?? '');
$business_name = sanitize($_POST['business_name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$account_number = sanitize($_POST['account_number'] ?? '');
$bank_code = sanitize($_POST['bank_code'] ?? '');

$errors = [];
if (empty($legal_name)) $errors[] = 'Legal name required';
if (empty($business_name)) $errors[] = 'Business name required';
if (empty($phone)) $errors[] = 'Phone required';
if (empty($address)) $errors[] = 'Address required';

if ($errors) {
    set_flash('error', implode('; ', $errors));
    redirect(APP_URL . '/vendor/kyc.php');
}

$conn->begin_transaction();
try {
    // Upsert vendor_kyc
    $stmt = $conn->prepare('SELECT id FROM vendor_kyc WHERE vendor_id = ? LIMIT 1');
    $stmt->bind_param('i', $vendor['id']);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if ($existing) {
        $u = $conn->prepare('UPDATE vendor_kyc SET legal_name = ?, business_name = ?, phone = ?, email = ?, address = ?, status = "submitted", updated_at = NOW() WHERE vendor_id = ?');
        $email = $vendor['email'] ?? '';
        $u->bind_param('sssssi', $legal_name, $business_name, $phone, $email, $address, $vendor['id']);
        $u->execute();
        $kyc_id = $existing['id'];
    } else {
        $ins = $conn->prepare('INSERT INTO vendor_kyc (vendor_id, legal_name, business_name, phone, email, address, status, submitted_at) VALUES (?,?,?,?,?,?,"submitted",NOW())');
        $email = $vendor['email'] ?? '';
        $ins->bind_param('isssss', $vendor['id'], $legal_name, $business_name, $phone, $email, $address);
        $ins->execute();
        $kyc_id = $conn->insert_id;
    }

    // Update vendor bank info fields
    $vup = $conn->prepare('UPDATE vendors SET bank_account_number = ?, bank_code = ?, bank_account_name = ?, kyc_status = "submitted", updated_at = NOW() WHERE id = ?');
    $bank_name = ''; // not captured here
    $vup->bind_param('sssi', $account_number, $bank_code, $bank_name, $vendor['id']);
    $vup->execute();

    // Handle file uploads
    if (!empty($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
        $allowed = ['image/jpeg','image/png','application/pdf'];
        $max = 5 * 1024 * 1024; // 5MB
        $uploadDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'kyc' . DIRECTORY_SEPARATOR . $vendor['id'];
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($_FILES['documents']['name'] as $i => $origName) {
            $tmp = $_FILES['documents']['tmp_name'][$i] ?? null;
            $size = $_FILES['documents']['size'][$i] ?? 0;
            $type = $_FILES['documents']['type'][$i] ?? '';
            if (!$tmp || !is_uploaded_file($tmp)) continue;
            if ($size > $max) continue;
            if (!in_array($type, $allowed)) continue;
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $safe = bin2hex(random_bytes(16)) . '.' . $ext;
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $safe;
            if (!move_uploaded_file($tmp, $dest)) continue;
            $relPath = 'uploads/kyc/' . $vendor['id'] . '/' . $safe;
            $docType = $origName;
            $dins = $conn->prepare('INSERT INTO vendor_kyc_documents (vendor_id, document_type, file_path, filename, mime_type, size, status, uploaded_at) VALUES (?,?,?,?,?,?,"pending",NOW())');
            $dins->bind_param('isssis', $vendor['id'], $docType, $relPath, $origName, $type, $size);
            $dins->execute();
        }
    }

    $conn->commit();
    add_audit_log($_SESSION['user_id'] ?? 0, 'vendor_kyc_submitted', 'Vendor ' . $vendor['id'] . ' submitted KYC');
    set_flash('success', 'KYC submitted. An administrator will review your documents.');
    redirect(APP_URL . '/vendor/kyc.php');
} catch (Exception $e) {
    $conn->rollback();
    error_log('KYC submit error: ' . $e->getMessage());
    set_flash('error', 'Failed to submit KYC. Try again.');
    redirect(APP_URL . '/vendor/kyc.php');
}
