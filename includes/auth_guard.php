<?php
/**
 * DukaSoft Hardware ERP — Session Auth Guard
 * Include at the top of every authenticated API endpoint.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/auth_guard.php';
 *   // $currentUser is now available as an array with user_id, username, role
 */

require_once __DIR__ . '/../includes/response.php';

// Prevent PHP error text from corrupting JSON responses
ini_set('display_errors', '0');

// Catch any unhandled exception and return proper JSON instead of HTML
set_exception_handler(function (Throwable $e): void {
    jsonError('Server error: ' . $e->getMessage(), 500);
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    jsonError('Unauthorized. Please log in.', 401);
}

$currentUser = [
    'user_id'  => (int) $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'Unknown',
    'role'     => $_SESSION['role']     ?? 'user',
];
