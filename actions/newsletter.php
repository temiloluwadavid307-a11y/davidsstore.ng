<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$email = sanitize($_POST['email'] ?? '');
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

if (!is_valid_email($email)) {
    $response = ['success' => false, 'message' => 'Please enter a valid email address.'];
    if ($is_ajax) { echo json_encode($response); exit; }
    set_flash('error', $response['message']);
    redirect($_SERVER['HTTP_REFERER'] ?? APP_URL . '/index.php');
}

$check = $conn->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
$check->bind_param('s', $email);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $response = ['success' => true, 'message' => 'You are already subscribed!'];
} else {
    $stmt = $conn->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?)');
    $stmt->bind_param('s', $email);
    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Successfully subscribed to our newsletter!'];
    } else {
        $response = ['success' => false, 'message' => 'Subscription failed. Please try again.'];
    }
}

if ($is_ajax) {
    echo json_encode($response);
} else {
    set_flash($response['success'] ? 'success' : 'error', $response['message']);
    redirect($_SERVER['HTTP_REFERER'] ?? APP_URL . '/index.php');
}
