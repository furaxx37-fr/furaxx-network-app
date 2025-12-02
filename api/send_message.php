<?php
require_once '../config.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$session_code = $input['session_code'] ?? '';
$message = trim($input['message'] ?? '');
$anonymous_id = $input['anonymous_id'] ?? '';

if (empty($session_code) || empty($message) || empty($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Get session_id from session_code
    $stmt = $pdo->prepare("SELECT id FROM sessions WHERE session_code = ? AND status = 'active'");
    $stmt->execute([$session_code]);
    $session = $stmt->fetch();
    
    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found']);
        exit;
    }
    
    $session_id = $session['id'];
    
    // Verify user is part of this session
    $stmt = $pdo->prepare("SELECT id FROM active_connections WHERE session_code = ? AND anonymous_id = ?");
    $stmt->execute([$session_code, $anonymous_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized for this session']);
        exit;
    }
    
    // Insert message
    // Insert message
    $stmt = $pdo->prepare("INSERT INTO messages (session_code, sender_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$session_code, $anonymous_id, $message]);
    
    // Clean old messages (keep only last 50 per session)
    $stmt = $pdo->prepare("
        DELETE FROM messages 
        WHERE session_code = ? AND id NOT IN (
            SELECT id FROM (
                SELECT id FROM messages 
                WHERE session_code = ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ) AS recent_messages
        )
    ");
    $stmt->execute([$session_code, $session_code]);
    
    echo json_encode(['success' => true, 'message' => 'Message sent']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
