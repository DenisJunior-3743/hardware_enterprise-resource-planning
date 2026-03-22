<?php
/**
 * GET /api/reports/summary.php
 * Aggregated report data for the Reports page.
 *
 * Query params:
 *   date_from     YYYY-MM-DD  (required)
 *   date_to       YYYY-MM-DD  (required)
 *   category_id   optional filter
 *   item_id       optional filter
 *
 * Response:
 *   summary        { total_revenue, total_transactions, items_sold }
 *   top_items      top 10 items by revenue
 *   by_category    revenue per category
 *   by_payment     revenue per payment method
 *   daily_totals   day-by-day breakdown
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

$pdo      = getDB();
$dateFrom = trim($_GET['date_from'] ?? date('Y-m-01'));
$dateTo   = trim($_GET['date_to']   ?? date('Y-m-d'));
$catId    = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$itemId   = isset($_GET['item_id'])     ? (int) $_GET['item_id']     : null;

/* ── Base join conditions ─────────────────────────────────── */
$itemWhere  = [];
$itemParams = [];

$itemWhere[]  = 'DATE(s.sale_date) BETWEEN ? AND ?';
$itemParams[] = $dateFrom;
$itemParams[] = $dateTo;

if ($catId !== null) {
    $itemWhere[]  = 'exists (SELECT 1 FROM items _i WHERE _i.item_id = si.item_id AND _i.category_id = ?)';
    $itemParams[] = $catId;
}
if ($itemId !== null) {
    $itemWhere[]  = 'si.item_id = ?';
    $itemParams[] = $itemId;
}

$whereClause = 'WHERE ' . implode(' AND ', $itemWhere);

/* ── Summary ─────────────────────────────────────────────── */
$sumStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(si.subtotal), 0) AS total_revenue,
            COUNT(DISTINCT s.sale_id)      AS total_transactions,
            COALESCE(SUM(si.quantity), 0)  AS items_sold
     FROM sales s
     JOIN sale_items si ON si.sale_id = s.sale_id
     $whereClause"
);
$sumStmt->execute($itemParams);
$summary = $sumStmt->fetch();

/* ── Top items ───────────────────────────────────────────── */
$topStmt = $pdo->prepare(
    "SELECT si.item_id, si.item_name, si.category,
            SUM(si.quantity) AS total_qty, SUM(si.subtotal) AS total_revenue
     FROM sales s
     JOIN sale_items si ON si.sale_id = s.sale_id
     $whereClause
     GROUP BY si.item_id, si.item_name, si.category
     ORDER BY total_revenue DESC
     LIMIT 10"
);
$topStmt->execute($itemParams);
$topItems = $topStmt->fetchAll();

/* ── By category ─────────────────────────────────────────── */
$catStmt = $pdo->prepare(
    "SELECT COALESCE(si.category,'Uncategorised') AS category,
            SUM(si.subtotal) AS total_revenue
     FROM sales s
     JOIN sale_items si ON si.sale_id = s.sale_id
     $whereClause
     GROUP BY si.category
     ORDER BY total_revenue DESC"
);
$catStmt->execute($itemParams);
$byCategory = $catStmt->fetchAll();

/* ── By payment method ───────────────────────────────────── */
$payWhere    = ["DATE(s.sale_date) BETWEEN ? AND ?"];
$payParams   = [$dateFrom, $dateTo];

$payStmt = $pdo->prepare(
    "SELECT s.payment_method, SUM(s.total_amount) AS total_revenue, COUNT(*) AS count
     FROM sales s
     WHERE " . implode(' AND ', $payWhere) . "
     GROUP BY s.payment_method
     ORDER BY total_revenue DESC"
);
$payStmt->execute($payParams);
$byPayment = $payStmt->fetchAll();

/* ── Daily totals ────────────────────────────────────────── */
$dailyStmt = $pdo->prepare(
    "SELECT DATE(s.sale_date) AS date,
            SUM(s.total_amount) AS total_revenue,
            COUNT(*)            AS transactions
     FROM sales s
     WHERE DATE(s.sale_date) BETWEEN ? AND ?
     GROUP BY DATE(s.sale_date)
     ORDER BY date ASC"
);
$dailyStmt->execute([$dateFrom, $dateTo]);
$dailyTotals = $dailyStmt->fetchAll();

jsonOk([
    'summary'      => $summary,
    'top_items'    => $topItems,
    'by_category'  => $byCategory,
    'by_payment'   => $byPayment,
    'daily_totals' => $dailyTotals,
    'date_from'    => $dateFrom,
    'date_to'      => $dateTo,
]);
