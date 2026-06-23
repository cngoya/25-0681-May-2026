<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/layout.php';

$products = db()->query(
    'SELECT p.id, p.name, p.kind, c.name AS category, p.price, p.availability, p.is_best_seller
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.kind, p.price DESC'
)->fetchAll();

render_header('Products', 'products');
?>
<p class="count"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> in the catalogue.</p>
<table class="admin-table">
    <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Category</th><th>Price</th><th>Availability</th><th>Best Seller</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <tr>
            <td><?= (int) $p['id'] ?></td>
            <td><?= h($p['name']) ?></td>
            <td><?= h($p['kind']) ?></td>
            <td><?= h($p['category'] ?? '—') ?></td>
            <td><?= kes($p['price']) ?></td>
            <td><span class="badge badge-<?= h($p['availability']) ?>"><?= h(str_replace('_', ' ', $p['availability'])) ?></span></td>
            <td><?= $p['is_best_seller'] ? '★' : '' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
render_footer();
