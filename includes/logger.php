<?php
/**
 * DukaSoft Hardware ERP — Activity Logger
 * Convenience helper to insert a row into the logs table.
 *
 * Usage:
 *   logActivity($pdo, $userId, 'sale_create', "Sale #$saleId recorded.");
 */

function logActivity(PDO $pdo, ?int $userId, string $action, string $details = ''): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        // Non-fatal — logging failure should not break the main request
    }
}
