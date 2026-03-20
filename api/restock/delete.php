<?php
/**
 * POST /api/restock/delete.php
 * Deletes a restock record and REVERSES stock for all its line items.
 * Body (JSON): { "restock_id": 5 }
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body      = json_decode(file_get_contents('php://input'), true);
$restockId = (int) ($body['restock_id'] ?? 0);

if ($restockId <= 0) jsonError('Invalid restock ID.');

$pdo = getDB();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare('SELECT restock_id, supplier FROM restocks WHERE restock_id = ? LIMIT 1');
    $stmt->execute([$restockId]);
    $restock = $stmt->fetch();
    if (!$restock) jsonError('Restock record not found.', 404);

    // Reverse stock for each line item
    $lines = $pdo->prepare(
        'SELECT item_id, quantity FROM restock_items WHERE restock_id = ? AND item_id IS NOT NULL'
    );
    $lines->execute([$restockId]);
    $upd = $pdo->prepare(
        'UPDATE items SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE item_id = ?'
    );
    foreach ($lines->fetchAll() as $line) {
        $upd->execute([$line['quantity'], $line['item_id']]);
    }

    // Cascade delete removes restock_items automatically (ON DELETE CASCADE)
    $pdo->prepare('DELETE FROM restocks WHERE restock_id = ?')->execute([$restockId]);

    $pdo->commit();

    logActivity(
        $pdo, $currentUser['user_id'], 'restock_delete',
        "Restock #$restockId (supplier: {$restock['supplier']}) deleted and stock reversed."
    );

    jsonOk(null, "Restock #$restockId deleted and stock reversed.");

} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError('Could not delete the restock record. Please try again.', 500);
}
