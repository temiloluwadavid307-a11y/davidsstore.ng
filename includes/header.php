<?php
/**
 * Page header - opens HTML document
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}
$page_title = $page_title ?? STORE_NAME;
$page_description = $page_description ?? STORE_TAGLINE;
$extra_css = $extra_css ?? [];
$body_class = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($page_description) ?>">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($page_description) ?>">
    <meta property="og:image" content="<?= APP_URL ?>/assets/images/favicon-32x32.png">
    <meta property="og:url" content="<?= APP_URL ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($page_title) ?>">
    <meta name="twitter:description" content="<?= e($page_description) ?>">
    <title><?= e($page_title) ?></title>
    <script>
        window.__APP_URL__ = <?= json_encode(APP_URL) ?>;
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/platform.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <link rel="icon" href="<?= APP_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    <link rel="icon" href="<?= APP_URL ?>/assets/images/favicon-32x32.png" sizes="32x32">
    <?php foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?= APP_URL ?>/<?= e($css) ?>">
    <?php endforeach; ?>
</head>
<body id="top" class="<?= e($body_class) ?>">

<div class="page-loader" id="pageLoader" aria-hidden="true">
    <div class="loader-spinner"></div>
</div>

<a href="#top" class="back-to-top" aria-label="Back to top"><i class="fas fa-chevron-up"></i></a>

<div class="toast-container" id="toastContainer" role="status" aria-live="polite"></div>

<?php
$flash = get_flash();
if ($flash):
?>
<div class="flash-message flash-<?= e($flash['type']) ?>" id="flashMessage">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
    <span><?= e($flash['message']) ?></span>
    <button type="button" class="flash-close" aria-label="Close">&times;</button>
</div>
<?php endif; ?>
