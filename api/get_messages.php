<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$session_code = $_GET['session_code'] ?? '';
$anonymous_id = $_GET['anonymous_id'] ?? '';

if (empty($session_code) || empty($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Verify user is part of this session
    // Get database connection
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Verify user is part of this session
    $stmt = $pdo->prepare("SELECT id FROM active_connections WHERE session_code = ? AND anonymous_id = ?");
    $stmt->execute([$session_code, $anonymous_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized for this session']);
        exit;
    }
    
    // Get messages for this session
    $stmt = $pdo->prepare("
        SELECT m.content, m.created_at, m.sender_id
        FROM messages m
        WHERE m.session_code = ? AND m.status = 'active'
        ORDER BY m.created_at ASC
        LIMIT 50
    ");
    $stmt->execute([$session_code]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages
    $formatted_messages = [];
    foreach ($messages as $msg) {
        $formatted_messages[] = [
            'username' => 'Anonyme_' . substr($msg['sender_id'], -6),
            'message' => $msg['content'],
            'timestamp' => date('H:i', strtotime($msg['created_at'])),
            'is_own' => $msg['sender_id'] == $anonymous_id
        ];
    }
    
    echo json_encode(['messages' => $formatted_messages]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>
