<?php
/**
 * POST /api/sales/create.php
 * Records a complete sale transaction and deducts stock atomically.
 *
 * Body (JSON):
 * {
 *   "customer_name":  "John Mukasa",   // optional
 *   "payment_method": "cash",
 *   "notes":          "",               // optional
 *   "items": [
 *     { "item_id": 3, "quantity": 2 },
 *     { "item_id": 7, "quantity": 5 }
 *   ]
 * }
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body          = json_decode(file_get_contents('php://input'), true);
$customerName  = trim($body['customer_name']  ?? '');
$paymentMethod = trim($body['payment_method'] ?? 'cash');
$notes         = trim($body['notes']          ?? '');
$lineItems     = $body['items'] ?? [];

$allowed = ['', 'cash', 'mobile_money', 'bank', 'credit'];
if (!in_array($paymentMethod, $allowed, true)) {
    jsonError('Invalid payment method.');
}

if (empty($lineItems) || !is_array($lineItems)) {
    jsonError('At least one item is required.');
}

$pdo = getDB();
$pdo->beginTransaction();

try {
    $totalAmount = 0;
    $resolvedLines = [];

    foreach ($lineItems as $line) {
        $itemId  = (int)   ($line['item_id']  ?? 0);
        $qty     = (int)   ($line['quantity'] ?? 0);

        if ($itemId <= 0 || $qty <= 0) {
            throw new RuntimeException('Invalid item ID or quantity.');
        }

        // Lock the row
        $stmt = $pdo->prepare(
            'SELECT i.item_id, i.name, i.price, i.stock_quantity, c.name AS category_name
             FROM items i
             LEFT JOIN categories c ON c.category_id = i.category_id
             WHERE i.item_id = ? FOR UPDATE'
        );
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            throw new RuntimeException("Item #$itemId not found.");
        }
        if ($item['stock_quantity'] < $qty) {
            throw new RuntimeException(
                "Insufficient stock for \"{$item['name']}\". Available: {$item['stock_quantity']}."
            );
        }

        $subtotal       = round((float) $item['price'] * $qty, 2);
        $totalAmount   += $subtotal;
        $resolvedLines[] = [
            'item_id'      => $itemId,
            'item_name'    => $item['name'],
            'category'     => $item['category_name'] ?? '',
            'quantity'     => $qty,
            'unit_price'   => (float) $item['price'],
            'subtotal'     => $subtotal,
            'stock_before' => $item['stock_quantity'],
        ];
    }

    // INSERT sales header
    $insSale = $pdo->prepare(
        'INSERT INTO sales (user_id, customer_name, payment_method, notes, total_amount)
         VALUES (?, ?, ?, ?, ?)'
    );
    $insSale->execute([
        $currentUser['user_id'],
        $customerName ?: null,
        $paymentMethod ?: null,
        $notes ?: null,
        $totalAmount,
    ]);
    $saleId = (int) $pdo->lastInsertId();

    // INSERT sale_items + deduct stock
    $insLine  = $pdo->prepare(
        'INSERT INTO sale_items (sale_id, item_id, item_name, category, quantity, unit_price, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $updStock = $pdo->prepare(
        'UPDATE items SET stock_quantity = stock_quantity - ? WHERE item_id = ?'
    );

    foreach ($resolvedLines as $line) {
        $insLine->execute([
            $saleId,
            $line['item_id'],
            $line['item_name'],
            $line['category'],
            $line['quantity'],
            $line['unit_price'],
            $line['subtotal'],
        ]);
        $updStock->execute([$line['quantity'], $line['item_id']]);
    }

    $pdo->commit();

    logActivity(
        $pdo, $currentUser['user_id'], 'sale_create',
        "Sale #$saleId — " . count($resolvedLines) . " item(s) — UGX $totalAmount"
    );

    jsonOk([
        'sale_id'      => $saleId,
        'total_amount' => $totalAmount,
        'items'        => $resolvedLines,
    ], 'Sale recorded successfully.');

} catch (RuntimeException $e) {
    $pdo->rollBack();
    jsonError($e->getMessage());
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError('An unexpected error occurred. The sale was not recorded.', 500);
}
