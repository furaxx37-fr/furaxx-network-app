<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$anonymous_id = trim($_GET['anonymous_id'] ?? '');

if (empty($anonymous_id) || !isValidAnonymousId($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid anonymous ID']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Get the session code for this user
    $stmt = $pdo->prepare("SELECT session_code FROM active_connections WHERE anonymous_id = ? ORDER BY last_ping DESC LIMIT 1");
    $stmt->execute([$anonymous_id]);
    $connection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$connection) {
        echo json_encode(['online_users' => []]);
        exit;
    }
    
    $session_code = $connection['session_code'];
    
    // Clean up old connections (older than 2 minutes)
    $stmt = $pdo->prepare("DELETE FROM active_connections WHERE last_ping < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
    $stmt->execute();
    
    // Get online users in the same session
    $stmt = $pdo->prepare("
        SELECT ac.anonymous_id, au.is_host, ac.last_ping
        FROM active_connections ac
        JOIN anonymous_users au ON ac.anonymous_id = au.anonymous_id
        WHERE ac.session_code = ? AND ac.last_ping > DATE_SUB(NOW(), INTERVAL 2 MINUTE)
        ORDER BY au.is_host DESC, ac.last_ping DESC
    ");
    $stmt->execute([$session_code]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['online_users' => $users]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get online users', 'debug' => $e->getMessage()]);
}
?>
