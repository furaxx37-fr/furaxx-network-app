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

if (empty($session_code) || empty($anonymous_id) || !isValidSessionCode($session_code) || !isValidAnonymousId($anonymous_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session code or anonymous ID']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Verify user is the host of this session
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_code = ? AND host_id = ? AND status = 'active'");
    $stmt->execute([$session_code, $anonymous_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        http_response_code(403);
        echo json_encode(['error' => 'Only session host can generate share links']);
        exit;
    }
    
    // Get current server URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_url = $protocol . '://' . $host;
    
    // Generate share link
    $share_link = $base_url . '/app.php?session=' . urlencode($session_code);
    
    // Get session info
    $stmt = $pdo->prepare("SELECT COUNT(*) as participant_count FROM active_connections WHERE session_code = ?");
    $stmt->execute([$session_code]);
    $count = $stmt->fetch()['participant_count'];
    
    echo json_encode([
        'success' => true,
        'session_code' => $session_code,
        'share_link' => $share_link,
        'participant_count' => $count,
        'expires_at' => $session['expires_at'],
        'message' => 'Share link generated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate share link', 'debug' => $e->getMessage()]);
}
?>
