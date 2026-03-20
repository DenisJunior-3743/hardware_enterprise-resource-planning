<?php
/**
 * GET /api/restock/list.php
 * Returns paginated restock records with their line items.
 *
 * Query params (all optional):
 *   date_from    YYYY-MM-DD
 *   date_to      YYYY-MM-DD
 *   item_id      filter by item
 *   view         daily | weekly | monthly
 *   page         default 1
 *   per_page     default 50, max 200
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

$pdo = getDB();

$hasCategoryColumn = false;
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM restock_items LIKE 'category'");
    $hasCategoryColumn = (bool) $colStmt->fetch();
} catch (Throwable $e) {
    $hasCategoryColumn = false;
}

$view  = $_GET['view'] ?? '';
$today = date('Y-m-d');

switch ($view) {
    case 'daily':
        $dateFrom = $today; $dateTo = $today; break;
    case 'weekly':
        $dateFrom = date('Y-m-d', strtotime('monday this week')); $dateTo = $today; break;
    case 'monthly':
        $dateFrom = date('Y-m-01'); $dateTo = $today; break;
    default:
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to']   ?? '');
}

$itemId = isset($_GET['item_id']) ? (int) $_GET['item_id'] : null;
$page   = max(1, (int) ($_GET['page']     ?? 1));
$pp     = min(200, max(1, (int) ($_GET['per_page'] ?? 50)));
$offset = ($page - 1) * $pp;

$joinsMain  = '';
$whereMain  = [];
$paramsMain = [];

if ($itemId !== null) {
    $joinsMain    = ' JOIN restock_items _ri ON _ri.restock_id = r.restock_id AND _ri.item_id = ?';
    $paramsMain[] = $itemId;
}
if ($dateFrom !== '') { $whereMain[] = 'r.restock_date >= ?'; $paramsMain[] = $dateFrom; }
if ($dateTo   !== '') { $whereMain[] = 'r.restock_date <= ?'; $paramsMain[] = $dateTo;   }

$whereSQL = $whereMain ? 'WHERE ' . implode(' AND ', $whereMain) : '';

$cntStmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT r.restock_id) FROM restocks r $joinsMain $whereSQL"
);
$cntStmt->execute($paramsMain);
$total = (int) $cntStmt->fetchColumn();

$mainStmt = $pdo->prepare(
    "SELECT DISTINCT r.restock_id, r.supplier, r.po_number, r.notes, r.restock_date,
            r.created_at, u.username AS recorded_by
     FROM restocks r $joinsMain
     LEFT JOIN users u ON u.user_id = r.user_id
     $whereSQL
     ORDER BY r.restock_date DESC, r.created_at DESC
     LIMIT ? OFFSET ?"
);
$mainStmt->execute(array_merge($paramsMain, [$pp, $offset]));
$restocks = $mainStmt->fetchAll();

if ($restocks) {
    $ids    = array_column($restocks, 'restock_id');
    $in     = implode(',', array_fill(0, count($ids), '?'));
    if ($hasCategoryColumn) {
        $lStmt = $pdo->prepare(
            "SELECT ri.restock_id, ri.item_id, ri.item_name,
                    COALESCE(NULLIF(ri.category, ''), c.name) AS category,
                    ri.quantity, ri.new_price
             FROM restock_items ri
             LEFT JOIN items i ON i.item_id = ri.item_id
             LEFT JOIN categories c ON c.category_id = i.category_id
             WHERE ri.restock_id IN ($in)"
        );
    } else {
        $lStmt = $pdo->prepare(
            "SELECT ri.restock_id, ri.item_id, ri.item_name, c.name AS category, ri.quantity, ri.new_price
             FROM restock_items ri
             LEFT JOIN items i ON i.item_id = ri.item_id
             LEFT JOIN categories c ON c.category_id = i.category_id
             WHERE ri.restock_id IN ($in)"
        );
    }
    $lStmt->execute($ids);
    $linesByRestock = [];
    foreach ($lStmt->fetchAll() as $l) {
        $linesByRestock[$l['restock_id']][] = $l;
    }
    foreach ($restocks as &$r) {
        $r['items'] = $linesByRestock[$r['restock_id']] ?? [];
    }
    unset($r);
}

jsonOk([
    'restocks' => $restocks,
    'total'    => $total,
    'page'     => $page,
    'per_page' => $pp,
]);
