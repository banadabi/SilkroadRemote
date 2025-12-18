<?php
/**
 * Silkroad Remote - Database Configuration
 * 
 * Configure your database connection settings here.
 * Make sure to keep this file secure and not publicly accessible.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'silkroad_remote');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set timezone
date_default_timezone_set('UTC');

/**
 * Get database connection using PDO
 * 
 * @return PDO Database connection object
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error but don't expose details
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    
    return $pdo;
}

/**
 * Sanitize input data
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Convert various boolean representations to integer (0 or 1)
 * 
 * @param mixed $value Value to convert (string, bool, int)
 * @return int 1 for true values, 0 for false values
 */
function convertToBoolean($value) {
    if ($value === null) {
        return 0;
    }
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    if (is_numeric($value)) {
        return (int)$value !== 0 ? 1 : 0;
    }
    $truthy = ['true', 'True', 'TRUE', '1', 'yes', 'Yes', 'YES', 'on'];
    return in_array($value, $truthy, true) ? 1 : 0;
}

/**
 * Send JSON response
 * 
 * @param array $data Data to send as JSON
 * @param int $statusCode HTTP status code
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    // Note: In production, replace '*' with your specific domain for security
    // Example: header('Access-Control-Allow-Origin: https://yourdomain.com');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
