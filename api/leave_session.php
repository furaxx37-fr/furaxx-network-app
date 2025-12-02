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
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Remove user from session
    $stmt = $pdo->prepare("DELETE FROM active_connections WHERE session_code = ? AND anonymous_id = ?");
    $stmt->execute([$session_code, $anonymous_id]);
    
    if ($stmt->rowCount() > 0) {
        // Check if session is now empty
        $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM active_connections WHERE session_code = ?");
        $stmt->execute([$session_code]);
        $result = $stmt->fetch();
        
        // If session is empty, clean up old messages
        if ($result['user_count'] == 0) {
            $stmt = $pdo->prepare("DELETE FROM messages WHERE session_code = ? AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute([$session_code]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Left session successfully']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Not in session']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to leave session']);
}
?>
