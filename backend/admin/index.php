<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/layout.php';

$pdo = db();

// ─── Headline stats ───
$stats = [
    'users'    => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'orders'   => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending'  => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
    'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'messages' => (int) $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
    'revenue'  => (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status<>'cancelled'")->fetchColumn(),
];

// ─── Recent orders ───
$recent = $pdo->query(
    'SELECT id, customer_name, total, status, created_at
     FROM orders ORDER BY id DESC LIMIT 5'
)->fetchAll();

render_header('Dashboard', 'dashboard');
?>
<div class="stat-grid">
    <div class="stat-card"><span class="stat-num"><?= $stats['users'] ?></span><span class="stat-label">Sign-ups</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['orders'] ?></span><span class="stat-label">Orders</span></div>
    <div class="stat-card stat-warn"><span class="stat-num"><?= $stats['pending'] ?></span><span class="stat-label">Pending</span></div>
    <div class="stat-card stat-money"><span class="stat-num"><?= kes($stats['revenue']) ?></span><span class="stat-label">Revenue</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['products'] ?></span><span class="stat-label">Products</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['messages'] ?></span><span class="stat-label">Messages</span></div>
</div>

<h3>Recent Orders</h3>
<?php if (!$recent): ?>
    <p class="empty">No orders yet.</p>
<?php else: ?>
    <table class="admin-table">
        <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $o): ?>
            <tr>
                <td>#<?= (int) $o['id'] ?></td>
                <td><?= h($o['customer_name']) ?></td>
                <td><?= kes($o['total']) ?></td>
                <td><span class="badge badge-<?= h($o['status']) ?>"><?= h($o['status']) ?></span></td>
                <td><?= h($o['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><a class="btn-link" href="orders.php">View all orders →</a></p>
<?php endif; ?>
<?php
render_footer();
