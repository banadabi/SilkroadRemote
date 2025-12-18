<?php
/**
 * Silkroad Remote - Party Receive Endpoint
 * 
 * Receives party member information from the phBot plugin.
 * Party data includes member names, HP and MP percentages.
 * 
 * Expected GET Parameters:
 * - accountId: Account ID
 * - qrId: Unique QR identifier for the character
 * - partyUsers: Party member data (format: name☽hp_percent☽mp_percentΨname2☽hp2☽mp2Ψ...)
 * 
 * The mobile app can retrieve this data to display party status.
 */

require_once 'config.php';

// Get parameters from request
$accountId = isset($_GET['accountId']) ? (int)$_GET['accountId'] : 0;
$qrId = isset($_GET['qrId']) ? sanitizeInput($_GET['qrId']) : '';
$partyUsers = isset($_GET['partyUsers']) ? $_GET['partyUsers'] : null;

// Validate required fields
if (empty($qrId)) {
    sendJsonResponse(['error' => 'Missing qrId'], 400);
}

try {
    $pdo = getDbConnection();
    
    if ($partyUsers !== null) {
        // Saving party data from bot
        $sql = "INSERT INTO party_info (qr_id, account_id, party_users) 
                VALUES (:qr_id, :account_id, :party_users)
                ON DUPLICATE KEY UPDATE 
                    party_users = VALUES(party_users),
                    updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':qr_id' => $qrId,
            ':account_id' => $accountId,
            ':party_users' => $partyUsers
        ]);
        
        sendJsonResponse(['success' => true, 'message' => 'Party info saved']);
    } else {
        // Retrieving party data for mobile app
        $sql = "SELECT party_users FROM party_info WHERE qr_id = :qr_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':qr_id' => $qrId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Parse party data into structured format
            $partyData = [];
            $partyString = $result['party_users'];
            
            if (!empty($partyString)) {
                $members = explode('Ψ', $partyString);
                foreach ($members as $member) {
                    if (!empty(trim($member))) {
                        $parts = explode('☽', $member);
                        if (count($parts) >= 3) {
                            $partyData[] = [
                                'name' => $parts[0],
                                'hp_percent' => (int)$parts[1],
                                'mp_percent' => (int)$parts[2]
                            ];
                        }
                    }
                }
            }
            
            sendJsonResponse([
                'success' => true,
                'partyUsers' => $result['party_users'],
                'partyData' => $partyData
            ]);
        } else {
            sendJsonResponse([
                'success' => true,
                'partyUsers' => '',
                'partyData' => []
            ]);
        }
    }
    
} catch (PDOException $e) {
    error_log("partyReceive.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Failed to process party data'], 500);
}
