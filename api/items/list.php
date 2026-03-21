<?php
/**
 * GET /api/items/list.php
 * Query params (all optional):
 *   category_id  – filter by category
 *   low_stock    – "1" → only items at or below restock_level
 *   search       – name search (LIKE)
 *   page         – page number (default 1)
 *   per_page     – rows per page (default 100, max 200)
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

$pdo = getDB();

$catId     = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$lowStock  = ($_GET['low_stock'] ?? '') === '1';
$search    = trim($_GET['search'] ?? '');
$page      = max(1, (int) ($_GET['page']     ?? 1));
$perPage   = min(200, max(1, (int) ($_GET['per_page'] ?? 100)));
$offset    = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($catId !== null) {
    $where[]  = 'i.category_id = ?';
    $params[] = $catId;
}
if ($lowStock) {
    $where[] = 'i.stock_quantity <= i.restock_level';
}
if ($search !== '') {
    $where[]  = 'i.name LIKE ?';
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT i.item_id, i.name, i.description, i.unit, i.price,
            i.stock_quantity, i.restock_level, i.created_at, i.updated_at,
            c.category_id, c.name AS category_name
     FROM items i
     LEFT JOIN categories c ON c.category_id = i.category_id
     $whereSQL
     ORDER BY c.name ASC, i.name ASC
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$perPage, $offset]));
$items = $stmt->fetchAll();

jsonOk([
    'items'    => $items,
    'total'    => $total,
    'page'     => $page,
    'per_page' => $perPage,
]);
