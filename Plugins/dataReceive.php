<?php
/**
 * Silkroad Remote - Data Receive Endpoint
 * 
 * Returns botting commands from the mobile app to the PC bot.
 * The mobile app can send training area coordinates and start/stop commands.
 * 
 * Expected GET Parameters:
 * - accountId: Account ID
 * - qrId: Unique QR identifier for the character
 * 
 * Response Format:
 * {
 *   "androidToPcData": [{
 *     "trainingRadius": "0",
 *     "trainingAreaX": "0",
 *     "trainingAreaY": "0",
 *     "startBot": "0",
 *     "stopBot": "0"
 *   }]
 * }
 */

require_once 'config.php';

// Get parameters from request
$accountId = isset($_GET['accountId']) ? (int)$_GET['accountId'] : 0;
$qrId = isset($_GET['qrId']) ? sanitizeInput($_GET['qrId']) : '';

// Validate required fields
if (empty($qrId)) {
    sendJsonResponse(['error' => 'Missing qrId'], 400);
}

try {
    $pdo = getDbConnection();
    
    // First, check if there's existing command data for this qrId
    $sql = "SELECT training_radius, training_area_x, training_area_y, start_bot, stop_bot 
            FROM botting_commands 
            WHERE qr_id = :qr_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':qr_id' => $qrId]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Return existing command data
        $response = [
            'androidToPcData' => [[
                'trainingRadius' => (string)$result['training_radius'],
                'trainingAreaX' => (string)$result['training_area_x'],
                'trainingAreaY' => (string)$result['training_area_y'],
                'startBot' => (string)$result['start_bot'],
                'stopBot' => (string)$result['stop_bot']
            ]]
        ];
    } else {
        // No commands yet, return default values
        // Also create a default entry for this qrId
        $insertSql = "INSERT INTO botting_commands (qr_id, account_id, training_radius, training_area_x, training_area_y, start_bot, stop_bot) 
                      VALUES (:qr_id, :account_id, 0, 0, 0, '0', '0')
                      ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";
        
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            ':qr_id' => $qrId,
            ':account_id' => $accountId
        ]);
        
        $response = [
            'androidToPcData' => [[
                'trainingRadius' => '0',
                'trainingAreaX' => '0',
                'trainingAreaY' => '0',
                'startBot' => '0',
                'stopBot' => '0'
            ]]
        ];
    }
    
    sendJsonResponse($response);
    
} catch (PDOException $e) {
    error_log("dataReceive.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Failed to retrieve data'], 500);
}
