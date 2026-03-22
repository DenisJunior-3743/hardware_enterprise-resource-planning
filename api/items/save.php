<?php
/**
 * POST /api/items/save.php
 * Creates or updates an inventory item.
 * Body (JSON):
 * {
 *   "item_id":       0,           // 0 or absent → new
 *   "category_id":  3,
 *   "name":         "Iron Sheet",
 *   "description":  "...",
 *   "unit":         "sheet",
 *   "price":        15000,
 *   "stock_quantity": 100,
 *   "restock_level":  20
 * }
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body    = json_decode(file_get_contents('php://input'), true);
$itemId  = (int)   ($body['item_id']       ?? 0);
$catId   = ($body['category_id'] !== null && $body['category_id'] !== '')
           ? (int) $body['category_id'] : null;
$name    = trim($body['name']   ?? '');
$desc    = trim($body['description'] ?? '');
$unit    = trim($body['unit']   ?? 'piece');
$price   = (float) ($body['price']          ?? 0);
$stock   = (int)   ($body['stock_quantity'] ?? 0);
$restock = (int)   ($body['restock_level']  ?? 20);

if ($name === '')   jsonError('Item name is required.');
if ($price < 0)     jsonError('Price cannot be negative.');
if ($stock < 0)     jsonError('Stock quantity cannot be negative.');
if ($restock < 1)   jsonError('Restock level must be at least 1.');

$pdo = getDB();

if ($itemId === 0) {
    $stmt = $pdo->prepare(
        'INSERT INTO items (category_id, name, description, unit, price, stock_quantity, restock_level)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$catId, $name, $desc, $unit, $price, $stock, $restock]);
    $itemId = (int) $pdo->lastInsertId();
    logActivity($pdo, $currentUser['user_id'], 'item_create', "Item '$name' (#$itemId) added.");
    $msg = 'Item added successfully.';
} else {
    $stmt = $pdo->prepare(
        'UPDATE items
         SET category_id = ?, name = ?, description = ?, unit = ?,
             price = ?, stock_quantity = ?, restock_level = ?
         WHERE item_id = ?'
    );
    $stmt->execute([$catId, $name, $desc, $unit, $price, $stock, $restock, $itemId]);
    logActivity($pdo, $currentUser['user_id'], 'item_update', "Item '$name' (#$itemId) updated.");
    $msg = 'Item updated successfully.';
}

$row = $pdo->prepare(
    'SELECT i.*, c.name AS category_name
     FROM items i LEFT JOIN categories c ON c.category_id = i.category_id
     WHERE i.item_id = ?'
);
$row->execute([$itemId]);

jsonOk($row->fetch(), $msg);
