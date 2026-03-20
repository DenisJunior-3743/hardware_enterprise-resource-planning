<?php
/**
 * POST /api/sales/delete.php
 * Deletes a sale record and RESTORES stock for all its line items.
 * Body (JSON): { "sale_id": 12 }
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body   = json_decode(file_get_contents('php://input'), true);
$saleId = (int) ($body['sale_id'] ?? 0);

if ($saleId <= 0) jsonError('Invalid sale ID.');

$pdo = getDB();
$pdo->beginTransaction();

try {
    $saleStmt = $pdo->prepare('SELECT sale_id, total_amount FROM sales WHERE sale_id = ? LIMIT 1');
    $saleStmt->execute([$saleId]);
    $sale = $saleStmt->fetch();
    if (!$sale) jsonError('Sale not found.', 404);

    // Restore stock for each line item
    $lines = $pdo->prepare(
        'SELECT item_id, quantity FROM sale_items WHERE sale_id = ? AND item_id IS NOT NULL'
    );
    $lines->execute([$saleId]);
    $upd = $pdo->prepare(
        'UPDATE items SET stock_quantity = stock_quantity + ? WHERE item_id = ?'
    );
    foreach ($lines->fetchAll() as $line) {
        $upd->execute([$line['quantity'], $line['item_id']]);
    }

    // Cascade delete removes sale_items automatically (ON DELETE CASCADE)
    $pdo->prepare('DELETE FROM sales WHERE sale_id = ?')->execute([$saleId]);

    $pdo->commit();

    logActivity(
        $pdo, $currentUser['user_id'], 'sale_delete',
        "Sale #$saleId (UGX {$sale['total_amount']}) deleted and stock restored."
    );

    jsonOk(null, "Sale #$saleId deleted and stock restored.");

} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError('Could not delete the sale. Please try again.', 500);
}
