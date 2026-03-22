<?php
/**
 * POST /api/categories/delete.php
 * Body (JSON): { "category_id": 5 }
 * Items in the deleted category have their category_id set to NULL (SET NULL FK).
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body  = json_decode(file_get_contents('php://input'), true);
$catId = (int) ($body['category_id'] ?? 0);

if ($catId <= 0) jsonError('Invalid category ID.');

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT name FROM categories WHERE category_id = ? LIMIT 1');
$stmt->execute([$catId]);
$cat = $stmt->fetch();

if (!$cat) jsonError('Category not found.', 404);

$pdo->prepare('DELETE FROM categories WHERE category_id = ?')->execute([$catId]);
logActivity($pdo, $currentUser['user_id'], 'category_delete', "Category '{$cat['name']}' (#$catId) deleted.");

jsonOk(null, "Category '{$cat['name']}' deleted.");
