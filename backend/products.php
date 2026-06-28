<?php
/**
 * GET /backend/products.php[?kind=product|bouquet]
 *
 * Returns the catalogue as JSON so the public site can render the product
 * and bouquet tables from the database instead of hard-coded HTML.
 *
 * Response:
 *   200 { ok:true, products:[ { id, name, kind, category, price,
 *                               availability, care_level, main_flower,
 *                               occasion, delivery_speed, image_url,
 *                               is_best_seller } , ... ] }
 *   405 { ok:false, message }
 */

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed. Use GET.']);
}

$kind = $_GET['kind'] ?? '';

try {
    $sql = 'SELECT p.id, p.name, p.kind, c.name AS category, p.price, p.availability,
                   p.care_level, p.main_flower, p.occasion, p.delivery_speed,
                   p.image_url, p.is_best_seller
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id';
    $params = [];

    if ($kind === 'product' || $kind === 'bouquet') {
        $sql .= ' WHERE p.kind = ?';
        $params[] = $kind;
    }
    $sql .= ' ORDER BY p.kind, p.price DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    $products = array_map(static function (array $r): array {
        return [
            'id'             => (int) $r['id'],
            'name'           => $r['name'],
            'kind'           => $r['kind'],
            'category'       => $r['category'],
            'price'          => (float) $r['price'],
            'availability'   => $r['availability'],
            'care_level'     => $r['care_level'],
            'main_flower'    => $r['main_flower'],
            'occasion'       => $r['occasion'],
            'delivery_speed' => $r['delivery_speed'],
            'image_url'      => $r['image_url'],
            'is_best_seller' => (bool) $r['is_best_seller'],
        ];
    }, $stmt->fetchAll());
} catch (PDOException $e) {
    error_log('Products fetch failed: ' . $e->getMessage());
    json_response(500, ['ok' => false, 'message' => 'Could not load products.']);
}

json_response(200, ['ok' => true, 'products' => $products]);
