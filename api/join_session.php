<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$session_code = trim($input['session_code'] ?? '');
$anonymous_id = trim($input['anonymous_id'] ?? '');

if (empty($session_code) || empty($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check if session exists and is active
    $stmt = $pdo->prepare("SELECT session_code FROM sessions WHERE session_code = ? AND status = 'active'");
    $stmt->execute([$session_code]);
    $session = $stmt->fetch();
    
    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found or expired']);
        exit;
    }
    
    // Create anonymous user if doesn't exist
    $stmt = $pdo->prepare("INSERT IGNORE INTO anonymous_users (anonymous_id, created_at) VALUES (?, NOW())");
    $stmt->execute([$anonymous_id]);
    
    // Check if user is already in session
    $stmt = $pdo->prepare("SELECT id FROM active_connections WHERE session_code = ? AND anonymous_id = ?");
    $stmt->execute([$session_code, $anonymous_id]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => true,
            'session_code' => $session_code,
            'message' => 'Already in session'
        ]);
        exit;
    }
    
    // Add user to session
    $stmt = $pdo->prepare("INSERT INTO active_connections (session_code, anonymous_id, last_ping) VALUES (?, ?, NOW())");
    $stmt->execute([$session_code, $anonymous_id]);
    
    echo json_encode([
        'success' => true,
        'session_code' => $session_code,
        'message' => 'Joined session successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to join session: ' . $e->getMessage()]);
}
?>
