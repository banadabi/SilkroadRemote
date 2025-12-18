<?php
/**
 * Silkroad Remote - Notification Input Endpoint
 * 
 * Receives notifications from the phBot plugin.
 * Types of notifications:
 * - 0: Character death notification
 * - 1: Rare item drop notification
 * 
 * Expected GET Parameters:
 * - accountId: Account ID
 * - qrId: Unique QR identifier for the character
 * - notificationCount: Type of notification (0=death, 1=rare item)
 */

require_once 'config.php';

// Get parameters from request
$accountId = isset($_GET['accountId']) ? (int)$_GET['accountId'] : 0;
$qrId = isset($_GET['qrId']) ? sanitizeInput($_GET['qrId']) : '';
$notificationCount = isset($_GET['notificationCount']) ? (int)$_GET['notificationCount'] : 0;

// Validate required fields
if (empty($qrId)) {
    sendJsonResponse(['error' => 'Missing qrId'], 400);
}

if ($accountId === 0) {
    sendJsonResponse(['error' => 'Missing accountId'], 400);
}

try {
    $pdo = getDbConnection();
    
    // Insert notification record
    $sql = "INSERT INTO notifications (qr_id, account_id, notification_type, notification_count) 
            VALUES (:qr_id, :account_id, :notification_type, 1)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':qr_id' => $qrId,
        ':account_id' => $accountId,
        ':notification_type' => $notificationCount
    ]);
    
    // Get notification type name for response
    $notificationType = $notificationCount === 0 ? 'death' : 'rare_item';
    
    sendJsonResponse([
        'success' => true, 
        'message' => 'Notification received',
        'notification_type' => $notificationType
    ]);
    
} catch (PDOException $e) {
    error_log("notificationIn.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Failed to save notification'], 500);
}
