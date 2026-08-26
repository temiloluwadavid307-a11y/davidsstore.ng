<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_vendor();

$page_title = 'Earnings - Vendor Dashboard — ' . APP_NAME;
$page_name = 'Earnings';
$user_role = 'vendor';
$user = $_SESSION['user'] ?? null;
$active_page = 'earnings';
$logout_url = APP_URL . '/actions/logout.php';

require_once __DIR__ . '/../includes/dashboard-header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/dashboard-topbar.php';
?>
<div class="dashboard-grid">
    <div class="dashboard-stat-card">
        <div class="icon blue"><i class="fas fa-sack-dollar"></i></div>
        <div class="info">
            <h3>₦ 1,250,000</h3>
            <p>Total Earnings</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon green"><i class="fas fa-calendar-month"></i></div>
        <div class="info">
            <h3>₦ 320,000</h3>
            <p>This Month</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon orange"><i class="fas fa-clock"></i></div>
        <div class="info">
            <h3>₦ 150,000</h3>
            <p>Pending Payout</p>
        </div>
    </div>
    <div class="dashboard-stat-card">
        <div class="icon purple"><i class="fas fa-check-circle"></i></div>
        <div class="info">
            <h3>₦ 1,100,000</h3>
            <p>Total Payouts</p>
        </div>
    </div>
</div>
<div class="dashboard-grid" style="margin-top:24px;">
    <div class="dashboard-card" style="grid-column:span 2;">
        <h2 style="margin-bottom:16px;">Payout History</h2>
        <div class="dashboard-table-wrapper">
            <table style="width:100%;">
                <thead>
                    <tr>
                        <th>Payout ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#PY-001</td>
                        <td>Jun 15, 2026</td>
                        <td>₦ 250,000</td>
                        <td><span class="dashboard-badge green">Completed</span></td>
                        <td>GT Bank</td>
                    </tr>
                    <tr>
                        <td>#PY-002</td>
                        <td>May 30, 2026</td>
                        <td>₦ 300,000</td>
                        <td><span class="dashboard-badge green">Completed</span></td>
                        <td>GT Bank</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dashboard-card">
        <h2 style="margin-bottom:16px;">Payment Methods</h2>
        <div style="padding:12px; background:#f9fafb; border-radius:8px; margin-bottom:12px;">
            <strong>GT Bank</strong><br>
            <span style="font-size:14px; color:#6b7280;">**** **** **** 1234</span>
            <span class="dashboard-badge" style="margin-left:8px; background:#dbeafe; color:#1d4ed8;">Default</span>
        </div>
        <button class="dashboard-btn dashboard-btn-primary" style="width:100%;">
            <i class="fas fa-plus"></i> Add Payment Method
        </button>
    </div>
</div>
            </main>
<?php require_once __DIR__ . '/../includes/dashboard-footer.php'; ?>
