<?php
/**
 * Silkroad Remote - Mobile API Endpoint
 * 
 * Main API endpoint for mobile app to:
 * - Get character data (stats, position, etc.)
 * - Send commands to the bot (start/stop, training area)
 * - Get notifications
 * 
 * Expected GET Parameters:
 * - qrId: Unique QR identifier for the character
 * - action: API action to perform
 *   - 'getCharacter': Get character data
 *   - 'setCommand': Set botting commands
 *   - 'getNotifications': Get recent notifications
 * 
 * For setCommand action:
 * - trainingRadius: Training radius
 * - trainingAreaX: Training area X coordinate
 * - trainingAreaY: Training area Y coordinate
 * - startBot: Start bot flag (0 or 1)
 * - stopBot: Stop bot flag (0 or 1)
 */

require_once 'config.php';

// Get parameters from request
$qrId = isset($_GET['qrId']) ? sanitizeInput($_GET['qrId']) : '';
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'getCharacter';

// Validate required fields
if (empty($qrId)) {
    sendJsonResponse(['error' => 'Missing qrId'], 400);
}

try {
    $pdo = getDbConnection();
    
    switch ($action) {
        case 'getCharacter':
            // Get character data
            $sql = "SELECT * FROM characters WHERE qr_id = :qr_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':qr_id' => $qrId]);
            $character = $stmt->fetch();
            
            if ($character) {
                // Also get party info
                $partySql = "SELECT party_users FROM party_info WHERE qr_id = :qr_id";
                $partyStmt = $pdo->prepare($partySql);
                $partyStmt->execute([':qr_id' => $qrId]);
                $partyResult = $partyStmt->fetch();
                
                // Parse party data
                $partyData = [];
                if ($partyResult && !empty($partyResult['party_users'])) {
                    $members = explode('Ψ', $partyResult['party_users']);
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
                
                // Get chat messages
                $chatSql = "SELECT party_message, guild_message FROM chat_messages WHERE qr_id = :qr_id";
                $chatStmt = $pdo->prepare($chatSql);
                $chatStmt->execute([':qr_id' => $qrId]);
                $chatResult = $chatStmt->fetch();
                
                sendJsonResponse([
                    'success' => true,
                    'character' => [
                        'name' => $character['name'],
                        'server' => $character['server'],
                        'level' => (int)$character['level'],
                        'max_level' => (int)$character['max_level'],
                        'exp' => (int)$character['exp'],
                        'max_exp' => (int)$character['max_exp'],
                        'sp' => (int)$character['sp'],
                        'gold' => (int)$character['gold'],
                        'gold_stored' => (int)$character['gold_stored'],
                        'hp' => (int)$character['hp'],
                        'max_hp' => (int)$character['max_hp'],
                        'mp' => (int)$character['mp'],
                        'max_mp' => (int)$character['max_mp'],
                        'region' => (int)$character['region'],
                        'region_palace' => $character['region_palace'],
                        'x' => (float)$character['x'],
                        'y' => (float)$character['y'],
                        'z' => (float)$character['z'],
                        'dead_counter' => (int)$character['dead_counter'],
                        'training_area_x' => (float)$character['actual_training_area_x'],
                        'training_area_y' => (float)$character['actual_training_area_y'],
                        'training_radius' => (float)$character['actual_training_radius'],
                        'is_botting' => (int)$character['pc_start_bot'] === 1,
                        'connected' => (int)$character['connected_state'] === 1,
                        'last_update' => $character['updated_at']
                    ],
                    'party' => $partyData,
                    'chat' => [
                        'party_message' => $chatResult['party_message'] ?? '',
                        'guild_message' => $chatResult['guild_message'] ?? ''
                    ]
                ]);
            } else {
                sendJsonResponse(['error' => 'Character not found', 'qrId' => $qrId], 404);
            }
            break;
            
        case 'setCommand':
            // Set botting commands from mobile app
            $trainingRadius = isset($_GET['trainingRadius']) ? (float)$_GET['trainingRadius'] : null;
            $trainingAreaX = isset($_GET['trainingAreaX']) ? (float)$_GET['trainingAreaX'] : null;
            $trainingAreaY = isset($_GET['trainingAreaY']) ? (float)$_GET['trainingAreaY'] : null;
            $startBot = isset($_GET['startBot']) ? sanitizeInput($_GET['startBot']) : null;
            $stopBot = isset($_GET['stopBot']) ? sanitizeInput($_GET['stopBot']) : null;
            
            // Build update query dynamically based on provided parameters
            $updates = [];
            $params = [':qr_id' => $qrId];
            
            if ($trainingRadius !== null) {
                $updates[] = "training_radius = :training_radius";
                $params[':training_radius'] = $trainingRadius;
            }
            if ($trainingAreaX !== null) {
                $updates[] = "training_area_x = :training_area_x";
                $params[':training_area_x'] = $trainingAreaX;
            }
            if ($trainingAreaY !== null) {
                $updates[] = "training_area_y = :training_area_y";
                $params[':training_area_y'] = $trainingAreaY;
            }
            if ($startBot !== null) {
                $updates[] = "start_bot = :start_bot";
                $params[':start_bot'] = $startBot;
            }
            if ($stopBot !== null) {
                $updates[] = "stop_bot = :stop_bot";
                $params[':stop_bot'] = $stopBot;
            }
            
            if (empty($updates)) {
                sendJsonResponse(['error' => 'No command parameters provided'], 400);
            }
            
            // First ensure record exists
            $insertSql = "INSERT IGNORE INTO botting_commands (qr_id, account_id) VALUES (:qr_id, 0)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([':qr_id' => $qrId]);
            
            // Then update
            $sql = "UPDATE botting_commands SET " . implode(', ', $updates) . " WHERE qr_id = :qr_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            sendJsonResponse(['success' => true, 'message' => 'Command set successfully']);
            break;
            
        case 'getNotifications':
            // Get recent notifications
            $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
            
            $sql = "SELECT notification_type, notification_count, created_at 
                    FROM notifications 
                    WHERE qr_id = :qr_id 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':qr_id', $qrId, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll();
            
            // Format notifications
            $formattedNotifications = array_map(function($n) {
                return [
                    'type' => $n['notification_type'] === 0 ? 'death' : 'rare_item',
                    'count' => (int)$n['notification_count'],
                    'timestamp' => $n['created_at']
                ];
            }, $notifications);
            
            sendJsonResponse([
                'success' => true,
                'notifications' => $formattedNotifications
            ]);
            break;
            
        case 'getInventory':
            // Get inventory items
            $sql = "SELECT item_data FROM item_info WHERE qr_id = :qr_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':qr_id' => $qrId]);
            $result = $stmt->fetch();
            
            $items = [];
            if ($result && !empty($result['item_data'])) {
                $itemEntries = explode('Ψ', $result['item_data']);
                foreach ($itemEntries as $entry) {
                    if (!empty(trim($entry))) {
                        $parts = explode('☽', $entry);
                        if (count($parts) >= 3) {
                            $items[] = [
                                'quantity' => (int)$parts[0],
                                'plus' => (int)$parts[1],
                                'name' => $parts[2]
                            ];
                        }
                    }
                }
            }
            
            sendJsonResponse([
                'success' => true,
                'items' => $items
            ]);
            break;
            
        default:
            sendJsonResponse(['error' => 'Unknown action'], 400);
    }
    
} catch (PDOException $e) {
    error_log("mobileApi.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Server error'], 500);
}
