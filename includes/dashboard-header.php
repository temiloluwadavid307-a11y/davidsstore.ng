<?php
/**
 * Shared Dashboard Header
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Defaults
$page_title = $page_title ?? 'Dashboard';
$page_name = $page_name ?? 'Dashboard';
$user_role = $user_role ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> | CodesbyDavid</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard-shell">
