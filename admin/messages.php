<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$page_title = 'Messages — ' . APP_NAME;
$page_name = 'Messages';
$user_role = 'admin';
$user = $_SESSION['user'] ?? null;
$active_page = 'messages';
$logout_url = '../actions/logout.php';

$messages = $conn->query('SELECT id, name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-card">
    <h2>Contact Messages</h2>
    <?php if (empty($messages)): ?>
        <p style="color:#6b7280; margin-top:16px;">No messages yet.</p>
    <?php else: ?>
        <div class="dashboard-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                    <tr>
                        <td>
                            <strong><?= e($message['name']) ?></strong><br>
                            <span style="font-size:13px; color:#6b7280;"><?= e(substr($message['message'], 0, 100)) ?><?= strlen($message['message']) > 100 ? '...' : '' ?></span>
                        </td>
                        <td><?= e($message['email']) ?></td>
                        <td><?= e($message['subject']) ?></td>
                        <td><?= e(date('M d, Y', strtotime($message['created_at']))) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
