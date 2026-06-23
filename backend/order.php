<?php
/**
 * POST /backend/order.php
 *
 * Places an order. The browser sends only product IDs + quantities (plus
 * customer/delivery details and an optional promo code) — NEVER prices.
 * All prices, the discount and the delivery fee are looked up / computed
 * here from the database, so the total can't be tampered with client-side.
 *
 * Request JSON:
 *   {
 *     "customer": { "name", "email", "phone", "address", "delivery_time" },
 *     "items":    [ { "id": 7, "quantity": 2 }, ... ],
 *     "promo_code": "BLOOM20"          // optional
 *   }
 *
 * Responses:
 *   201 { ok:true, order_id, subtotal, discount, delivery_fee, total, status }
 *   400 { ok:false, errors:{...} | message }
 *   500 { ok:false, message }
 */

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';

const DELIVERY_FEE            = 300.00;   // standard delivery charge (KES)
const FREE_DELIVERY_THRESHOLD = 3000.00;  // free delivery on orders above this

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(405, ['ok' => false, 'message' => 'Method not allowed. Use POST.']);
}

$in       = read_input();
$customer = is_array($in['customer'] ?? null) ? $in['customer'] : [];
$items    = is_array($in['items'] ?? null) ? $in['items'] : [];
$promoIn  = strtoupper(trim((string) ($in['promo_code'] ?? '')));

// ─── Validate customer / delivery details ───
$name    = trim((string) ($customer['name']          ?? ''));
$email   = trim((string) ($customer['email']         ?? ''));
$phone   = trim((string) ($customer['phone']         ?? ''));
$address = trim((string) ($customer['address']       ?? ''));
$time    = trim((string) ($customer['delivery_time'] ?? ''));

$errors = [];
if (!valid_name($name))             $errors['name']    = 'Please enter a valid name.';
if (!valid_email($email))           $errors['email']   = 'Please enter a valid email.';
if (!valid_phone($phone))           $errors['phone']   = 'Please enter a valid phone number.';
if (mb_strlen($address) < 6)        $errors['address'] = 'Please enter a delivery address.';
if (!$items)                        $errors['items']   = 'Your cart is empty.';

if ($errors) {
    json_response(400, ['ok' => false, 'errors' => $errors]);
}

// ─── Normalise the requested quantities, keyed by product id ───
$wanted = [];   // id => quantity
foreach ($items as $item) {
    $id  = (int) ($item['id'] ?? 0);
    $qty = (int) ($item['quantity'] ?? 0);
    if ($id <= 0 || $qty <= 0) {
        continue;
    }
    $qty = min($qty, 99);                       // sane upper bound
    $wanted[$id] = ($wanted[$id] ?? 0) + $qty;  // merge duplicates
}

if (!$wanted) {
    json_response(400, ['ok' => false, 'message' => 'No valid items in the order.']);
}

try {
    $pdo = db();

    // Fetch the real price/name for every requested product in one query.
    $ids          = array_keys($wanted);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT id, name, price, availability FROM products WHERE id IN ($placeholders)"
    );
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[(int) $row['id']] = $row;
    }

    // Every requested id must exist and be purchasable.
    foreach ($ids as $id) {
        if (!isset($products[$id])) {
            json_response(400, ['ok' => false, 'message' => "A product in your cart no longer exists (#$id)."]);
        }
        if ($products[$id]['availability'] === 'out_of_stock') {
            json_response(409, ['ok' => false, 'message' => $products[$id]['name'] . ' is out of stock.']);
        }
    }

    // Build the line items and subtotal from DB prices.
    $lines    = [];
    $subtotal = 0.0;
    foreach ($wanted as $id => $qty) {
        $price     = (float) $products[$id]['price'];
        $lineTotal = $price * $qty;
        $subtotal += $lineTotal;
        $lines[] = [
            'product_id'   => $id,
            'product_name' => $products[$id]['name'],
            'unit_price'   => $price,
            'quantity'     => $qty,
            'line_total'   => $lineTotal,
        ];
    }

    // ─── Apply a promo code (looked up + validated server-side) ───
    $discount   = 0.0;
    $promoCode  = null;
    if ($promoIn !== '') {
        $p = $pdo->prepare(
            'SELECT code, discount_percent, min_order_amount
             FROM promotions
             WHERE code = ? AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= CURDATE())
               AND (ends_at   IS NULL OR ends_at   >= CURDATE())'
        );
        $p->execute([$promoIn]);
        $promo = $p->fetch();

        if (!$promo) {
            json_response(400, ['ok' => false, 'errors' => ['promo_code' => 'That promo code is not valid.']]);
        }
        if ($subtotal < (float) $promo['min_order_amount']) {
            json_response(400, ['ok' => false, 'errors' => [
                'promo_code' => 'Order must be at least KES ' . number_format((float) $promo['min_order_amount']) . ' to use this code.',
            ]]);
        }
        $discount  = round($subtotal * ((float) $promo['discount_percent'] / 100), 2);
        $promoCode = $promo['code'];
    }

    // ─── Delivery fee (free above the threshold) ───
    $deliveryFee = ($subtotal >= FREE_DELIVERY_THRESHOLD) ? 0.0 : DELIVERY_FEE;
    $total       = $subtotal - $discount + $deliveryFee;

    // ─── Persist order + items atomically ───
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders
            (customer_name, customer_email, customer_phone, delivery_address,
             delivery_time, promo_code, subtotal, discount, delivery_fee, total, status)
         VALUES (:name, :email, :phone, :address, :time, :promo,
                 :subtotal, :discount, :delivery, :total, :status)'
    );
    $orderStmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':phone'    => $phone,
        ':address'  => $address,
        ':time'     => $time !== '' ? $time : null,
        ':promo'    => $promoCode,
        ':subtotal' => $subtotal,
        ':discount' => $discount,
        ':delivery' => $deliveryFee,
        ':total'    => $total,
        ':status'   => 'pending',
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items
            (order_id, product_id, product_name, unit_price, quantity, line_total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($lines as $line) {
        $itemStmt->execute([
            $orderId,
            $line['product_id'],
            $line['product_name'],
            $line['unit_price'],
            $line['quantity'],
            $line['line_total'],
        ]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order failed: ' . $e->getMessage());
    json_response(500, ['ok' => false, 'message' => 'Could not place your order. Please try again.']);
}

json_response(201, [
    'ok'           => true,
    'order_id'     => $orderId,
    'subtotal'     => $subtotal,
    'discount'     => $discount,
    'delivery_fee' => $deliveryFee,
    'total'        => $total,
    'status'       => 'pending',
    'message'      => 'Order placed successfully.',
]);
