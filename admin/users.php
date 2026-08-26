<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Users — ' . APP_NAME;
$page_name = 'Users';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'users';
$logout_url = APP_URL . '/actions/logout.php';

$users = $conn->query('SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-table">
    <div class="dashboard-table-header">
        <h2>Registered Users</h2>
    </div>
    <div class="dashboard-table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="4" style="padding:18px;text-align:center;">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="dashboard-badge" style="background:#fef3c7; color:#92400e;"><?= e(ucfirst($u['role'] ?? 'customer')) ?></span></td>
                        <td><?= e(date('M d, Y', strtotime($u['created_at']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
