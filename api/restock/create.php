<?php
/**
 * POST /api/restock/create.php
 * Records a restock batch and increases stock quantities atomically.
 *
 * Body (JSON):
 * {
 *   "supplier":     "Nile Hardware",
 *   "po_number":    "INV-2024-001",
 *   "restock_date": "2024-03-15",
 *   "notes":        "",
 *   "items": [
 *     { "item_id": 3, "quantity": 50, "new_price": 16000 },
 *     { "item_id": 7, "quantity": 20, "new_price": null  }
 *   ]
 * }
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body        = json_decode(file_get_contents('php://input'), true);
$supplier    = trim($body['supplier']     ?? '');
$poNumber    = trim($body['po_number']    ?? '');
$restockDate = trim($body['restock_date'] ?? date('Y-m-d'));
$notes       = trim($body['notes']        ?? '');
$lineItems   = $body['items'] ?? [];

if (empty($lineItems) || !is_array($lineItems)) {
    jsonError('At least one item is required.');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $restockDate)) {
    jsonError('Invalid restock date format. Use YYYY-MM-DD.');
}

$pdo = getDB();
$hasCategoryColumn = false;
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM restock_items LIKE 'category'");
    $hasCategoryColumn = (bool) $colStmt->fetch();
} catch (Throwable $e) {
    $hasCategoryColumn = false;
}
$pdo->beginTransaction();

try {
    $insRestock = $pdo->prepare(
        'INSERT INTO restocks (user_id, supplier, po_number, notes, restock_date)
         VALUES (?, ?, ?, ?, ?)'
    );
    $insRestock->execute([
        $currentUser['user_id'],
        $supplier ?: null,
        $poNumber ?: null,
        $notes    ?: null,
        $restockDate,
    ]);
    $restockId = (int) $pdo->lastInsertId();

    $insLine  = $hasCategoryColumn
        ? $pdo->prepare(
            'INSERT INTO restock_items (restock_id, item_id, item_name, category, quantity, new_price)
             VALUES (?, ?, ?, ?, ?, ?)'
        )
        : $pdo->prepare(
            'INSERT INTO restock_items (restock_id, item_id, item_name, quantity, new_price)
             VALUES (?, ?, ?, ?, ?)'
        );
    $updStock = $pdo->prepare(
        'UPDATE items SET stock_quantity = stock_quantity + ? WHERE item_id = ?'
    );
    $updPrice = $pdo->prepare(
        'UPDATE items SET price = ? WHERE item_id = ?'
    );
    $getItem  = $pdo->prepare(
        'SELECT i.item_id, i.name, c.name AS category_name
         FROM items i
         LEFT JOIN categories c ON c.category_id = i.category_id
         WHERE i.item_id = ? LIMIT 1'
    );

    $resolvedLines = [];
    foreach ($lineItems as $line) {
        $itemId   = (int)   ($line['item_id']  ?? 0);
        $qty      = (int)   ($line['quantity'] ?? 0);
        $newPrice = isset($line['new_price']) && $line['new_price'] !== '' && $line['new_price'] !== null
                    ? (float) $line['new_price'] : null;

        if ($itemId <= 0 || $qty <= 0) {
            throw new RuntimeException('Invalid item ID or quantity.');
        }

        $getItem->execute([$itemId]);
        $item = $getItem->fetch();
        if (!$item) {
            throw new RuntimeException("Item #$itemId not found.");
        }

        $insertParams = $hasCategoryColumn
            ? [$restockId, $itemId, $item['name'], $item['category_name'], $qty, $newPrice]
            : [$restockId, $itemId, $item['name'], $qty, $newPrice];
        $insLine->execute($insertParams);
        $updStock->execute([$qty, $itemId]);

        if ($newPrice !== null) {
            $updPrice->execute([$newPrice, $itemId]);
        }

        $resolvedLines[] = [
            'item_id'   => $itemId,
            'item_name' => $item['name'],
            'category'  => $item['category_name'],
            'quantity'  => $qty,
            'new_price' => $newPrice,
        ];
    }

    $pdo->commit();

    logActivity(
        $pdo, $currentUser['user_id'], 'restock_create',
        "Restock #$restockId — " . count($resolvedLines) . " item(s) from '$supplier'."
    );

    jsonOk([
        'restock_id' => $restockId,
        'items'      => $resolvedLines,
    ], 'Restock recorded successfully.');

} catch (RuntimeException $e) {
    $pdo->rollBack();
    jsonError($e->getMessage());
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError('An unexpected error occurred. The restock was not recorded.', 500);
}
