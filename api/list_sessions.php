<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$anonymous_id = $_GET['anonymous_id'] ?? '';

if (empty($anonymous_id) || !isValidAnonymousId($anonymous_id)) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$pdo = Database::getInstance()->getConnection();

try {
    // Get active sessions with user count
    $stmt = $pdo->prepare("
        SELECT 
            s.id as session_id,
            s.session_code as session_name,
            s.created_at,
            COUNT(ac.anonymous_id) as user_count,
            MAX(CASE WHEN ac.anonymous_id = ? THEN 1 ELSE 0 END) as is_member
        FROM sessions s
        LEFT JOIN active_connections ac ON s.session_code = ac.session_code
        WHERE s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY s.id, s.session_code, s.created_at
        HAVING user_count > 0
        ORDER BY s.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$anonymous_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format sessions
    $formatted_sessions = [];
    foreach ($sessions as $session) {
        $formatted_sessions[] = [
            'session_id' => $session['session_id'],
            'session_name' => $session['session_name'],
            'user_count' => (int)$session['user_count'],
            'created_time' => date('H:i', strtotime($session['created_at'])),
            'is_member' => (bool)$session['is_member']
        ];
    }
    
    echo json_encode(['sessions' => $formatted_sessions]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>
