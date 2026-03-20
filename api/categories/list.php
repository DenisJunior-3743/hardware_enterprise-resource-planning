<?php
/**
 * GET /api/categories/list.php
 * Returns all categories with per-category item counts and low-stock counts.
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

$pdo = getDB();

$rows = $pdo->query(
    'SELECT c.category_id, c.name, c.description, c.created_at,
            COUNT(i.item_id)                                            AS item_count,
            SUM(i.stock_quantity <= i.restock_level)                   AS low_stock_count
     FROM categories c
     LEFT JOIN items i ON i.category_id = c.category_id
     GROUP BY c.category_id
     ORDER BY c.name ASC'
)->fetchAll();

jsonOk(['categories' => $rows]);
