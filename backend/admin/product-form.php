<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/layout.php';

$pdo = db();

$CARE_LEVELS  = ['very_easy', 'easy', 'moderate', 'hard'];
$AVAILABILITY = ['in_stock', 'limited', 'out_of_stock'];
$KINDS        = ['product', 'bouquet'];
$DELIVERY     = ['same_day', 'next_day'];

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// Default (blank) product for the "add" case.
$product = [
    'id' => 0, 'name' => '', 'kind' => 'product', 'category_id' => '', 'description' => '',
    'main_flower' => '', 'occasion' => '', 'care_level' => '', 'delivery_speed' => '',
    'price' => '', 'availability' => 'in_stock', 'image_url' => '',
    'is_best_seller' => 0, 'is_featured' => 0,
];
$errors = [];

// Load existing product for edit (on GET).
if ($id > 0 && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        $_SESSION['flash'] = "Product #$id not found.";
        header('Location: products.php');
        exit;
    }
    $product = $found;
}

// ─── Handle save (add or update) ───
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $product = [
        'id'             => $id,
        'name'           => trim((string) ($_POST['name'] ?? '')),
        'kind'           => (string) ($_POST['kind'] ?? 'product'),
        'category_id'    => ($_POST['category_id'] ?? '') !== '' ? (int) $_POST['category_id'] : '',
        'description'    => trim((string) ($_POST['description'] ?? '')),
        'main_flower'    => trim((string) ($_POST['main_flower'] ?? '')),
        'occasion'       => trim((string) ($_POST['occasion'] ?? '')),
        'care_level'     => (string) ($_POST['care_level'] ?? ''),
        'delivery_speed' => (string) ($_POST['delivery_speed'] ?? ''),
        'price'          => trim((string) ($_POST['price'] ?? '')),
        'availability'   => (string) ($_POST['availability'] ?? 'in_stock'),
        'image_url'      => trim((string) ($_POST['image_url'] ?? '')),
        'is_best_seller' => isset($_POST['is_best_seller']) ? 1 : 0,
        'is_featured'    => isset($_POST['is_featured']) ? 1 : 0,
    ];

    // Validate.
    if (mb_strlen($product['name']) < 2)             $errors['name']  = 'Name must be at least 2 characters.';
    if (!is_numeric($product['price']) || (float) $product['price'] < 0) $errors['price'] = 'Enter a valid price (0 or more).';
    if (!in_array($product['kind'], $KINDS, true))   $errors['kind']  = 'Invalid type.';
    if ($product['care_level'] !== '' && !in_array($product['care_level'], $CARE_LEVELS, true))       $errors['care_level'] = 'Invalid care level.';
    if (!in_array($product['availability'], $AVAILABILITY, true))      $errors['availability'] = 'Invalid availability.';
    if ($product['delivery_speed'] !== '' && !in_array($product['delivery_speed'], $DELIVERY, true))  $errors['delivery_speed'] = 'Invalid delivery speed.';

    if (!$errors) {
        $params = [
            ':name'           => $product['name'],
            ':kind'           => $product['kind'],
            ':category_id'    => $product['category_id'] !== '' ? $product['category_id'] : null,
            ':description'    => $product['description'] !== '' ? $product['description'] : null,
            ':main_flower'    => $product['main_flower'] !== '' ? $product['main_flower'] : null,
            ':occasion'       => $product['occasion'] !== '' ? $product['occasion'] : null,
            ':care_level'     => $product['care_level'] !== '' ? $product['care_level'] : null,
            ':delivery_speed' => $product['delivery_speed'] !== '' ? $product['delivery_speed'] : null,
            ':price'          => (float) $product['price'],
            ':availability'   => $product['availability'],
            ':image_url'      => $product['image_url'] !== '' ? $product['image_url'] : null,
            ':is_best_seller' => $product['is_best_seller'],
            ':is_featured'    => $product['is_featured'],
        ];

        try {
            if ($id > 0) {
                $params[':id'] = $id;
                $sql = 'UPDATE products SET name=:name, kind=:kind, category_id=:category_id,
                            description=:description, main_flower=:main_flower, occasion=:occasion,
                            care_level=:care_level, delivery_speed=:delivery_speed, price=:price,
                            availability=:availability, image_url=:image_url,
                            is_best_seller=:is_best_seller, is_featured=:is_featured
                        WHERE id=:id';
                $pdo->prepare($sql)->execute($params);
                $_SESSION['flash'] = 'Product "' . $product['name'] . '" updated.';
            } else {
                $sql = 'INSERT INTO products (name, kind, category_id, description, main_flower,
                            occasion, care_level, delivery_speed, price, availability, image_url,
                            is_best_seller, is_featured)
                        VALUES (:name, :kind, :category_id, :description, :main_flower, :occasion,
                            :care_level, :delivery_speed, :price, :availability, :image_url,
                            :is_best_seller, :is_featured)';
                $pdo->prepare($sql)->execute($params);
                $_SESSION['flash'] = 'Product "' . $product['name'] . '" added.';
            }
            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['name'] = 'A product with that name already exists.';
            } else {
                $errors['name'] = 'Could not save product. Please try again.';
                error_log('Product save failed: ' . $e->getMessage());
            }
        }
    }
}

$isEdit = $id > 0;
render_header($isEdit ? 'Edit Product' : 'Add Product', 'products');

// Helper for showing a field error.
$err = fn(string $f) => isset($errors[$f]) ? '<span class="error-msg show">' . h($errors[$f]) . '</span>' : '';
?>
<p><a class="btn-link" href="products.php">← Back to products</a></p>

<form class="product-form" method="post" action="product-form.php<?= $isEdit ? '?id=' . $id : '' ?>">
    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">

    <div class="pf-grid">
        <label>Name *
            <input type="text" name="name" value="<?= h($product['name']) ?>" required>
            <?= $err('name') ?>
        </label>
        <label>Price (KES) *
            <input type="number" name="price" step="0.01" min="0" value="<?= h($product['price']) ?>" required>
            <?= $err('price') ?>
        </label>
        <label>Type
            <select name="kind">
                <?php foreach ($KINDS as $k): ?>
                    <option value="<?= $k ?>" <?= $product['kind'] === $k ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Category
            <select name="category_id">
                <option value="">— none —</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string) $product['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Availability
            <select name="availability">
                <?php foreach ($AVAILABILITY as $a): ?>
                    <option value="<?= $a ?>" <?= $product['availability'] === $a ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $a)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Care Level
            <select name="care_level">
                <option value="">— n/a —</option>
                <?php foreach ($CARE_LEVELS as $cl): ?>
                    <option value="<?= $cl ?>" <?= $product['care_level'] === $cl ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $cl)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Main Flower (bouquets)
            <input type="text" name="main_flower" value="<?= h($product['main_flower']) ?>">
        </label>
        <label>Occasion (bouquets)
            <input type="text" name="occasion" value="<?= h($product['occasion']) ?>">
        </label>
        <label>Delivery Speed (bouquets)
            <select name="delivery_speed">
                <option value="">— n/a —</option>
                <?php foreach ($DELIVERY as $d): ?>
                    <option value="<?= $d ?>" <?= $product['delivery_speed'] === $d ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $d)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Image URL
            <input type="text" name="image_url" value="<?= h($product['image_url']) ?>" placeholder="https://…">
        </label>
    </div>

    <label class="pf-full">Description
        <textarea name="description" rows="3"><?= h($product['description']) ?></textarea>
    </label>

    <div class="pf-checks">
        <label class="pf-check"><input type="checkbox" name="is_best_seller" <?= $product['is_best_seller'] ? 'checked' : '' ?>> Best seller ★</label>
        <label class="pf-check"><input type="checkbox" name="is_featured" <?= $product['is_featured'] ? 'checked' : '' ?>> Featured</label>
    </div>

    <button type="submit" class="btn-save"><?= $isEdit ? 'Update Product' : 'Add Product' ?></button>
</form>
<?php
render_footer();
