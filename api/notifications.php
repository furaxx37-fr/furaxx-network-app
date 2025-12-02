<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../settings.php';
require_once '../config.php';
setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Récupérer les notifications pour un utilisateur
        $anonymous_id = $_GET['anonymous_id'] ?? '';
        
        if (!validateAnonymousId($anonymous_id)) {
            echo json_encode(['error' => 'ID anonyme invalide']);
            exit;
        }
        
        // Vérifier les nouvelles activités dans les sessions de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                s.session_code,
                s.session_name,
                COUNT(m.id) as new_messages,
                MAX(m.created_at) as last_activity
            FROM sessions s
            JOIN user_sessions us ON s.session_code = us.session_code
            LEFT JOIN messages m ON s.session_code = m.session_code 
                AND m.created_at > (
                    SELECT COALESCE(last_seen, us.joined_at) 
                    FROM user_sessions us2 
                    WHERE us2.anonymous_id = ? 
                    AND us2.session_code = s.session_code
                )
                AND m.anonymous_id != ?
            WHERE us.anonymous_id = ? 
            AND us.is_active = 1
            GROUP BY s.session_code, s.session_name
            HAVING new_messages > 0
            ORDER BY last_activity DESC
        ");
        
        $stmt->execute([$anonymous_id, $anonymous_id, $anonymous_id]);
        $notifications = $stmt->fetchAll();
        
        // Compter le total de notifications
        $total_notifications = array_sum(array_column($notifications, 'new_messages'));
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'total_count' => $total_notifications,
            'timestamp' => time()
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Marquer les notifications comme lues
        $input = json_decode(file_get_contents('php://input'), true);
        $anonymous_id = $input['anonymous_id'] ?? '';
        $session_code = $input['session_code'] ?? '';
        
        if (!validateAnonymousId($anonymous_id) || !validateSessionCode($session_code)) {
            echo json_encode(['error' => 'Paramètres invalides']);
            exit;
        }
        
        // Mettre à jour le timestamp de dernière consultation
        $stmt = $pdo->prepare("
            UPDATE user_sessions 
            SET last_seen = NOW() 
            WHERE anonymous_id = ? AND session_code = ?
        ");
        
        $stmt->execute([$anonymous_id, $session_code]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notifications marquées comme lues'
        ]);
    }
    
} catch (Exception $e) {
    logActivity("Erreur notifications: " . $e->getMessage(), 'ERROR');
    echo json_encode(['error' => 'Erreur serveur']);
}
?>
