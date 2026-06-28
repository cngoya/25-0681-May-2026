<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/layout.php';

$pdo = db();

// ─── Handle delete ───
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash'] = "Product #$id deleted.";
    }
    header('Location: products.php');
    exit;
}

// ─── Read + clear flash message ───
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$products = $pdo->query(
    'SELECT p.id, p.name, p.kind, c.name AS category, p.price, p.availability, p.is_best_seller
     FROM products p LEFT JOIN categories c ON c.id = p.category_id
     ORDER BY p.kind, p.price DESC'
)->fetchAll();

render_header('Products', 'products');

if ($flash): ?>
    <div class="notice"><?= h($flash) ?></div>
<?php endif; ?>

<p class="toolbar">
    <span class="count"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> in the catalogue.</span>
    <a class="btn-add" href="product-form.php">+ Add Product</a>
</p>

<table class="admin-table">
    <thead><tr><th>#</th><th>Name</th><th>Type</th><th>Category</th><th>Price</th><th>Availability</th><th>Best</th><th>Actions</th></tr></thead>
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
            <td class="actions">
                <a class="btn-edit" href="product-form.php?id=<?= (int) $p['id'] ?>">Edit</a>
                <form method="post" action="products.php" onsubmit="return confirm('Delete &quot;<?= h(addslashes($p['name'])) ?>&quot;? This cannot be undone.');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="btn-delete">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php
render_footer();
