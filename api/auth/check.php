<?php
/**
 * GET /api/auth/check.php
 * Returns current session state — used on page load to verify the user is logged in.
 */

require_once __DIR__ . '/../../includes/response.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    jsonError('Not authenticated.', 401);
}

jsonOk([
    'user_id'  => (int) $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role'     => $_SESSION['role'],
]);
