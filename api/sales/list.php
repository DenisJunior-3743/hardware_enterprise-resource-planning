<?php
/**
 * GET /api/sales/list.php
 * Returns paginated sales with their line items.
 *
 * Query params (all optional):
 *   date_from    YYYY-MM-DD
 *   date_to      YYYY-MM-DD
 *   category_id  filter by category of items in the sale
 *   item_id      filter by specific item
 *   view         daily | weekly | monthly  (shortcut for common ranges)
 *   page         default 1
 *   per_page     default 50, max 200
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

$pdo = getDB();

/* ── Date shortcuts ─────────────────────────────────────── */
$view = $_GET['view'] ?? '';
$today = date('Y-m-d');

switch ($view) {
    case 'daily':
        $dateFrom = $today;
        $dateTo   = $today;
        break;
    case 'weekly':
        $dateFrom = date('Y-m-d', strtotime('monday this week'));
        $dateTo   = $today;
        break;
    case 'monthly':
        $dateFrom = date('Y-m-01');
        $dateTo   = $today;
        break;
    default:
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to']   ?? '');
}

$catId  = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$itemId = isset($_GET['item_id'])     ? (int) $_GET['item_id']     : null;
$page   = max(1, (int) ($_GET['page']     ?? 1));
$pp     = min(200, max(1, (int) ($_GET['per_page'] ?? 50)));
$offset = ($page - 1) * $pp;

/* ── Build WHERE ─────────────────────────────────────────── */
$joins  = '';
$where  = [];
$params = [];

if ($dateFrom !== '') {
    $where[]  = 'DATE(s.sale_date) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[]  = 'DATE(s.sale_date) <= ?';
    $params[] = $dateTo;
}
if ($catId !== null) {
    $joins   .= ' JOIN sale_items si2 ON si2.sale_id = s.sale_id
                  JOIN items it2      ON it2.item_id  = si2.item_id AND it2.category_id = ?';
    array_unshift($params, $catId);  // added before date params isn't right; rebuild
    // Re-build properly
}

// Rebuild cleanly
$whereMain  = [];
$paramsMain = [];
$joinsMain  = '';

if ($catId !== null) {
    $joinsMain   = ' JOIN sale_items _si ON _si.sale_id = s.sale_id
                     JOIN items _it ON _it.item_id = _si.item_id AND _it.category_id = ?';
    $paramsMain[] = $catId;
}
if ($itemId !== null) {
    $joinsMain   .= ' JOIN sale_items _sii ON _sii.sale_id = s.sale_id AND _sii.item_id = ?';
    $paramsMain[] = $itemId;
}
if ($dateFrom !== '') { $whereMain[] = 'DATE(s.sale_date) >= ?'; $paramsMain[] = $dateFrom; }
if ($dateTo   !== '') { $whereMain[] = 'DATE(s.sale_date) <= ?'; $paramsMain[] = $dateTo;   }

$whereSQL = $whereMain ? 'WHERE ' . implode(' AND ', $whereMain) : '';

/* ── Count ───────────────────────────────────────────────── */
$cntStmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT s.sale_id) FROM sales s $joinsMain $whereSQL"
);
$cntStmt->execute($paramsMain);
$total = (int) $cntStmt->fetchColumn();

/* ── Paginated sale headers ──────────────────────────────── */
$salesStmt = $pdo->prepare(
    "SELECT DISTINCT s.sale_id, s.customer_name, s.payment_method, s.notes,
            s.total_amount, s.sale_date, u.username AS recorded_by
     FROM sales s $joinsMain
     LEFT JOIN users u ON u.user_id = s.user_id
     $whereSQL
     ORDER BY s.sale_date DESC
     LIMIT ? OFFSET ?"
);
$salesStmt->execute(array_merge($paramsMain, [$pp, $offset]));
$salesRows = $salesStmt->fetchAll();

/* ── Fetch line items for each sale ─────────────────────── */
if ($salesRows) {
    $saleIds   = array_column($salesRows, 'sale_id');
    $in        = implode(',', array_fill(0, count($saleIds), '?'));
    $linesStmt = $pdo->prepare(
        "SELECT sale_id, item_id, item_name, category, quantity, unit_price, subtotal
         FROM sale_items WHERE sale_id IN ($in)"
    );
    $linesStmt->execute($saleIds);
    $allLines  = $linesStmt->fetchAll();

    $linesBySale = [];
    foreach ($allLines as $l) {
        $linesBySale[$l['sale_id']][] = $l;
    }
    foreach ($salesRows as &$sale) {
        $sale['items'] = $linesBySale[$sale['sale_id']] ?? [];
    }
    unset($sale);
}

jsonOk([
    'sales'    => $salesRows,
    'total'    => $total,
    'page'     => $page,
    'per_page' => $pp,
]);
