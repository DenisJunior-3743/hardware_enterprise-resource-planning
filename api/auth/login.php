<?php
/**
 * POST /api/auth/login.php
 * Body (JSON): { "username": "...", "password": "..." }
 * Response: { success, data: { user_id, username, role }, message }
 */

require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if ($username === '' || $password === '') {
    jsonError('Username and password are required.');
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT user_id, username, password, role FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonError('Invalid username or password.', 401);
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

$_SESSION['user_id']  = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

jsonOk([
    'user_id'  => $user['user_id'],
    'username' => $user['username'],
    'role'     => $user['role'],
], 'Login successful.');
