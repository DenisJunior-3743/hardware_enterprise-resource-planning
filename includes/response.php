<?php
/**
 * DukaSoft Hardware ERP — JSON Response Helper
 * Include in every API endpoint to produce uniform JSON output.
 */

// Ensure no accidental output before these headers
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

/**
 * Terminate with a JSON response.
 *
 * @param bool   $success
 * @param mixed  $data
 * @param string $message
 * @param int    $code     HTTP status code
 */
function jsonResponse(bool $success, $data = null, string $message = '', int $code = 200): void
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonOk($data = null, string $message = ''): void
{
    jsonResponse(true, $data, $message, 200);
}

function jsonError(string $message, int $code = 400, $data = null): void
{
    jsonResponse(false, $data, $message, $code);
}
