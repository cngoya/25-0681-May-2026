<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/layout.php';

$pdo    = db();
$notice = '';

// ─── Handle status update ───
$VALID_STATUSES = ['pending', 'confirmed', 'delivered', 'cancelled'];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status  = (string) ($_POST['status'] ?? '');
    if ($orderId > 0 && in_array($status, $VALID_STATUSES, true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $orderId]);
        $notice = "Order #$orderId updated to \"$status\".";
    } else {
        $notice = 'Invalid status update.';
    }
}

// ─── Load orders with their line items ───
$orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();

$itemsByOrder = [];
if ($orders) {
    $ids   = array_column($orders, 'id');
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmt  = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($place) ORDER BY id");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $item) {
        $itemsByOrder[(int) $item['order_id']][] = $item;
    }
}

render_header('Orders', 'orders');

if ($notice): ?>
    <div class="notice"><?= h($notice) ?></div>
<?php endif; ?>

<p class="count"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?>.</p>

<?php if (!$orders): ?>
    <p class="empty">No orders yet.</p>
<?php else: ?>
    <?php foreach ($orders as $o): $oid = (int) $o['id']; ?>
        <div class="order-card">
            <div class="order-head">
                <div>
                    <strong>Order #<?= $oid ?></strong> ·
                    <?= h($o['customer_name']) ?> ·
                    <span class="badge badge-<?= h($o['status']) ?>"><?= h($o['status']) ?></span>
                </div>
                <div class="order-total"><?= kes($o['total']) ?></div>
            </div>

            <div class="order-meta">
                <span>📧 <?= h($o['customer_email']) ?></span>
                <span>📞 <?= h($o['customer_phone']) ?></span>
                <span>📍 <?= h($o['delivery_address']) ?></span>
                <?php if ($o['delivery_time']): ?><span>🕑 <?= h($o['delivery_time']) ?></span><?php endif; ?>
                <span>🗓 <?= h($o['created_at']) ?></span>
            </div>

            <table class="admin-table compact">
                <thead><tr><th>Item</th><th>Unit</th><th>Qty</th><th>Line</th></tr></thead>
                <tbody>
                <?php foreach ($itemsByOrder[$oid] ?? [] as $it): ?>
                    <tr>
                        <td><?= h($it['product_name']) ?></td>
                        <td><?= kes($it['unit_price']) ?></td>
                        <td><?= (int) $it['quantity'] ?></td>
                        <td><?= kes($it['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="order-summary">
                <span>Subtotal: <?= kes($o['subtotal']) ?></span>
                <?php if ((float) $o['discount'] > 0): ?>
                    <span>Discount<?= $o['promo_code'] ? ' (' . h($o['promo_code']) . ')' : '' ?>: −<?= kes($o['discount']) ?></span>
                <?php endif; ?>
                <span>Delivery: <?= (float) $o['delivery_fee'] > 0 ? kes($o['delivery_fee']) : 'FREE' ?></span>
                <span class="grand">Total: <?= kes($o['total']) ?></span>
            </div>

            <form class="status-form" method="post" action="orders.php">
                <input type="hidden" name="order_id" value="<?= $oid ?>">
                <label>Update status:</label>
                <select name="status">
                    <?php foreach ($VALID_STATUSES as $s): ?>
                        <option value="<?= $s ?>" <?= $s === $o['status'] ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Save</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php
render_footer();
