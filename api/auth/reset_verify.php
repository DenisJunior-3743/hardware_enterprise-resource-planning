<?php
/**
 * POST /api/auth/reset_verify.php
 * Step 1 of password reset: confirm the username + phone match.
 * Body (JSON): { "username": "...", "phone": "..." }
 */

require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed.', 405);

$body     = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$phone    = trim($body['phone']    ?? '');

if ($username === '') jsonError('Username is required.');
if ($phone    === '') jsonError('Phone number is required.');

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT user_id, phone FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonError('No account found with that username.', 404);
}

if ((string)($user['phone'] ?? '') !== $phone) {
    jsonError('Phone number does not match our records.', 403);
}

jsonOk(null, 'Account verified. Please set your new password.');
