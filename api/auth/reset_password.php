<?php
/**
 * POST /api/auth/reset_password.php
 * Step 2 of password reset: set a new password.
 * Body (JSON): { "username": "...", "password": "..." }
 *
 * NOTE: In production, add a one-time token / OTP step before allowing this.
 *       This simplified version trusts the verify step on the same session.
 */

require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body     = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if ($username === '') jsonError('Username is required.');
if (strlen($password) < 6) jsonError('Password must be at least 6 characters.');

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
if (!$stmt->fetch()) jsonError('Account not found.', 404);

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$upd  = $pdo->prepare('UPDATE users SET password = ? WHERE username = ?');
$upd->execute([$hash, $username]);

jsonOk(null, 'Password updated successfully.');
