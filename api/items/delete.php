<?php
/**
 * POST /api/items/delete.php
 * Body (JSON): { "item_id": 7 }
 * Note: deletes the item; related sale_items/restock_items retain a NULL item_id FK.
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body   = json_decode(file_get_contents('php://input'), true);
$itemId = (int) ($body['item_id'] ?? 0);

if ($itemId <= 0) jsonError('Invalid item ID.');

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT name FROM items WHERE item_id = ? LIMIT 1');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) jsonError('Item not found.', 404);

$pdo->prepare('DELETE FROM items WHERE item_id = ?')->execute([$itemId]);
logActivity($pdo, $currentUser['user_id'], 'item_delete', "Item '{$item['name']}' (#$itemId) deleted.");

jsonOk(null, "Item '{$item['name']}' deleted.");
