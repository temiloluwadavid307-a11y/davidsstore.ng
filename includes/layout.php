<?php
/**
 * Shared dashboard layout wrapper
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config.php';
}
if (!function_exists('e')) {
    require_once __DIR__ . '/functions.php';
}

function render_dashboard_page(array $options, callable $content_renderer): void
{
    $page_title = $options['page_title'] ?? APP_NAME;
    $page_name = $options['page_name'] ?? 'Dashboard';
    $user_role = $options['user_role'] ?? 'admin';
    $active_page = $options['active_page'] ?? 'dashboard';
    $logout_url = $options['logout_url'] ?? '../actions/logout.php';
    $user = $options['user'] ?? current_user();

    require_once __DIR__ . '/dashboard-header.php';
    require_once __DIR__ . '/sidebar.php';
    require_once __DIR__ . '/dashboard-topbar.php';

    call_user_func($content_renderer, $options);

    require_once __DIR__ . '/dashboard-footer.php';
}
