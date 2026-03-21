<?php
/**
 * POST /api/categories/save.php
 * Creates a new category or updates an existing one.
 * Body (JSON): { "category_id": 0, "name": "...", "description": "..." }
 * category_id = 0 or absent → new record; otherwise update.
 */

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body       = json_decode(file_get_contents('php://input'), true);
$catId      = (int)   ($body['category_id'] ?? 0);
$name       = trim($body['name']        ?? '');
$description = trim($body['description'] ?? '');

if ($name === '') jsonError('Category name is required.');

$pdo = getDB();

// Duplicate name check
$dupStmt = $pdo->prepare(
    'SELECT category_id FROM categories WHERE name = ? AND category_id <> ? LIMIT 1'
);
$dupStmt->execute([$name, $catId]);
if ($dupStmt->fetch()) {
    jsonError('A category with this name already exists.', 409);
}

if ($catId === 0) {
    // INSERT
    $stmt = $pdo->prepare(
        'INSERT INTO categories (name, description) VALUES (?, ?)'
    );
    $stmt->execute([$name, $description]);
    $catId = (int) $pdo->lastInsertId();
    logActivity($pdo, $currentUser['user_id'], 'category_create', "Category '$name' (#$catId) created.");
    $msg = 'Category created successfully.';
} else {
    // UPDATE
    $stmt = $pdo->prepare(
        'UPDATE categories SET name = ?, description = ? WHERE category_id = ?'
    );
    $stmt->execute([$name, $description, $catId]);
    logActivity($pdo, $currentUser['user_id'], 'category_update', "Category '$name' (#$catId) updated.");
    $msg = 'Category updated successfully.';
}

$cat = $pdo->prepare('SELECT * FROM categories WHERE category_id = ?');
$cat->execute([$catId]);

jsonOk($cat->fetch(), $msg);
