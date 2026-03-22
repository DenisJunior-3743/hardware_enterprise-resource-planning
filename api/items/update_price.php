<?php
/**
 * POST /api/items/update_price.php
 * Quick price-only update (used from the inline price editor on the inventory page).
 * Body (JSON): { "item_id": 5, "price": 18500 }
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body   = json_decode(file_get_contents('php://input'), true);
$itemId = (int)   ($body['item_id'] ?? 0);
$price  = (float) ($body['price']   ?? -1);

if ($itemId <= 0)  jsonError('Invalid item ID.');
if ($price < 0)    jsonError('Price cannot be negative.');

$pdo  = getDB();
$stmt = $pdo->prepare('UPDATE items SET price = ? WHERE item_id = ?');
$stmt->execute([$price, $itemId]);

if ($stmt->rowCount() === 0) jsonError('Item not found.', 404);

logActivity($pdo, $currentUser['user_id'], 'item_price_update', "Item #$itemId price updated to $price.");

jsonOk(['item_id' => $itemId, 'price' => $price], 'Price updated.');
