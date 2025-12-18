<?php
/**
 * Silkroad Remote - QR Code Receive Endpoint
 * 
 * Handles QR code registration from the phBot plugin.
 * When a user creates a new QR code, this endpoint saves the mapping
 * between the QR ID and the character data.
 * 
 * Expected GET Parameters:
 * - newQrId: The newly generated QR identifier
 * - oldQrId: (optional) Previous QR ID to replace
 * - account_id: Account ID
 * - player_id: Player ID
 * - server: Server name
 * - name: Character name
 */

require_once 'config.php';

// Get parameters from request
$newQrId = isset($_GET['newQrId']) ? sanitizeInput($_GET['newQrId']) : '';
$oldQrId = isset($_GET['oldQrId']) ? sanitizeInput($_GET['oldQrId']) : '';
$accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
$playerId = isset($_GET['player_id']) ? (int)$_GET['player_id'] : 0;
$server = isset($_GET['server']) ? sanitizeInput($_GET['server']) : '';
$name = isset($_GET['name']) ? sanitizeInput($_GET['name']) : '';

// Validate required fields
if (empty($newQrId)) {
    sendJsonResponse(['error' => 'Missing newQrId'], 400);
}

if ($accountId === 0) {
    sendJsonResponse(['error' => 'Missing account_id'], 400);
}

try {
    $pdo = getDbConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // If there's an old QR ID, deactivate it
    if (!empty($oldQrId)) {
        $deactivateSql = "UPDATE qr_codes SET is_active = 0 WHERE qr_id = :old_qr_id";
        $deactivateStmt = $pdo->prepare($deactivateSql);
        $deactivateStmt->execute([':old_qr_id' => $oldQrId]);
        
        // Also migrate data from old QR to new QR in related tables
        // Using individual queries to avoid SQL injection - table names are hardcoded
        $migrateStmt = $pdo->prepare("UPDATE characters SET qr_id = :new_qr_id WHERE qr_id = :old_qr_id");
        $migrateStmt->execute([':new_qr_id' => $newQrId, ':old_qr_id' => $oldQrId]);
        
        $migrateStmt = $pdo->prepare("UPDATE botting_commands SET qr_id = :new_qr_id WHERE qr_id = :old_qr_id");
        $migrateStmt->execute([':new_qr_id' => $newQrId, ':old_qr_id' => $oldQrId]);
        
        $migrateStmt = $pdo->prepare("UPDATE chat_messages SET qr_id = :new_qr_id WHERE qr_id = :old_qr_id");
        $migrateStmt->execute([':new_qr_id' => $newQrId, ':old_qr_id' => $oldQrId]);
        
        $migrateStmt = $pdo->prepare("UPDATE party_info SET qr_id = :new_qr_id WHERE qr_id = :old_qr_id");
        $migrateStmt->execute([':new_qr_id' => $newQrId, ':old_qr_id' => $oldQrId]);
        
        $migrateStmt = $pdo->prepare("UPDATE item_info SET qr_id = :new_qr_id WHERE qr_id = :old_qr_id");
        $migrateStmt->execute([':new_qr_id' => $newQrId, ':old_qr_id' => $oldQrId]);
    }
    
    // Insert or update the new QR code
    $sql = "INSERT INTO qr_codes (qr_id, old_qr_id, account_id, player_id, server, name, is_active) 
            VALUES (:qr_id, :old_qr_id, :account_id, :player_id, :server, :name, 1)
            ON DUPLICATE KEY UPDATE 
                old_qr_id = VALUES(old_qr_id),
                account_id = VALUES(account_id),
                player_id = VALUES(player_id),
                server = VALUES(server),
                name = VALUES(name),
                is_active = 1,
                updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':qr_id' => $newQrId,
        ':old_qr_id' => $oldQrId ?: null,
        ':account_id' => $accountId,
        ':player_id' => $playerId,
        ':server' => $server,
        ':name' => $name
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    sendJsonResponse([
        'success' => true, 
        'message' => 'QR Code registered successfully',
        'qrId' => $newQrId
    ]);
    
} catch (PDOException $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("qrReceive.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Failed to register QR code'], 500);
}
