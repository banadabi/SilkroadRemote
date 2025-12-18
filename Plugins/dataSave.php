<?php
/**
 * Silkroad Remote - Data Save Endpoint
 * 
 * Receives and saves character data from the phBot plugin.
 * Called periodically by the bot to update character status.
 * 
 * Expected GET Parameters:
 * - qrId: Unique QR identifier for the character
 * - account_id: Account ID
 * - player_id: Player ID
 * - server: Server name
 * - name: Character name
 * - level: Current level
 * - max_level: Maximum level
 * - exp: Current experience
 * - max_exp: Maximum experience
 * - sp: Skill points
 * - gold: Gold in inventory
 * - gold_stored: Gold in storage
 * - hp: Current HP
 * - max_hp: Maximum HP
 * - mp: Current MP
 * - max_mp: Maximum MP
 * - region: Region ID
 * - regionPalace: Region/Zone name
 * - x, y, z: Character position coordinates
 * - deadCounter: Death count
 * - actualTrainingAreaX, actualTrainingAreaY: Training area coordinates
 * - actualTrainingRadius: Training radius
 * - pcStartBot: Bot started flag (from PC)
 * - pcStopBot: Bot stopped flag (from PC)
 * - connectedState: Connection status
 */

require_once 'config.php';

// Get parameters from request
$qrId = isset($_GET['qrId']) ? sanitizeInput($_GET['qrId']) : '';
$accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;
$playerId = isset($_GET['player_id']) ? (int)$_GET['player_id'] : 0;
$server = isset($_GET['server']) ? sanitizeInput($_GET['server']) : '';
$name = isset($_GET['name']) ? sanitizeInput($_GET['name']) : '';
$level = isset($_GET['level']) ? (int)$_GET['level'] : 0;
$maxLevel = isset($_GET['max_level']) ? (int)$_GET['max_level'] : 0;
$exp = isset($_GET['exp']) ? (int)$_GET['exp'] : 0;
$maxExp = isset($_GET['max_exp']) ? (int)$_GET['max_exp'] : 0;
$sp = isset($_GET['sp']) ? (int)$_GET['sp'] : 0;
$gold = isset($_GET['gold']) ? (int)$_GET['gold'] : 0;
$goldStored = isset($_GET['gold_stored']) ? (int)$_GET['gold_stored'] : 0;
$hp = isset($_GET['hp']) ? (int)$_GET['hp'] : 0;
$maxHp = isset($_GET['max_hp']) ? (int)$_GET['max_hp'] : 0;
$mp = isset($_GET['mp']) ? (int)$_GET['mp'] : 0;
$maxMp = isset($_GET['max_mp']) ? (int)$_GET['max_mp'] : 0;
$region = isset($_GET['region']) ? (int)$_GET['region'] : 0;
$regionPalace = isset($_GET['regionPalace']) ? sanitizeInput($_GET['regionPalace']) : '';
$x = isset($_GET['x']) ? (float)$_GET['x'] : 0;
$y = isset($_GET['y']) ? (float)$_GET['y'] : 0;
$z = isset($_GET['z']) ? (float)$_GET['z'] : 0;
$deadCounter = isset($_GET['deadCounter']) ? (int)$_GET['deadCounter'] : 0;
$actualTrainingAreaX = isset($_GET['actualTrainingAreaX']) ? (float)$_GET['actualTrainingAreaX'] : 0;
$actualTrainingAreaY = isset($_GET['actualTrainingAreaY']) ? (float)$_GET['actualTrainingAreaY'] : 0;
$actualTrainingRadius = isset($_GET['actualTrainingRadius']) ? (float)$_GET['actualTrainingRadius'] : 0;
$pcStartBot = isset($_GET['pcStartBot']) ? (int)$_GET['pcStartBot'] : 0;
$pcStopBot = isset($_GET['pcStopBot']) ? (int)$_GET['pcStopBot'] : 0;
$connectedState = isset($_GET['connectedState']) ? convertToBoolean($_GET['connectedState']) : 0;

// Validate required fields
if (empty($qrId)) {
    sendJsonResponse(['error' => 'Missing qrId'], 400);
}

try {
    $pdo = getDbConnection();
    
    // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert functionality
    $sql = "INSERT INTO characters (
                qr_id, account_id, player_id, server, name, level, max_level, 
                exp, max_exp, sp, gold, gold_stored, hp, max_hp, mp, max_mp, 
                region, region_palace, x, y, z, dead_counter, 
                actual_training_area_x, actual_training_area_y, actual_training_radius,
                pc_start_bot, pc_stop_bot, connected_state
            ) VALUES (
                :qr_id, :account_id, :player_id, :server, :name, :level, :max_level,
                :exp, :max_exp, :sp, :gold, :gold_stored, :hp, :max_hp, :mp, :max_mp,
                :region, :region_palace, :x, :y, :z, :dead_counter,
                :actual_training_area_x, :actual_training_area_y, :actual_training_radius,
                :pc_start_bot, :pc_stop_bot, :connected_state
            ) ON DUPLICATE KEY UPDATE
                account_id = VALUES(account_id),
                player_id = VALUES(player_id),
                server = VALUES(server),
                name = VALUES(name),
                level = VALUES(level),
                max_level = VALUES(max_level),
                exp = VALUES(exp),
                max_exp = VALUES(max_exp),
                sp = VALUES(sp),
                gold = VALUES(gold),
                gold_stored = VALUES(gold_stored),
                hp = VALUES(hp),
                max_hp = VALUES(max_hp),
                mp = VALUES(mp),
                max_mp = VALUES(max_mp),
                region = VALUES(region),
                region_palace = VALUES(region_palace),
                x = VALUES(x),
                y = VALUES(y),
                z = VALUES(z),
                dead_counter = VALUES(dead_counter),
                actual_training_area_x = VALUES(actual_training_area_x),
                actual_training_area_y = VALUES(actual_training_area_y),
                actual_training_radius = VALUES(actual_training_radius),
                pc_start_bot = VALUES(pc_start_bot),
                pc_stop_bot = VALUES(pc_stop_bot),
                connected_state = VALUES(connected_state),
                updated_at = CURRENT_TIMESTAMP";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':qr_id' => $qrId,
        ':account_id' => $accountId,
        ':player_id' => $playerId,
        ':server' => $server,
        ':name' => $name,
        ':level' => $level,
        ':max_level' => $maxLevel,
        ':exp' => $exp,
        ':max_exp' => $maxExp,
        ':sp' => $sp,
        ':gold' => $gold,
        ':gold_stored' => $goldStored,
        ':hp' => $hp,
        ':max_hp' => $maxHp,
        ':mp' => $mp,
        ':max_mp' => $maxMp,
        ':region' => $region,
        ':region_palace' => $regionPalace,
        ':x' => $x,
        ':y' => $y,
        ':z' => $z,
        ':dead_counter' => $deadCounter,
        ':actual_training_area_x' => $actualTrainingAreaX,
        ':actual_training_area_y' => $actualTrainingAreaY,
        ':actual_training_radius' => $actualTrainingRadius,
        ':pc_start_bot' => $pcStartBot,
        ':pc_stop_bot' => $pcStopBot,
        ':connected_state' => $connectedState
    ]);
    
    sendJsonResponse(['success' => true, 'message' => 'Data saved successfully']);
    
} catch (PDOException $e) {
    error_log("dataSave.php error: " . $e->getMessage());
    sendJsonResponse(['error' => 'Failed to save data'], 500);
}
