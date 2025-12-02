<?php
require_once '../config.php';
require_once '../functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$session_name = trim($input['session_name'] ?? '');
$anonymous_id = trim($input['anonymous_id'] ?? '');
$custom_code = trim($input['custom_code'] ?? '');

if (empty($session_name) || empty($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First, ensure the anonymous user exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO anonymous_users (anonymous_id, is_host, created_at) VALUES (?, 1, NOW())");
    $stmt->execute([$anonymous_id]);
    
    // Handle session code - user custom or auto-generated
    if (!empty($custom_code)) {
        // Validate custom code (alphanumeric, 4-12 characters)
        if (!preg_match('/^[a-zA-Z0-9]{4,12}$/', $custom_code)) {
            http_response_code(400);
            echo json_encode(['error' => 'Custom code must be 4-12 alphanumeric characters']);
            exit;
        }
        
        // Check if custom code already exists
        $stmt = $pdo->prepare("SELECT session_code FROM sessions WHERE session_code = ?");
        $stmt->execute([$custom_code]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'This session code is already taken']);
            exit;
        }
        
        $session_code = $custom_code;
    } else {
        // Generate unique session code (10 chars max)
        $session_code = 'fx' . rand(10000000, 99999999);
    }
    
    // Create session
    $stmt = $pdo->prepare("INSERT INTO sessions (session_code, host_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$session_code, $anonymous_id]);
    
    // Add creator to active connections
    $stmt = $pdo->prepare("INSERT INTO active_connections (session_code, anonymous_id, last_ping) VALUES (?, ?, NOW())");
    $stmt->execute([$session_code, $anonymous_id]);
    
    echo json_encode([
        'success' => true, 
        'session_code' => $session_code,
        'session_name' => $session_name,
        'message' => 'Session created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create session', 'debug' => $e->getMessage()]);
}
?>
