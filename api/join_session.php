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
$session_code = trim($input['session_code'] ?? '');
$anonymous_id = trim($input['anonymous_id'] ?? '');

if (empty($session_code) || empty($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session code or anonymous ID']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if session exists and is active
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_code = ? AND status = 'active' AND expires_at > NOW()");
    $stmt->execute([$session_code]);
    $session = $stmt->fetch();
    
    if (!$session) {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found or expired']);
        exit;
    }
    
    // Ensure the anonymous user exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO anonymous_users (anonymous_id, is_host, created_at) VALUES (?, 0, NOW())");
    $stmt->execute([$anonymous_id]);
    
    // Add user to active connections
    $stmt = $pdo->prepare("INSERT INTO active_connections (session_code, anonymous_id, last_ping) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE last_ping = NOW()");
    $stmt->execute([$session_code, $anonymous_id]);
    
    // Get session participants count
    $stmt = $pdo->prepare("SELECT COUNT(*) as participant_count FROM active_connections WHERE session_code = ?");
    $stmt->execute([$session_code]);
    $count = $stmt->fetch()['participant_count'];
    
    echo json_encode([
        'success' => true,
        'session_code' => $session_code,
        'host_id' => $session['host_id'],
        'participant_count' => $count,
        'message' => 'Successfully joined session'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to join session', 'debug' => $e->getMessage()]);
}
?>
