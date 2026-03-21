<?php
/**
 * GET /api/dashboard/stats.php
 * Returns KPIs and chart data needed by the dashboard page.
 *
 * Response data:
 *   today_sales, week_sales, month_sales          – UGX totals + counts
 *   total_items, low_stock_count, out_of_stock
 *   chart_daily    – last 7 days  [{date, total}]
 *   chart_weekly   – last 8 weeks [{week_start, total}]
 *   chart_monthly  – last 12 months [{month, total}]
 *   recent_sales   – last 5 sales
 *   low_stock_items – up to 10 items at/below restock_level
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

$pdo   = getDB();
$today = date('Y-m-d');

/* ── KPIs ─────────────────────────────────────────────────── */
function salesKpi(PDO $pdo, string $from, string $to): array {
    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(total_amount),0) AS total, COUNT(*) AS count
         FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?'
    );
    $stmt->execute([$from, $to]);
    return $stmt->fetch();
}

$todayKpi = salesKpi($pdo, $today, $today);
$weekKpi  = salesKpi($pdo, date('Y-m-d', strtotime('monday this week')), $today);
$monthKpi = salesKpi($pdo, date('Y-m-01'), $today);

/* ── Inventory stats ──────────────────────────────────────── */
$invStmt = $pdo->query(
    'SELECT COUNT(*) AS total_items,
            SUM(stock_quantity <= restock_level) AS low_stock_count,
            SUM(stock_quantity = 0)              AS out_of_stock
     FROM items'
);
$invStats = $invStmt->fetch();

/* ── Daily chart – last 7 days ───────────────────────────── */
$dailyStmt = $pdo->prepare(
    'SELECT DATE(sale_date) AS date, COALESCE(SUM(total_amount),0) AS total
     FROM sales
     WHERE DATE(sale_date) >= ?
     GROUP BY DATE(sale_date)
     ORDER BY date ASC'
);
$dailyStmt->execute([date('Y-m-d', strtotime('-6 days'))]);
$chartDaily = $dailyStmt->fetchAll();

/* ── Weekly chart – last 8 weeks ─────────────────────────── */
$weeklyStmt = $pdo->prepare(
    'SELECT DATE(sale_date - INTERVAL (WEEKDAY(sale_date)) DAY) AS week_start,
            COALESCE(SUM(total_amount),0) AS total
     FROM sales
     WHERE sale_date >= ?
     GROUP BY week_start
     ORDER BY week_start ASC'
);
$weeklyStmt->execute([date('Y-m-d', strtotime('-7 weeks'))]);
$chartWeekly = $weeklyStmt->fetchAll();

/* ── Monthly chart – last 12 months ──────────────────────── */
$monthlyStmt = $pdo->prepare(
    'SELECT DATE_FORMAT(sale_date, "%Y-%m") AS month,
            COALESCE(SUM(total_amount),0) AS total
     FROM sales
     WHERE sale_date >= ?
     GROUP BY month
     ORDER BY month ASC'
);
$monthlyStmt->execute([date('Y-m-01', strtotime('-11 months'))]);
$chartMonthly = $monthlyStmt->fetchAll();

/* ── Recent sales ─────────────────────────────────────────── */
$recentStmt = $pdo->query(
    'SELECT s.sale_id, s.customer_name, s.payment_method, s.total_amount, s.sale_date,
            GROUP_CONCAT(si.item_name ORDER BY si.sale_item_id SEPARATOR ", ") AS item_names
     FROM sales s
     LEFT JOIN sale_items si ON si.sale_id = s.sale_id
     GROUP BY s.sale_id
     ORDER BY s.sale_date DESC
     LIMIT 5'
);
$recentSales = $recentStmt->fetchAll();

/* ── Low stock items ──────────────────────────────────────── */
$lowStmt = $pdo->query(
    'SELECT i.item_id, i.name, i.stock_quantity, i.restock_level, c.name AS category_name
     FROM items i
     LEFT JOIN categories c ON c.category_id = i.category_id
     WHERE i.stock_quantity <= i.restock_level
     ORDER BY i.stock_quantity ASC
     LIMIT 10'
);
$lowStockItems = $lowStmt->fetchAll();

jsonOk([
    'today_sales'   => $todayKpi,
    'week_sales'    => $weekKpi,
    'month_sales'   => $monthKpi,
    'inv_stats'     => $invStats,
    'chart_daily'   => $chartDaily,
    'chart_weekly'  => $chartWeekly,
    'chart_monthly' => $chartMonthly,
    'recent_sales'  => $recentSales,
    'low_stock'     => $lowStockItems,
]);
