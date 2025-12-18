<?php
/**
 * Silkroad Remote - Chat Receive/Send Endpoint
 * 
 * Handles bidirectional chat communication between mobile app and PC bot.
 * - Receives party/guild chat messages from the bot
 * - Returns messages from mobile app to be sent to game chat
 * 
 * Expected GET Parameters:
 * - accountId: Account ID
 * - qrId: Unique QR identifier for the character
 * - partyMessage: (optional) Party chat messages from bot (Ψ delimited)
 * - guildMessage: (optional) Guild chat messages from bot (Ψ delimited)
 * 
 * For mobile app to send messages to PC:
 * - sendMessage: Message text to send
 * - whichChat: Chat type (0=party, 1=guild)
 * 
 * Response Format (for PC to read messages from mobile):
 * {
 *   "androidToPcData": [{
 *     "sendMessage": "message text",
 *     "whichChat": "0",
 *     "sendMessageCount": 1
 *   }]
 * }
 */

require_once 'config.php';

// Get parameters from request
$accountId = isset($_GET['accountId']) ? (int)$_GET['accountId'] : 0;
$qrId = isset($_GET['qrId']) ? sanitizeInput($_GET['qrId']) : '';
$partyMessage = isset($_GET['partyMessage']) ? $_GET['partyMessage'] : null;
$guildMessage = isset($_GET['guildMessage']) ? $_GET['guildMessage'] : null;

// Mobile app sends messages with these parameters
$sendMessage = isset($_GET['sendMessage']) ? $_GET['sendMessage'] : null;
$whichChat = isset($_GET['whichChat']) ? sanitizeInput($_GET['whichChat']) : '0';

// Validate required fields
if (empty($qrId)) {
    sendJsonResponse(['error' => 'Missing qrId'], 400);
}

try {
    $pdo = getDbConnection();
    
    // If receiving chat messages from bot
    if ($partyMessage !== null || $guildMessage !== null) {
        // Check if record exists
        $checkSql = "SELECT id FROM chat_messages WHERE qr_id = :qr_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':qr_id' => $qrId]);
        $exists = $checkStmt->fetch();
        
        if ($exists) {
            // Update existing record
            $updates = [];
            $params = [':qr_id' => $qrId];
            
            if ($partyMessage !== null) {
                $updates[] = "party_message = :party_message";
                $params[':party_message'] = $partyMessage;
            }
            if ($guildMessage !== null) {
                $updates[] = "guild_message = :guild_message";
                $params[':guild_message'] = $guildMessage;
            }
            
            if (!empty($updates)) {
                $sql = "UPDATE chat_messages SET " . implode(', ', $updates) . " WHERE qr_id = :qr_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
        } else {
            // Insert new record
            $sql = "INSERT INTO chat_messages (qr_id, account_id, party_message, guild_message) 
                    VALUES (:qr_id, :account_id, :party_message, :guild_message)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':qr_id' => $qrId,
                ':account_id' => $accountId,
                ':party_message' => $partyMessage,
                ':guild_message' => $guildMessage
            ]);
        }
        
        sendJsonResponse(['success' => true, 'message' => 'Chat messages saved']);
    }
    
    // If mobile app is sending a message to be relayed to game
    if ($sendMessage !== null) {
        // Get current message count to increment
        $countSql = "SELECT send_message_count FROM chat_messages WHERE qr_id = :qr_id";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':qr_id' => $qrId]);
        $countResult = $countStmt->fetch();
        
        $newCount = $countResult ? (int)$countResult['send_message_count'] + 1 : 1;
        
        // Update or insert the message to send
        $sql = "INSERT INTO chat_messages (qr_id, account_id, send_message, which_chat, send_message_count) 
                VALUES (:qr_id, :account_id, :send_message, :which_chat, :send_message_count)
                ON DUPLICATE KEY UPDATE 
                    send_message = VALUES(send_message),
                    which_chat = VALUES(which_chat),
                    send_message_count = VALUES(send_message_count)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':qr_id' => $qrId,
            ':account_id' => $accountId,
            ':send_message' => $sendMessage,
            ':which_chat' => $whichChat,
            ':send_message_count' => $newCount
        ]);
        
        sendJsonResponse(['success' => true, 'message' => 'Message queued for sending', 'count' => $newCount]);
    }
    
    // Default: Return current chat state and any pending messages to send
    $sql = "SELECT party_message, guild_message, send_message, which_chat, send_message_count 
            FROM chat_messages 
            WHERE qr_id = :qr_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':qr_id' => $qrId]);
    $result = $stmt->fetch();
    
    if ($result) {
        $response = [
            'androidToPcData' => [[
                'sendMessage' => $result['send_message'] ?? '',
                'whichChat' => $result['which_chat'] ?? '0',
                'sendMessageCount' => (int)($result['send_message_count'] ?? 0)
            ]],
            'partyMessage' => $result['party_message'] ?? '',
            'guildMessage' => $result['guild_message'] ?? ''
        ];
    } else {
        // Create default entry
        $insertSql = "INSERT INTO chat_messages (qr_id, account_id) VALUES (:qr_id, :account_id)
                      ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([':qr_id' => $qrId, ':account_id' => $accountId]);
        
        $response = [
            'androidToPcData' => [[
                'sendMessage' => '',
                'whichChat' => '0',
                'sendMessageCount' => 0
            ]],
            'partyMessage' => '',
            'guildMessage' => ''
        ];
    }
    
    sendJsonResponse($response);
    
} catch (PDOException $e) {
    error_log("chatReceive.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Failed to process chat data'], 500);
}
