<?php
/**
 * POST /api/auth/register.php
 * Body (JSON): { "username": "...", "password": "...", "full_name": "...", "phone": "..." }
 */

require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$body      = json_decode(file_get_contents('php://input'), true);
$username  = trim($body['username']  ?? '');
$password  = $body['password']       ?? '';
$full_name = trim($body['full_name'] ?? '');
$phone     = trim($body['phone']     ?? '');

if ($username === '' || $password === '') {
    jsonError('Username and password are required.');
}

if (strlen($password) < 6) {
    jsonError('Password must be at least 6 characters.');
}

$pdo = getDB();

// Check for duplicate username
$dup = $pdo->prepare(
    'SELECT user_id FROM users WHERE username = ? LIMIT 1'
);
$dup->execute([$username]);
if ($dup->fetch()) {
    jsonError('Username is already taken.', 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$ins = $pdo->prepare(
    'INSERT INTO users (username, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?)'
);
$ins->execute([$username, $hash, $full_name ?: null, $phone ?: null, 'user']);
$userId = (int) $pdo->lastInsertId();

// Auto-login after registration
session_regenerate_id(true);
$_SESSION['user_id']  = $userId;
$_SESSION['username'] = $username;
$_SESSION['role']     = 'user';

jsonOk([
    'user_id'  => $userId,
    'username' => $username,
    'role'     => 'user',
], 'Account created successfully.', 201);
