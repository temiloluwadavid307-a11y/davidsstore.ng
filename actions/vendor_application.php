<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    redirect(APP_URL . '/login.php');
}

$user = current_user();
if (($user['role'] ?? 'customer') !== 'customer') {
    redirect(APP_URL . '/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = sanitize($_POST['store_name'] ?? '');
    $business_name = sanitize($_POST['business_name'] ?? '');
    $contact_phone = sanitize($_POST['contact_phone'] ?? '');
    $business_email = sanitize($_POST['business_email'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $business_type = sanitize($_POST['business_type'] ?? '');
    $website = sanitize($_POST['website'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $terms_accepted = !empty($_POST['terms_accepted']);

    if (empty($store_name) || empty($business_name) || empty($contact_phone) || empty($business_email) || empty($location) || empty($business_type) || mb_strlen($description) < 20 || !$terms_accepted) {
        set_flash('error', 'Please complete the form accurately and accept the terms to continue.');
        redirect(APP_URL . '/vendor/become-vendor.php');
    }

    if (!is_valid_email($business_email)) {
        set_flash('error', 'Please provide a valid business email address.');
        redirect(APP_URL . '/vendor/become-vendor.php');
    }

    $check = $conn->prepare('SELECT id FROM vendor_applications WHERE user_id = ? AND status = "pending" LIMIT 1');
    $check->bind_param('i', $user['id']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        set_flash('error', 'You already have a pending vendor application.');
        redirect(APP_URL . '/customer/index.php');
    }

    $stmt = $conn->prepare('INSERT INTO vendor_applications (user_id, store_name, business_name, contact_phone, business_email, location, business_type, website, description, terms_accepted, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", NOW())');
    $stmt->bind_param('issssssssi', $user['id'], $store_name, $business_name, $contact_phone, $business_email, $location, $business_type, $website, $description, $terms_accepted ? 1 : 0);
    if ($stmt->execute()) {
        $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        send_vendor_registration_email((int) $user['id'], $full_name ?: ($user['name'] ?? 'Vendor Applicant'), $user['email']);
        send_vendor_application_admin_notification(
            (int) $user['id'],
            $full_name ?: ($user['name'] ?? 'Vendor Applicant'),
            $store_name,
            $business_name,
            $business_email,
            $location
        );
        set_flash('success', 'Your vendor application has been submitted successfully. We will review it shortly.');
        redirect(APP_URL . '/customer/index.php');
    }
}

set_flash('error', 'Unable to process the request at the moment.');
redirect(APP_URL . '/vendor/become-vendor.php');
