<?php
/**
 * Silkroad Remote - Item/Inventory Receive Endpoint
 * 
 * Receives inventory/item data from the phBot plugin.
 * Item data includes quantity, plus level, and item name.
 * 
 * Expected GET Parameters:
 * - accountId: Account ID
 * - qrId: Unique QR identifier for the character
 * - itemData: Inventory item data (format: quantity☽plus☽nameΨquantity2☽plus2☽name2Ψ...)
 * 
 * The mobile app can retrieve this data to display inventory contents.
 */

require_once 'config.php';

// Get parameters from request
$accountId = isset($_GET['accountId']) ? (int)$_GET['accountId'] : 0;
$qrId = isset($_GET['qrId']) ? sanitizeInput($_GET['qrId']) : '';
$itemData = isset($_GET['itemData']) ? $_GET['itemData'] : null;

// Validate required fields
if (empty($qrId)) {
    sendJsonResponse(['error' => 'Missing qrId'], 400);
}

try {
    $pdo = getDbConnection();
    
    if ($itemData !== null) {
        // Saving item data from bot
        $sql = "INSERT INTO item_info (qr_id, account_id, item_data) 
                VALUES (:qr_id, :account_id, :item_data)
                ON DUPLICATE KEY UPDATE 
                    item_data = VALUES(item_data),
                    updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':qr_id' => $qrId,
            ':account_id' => $accountId,
            ':item_data' => $itemData
        ]);
        
        sendJsonResponse(['success' => true, 'message' => 'Item info saved']);
    } else {
        // Retrieving item data for mobile app
        $sql = "SELECT item_data FROM item_info WHERE qr_id = :qr_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':qr_id' => $qrId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Parse item data into structured format
            $items = [];
            $itemString = $result['item_data'];
            
            if (!empty($itemString)) {
                $itemEntries = explode('Ψ', $itemString);
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
                'itemData' => $result['item_data'],
                'items' => $items
            ]);
        } else {
            sendJsonResponse([
                'success' => true,
                'itemData' => '',
                'items' => []
            ]);
        }
    }
    
} catch (PDOException $e) {
    error_log("itemReceive.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Failed to process item data'], 500);
}
