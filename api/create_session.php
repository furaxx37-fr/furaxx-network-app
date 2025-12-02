<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$session_name = trim($input['session_name'] ?? '');
$anonymous_id = trim($input['anonymous_id'] ?? '');

if (empty($session_name) || empty($anonymous_id) || !isValidAnonymousId($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // First, ensure the anonymous user exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO anonymous_users (anonymous_id, is_host, created_at) VALUES (?, 1, NOW())");
    $stmt->execute([$anonymous_id]);
    
    // Generate unique session code
    $session_code = 'fx_' . uniqid() . '_' . rand(1000, 9999);
    
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
