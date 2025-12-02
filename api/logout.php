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
$anonymous_id = trim($input['anonymous_id'] ?? '');
$session_code = trim($input['session_code'] ?? '');

if (empty($anonymous_id) || empty($session_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate inputs
if (!isValidAnonymousId($anonymous_id) || !isValidSessionCode($session_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input format']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Remove user from active connections
    $stmt = $pdo->prepare("DELETE FROM active_connections WHERE session_code = ? AND anonymous_id = ?");
    $stmt->execute([$session_code, $anonymous_id]);
    
    $affected_rows = $stmt->rowCount();
    
    if ($affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Successfully logged out'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found in session']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to logout', 'debug' => $e->getMessage()]);
}
?>
