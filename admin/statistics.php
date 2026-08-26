<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Statistics — ' . APP_NAME;
$page_name = 'Statistics';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'statistics';
$logout_url = APP_URL . '/actions/logout.php';

$counts = [];
$counts['products'] = $conn->query('SELECT COUNT(*) AS total FROM products')->fetch_assoc()['total'] ?? 0;
$counts['orders'] = $conn->query('SELECT COUNT(*) AS total FROM orders')->fetch_assoc()['total'] ?? 0;
$counts['users'] = $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'] ?? 0;
$counts['messages'] = $conn->query('SELECT COUNT(*) AS total FROM contact_messages')->fetch_assoc()['total'] ?? 0;

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-stat-card">
        <div class="icon blue"><i class="fas fa-box"></i></div>
        <div class="info">
            <h3><?= (int)$counts['products'] ?></h3>
            <p>Products</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon green"><i class="fas fa-shopping-bag"></i></div>
        <div class="info">
            <h3><?= (int)$counts['orders'] ?></h3>
            <p>Orders</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon orange"><i class="fas fa-users"></i></div>
        <div class="info">
            <h3><?= (int)$counts['users'] ?></h3>
            <p>Users</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon purple"><i class="fas fa-envelope"></i></div>
        <div class="info">
            <h3><?= (int)$counts['messages'] ?></h3>
            <p>Messages</p>
        </div>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
